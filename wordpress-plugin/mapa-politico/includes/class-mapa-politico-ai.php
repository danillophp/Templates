<?php

if (!defined('ABSPATH')) {
    exit;
}

class MapaPoliticoAI
{
    private const CRON_HOOK = 'mapa_politico_ai_sync_event';
    private const LOG_OPTION = 'mapa_politico_ai_sync_logs';

    public static function init(): void
    {
        add_filter('cron_schedules', [self::class, 'addSchedules']);
        add_action(self::CRON_HOOK, [self::class, 'runScheduledSync']);
    }

    public static function activate(): void
    {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + 120, 'monthly', self::CRON_HOOK);
        }
    }

    public static function deactivate(): void
    {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
        }
    }

    public static function addSchedules(array $schedules): array
    {
        if (!isset($schedules['monthly'])) {
            $schedules['monthly'] = [
                'interval' => 30 * DAY_IN_SECONDS,
                'display' => 'Uma vez por mês',
            ];
        }

        return $schedules;
    }

    public static function runScheduledSync(): void
    {
        self::runSync();
    }

    /**
     * Fluxo fixo por etapas:
     * 1) Município (IBGE)
     * 2) Fonte oficial prefeitura (.gov.br)
     * 3) Prefeito
     * 4) Vice-prefeito
     * 5) Partido
     * 6) Validação final + persistência
     */
    public static function runSync(): array
    {
        global $wpdb;

        $locationsTable = $wpdb->prefix . 'mapa_politico_locations';
        $politiciansTable = $wpdb->prefix . 'mapa_politico_politicians';

        $municipalities = self::fetchGoiasMunicipalities();
        if (empty($municipalities)) {
            self::logFailure('N/A', 'etapa_1_municipios_ibge', 'Nenhum município retornado pelo IBGE', 'https://servicodados.ibge.gov.br/api/v1/localidades/estados/52/municipios');
            return ['processed' => 0, 'created' => 0, 'updated' => 0, 'errors' => 1];
        }

        $created = 0;
        $updated = 0;
        $errors = 0;

        foreach ($municipalities as $municipality) {
            $city = sanitize_text_field((string) ($municipality['nome'] ?? ''));
            $ibgeCode = (string) ($municipality['id'] ?? '');

            if ($city === '' || $ibgeCode === '') {
                $errors++;
                self::logFailure('N/A', 'etapa_1_municipios_ibge', 'Município sem nome ou sem código IBGE', 'IBGE');
                continue;
            }

            try {
                // ETAPA 2 - Fonte oficial prefeitura
                $prefeituraUrl = self::discoverOfficialPrefeituraUrl($city);
                if ($prefeituraUrl === '') {
                    $errors++;
                    self::logFailure($city, 'etapa_2_fonte_oficial', 'Site oficial da prefeitura (.gov.br) não localizado', 'N/A');
                    continue;
                }

                $prefeituraHtml = self::safeGetHtml($prefeituraUrl, true);
                if ($prefeituraHtml === '') {
                    $errors++;
                    self::logFailure($city, 'etapa_2_fonte_oficial', 'Falha ao obter HTML da prefeitura', $prefeituraUrl);
                    continue;
                }

                if (!self::isInstitutionalPage($prefeituraHtml)) {
                    $errors++;
                    self::logFailure($city, 'etapa_2_fonte_oficial', 'Página não aparenta conteúdo institucional da prefeitura', $prefeituraUrl);
                    continue;
                }

                $ibgeCityUrl = self::buildIbgeCityUrl($city);
                $ibgeHtml = self::safeGetHtml($ibgeCityUrl, false);

                // ETAPA 3 - Prefeito
                $prefeito = self::extractMayorName($prefeituraHtml);
                if ($prefeito === '' && $ibgeHtml !== '') {
                    $prefeito = self::extractMayorName($ibgeHtml);
                }
                if (!self::isValidPersonName($prefeito)) {
                    $errors++;
                    self::logFailure($city, 'etapa_3_prefeito', 'Nome completo do prefeito não identificado com segurança', $prefeituraUrl);
                    continue;
                }

                // ETAPA 4 - Vice-prefeito
                $vice = self::extractViceName($prefeituraHtml);
                if ($vice === '' && $ibgeHtml !== '') {
                    $vice = self::extractViceName($ibgeHtml);
                }
                if (!self::isValidPersonName($vice)) {
                    $errors++;
                    self::logFailure($city, 'etapa_4_vice_prefeito', 'Nome completo do vice-prefeito não identificado com segurança', $prefeituraUrl);
                    continue;
                }

                // ETAPA 5 - Partido
                $party = self::extractParty($prefeituraHtml);
                if ($party === '' && $ibgeHtml !== '') {
                    $party = self::extractParty($ibgeHtml);
                }
                $party = self::normalizeParty($party);
                if ($party === '') {
                    $errors++;
                    self::logFailure($city, 'etapa_5_partido', 'Partido político não identificado com clareza', $prefeituraUrl . ' | ' . $ibgeCityUrl);
                    continue;
                }

                $address = self::extractAddressFromHtml($prefeituraHtml, $city);
                if ($address === '') {
                    $errors++;
                    self::logFailure($city, 'etapa_6_validacao_final', 'Endereço institucional da prefeitura não encontrado', $prefeituraUrl);
                    continue;
                }

                // ETAPA 6 - Validação final
                $missing = self::validateFinalPayload([
                    'prefeito' => $prefeito,
                    'vice' => $vice,
                    'party' => $party,
                    'city' => $city,
                    'source_url' => $prefeituraUrl,
                ]);
                if (!empty($missing)) {
                    $errors++;
                    self::logFailure($city, 'etapa_6_validacao_final', 'Validação final falhou: ' . implode(', ', $missing), $prefeituraUrl);
                    continue;
                }

                $geo = self::geocodeNominatim($address);
                if (!isset($geo['lat'], $geo['lng'])) {
                    $errors++;
                    self::logFailure($city, 'etapa_6_validacao_final', 'Falha ao geocodificar endereço oficial', $address);
                    continue;
                }

                $locationId = self::upsertLocation($locationsTable, [
                    'name' => 'Prefeitura de ' . $city,
                    'city' => $city,
                    'state' => 'Goiás',
                    'postal_code' => '',
                    'latitude' => $geo['lat'],
                    'longitude' => $geo['lng'],
                    'address' => $address,
                    'ibge_code' => $ibgeCode,
                    'institution_type' => 'prefeitura',
                    'source_url' => $prefeituraUrl,
                    'last_synced_at' => current_time('mysql'),
                ]);

                if ($locationId < 1) {
                    $errors++;
                    self::logFailure($city, 'persistencia_localizacao', 'Falha no upsert de localização', $prefeituraUrl);
                    continue;
                }

                $prefeitoResult = self::upsertPolitician($politiciansTable, [
                    'location_id' => $locationId,
                    'full_name' => $prefeito,
                    'position' => 'Prefeito',
                    'party' => $party,
                    'phone' => '',
                    'email' => '',
                    'biography' => '',
                    'source_url' => $prefeituraUrl,
                    'source_name' => 'Prefeitura (.gov.br) + IBGE',
                    'data_status' => 'completo',
                    'is_auto' => 1,
                    'last_synced_at' => current_time('mysql'),
                    'municipality_code' => $ibgeCode,
                    'photo_id' => null,
                    'validation_notes' => 'Fluxo rígido por etapas concluído com sucesso.',
                ]);

                $viceResult = self::upsertPolitician($politiciansTable, [
                    'location_id' => $locationId,
                    'full_name' => $vice,
                    'position' => 'Vice-prefeito',
                    'party' => $party,
                    'phone' => '',
                    'email' => '',
                    'biography' => '',
                    'source_url' => $prefeituraUrl,
                    'source_name' => 'Prefeitura (.gov.br) + IBGE',
                    'data_status' => 'completo',
                    'is_auto' => 1,
                    'last_synced_at' => current_time('mysql'),
                    'municipality_code' => $ibgeCode,
                    'photo_id' => null,
                    'validation_notes' => 'Fluxo rígido por etapas concluído com sucesso.',
                ]);

                if ($prefeitoResult === 'error' || $viceResult === 'error') {
                    $errors++;
                    self::logFailure($city, 'persistencia_politicos', 'Falha no upsert de prefeito/vice', $prefeituraUrl);
                    continue;
                }

                if ($prefeitoResult === 'created') {
                    $created++;
                } elseif ($prefeitoResult === 'updated') {
                    $updated++;
                }

                if ($viceResult === 'created') {
                    $created++;
                } elseif ($viceResult === 'updated') {
                    $updated++;
                }

                self::logSuccess($city, 'concluido', 'Prefeito e vice-prefeito validados e sincronizados', $prefeituraUrl);
            } catch (Throwable $e) {
                $errors++;
                self::logFailure($city, 'erro_inesperado', $e->getMessage(), 'runtime');
            }
        }

        update_option('mapa_politico_ai_last_sync', current_time('mysql'));

        return [
            'processed' => count($municipalities),
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }

    public static function runManualSearch(string $name, string $position): array
    {
        $name = sanitize_text_field($name);
        $position = sanitize_text_field($position);
        if ($name === '' || !in_array($position, ['Prefeito', 'Vice-prefeito'], true)) {
            return ['found' => false, 'message' => 'Parâmetros inválidos'];
        }

        global $wpdb;
        $table = $wpdb->prefix . 'mapa_politico_politicians';

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE position = %s AND full_name LIKE %s LIMIT 1", $position, '%' . $wpdb->esc_like($name) . '%'),
            ARRAY_A
        );

        if ($row) {
            $wpdb->update($table, [
                'last_synced_at' => current_time('mysql'),
                'validation_notes' => 'Pesquisa manual por nome executada em ' . current_time('mysql'),
            ], ['id' => (int) $row['id']]);

            return ['found' => true, 'message' => 'Registro localizado e marcado para revisão'];
        }

        self::runSync();

        return ['found' => false, 'message' => 'Nome não encontrado inicialmente. Sincronização geral executada para atualização.'];
    }

    private static function fetchGoiasMunicipalities(): array
    {
        $url = 'https://servicodados.ibge.gov.br/api/v1/localidades/estados/52/municipios';
        $response = wp_remote_get($url, [
            'timeout' => 25,
            'user-agent' => 'MapaPoliticoBot/2.1 (+https://www.andredopremium.com.br/mapapolitico)',
        ]);

        if (is_wp_error($response)) {
            self::logFailure('N/A', 'etapa_1_municipios_ibge', 'Erro IBGE municípios: ' . $response->get_error_message(), $url);
            return [];
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        if ($status < 200 || $status >= 300) {
            self::logFailure('N/A', 'etapa_1_municipios_ibge', 'Status inválido IBGE municípios: ' . $status, $url);
            return [];
        }

        $data = json_decode((string) wp_remote_retrieve_body($response), true);

        return is_array($data) ? $data : [];
    }

    private static function buildIbgeCityUrl(string $city): string
    {
        return 'https://cidades.ibge.gov.br/brasil/go/' . sanitize_title($city) . '/panorama';
    }

    private static function discoverOfficialPrefeituraUrl(string $city): string
    {
        $slug = sanitize_title($city);

        $candidates = [
            'https://www.' . $slug . '.go.gov.br',
            'https://' . $slug . '.go.gov.br',
            'https://www.prefeitura.' . $slug . '.go.gov.br',
            'https://prefeitura.' . $slug . '.go.gov.br',
        ];

        foreach ($candidates as $url) {
            $host = (string) wp_parse_url($url, PHP_URL_HOST);
            if ($host === '' || !str_ends_with($host, '.gov.br')) {
                continue;
            }

            $response = wp_remote_head($url, [
                'timeout' => 10,
                'redirection' => 4,
                'user-agent' => 'MapaPoliticoBot/2.1',
            ]);

            if (is_wp_error($response)) {
                continue;
            }

            $status = (int) wp_remote_retrieve_response_code($response);
            if ($status >= 200 && $status < 400) {
                return $url;
            }
        }

        return '';
    }

    private static function safeGetHtml(string $url, bool $mustBeGovBr): string
    {
        $host = (string) wp_parse_url($url, PHP_URL_HOST);
        if ($host === '') {
            return '';
        }

        if ($mustBeGovBr && !str_ends_with($host, '.gov.br')) {
            return '';
        }

        $response = wp_remote_get($url, [
            'timeout' => 18,
            'redirection' => 4,
            'user-agent' => 'MapaPoliticoBot/2.1',
        ]);

        if (is_wp_error($response)) {
            return '';
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        if ($status < 200 || $status >= 300) {
            return '';
        }

        return (string) wp_remote_retrieve_body($response);
    }

    private static function isInstitutionalPage(string $html): bool
    {
        $text = mb_strtolower(wp_strip_all_tags($html), 'UTF-8');
        $keywords = ['prefeitura', 'municipal', 'governo', 'gabinete', 'secretaria'];

        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private static function extractMayorName(string $html): string
    {
        $roles = ['prefeito municipal', 'prefeito', 'prefeita', 'chefe do executivo', 'gestão municipal'];
        return self::extractPersonByRole($html, $roles);
    }

    private static function extractViceName(string $html): string
    {
        $roles = ['vice-prefeito', 'vice-prefeita', 'vice prefeito', 'vice prefeita'];
        return self::extractPersonByRole($html, $roles);
    }

    private static function extractPersonByRole(string $html, array $roles): string
    {
        $text = self::normalizeText(wp_strip_all_tags($html));

        foreach ($roles as $role) {
            $patterns = [
                "/(?:" . preg_quote($role, '/') . ")\\s*[:\\-–]\\s*([A-ZÁÉÍÓÚÂÊÔÃÕÇ][A-Za-zÁÉÍÓÚÂÊÔÃÕÇ'\\s]{5,100})/iu",
                "/([A-ZÁÉÍÓÚÂÊÔÃÕÇ][A-Za-zÁÉÍÓÚÂÊÔÃÕÇ'\\s]{5,100})\\s*[-–]\\s*(?:" . preg_quote($role, '/') . ")/iu",
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $text, $m)) {
                    $name = self::normalizePersonName((string) ($m[1] ?? ''));
                    if (self::isValidPersonName($name)) {
                        return $name;
                    }
                }
            }
        }

        return '';
    }

    private static function extractParty(string $html): string
    {
        $text = self::normalizeText(wp_strip_all_tags($html));
        if (preg_match('/\b(MDB|PL|PSDB|PT|PSD|PP|PSB|REPUBLICANOS|PODEMOS|PDT|PV|CIDADANIA|AVANTE|UNI[ÃA]O BRASIL|SOLIDARIEDADE)\b/iu', $text, $m)) {
            return (string) $m[1];
        }

        return '';
    }

    private static function extractAddressFromHtml(string $html, string $city): string
    {
        $text = self::normalizeText(wp_strip_all_tags($html));
        $patterns = [
            '/(?:endere[cç]o|localiza[cç][aã]o|sede)\s*[:\-]\s*([^\n\r]{12,180})/iu',
            '/(Rua\s+[^\n\r]{8,180})/iu',
            '/(Avenida\s+[^\n\r]{8,180})/iu',
            '/(Pra[cç]a\s+[^\n\r]{8,180})/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $raw = trim((string) ($m[1] ?? ''));
                if ($raw !== '') {
                    $full = $raw . ', ' . $city . ', Goiás, Brasil';
                    return sanitize_text_field($full);
                }
            }
        }

        return '';
    }

    private static function validateFinalPayload(array $payload): array
    {
        $missing = [];

        if (!self::isValidPersonName((string) ($payload['prefeito'] ?? ''))) {
            $missing[] = 'prefeito';
        }

        if (!self::isValidPersonName((string) ($payload['vice'] ?? ''))) {
            $missing[] = 'vice-prefeito';
        }

        if ((string) ($payload['party'] ?? '') === '') {
            $missing[] = 'partido';
        }

        if ((string) ($payload['city'] ?? '') === '') {
            $missing[] = 'município';
        }

        if (!self::isValidOfficialSource((string) ($payload['source_url'] ?? ''))) {
            $missing[] = 'fonte oficial';
        }

        return $missing;
    }

    private static function normalizeText(string $text): string
    {
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim((string) $text);
    }

    private static function normalizePersonName(string $name): string
    {
        $name = self::normalizeText(sanitize_text_field($name));
        if ($name === '') {
            return '';
        }

        return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
    }

    private static function isValidPersonName(string $name): bool
    {
        $name = self::normalizeText($name);
        if ($name === '' || mb_strlen($name) < 7 || preg_match('/\s/u', $name) !== 1) {
            return false;
        }

        $blocked = [
            'prefeito',
            'prefeito municipal',
            'prefeita',
            'vice-prefeito',
            'vice prefeito',
            'chefe do executivo',
            'gestão municipal',
            'gabinete do prefeito',
            'pendente de validação',
        ];

        $lower = mb_strtolower($name, 'UTF-8');
        foreach ($blocked as $item) {
            if ($lower === $item || str_contains($lower, $item)) {
                return false;
            }
        }

        return true;
    }

    private static function normalizeParty(string $party): string
    {
        $party = strtoupper(self::normalizeText(sanitize_text_field($party)));
        if ($party === '') {
            return '';
        }

        $party = str_replace(['UNIAO BRASIL', 'UNIÃO BRASIL'], 'UNIÃO BRASIL', $party);

        $valid = ['MDB', 'PL', 'PSDB', 'PT', 'PSD', 'PP', 'PSB', 'REPUBLICANOS', 'PODEMOS', 'PDT', 'PV', 'CIDADANIA', 'AVANTE', 'UNIÃO BRASIL', 'SOLIDARIEDADE'];
        return in_array($party, $valid, true) ? $party : '';
    }

    private static function isValidOfficialSource(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        $host = (string) wp_parse_url($url, PHP_URL_HOST);
        if ($host === '') {
            return false;
        }

        return str_ends_with($host, '.gov.br') || $host === 'cidades.ibge.gov.br' || str_ends_with($host, '.ibge.gov.br');
    }

    private static function geocodeNominatim(string $query): array
    {
        $url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=' . rawurlencode($query);
        $response = wp_remote_get($url, [
            'timeout' => 20,
            'user-agent' => 'MapaPoliticoBot/2.1 (+https://www.andredopremium.com.br/mapapolitico)',
            'headers' => ['Accept' => 'application/json'],
        ]);

        if (is_wp_error($response)) {
            return [];
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        if ($status < 200 || $status >= 300) {
            return [];
        }

        $json = json_decode((string) wp_remote_retrieve_body($response), true);
        if (!is_array($json) || empty($json[0])) {
            return [];
        }

        $lat = isset($json[0]['lat']) ? (float) $json[0]['lat'] : null;
        $lng = isset($json[0]['lon']) ? (float) $json[0]['lon'] : null;
        if ($lat === null || $lng === null) {
            return [];
        }

        return ['lat' => $lat, 'lng' => $lng];
    }

    private static function upsertLocation(string $table, array $data): int
    {
        global $wpdb;

        $existingId = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE city = %s AND state = %s AND institution_type = %s LIMIT 1",
            $data['city'],
            $data['state'],
            $data['institution_type']
        ));

        if ($existingId > 0) {
            $ok = $wpdb->update($table, $data, ['id' => $existingId]);
            if ($ok === false) {
                return 0;
            }
            return $existingId;
        }

        $ok = $wpdb->insert($table, $data);
        if ($ok === false) {
            return 0;
        }

        return (int) $wpdb->insert_id;
    }

    private static function upsertPolitician(string $table, array $data): string
    {
        global $wpdb;

        $existingId = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE municipality_code = %s AND position = %s LIMIT 1",
            $data['municipality_code'],
            $data['position']
        ));

        if ($existingId > 0) {
            $ok = $wpdb->update($table, $data, ['id' => $existingId]);
            if ($ok === false) {
                return 'error';
            }
            return 'updated';
        }

        $ok = $wpdb->insert($table, $data);
        if ($ok === false) {
            return 'error';
        }

        return 'created';
    }

    private static function logFailure(string $municipality, string $step, string $reason, string $source): void
    {
        self::appendLog([
            'type' => 'erro',
            'municipality' => $municipality,
            'step' => $step,
            'reason' => $reason,
            'source' => $source,
            'created_at' => current_time('mysql'),
        ]);
    }

    private static function logSuccess(string $municipality, string $step, string $reason, string $source): void
    {
        self::appendLog([
            'type' => 'sucesso',
            'municipality' => $municipality,
            'step' => $step,
            'reason' => $reason,
            'source' => $source,
            'created_at' => current_time('mysql'),
        ]);
    }

    private static function appendLog(array $entry): void
    {
        $logs = get_option(self::LOG_OPTION, []);
        if (!is_array($logs)) {
            $logs = [];
        }

        $logs[] = $entry;
        if (count($logs) > 500) {
            $logs = array_slice($logs, -500);
        }

        update_option(self::LOG_OPTION, $logs, false);
    }
}
