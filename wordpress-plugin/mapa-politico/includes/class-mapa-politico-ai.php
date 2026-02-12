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

    public static function runSync(): array
    {
        global $wpdb;

        $locationsTable = $wpdb->prefix . 'mapa_politico_locations';
        $politiciansTable = $wpdb->prefix . 'mapa_politico_politicians';

        $municipalities = self::fetchGoiasMunicipalities();
        if (empty($municipalities)) {
            self::logFailure('N/A', 'ibge_municipios', 'Nenhum município retornado pelo IBGE', 'https://servicodados.ibge.gov.br/api/v1/localidades/estados/52/municipios');
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
                self::logFailure($city !== '' ? $city : 'N/A', 'validacao_inicial', 'Município sem nome ou código IBGE', 'IBGE');
                continue;
            }

            $ibgeUrl = self::buildIbgeCityUrl($city);
            $prefeituraUrl = self::discoverOfficialPrefeituraUrl($city);

            if ($prefeituraUrl === '') {
                $errors++;
                self::logFailure($city, 'descoberta_fonte_prefeitura', 'Portal oficial da prefeitura não localizado (.gov.br)', $ibgeUrl);
                continue;
            }

            $prefeituraHtml = self::safeGetHtml($prefeituraUrl, true);
            if ($prefeituraHtml === '') {
                $errors++;
                self::logFailure($city, 'leitura_prefeitura', 'Falha ao obter HTML do portal da prefeitura', $prefeituraUrl);
                continue;
            }

            $ibgeHtml = self::safeGetHtml($ibgeUrl, false);
            if ($ibgeHtml === '') {
                $errors++;
                self::logFailure($city, 'leitura_ibge', 'Falha ao obter página do IBGE da cidade', $ibgeUrl);
                continue;
            }

            $parsed = self::extractMayorAndViceData($city, $prefeituraHtml, $ibgeHtml, $prefeituraUrl, $ibgeUrl);

            $missing = self::validateMandatoryData($parsed);
            if (!empty($missing)) {
                $errors++;
                self::logFailure($city, 'validacao_obrigatoria', 'Dados obrigatórios ausentes: ' . implode(', ', $missing), $prefeituraUrl . ' | ' . $ibgeUrl);
                continue;
            }

            $geo = self::geocodeNominatim($parsed['address']);
            if (!isset($geo['lat'], $geo['lng'])) {
                $errors++;
                self::logFailure($city, 'geocodificacao', 'Falha na geocodificação do endereço da prefeitura', $parsed['address']);
                continue;
            }

            $locationId = self::upsertLocation($locationsTable, [
                'name' => 'Prefeitura de ' . $city,
                'city' => $city,
                'state' => 'Goiás',
                'postal_code' => '',
                'latitude' => $geo['lat'],
                'longitude' => $geo['lng'],
                'address' => $parsed['address'],
                'ibge_code' => $ibgeCode,
                'institution_type' => 'prefeitura',
                'source_url' => $prefeituraUrl,
                'last_synced_at' => current_time('mysql'),
            ]);

            if ($locationId < 1) {
                $errors++;
                self::logFailure($city, 'upsert_localizacao', 'Falha ao inserir/atualizar localização da prefeitura', $prefeituraUrl);
                continue;
            }

            $prefeitoResult = self::upsertPolitician($politiciansTable, [
                'location_id' => $locationId,
                'full_name' => $parsed['prefeito_name'],
                'position' => 'Prefeito',
                'party' => $parsed['prefeito_party'],
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
                'validation_notes' => 'Dados validados por regra obrigatória (prefeito + vice + partido + geolocalização).',
            ]);

            $viceResult = self::upsertPolitician($politiciansTable, [
                'location_id' => $locationId,
                'full_name' => $parsed['vice_name'],
                'position' => 'Vice-prefeito',
                'party' => $parsed['prefeito_party'],
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
                'validation_notes' => 'Vice-prefeito validado em fonte oficial. Partido herdado da gestão municipal informada na fonte.',
            ]);

            if ($prefeitoResult === 'error' || $viceResult === 'error') {
                $errors++;
                self::logFailure($city, 'upsert_politicos', 'Falha ao inserir/atualizar prefeito ou vice-prefeito', $prefeituraUrl);
                continue;
            }

            if ($prefeitoResult === 'created') {
                $created++;
            }
            if ($prefeitoResult === 'updated') {
                $updated++;
            }
            if ($viceResult === 'created') {
                $created++;
            }
            if ($viceResult === 'updated') {
                $updated++;
            }

            self::logSuccess($city, 'sincronizacao_concluida', 'Prefeito e vice-prefeito sincronizados com dados completos', $prefeituraUrl . ' | ' . $ibgeUrl);
        }

        update_option('mapa_politico_ai_last_sync', current_time('mysql'));

        return ['processed' => count($municipalities), 'created' => $created, 'updated' => $updated, 'errors' => $errors];
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
            'user-agent' => 'MapaPoliticoBot/2.0 (+https://www.andredopremium.com.br/mapapolitico)',
        ]);

        if (is_wp_error($response)) {
            self::logFailure('N/A', 'ibge_municipios', 'Erro ao consultar municípios no IBGE: ' . $response->get_error_message(), $url);
            return [];
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        if ($status < 200 || $status >= 300) {
            self::logFailure('N/A', 'ibge_municipios', 'Status inválido ao consultar municípios no IBGE: ' . $status, $url);
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
                'user-agent' => 'MapaPoliticoBot/2.0',
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
            'user-agent' => 'MapaPoliticoBot/2.0',
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

    private static function extractMayorAndViceData(string $city, string $prefeituraHtml, string $ibgeHtml, string $prefeituraUrl, string $ibgeUrl): array
    {
        $prefeituraText = self::normalizeTextForParsing(wp_strip_all_tags($prefeituraHtml));
        $ibgeText = self::normalizeTextForParsing(wp_strip_all_tags($ibgeHtml));

        $prefeitoName = self::normalizePersonName(self::extractNameByRole($prefeituraText, ['prefeito', 'prefeita']));
        if ($prefeitoName === '') {
            $prefeitoName = self::normalizePersonName(self::extractNameByRole($ibgeText, ['prefeito', 'prefeita']));
        }

        $viceName = self::normalizePersonName(self::extractNameByRole($prefeituraText, ['vice-prefeito', 'vice prefeita', 'vice prefeito']));
        if ($viceName === '') {
            $viceName = self::normalizePersonName(self::extractNameByRole($ibgeText, ['vice-prefeito', 'vice prefeita', 'vice prefeito']));
        }

        $party = self::normalizeParty(self::extractPrefeitoParty($prefeituraText));
        if ($party === '') {
            $party = self::normalizeParty(self::extractPrefeitoParty($ibgeText));
        }

        $address = self::extractAddressFromText($prefeituraText, $city);
        if ($address === '') {
            $address = 'Prefeitura Municipal de ' . $city . ', Goiás, Brasil';
        }

        return [
            'prefeito_name' => $prefeitoName,
            'vice_name' => $viceName,
            'prefeito_party' => $party,
            'municipio' => $city,
            'source_url' => $prefeituraUrl,
            'ibge_url' => $ibgeUrl,
            'address' => $address,
        ];
    }

    private static function normalizeTextForParsing(string $text): string
    {
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim((string) $text);
    }

    private static function extractNameByRole(string $text, array $roles): string
    {
        foreach ($roles as $role) {
            $pattern = "/" . preg_quote($role, '/') . "\\s*[:\\-–]\\s*([A-ZÁÉÍÓÚÂÊÔÃÕÇ][A-Za-zÁÉÍÓÚÂÊÔÃÕÇ'\\s]{4,100})/iu";
            if (preg_match($pattern, $text, $m)) {
                return sanitize_text_field((string) $m[1]);
            }

            $patternInline = "/([A-ZÁÉÍÓÚÂÊÔÃÕÇ][A-Za-zÁÉÍÓÚÂÊÔÃÕÇ'\\s]{4,100})\\s*\\(?" . preg_quote($role, '/') . "\\)?/iu";
            if (preg_match($patternInline, $text, $m2)) {
                return sanitize_text_field((string) $m2[1]);
            }
        }

        return '';
    }

    private static function extractPrefeitoParty(string $text): string
    {
        if (preg_match('/prefeit(?:o|a)[^\n\r]{0,90}?\b(MDB|UNI[ÃA]O BRASIL|PP|PSD|PSDB|PT|PL|PSB|REPUBLICANOS|PODEMOS|SOLIDARIEDADE|PDT|PV|CIDADANIA|AVANTE)\b/iu', $text, $m)) {
            return (string) $m[1];
        }

        if (preg_match('/\b(MDB|UNI[ÃA]O BRASIL|PP|PSD|PSDB|PT|PL|PSB|REPUBLICANOS|PODEMOS|SOLIDARIEDADE|PDT|PV|CIDADANIA|AVANTE)\b/iu', $text, $m2)) {
            return (string) $m2[1];
        }

        return '';
    }

    private static function extractAddressFromText(string $text, string $city): string
    {
        $patterns = [
            '/(?:endere[cç]o|localiza[cç][aã]o|sede)\s*[:\-]\s*([^\n\r]{15,180})/iu',
            '/(Rua\s+[^\n\r]{8,140}\d{1,5}[^\n\r]{0,50})/iu',
            '/(Avenida\s+[^\n\r]{8,140}\d{1,5}[^\n\r]{0,50})/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $value = trim((string) ($m[1] ?? ''));
                if ($value !== '' && mb_strlen($value) >= 15) {
                    return sanitize_text_field($value . ', ' . $city . ', Goiás, Brasil');
                }
            }
        }

        return '';
    }

    private static function normalizePersonName(string $name): string
    {
        $name = sanitize_text_field(trim($name));
        if ($name === '') {
            return '';
        }

        $name = preg_replace('/\s+/u', ' ', $name);
        $name = mb_convert_case((string) $name, MB_CASE_TITLE, 'UTF-8');

        if (!self::isValidPersonName($name)) {
            return '';
        }

        return $name;
    }

    private static function isValidPersonName(string $name): bool
    {
        if ($name === '' || mb_strlen($name) < 7) {
            return false;
        }

        $blocked = ['prefeito municipal', 'prefeita municipal', 'vice-prefeito', 'vice prefeita', 'gabinete do prefeito', 'pendente de validação'];
        $nameLower = mb_strtolower($name, 'UTF-8');
        foreach ($blocked as $item) {
            if (str_contains($nameLower, $item)) {
                return false;
            }
        }

        return preg_match('/\s/u', $name) === 1;
    }

    private static function normalizeParty(string $party): string
    {
        $party = sanitize_text_field(trim($party));
        if ($party === '') {
            return '';
        }

        $party = strtoupper($party);
        $party = str_replace(['UNIÃO BRASIL', 'UNIAO BRASIL'], 'UNIÃO BRASIL', $party);

        return preg_replace('/\s+/u', ' ', $party) ?: '';
    }

    private static function validateMandatoryData(array $data): array
    {
        $missing = [];

        if (empty($data['prefeito_name'])) {
            $missing[] = 'nome completo do prefeito';
        }
        if (empty($data['vice_name'])) {
            $missing[] = 'nome completo do vice-prefeito';
        }
        if (empty($data['prefeito_party'])) {
            $missing[] = 'partido do prefeito';
        }
        if (empty($data['municipio'])) {
            $missing[] = 'município';
        }
        if (empty($data['address'])) {
            $missing[] = 'endereço da prefeitura';
        }
        if (empty($data['source_url']) || !self::isValidOfficialSource((string) $data['source_url'])) {
            $missing[] = 'fonte oficial válida';
        }

        return $missing;
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
            'user-agent' => 'MapaPoliticoBot/2.0 (+https://www.andredopremium.com.br/mapapolitico)',
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
