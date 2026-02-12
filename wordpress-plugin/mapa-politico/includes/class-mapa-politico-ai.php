<?php

if (!defined('ABSPATH')) {
    exit;
}

class MapaPoliticoAI
{
    private const CRON_HOOK = 'mapa_politico_ai_sync_event';

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
     * Sincronização automática segura (sem scraping ilegal):
     * - municípios via IBGE
     * - fontes institucionais .gov.br descobertas por heurística
     * - geocodificação Nominatim
     * - dados incompletos marcados para validação humana
     */
    public static function runSync(): array
    {
        global $wpdb;

        $locationsTable = $wpdb->prefix . 'mapa_politico_locations';
        $politiciansTable = $wpdb->prefix . 'mapa_politico_politicians';

        $municipalities = self::fetchGoiasMunicipalities();
        if (empty($municipalities)) {
            error_log('[MapaPoliticoAI] Nenhum município retornado pelo IBGE.');
            return ['processed' => 0, 'created' => 0, 'updated' => 0];
        }

        $created = 0;
        $updated = 0;

        foreach ($municipalities as $municipality) {
            $city = sanitize_text_field((string) ($municipality['nome'] ?? ''));
            $ibgeCode = (string) ($municipality['id'] ?? '');
            if ($city === '' || $ibgeCode === '') {
                continue;
            }

            // MÓDULO 1: PREFEITO (APARECE NO MAPA)
            $prefeituraUrl = self::discoverOfficialUrl($city, 'prefeitura');
            $prefeituraGeo = self::geocodeNominatim('Prefeitura Municipal de ' . $city . ', Goiás, Brasil');

            $locationIdPrefeitura = self::upsertLocation($locationsTable, [
                'name' => 'Prefeitura de ' . $city,
                'city' => $city,
                'state' => 'Goiás',
                'postal_code' => '',
                'latitude' => $prefeituraGeo['lat'] ?? -15.8270,
                'longitude' => $prefeituraGeo['lng'] ?? -49.8362,
                'address' => 'Prefeitura Municipal de ' . $city,
                'ibge_code' => $ibgeCode,
                'institution_type' => 'prefeitura',
                'source_url' => $prefeituraUrl,
                'last_synced_at' => current_time('mysql'),
            ]);

            if ($locationIdPrefeitura > 0) {
                $prefeitoData = self::extractPrefeitoData($prefeituraUrl, $city);
                $prefeitoResult = self::upsertPolitician($politiciansTable, [
                    'location_id' => $locationIdPrefeitura,
                    'full_name' => $prefeitoData['full_name'],
                    'position' => 'Prefeito',
                    'party' => $prefeitoData['party'],
                    'phone' => $prefeitoData['phone'],
                    'email' => $prefeitoData['email'],
                    'biography' => $prefeitoData['biography'],
                    'source_url' => $prefeitoData['source_url'],
                    'source_name' => 'Portal oficial da Prefeitura (.gov.br)',
                    'data_status' => $prefeitoData['data_status'],
                    'is_auto' => 1,
                    'last_synced_at' => current_time('mysql'),
                    'municipality_code' => $ibgeCode,
                    'photo_id' => $prefeitoData['photo_id'],
                ]);

                if ($prefeitoResult === 'created') {
                    $created++;
                } elseif ($prefeitoResult === 'updated') {
                    $updated++;
                }
            }

            // MÓDULO 2: VEREADORES (NÃO APARECEM NO MAPA)
            $camaraUrl = self::discoverOfficialUrl($city, 'camara');
            $camaraGeo = self::geocodeNominatim('Câmara Municipal de ' . $city . ', Goiás, Brasil');

            $locationIdCamara = self::upsertLocation($locationsTable, [
                'name' => 'Câmara de Vereadores de ' . $city,
                'city' => $city,
                'state' => 'Goiás',
                'postal_code' => '',
                'latitude' => $camaraGeo['lat'] ?? -15.8270,
                'longitude' => $camaraGeo['lng'] ?? -49.8362,
                'address' => 'Câmara Municipal de ' . $city,
                'ibge_code' => $ibgeCode,
                'institution_type' => 'camara',
                'source_url' => $camaraUrl,
                'last_synced_at' => current_time('mysql'),
            ]);

            if ($locationIdCamara > 0) {
                $vereadores = self::extractVereadoresData($camaraUrl, $city);
                if (empty($vereadores)) {
                    // Registro mínimo auditável, sem inventar nomes
                    $vereadores[] = [
                        'full_name' => 'Pendente de validação',
                        'party' => '',
                        'phone' => '',
                        'email' => '',
                        'source_url' => $camaraUrl,
                        'data_status' => 'aguardando_validacao',
                    ];
                }

                foreach ($vereadores as $vereador) {
                    $result = self::upsertPolitician($politiciansTable, [
                        'location_id' => $locationIdCamara,
                        'full_name' => $vereador['full_name'],
                        'position' => 'Vereador',
                        'party' => $vereador['party'],
                        'phone' => $vereador['phone'],
                        'email' => $vereador['email'],
                        'source_url' => $vereador['source_url'],
                        'source_name' => 'Portal oficial da Câmara (.gov.br)',
                        'data_status' => $vereador['data_status'],
                        'is_auto' => 1,
                        'last_synced_at' => current_time('mysql'),
                        'municipality_code' => $ibgeCode,
                        'photo_id' => null,
                    ]);

                    if ($result === 'created') {
                        $created++;
                    } elseif ($result === 'updated') {
                        $updated++;
                    }
                }
            }
        }

        update_option('mapa_politico_ai_last_sync', current_time('mysql'));

        return ['processed' => count($municipalities), 'created' => $created, 'updated' => $updated];
    }

