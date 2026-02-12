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
            wp_schedule_event(time() + 120, 'weekly', self::CRON_HOOK);
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
        if (!isset($schedules['weekly'])) {
            $schedules['weekly'] = [
                'interval' => 7 * DAY_IN_SECONDS,
                'display' => 'Uma vez por semana',
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

            $institutions = [
                ['type' => 'prefeitura', 'query' => 'Prefeitura Municipal de ' . $city . ', Goiás, Brasil'],
                ['type' => 'camara', 'query' => 'Câmara Municipal de ' . $city . ', Goiás, Brasil'],
            ];

            $citySlug = sanitize_title($city);
            $sourceUrl = 'https://cidades.ibge.gov.br/brasil/go/' . $citySlug . '/panorama';

            foreach ($institutions as $institution) {
                $geo = self::geocodeNominatim($institution['query']);
                $lat = $geo['lat'] ?? -15.8270;
                $lng = $geo['lng'] ?? -49.8362;

                $locationId = self::upsertLocation($locationsTable, [
                    'name' => ucfirst($institution['type']) . ' de ' . $city,
                    'city' => $city,
                    'state' => 'Goiás',
                    'postal_code' => '',
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'address' => $institution['query'],
                    'ibge_code' => $ibgeCode,
                    'institution_type' => $institution['type'],
                    'source_url' => $sourceUrl,
                    'last_synced_at' => current_time('mysql'),
                ]);

                if (!$locationId) {
                    continue;
                }

                $roles = $institution['type'] === 'prefeitura'
                    ? ['Prefeito', 'Vice-prefeito']
                    : ['Vereador'];

                foreach ($roles as $role) {
                    $result = self::upsertPolitician($politiciansTable, [
                        'location_id' => $locationId,
                        'full_name' => 'Pendente de validação',
                        'position' => $role,
                        'party' => 'Pendente',
                        'phone' => '',
                        'email' => '',
                        'source_url' => $sourceUrl,
                        'source_name' => 'IBGE + Nominatim + validação IA pendente',
                        'data_status' => 'aguardando_validacao',
                        'is_auto' => 1,
                        'last_synced_at' => current_time('mysql'),
                        'municipality_code' => $ibgeCode,
                    ]);

                    if ($result === 'created') {
                        $created++;
                    }
                    if ($result === 'updated') {
                        $updated++;
                    }
                }
            }
        }

        update_option('mapa_politico_ai_last_sync', current_time('mysql'));

        return [
            'processed' => count($municipalities),
            'created' => $created,
            'updated' => $updated,
        ];
    }

    private static function fetchGoiasMunicipalities(): array
    {
        $url = 'https://servicodados.ibge.gov.br/api/v1/localidades/estados/52/municipios';

        $response = wp_remote_get($url, [
            'timeout' => 25,
            'user-agent' => 'MapaPoliticoBot/1.0 (+https://www.andredopremium.com.br/mapapolitico)',
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

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        return is_array($data) ? $data : [];
    }

    private static function geocodeNominatim(string $query): array
    {
        $url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=' . rawurlencode($query);
        $response = wp_remote_get($url, [
            'timeout' => 20,
            'user-agent' => 'MapaPoliticoBot/1.0 (+https://www.andredopremium.com.br/mapapolitico)',
            'headers' => ['Accept' => 'application/json'],
        ]);

        if (is_wp_error($response)) {
            return [];
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        if ($status < 200 || $status >= 300) {
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $json = json_decode($body, true);
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
            "SELECT id FROM {$table} WHERE municipality_code = %s AND position = %s LIMIT 1",
            $data['municipality_code'],
            $data['position']
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
}