    public static function runManualSearch(string $name, string $position): array
    {
        $name = sanitize_text_field($name);
        $position = sanitize_text_field($position);
        if ($name === '' || !in_array($position, ['Prefeito', 'Vereador'], true)) {
            return ['found' => false, 'message' => 'Parâmetros inválidos'];
        }

        global $wpdb;
        $table = $wpdb->prefix . 'mapa_politico_politicians';

        // Primeiro tenta localizar registro já existente para atualizar
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

        // Se não encontrou, roda sincronização completa para tentar captar novos dados oficiais
        self::runSync();

        return ['found' => false, 'message' => 'Nome não encontrado inicialmente. Sincronização geral executada para atualização.'];
    }

    private static function fetchGoiasMunicipalities(): array
    {
        $url = 'https://servicodados.ibge.gov.br/api/v1/localidades/estados/52/municipios';
        $response = wp_remote_get($url, [
            'timeout' => 25,
            'user-agent' => 'MapaPoliticoBot/1.2 (+https://www.andredopremium.com.br/mapapolitico)',
        ]);

        if (is_wp_error($response)) {
            error_log('[MapaPoliticoAI] Erro IBGE: ' . $response->get_error_message());
            return [];
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        if ($status < 200 || $status >= 300) {
            error_log('[MapaPoliticoAI] IBGE status inválido: ' . $status);
            return [];
        }

        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        return is_array($data) ? $data : [];
    }

    private static function discoverOfficialUrl(string $city, string $institution): string
    {
        $slug = sanitize_title($city);

        $candidates = $institution === 'prefeitura'
            ? [
                'https://www.' . $slug . '.go.gov.br',
                'https://' . $slug . '.go.gov.br',
            ]
            : [
                'https://www.camara.' . $slug . '.go.gov.br',
                'https://camara.' . $slug . '.go.gov.br',
                'https://www.' . $slug . '.go.gov.br/camara',
            ];

        foreach ($candidates as $url) {
            $host = (string) wp_parse_url($url, PHP_URL_HOST);
            if ($host === '' || !str_ends_with($host, '.gov.br')) {
                continue;
            }

            $response = wp_remote_head($url, [
                'timeout' => 10,
                'redirection' => 3,
                'user-agent' => 'MapaPoliticoBot/1.2',
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

    private static function extractPrefeitoData(string $sourceUrl, string $city): array
    {
        $fallback = [
            'full_name' => 'Pendente de validação',
            'party' => '',
            'phone' => '',
            'email' => '',
            'biography' => '',
            'photo_id' => null,
            'source_url' => $sourceUrl,
            'data_status' => 'aguardando_validacao',
        ];

        if ($sourceUrl === '') {
            return $fallback;
        }

        $html = self::safeGetHtml($sourceUrl);
        if ($html === '') {
            return $fallback;
        }

        $text = wp_strip_all_tags($html);

        $name = self::findAfterKeywords($text, ['Prefeito', 'Prefeita']);
        $party = self::findSimpleParty($text);
        $email = self::findFirstEmail($text);
        $phone = self::findFirstPhone($text);
        $biography = mb_substr(trim($text), 0, 800);
        $photoId = self::downloadOfficialPhoto($html, $sourceUrl, $city);

        return [
            'full_name' => $name !== '' ? $name : $fallback['full_name'],
            'party' => $party,
            'phone' => $phone,
            'email' => $email,
            'biography' => $biography,
            'photo_id' => $photoId,
            'source_url' => $sourceUrl,
            'data_status' => ($name !== '' ? 'incompleto' : 'aguardando_validacao'),
        ];
    }

    private static function extractVereadoresData(string $sourceUrl, string $city): array
    {
        if ($sourceUrl === '') {
            return [];
        }

        $html = self::safeGetHtml($sourceUrl);
        if ($html === '') {
            return [];
        }

        $text = wp_strip_all_tags($html);
        $emails = self::findAllEmails($text);

        // Extração conservadora para não inventar nomes
        $names = [];
        if (preg_match_all('/vereador(?:a)?\s*[:\-]\s*([A-ZÁÉÍÓÚÂÊÔÃÕÇ][A-Za-zÁÉÍÓÚÂÊÔÃÕÇ\s]{4,70})/iu', $text, $matches)) {
            $names = array_unique(array_map('trim', $matches[1]));
        }

        $rows = [];
        foreach ($names as $idx => $name) {
            $rows[] = [
                'full_name' => sanitize_text_field($name),
                'party' => self::findSimpleParty($text),
                'phone' => self::findFirstPhone($text),
                'email' => $emails[$idx] ?? '',
                'source_url' => $sourceUrl,
                'data_status' => 'incompleto',
            ];
        }

        return $rows;
    }

    private static function safeGetHtml(string $url): string
    {
        $host = (string) wp_parse_url($url, PHP_URL_HOST);
        if ($host === '' || !str_ends_with($host, '.gov.br')) {
            return '';
        }

        $response = wp_remote_get($url, [
            'timeout' => 18,
            'redirection' => 3,
            'user-agent' => 'MapaPoliticoBot/1.2',
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

    private static function geocodeNominatim(string $query): array
    {
        $url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=' . rawurlencode($query);
        $response = wp_remote_get($url, [
            'timeout' => 20,
            'user-agent' => 'MapaPoliticoBot/1.2 (+https://www.andredopremium.com.br/mapapolitico)',
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
            $wpdb->update($table, $data, ['id' => $existingId]);
            return $existingId;
        }

        $ok = $wpdb->insert($table, $data);
        if ($ok === false) {
            error_log('[MapaPoliticoAI] Falha location upsert: ' . $wpdb->last_error);
            return 0;
        }

        return (int) $wpdb->insert_id;
    }

    private static function upsertPolitician(string $table, array $data): string
    {
        global $wpdb;

        $existingId = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE municipality_code = %s AND position = %s AND full_name = %s LIMIT 1",
            $data['municipality_code'],
            $data['position'],
            $data['full_name']
        ));

        if ($existingId > 0) {
            $ok = $wpdb->update($table, $data, ['id' => $existingId]);
            if ($ok === false) {
                error_log('[MapaPoliticoAI] Falha politician update: ' . $wpdb->last_error);
                return 'error';
            }
            return 'updated';
        }

        $ok = $wpdb->insert($table, $data);
        if ($ok === false) {
            error_log('[MapaPoliticoAI] Falha politician insert: ' . $wpdb->last_error);
            return 'error';
        }

        return 'created';
    }

    private static function findAfterKeywords(string $text, array $keywords): string
    {
        foreach ($keywords as $keyword) {
            $pattern = '/' . preg_quote($keyword, '/') . '\s*[:\-]\s*([A-ZÁÉÍÓÚÂÊÔÃÕÇ][A-Za-zÁÉÍÓÚÂÊÔÃÕÇ\s]{5,80})/iu';
            if (preg_match($pattern, $text, $m)) {
                return sanitize_text_field(trim($m[1]));
            }
        }

        return '';
    }

    private static function findSimpleParty(string $text): string
    {
        if (preg_match('/\b(MDB|UNI[ÃA]O BRASIL|PP|PSD|PSDB|PT|PL|PSB|REPUBLICANOS|PODEMOS|SOLIDARIEDADE|PDT|PV|CIDADANIA|AVANTE)\b/iu', $text, $m)) {
            return sanitize_text_field(strtoupper((string) $m[1]));
        }
        return '';
    }

    private static function findFirstEmail(string $text): string
    {
        if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu', $text, $m)) {
            return sanitize_email((string) $m[0]);
        }
        return '';
    }

    private static function findAllEmails(string $text): array
    {
        if (!preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu', $text, $m)) {
            return [];
        }

        $emails = array_map('sanitize_email', $m[0]);
        return array_values(array_unique(array_filter($emails)));
    }

    private static function findFirstPhone(string $text): string
    {
        if (preg_match('/(?:\+?55\s*)?(?:\(?\d{2}\)?\s*)?(?:9?\d{4})[-\s]?\d{4}/', $text, $m)) {
            $digits = preg_replace('/[^0-9]/', '', (string) $m[0]);
            if ($digits !== '') {
                return '+' . $digits;
            }
        }
        return '';
    }

    private static function downloadOfficialPhoto(string $html, string $sourceUrl, string $city): ?int
    {
        if ($sourceUrl === '') {
            return null;
        }

        $host = (string) wp_parse_url($sourceUrl, PHP_URL_HOST);
        if ($host === '' || !str_ends_with($host, '.gov.br')) {
            return null;
        }

        $imageUrl = null;
        if (preg_match('/property=["\']og:image["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $m)) {
            $imageUrl = esc_url_raw((string) $m[1]);
        }

        if (!$imageUrl) {
            return null;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $tmp = download_url($imageUrl, 20);
        if (is_wp_error($tmp)) {
            return null;
        }

        $filename = 'prefeito-' . sanitize_title($city) . '-' . wp_generate_password(6, false) . '.jpg';
        $fileArray = [
            'name' => $filename,
            'tmp_name' => $tmp,
        ];

        $id = media_handle_sideload($fileArray, 0, 'Foto oficial de prefeito - ' . $city);
        if (is_wp_error($id)) {
            @unlink($tmp);
            return null;
        }

        return (int) $id;
    }
}
