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
        self::processNextQueueItem();
    }

    public static function runSync(): array
    {
        self::enqueueAllMunicipalities();
        return self::processNextQueueItem();
    }

    public static function enqueueAllMunicipalities(): array
    {
        $municipalities = self::fetchGoiasMunicipalities();
        if (empty($municipalities)) {
            self::logEvent('erro', 'N/A', 'fila_insercao', 'Falha ao obter municípios do IBGE', []);
            return ['enqueued' => 0, 'errors' => 1];
        }

        $enqueued = 0;
        $errors = 0;
        foreach ($municipalities as $municipality) {
            $city = sanitize_text_field((string) ($municipality['nome'] ?? ''));
            $ibgeCode = (string) ($municipality['id'] ?? '');
            if ($city === '' || $ibgeCode === '') {
                $errors++;
                continue;
            }

            $ok = self::enqueueMunicipality($city, $ibgeCode, 'lote_ibge');
            if ($ok) {
                $enqueued++;
            }
        }

        return ['enqueued' => $enqueued, 'errors' => $errors];
    }

    public static function enqueueMunicipalityByCode(string $ibgeCode): array
    {
        $ibgeCode = sanitize_text_field($ibgeCode);
        if ($ibgeCode === '') {
            return ['ok' => false, 'message' => 'Código IBGE inválido'];
        }

        $municipalities = self::fetchGoiasMunicipalities();
        foreach ($municipalities as $municipality) {
            if ((string) ($municipality['id'] ?? '') !== $ibgeCode) {
                continue;
            }

            $city = sanitize_text_field((string) ($municipality['nome'] ?? ''));
            if ($city === '') {
                return ['ok' => false, 'message' => 'Município inválido'];
            }

            $ok = self::enqueueMunicipality($city, $ibgeCode, 'manual_admin');
            return ['ok' => $ok, 'message' => $ok ? 'Município enfileirado' : 'Município já estava na fila'];
        }

        return ['ok' => false, 'message' => 'Município não encontrado no IBGE'];
    }

    private static function enqueueMunicipality(string $city, string $ibgeCode, string $sourceNote): bool
    {
        global $wpdb;
        $queueTable = $wpdb->prefix . 'mapa_politico_sync_queue';

        $exists = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$queueTable} WHERE municipality_code = %s LIMIT 1", $ibgeCode));
        if ($exists > 0) {
            return false;
        }

        $insert = $wpdb->insert($queueTable, [
            'municipality_name' => $city,
            'municipality_code' => $ibgeCode,
            'status' => 'pendente',
            'source_note' => $sourceNote,
        ]);

        return $insert !== false;
    }

    public static function processNextQueueItem(): array
    {
        global $wpdb;

        $queueTable = $wpdb->prefix . 'mapa_politico_sync_queue';
        $locationsTable = $wpdb->prefix . 'mapa_politico_locations';
        $politiciansTable = $wpdb->prefix . 'mapa_politico_politicians';

        $item = $wpdb->get_row("SELECT * FROM {$queueTable} WHERE status IN ('pendente','erro') ORDER BY id ASC LIMIT 1", ARRAY_A);
        if (!$item) {
            return ['processed' => 0, 'created' => 0, 'updated' => 0, 'errors' => 0, 'message' => 'Fila vazia'];
        }

        $queueId = (int) $item['id'];
        $city = sanitize_text_field((string) $item['municipality_name']);
        $ibgeCode = sanitize_text_field((string) $item['municipality_code']);

        $wpdb->update($queueTable, [
            'status' => 'processando',
            'attempts' => (int) $item['attempts'] + 1,
            'started_at' => current_time('mysql'),
        ], ['id' => $queueId]);

        try {
            $documents = self::collectSourceDocuments($city);
            $sourceUrls = array_values(array_unique(array_filter(array_map(static fn($d) => (string) ($d['url'] ?? ''), $documents))));
            if (empty($documents)) {
                throw new RuntimeException('Nenhuma fonte acessível encontrada');
            }

            $extracted = self::extractStructuredData($city, $documents);
            $validation = self::validateForRegistration($extracted);
            if (!$validation['ok']) {
                throw new RuntimeException('Validação falhou: ' . implode('; ', $validation['issues']));
            }

            $locationId = self::upsertLocation($locationsTable, [
                'name' => 'Prefeitura de ' . $city,
                'city' => $city,
                'state' => 'Goiás',
                'postal_code' => '',
                'latitude' => $extracted['latitude'],
                'longitude' => $extracted['longitude'],
                'address' => $extracted['address'],
                'ibge_code' => $ibgeCode,
                'institution_type' => 'prefeitura',
                'source_url' => $extracted['primary_source'],
                'last_synced_at' => current_time('mysql'),
            ]);
            if ($locationId < 1) {
                throw new RuntimeException('Falha ao salvar localização');
            }

            $photoUrl = self::extractOfficialPhotoUrl($documents);

            $prefeitoResult = self::upsertPoliticianSafe($politiciansTable, [
                'location_id' => $locationId,
                'full_name' => $extracted['prefeito'],
                'position' => 'Prefeito',
                'party' => $extracted['party'],
                'phone' => $extracted['phone'],
                'email' => $extracted['email'],
                'biography' => '',
                'source_url' => $extracted['primary_source'],
                'source_name' => 'Pesquisa multifonte validada',
                'data_status' => 'completo',
                'is_auto' => 1,
                'last_synced_at' => current_time('mysql'),
                'municipality_code' => $ibgeCode,
                'photo_url' => $photoUrl,
                'municipality_history' => $extracted['mandate'],
                'validation_notes' => 'Sincronização por fila: item #' . $queueId,
            ]);

            $viceResult = self::upsertPoliticianSafe($politiciansTable, [
                'location_id' => $locationId,
                'full_name' => $extracted['vice'],
                'position' => 'Vice-prefeito',
                'party' => $extracted['party'],
                'phone' => $extracted['phone'],
                'email' => $extracted['email'],
                'biography' => '',
                'source_url' => $extracted['primary_source'],
                'source_name' => 'Pesquisa multifonte validada',
                'data_status' => $extracted['vice'] === 'Não localizado' ? 'incompleto' : 'completo',
                'is_auto' => 1,
                'last_synced_at' => current_time('mysql'),
                'municipality_code' => $ibgeCode,
                'photo_url' => null,
                'municipality_history' => $extracted['mandate'],
                'validation_notes' => $extracted['vice'] === 'Não localizado' ? 'Vice não localizado.' : 'Vice localizado.',
            ]);

            if ($prefeitoResult === 'error' || $viceResult === 'error') {
                throw new RuntimeException('Falha ao salvar prefeito/vice');
            }

            $created = 0;
            $updated = 0;
            if ($prefeitoResult === 'created') { $created++; } elseif ($prefeitoResult === 'updated') { $updated++; }
            if ($viceResult === 'created') { $created++; } elseif ($viceResult === 'updated') { $updated++; }

            $wpdb->update($queueTable, [
                'status' => 'concluido',
                'last_error' => null,
                'finished_at' => current_time('mysql'),
            ], ['id' => $queueId]);

            self::logEvent('sucesso', $city, 'fila_processada', 'Sincronização concluída para município', $sourceUrls, [
                'prefeito' => $extracted['prefeito'],
                'vice' => $extracted['vice'],
                'party' => $extracted['party'],
            ]);

            update_option('mapa_politico_ai_last_sync', current_time('mysql'));

            return ['processed' => 1, 'created' => $created, 'updated' => $updated, 'errors' => 0, 'message' => 'Município processado'];
        } catch (Throwable $e) {
            $wpdb->update($queueTable, [
                'status' => 'erro',
                'last_error' => $e->getMessage(),
                'finished_at' => current_time('mysql'),
            ], ['id' => $queueId]);

            self::logEvent('erro', $city, 'fila_processada', $e->getMessage(), []);
            return ['processed' => 1, 'created' => 0, 'updated' => 0, 'errors' => 1, 'message' => 'Erro no município'];
        }
    }

    public static function getQueueRows(int $limit = 300): array
    {
        global $wpdb;
        $queueTable = $wpdb->prefix . 'mapa_politico_sync_queue';
        $limit = max(1, min(1000, $limit));

        $sql = $wpdb->prepare("SELECT * FROM {$queueTable} ORDER BY id DESC LIMIT %d", $limit);
        $rows = $wpdb->get_results($sql, ARRAY_A);
        return is_array($rows) ? $rows : [];
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


    public static function getGoiasMunicipalities(): array
    {
        return self::fetchGoiasMunicipalities();
    }

    private static function fetchGoiasMunicipalities(): array
    {
        $url = 'https://servicodados.ibge.gov.br/api/v1/localidades/estados/52/municipios';
        $response = wp_remote_get($url, [
            'timeout' => 25,
            'user-agent' => 'MapaPoliticoBot/3.0 (+https://www.andredopremium.com.br/mapapolitico)',
        ]);

        if (is_wp_error($response)) {
            return [];
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        if ($status < 200 || $status >= 300) {
            return [];
        }

        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        return is_array($data) ? $data : [];
    }

    private static function collectSourceDocuments(string $city): array
    {
        $documents = [];
        $slug = sanitize_title($city);

        $sources = [
            ['type' => 'prefeitura', 'url' => self::discoverOfficialPrefeituraUrl($city), 'official' => true],
            ['type' => 'ibge', 'url' => 'https://cidades.ibge.gov.br/brasil/go/' . $slug . '/panorama', 'official' => true],
            ['type' => 'tse', 'url' => 'https://www.tse.jus.br', 'official' => true],
            ['type' => 'transparencia', 'url' => 'https://transparencia.' . $slug . '.go.gov.br', 'official' => true],
            ['type' => 'estado_goias', 'url' => 'https://goias.gov.br', 'official' => true],
            ['type' => 'noticias_governo', 'url' => 'https://www.agenciacoradenoticias.go.gov.br', 'official' => true],
            ['type' => 'wikidata', 'url' => 'https://www.wikidata.org/wiki/Special:Search?search=' . rawurlencode('prefeito ' . $city . ' goias'), 'official' => false],
            ['type' => 'wikipedia', 'url' => 'https://pt.wikipedia.org/wiki/' . rawurlencode($city), 'official' => false],
        ];

        foreach ($sources as $source) {
            $url = (string) ($source['url'] ?? '');
            if ($url === '') {
                continue;
            }

            $html = self::safeGetHtml($url, (bool) ($source['official'] ?? false));
            if ($html === '') {
                continue;
            }

            $documents[] = [
                'type' => (string) $source['type'],
                'url' => $url,
                'official' => (bool) ($source['official'] ?? false),
                'html' => $html,
            ];
        }

        return $documents;
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
                'user-agent' => 'MapaPoliticoBot/3.0',
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

    private static function safeGetHtml(string $url, bool $mustBeOfficial): string
    {
        $host = (string) wp_parse_url($url, PHP_URL_HOST);
        if ($host === '') {
            return '';
        }

        if ($mustBeOfficial && !self::isOfficialHost($host)) {
            return '';
        }

        $response = wp_remote_get($url, [
            'timeout' => 16,
            'redirection' => 4,
            'user-agent' => 'MapaPoliticoBot/3.0',
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

    private static function isOfficialHost(string $host): bool
    {
        return str_ends_with($host, '.gov.br')
            || str_ends_with($host, '.jus.br')
            || str_ends_with($host, '.leg.br')
            || $host === 'cidades.ibge.gov.br'
            || str_ends_with($host, '.ibge.gov.br');
    }

    private static function extractStructuredData(string $city, array $documents): array
    {
        $mayorCandidates = [];
        $viceCandidates = [];
        $partyCandidates = [];
        $address = '';
        $phone = '';
        $email = '';
        $mandate = '';
        $geo = [];

        foreach ($documents as $doc) {
            $html = (string) ($doc['html'] ?? '');
            $text = self::normalizeText(wp_strip_all_tags($html));
            $sourceType = (string) ($doc['type'] ?? '');

            foreach (self::extractPeopleByRole($text, ['prefeito', 'prefeito municipal', 'prefeita', 'chefe do executivo', 'gestão municipal']) as $name) {
                $mayorCandidates[] = ['name' => $name, 'source' => $sourceType];
            }

            foreach (self::extractPeopleByRole($text, ['vice-prefeito', 'vice-prefeita', 'vice prefeito', 'vice prefeita']) as $name) {
                $viceCandidates[] = ['name' => $name, 'source' => $sourceType];
            }

            $party = self::extractParty($text);
            if ($party !== '') {
                $partyCandidates[] = ['party' => $party, 'source' => $sourceType];
            }

            if ($address === '') {
                $address = self::extractAddress($text, $city);
            }

            if ($phone === '') {
                $phone = self::findFirstPhone($text);
            }

            if ($email === '') {
                $email = self::findFirstEmail($text);
            }

            if ($mandate === '') {
                $mandate = self::extractMandate($text);
            }
        }

        $mayor = self::pickCrossValidatedName($mayorCandidates);
        $vice = self::pickCrossValidatedName($viceCandidates);
        $party = self::pickCrossValidatedParty($partyCandidates);

        if ($address === '') {
            $address = 'Prefeitura Municipal de ' . $city . ', Goiás, Brasil';
        }
        $geo = self::geocodeNominatim($address);

        $primarySource = '';
        foreach ($documents as $doc) {
            if (!empty($doc['official'])) {
                $primarySource = (string) ($doc['url'] ?? '');
                break;
            }
        }

        return [
            'prefeito' => $mayor,
            'vice' => $vice !== '' ? $vice : 'Não localizado',
            'party' => $party !== '' ? $party : 'Não informado',
            'city' => $city,
            'state' => 'Goiás',
            'address' => $address,
            'latitude' => isset($geo['lat']) ? (float) $geo['lat'] : null,
            'longitude' => isset($geo['lng']) ? (float) $geo['lng'] : null,
            'phone' => $phone,
            'email' => $email,
            'mandate' => $mandate,
            'primary_source' => $primarySource,
        ];
    }

    private static function validateForRegistration(array $data): array
    {
        $issues = [];

        if (!self::isValidPersonName((string) ($data['prefeito'] ?? ''))) {
            $issues[] = 'Nome completo do prefeito não encontrado';
        }

        if ((string) ($data['city'] ?? '') === '') {
            $issues[] = 'Município não identificado';
        }

        if (!self::isValidOfficialSource((string) ($data['primary_source'] ?? ''))) {
            $issues[] = 'Fonte oficial inválida';
        }

        if (!is_float($data['latitude']) || !is_float($data['longitude'])) {
            $issues[] = 'Coordenadas da prefeitura não localizadas';
        }

        return ['ok' => empty($issues), 'issues' => $issues];
    }

    private static function extractPeopleByRole(string $text, array $roles): array
    {
        $results = [];
        foreach ($roles as $role) {
            $patternA = "/(?:" . preg_quote($role, '/') . ")\\s*[:\\-–]\\s*([A-ZÁÉÍÓÚÂÊÔÃÕÇ][A-Za-zÁÉÍÓÚÂÊÔÃÕÇ'\\s]{5,100})/iu";
            $patternB = "/([A-ZÁÉÍÓÚÂÊÔÃÕÇ][A-Za-zÁÉÍÓÚÂÊÔÃÕÇ'\\s]{5,100})\\s*[-–]\\s*(?:" . preg_quote($role, '/') . ")/iu";

            if (preg_match_all($patternA, $text, $mA)) {
                foreach ($mA[1] as $candidate) {
                    $name = self::normalizePersonName((string) $candidate);
                    if (self::isValidPersonName($name)) {
                        $results[] = $name;
                    }
                }
            }

            if (preg_match_all($patternB, $text, $mB)) {
                foreach ($mB[1] as $candidate) {
                    $name = self::normalizePersonName((string) $candidate);
                    if (self::isValidPersonName($name)) {
                        $results[] = $name;
                    }
                }
            }
        }

        return array_values(array_unique($results));
    }

    private static function pickCrossValidatedName(array $candidates): string
    {
        if (empty($candidates)) {
            return '';
        }

        $votes = [];
        foreach ($candidates as $c) {
            $name = (string) ($c['name'] ?? '');
            $source = (string) ($c['source'] ?? '');
            if ($name === '' || $source === '') {
                continue;
            }
            if (!isset($votes[$name])) {
                $votes[$name] = [];
            }
            $votes[$name][$source] = true;
        }

        $bestName = '';
        $bestCount = 0;
        foreach ($votes as $name => $sources) {
            $count = count($sources);
            if ($count > $bestCount) {
                $bestName = $name;
                $bestCount = $count;
            }
        }

        return $bestCount >= 2 ? $bestName : '';
    }

    private static function pickCrossValidatedParty(array $candidates): string
    {
        if (empty($candidates)) {
            return '';
        }

        $votes = [];
        foreach ($candidates as $c) {
            $party = self::normalizeParty((string) ($c['party'] ?? ''));
            $source = (string) ($c['source'] ?? '');
            if ($party === '' || $source === '') {
                continue;
            }
            if (!isset($votes[$party])) {
                $votes[$party] = [];
            }
            $votes[$party][$source] = true;
        }

        $best = '';
        $bestCount = 0;
        foreach ($votes as $party => $sources) {
            $count = count($sources);
            if ($count > $bestCount) {
                $best = $party;
                $bestCount = $count;
            }
        }

        return $bestCount >= 2 ? $best : '';
    }

    private static function extractParty(string $text): string
    {
        if (preg_match('/\b(MDB|PL|PSDB|PT|PSD|PP|PSB|REPUBLICANOS|PODEMOS|PDT|PV|CIDADANIA|AVANTE|UNI[ÃA]O BRASIL|SOLIDARIEDADE)\b/iu', $text, $m)) {
            return (string) $m[1];
        }
        return '';
    }

    private static function extractAddress(string $text, string $city): string
    {
        $patterns = [
            '/(?:endere[cç]o|localiza[cç][aã]o|sede)\s*[:\-]\s*([^\n\r]{12,180})/iu',
            '/(Rua\s+[^\n\r]{8,160})/iu',
            '/(Avenida\s+[^\n\r]{8,160})/iu',
            '/(Pra[cç]a\s+[^\n\r]{8,160})/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $raw = trim((string) ($m[1] ?? ''));
                if ($raw !== '') {
                    return sanitize_text_field($raw . ', ' . $city . ', Goiás, Brasil');
                }
            }
        }

        return '';
    }

    private static function extractMandate(string $text): string
    {
        if (preg_match('/(20\d{2}\s*[\-–/]\s*20\d{2})/u', $text, $m)) {
            return sanitize_text_field((string) $m[1]);
        }
        return '';
    }

    private static function normalizeText(string $text): string
    {
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim((string) $text);
    }

    private static function normalizePersonName(string $name): string
    {
        $name = self::normalizeText(sanitize_text_field($name));
        return $name === '' ? '' : mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
    }

    private static function isValidPersonName(string $name): bool
    {
        $name = self::normalizeText($name);
        if ($name === '' || mb_strlen($name) < 7 || preg_match('/\s/u', $name) !== 1) {
            return false;
        }

        $blocked = [
            'prefeito', 'prefeito municipal', 'prefeita', 'vice-prefeito', 'vice prefeito',
            'chefe do executivo', 'gestão municipal', 'gabinete do prefeito', 'não localizado',
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
        $party = str_replace(['UNIAO BRASIL', 'UNIÃO BRASIL'], 'UNIÃO BRASIL', $party);
        $valid = ['MDB', 'PL', 'PSDB', 'PT', 'PSD', 'PP', 'PSB', 'REPUBLICANOS', 'PODEMOS', 'PDT', 'PV', 'CIDADANIA', 'AVANTE', 'UNIÃO BRASIL', 'SOLIDARIEDADE'];
        return in_array($party, $valid, true) ? $party : '';
    }

    private static function isValidOfficialSource(string $url): bool
    {
        $host = (string) wp_parse_url($url, PHP_URL_HOST);
        return $host !== '' && self::isOfficialHost($host);
    }

    private static function findFirstEmail(string $text): string
    {
        if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu', $text, $m)) {
            return sanitize_email((string) $m[0]);
        }
        return '';
    }

    private static function findFirstPhone(string $text): string
    {
        if (preg_match('/(?:\+?55\s*)?(?:\(?\d{2}\)?\s*)?(?:9?\d{4})[-\s]?\d{4}/', $text, $m)) {
            $digits = preg_replace('/[^0-9]/', '', (string) $m[0]);
            return $digits !== '' ? '+' . $digits : '';
        }
        return '';
    }

    private static function geocodeNominatim(string $query): array
    {
        $url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=' . rawurlencode($query);
        $response = wp_remote_get($url, [
            'timeout' => 20,
            'user-agent' => 'MapaPoliticoBot/3.0 (+https://www.andredopremium.com.br/mapapolitico)',
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

        return ($lat !== null && $lng !== null) ? ['lat' => $lat, 'lng' => $lng] : [];
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
            return $ok === false ? 0 : $existingId;
        }

        $ok = $wpdb->insert($table, $data);
        return $ok === false ? 0 : (int) $wpdb->insert_id;
    }

    private static function upsertPoliticianSafe(string $table, array $data): string
    {
        global $wpdb;

        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id, data_status, source_url FROM {$table} WHERE municipality_code = %s AND position = %s LIMIT 1",
            $data['municipality_code'],
            $data['position']
        ), ARRAY_A);

        if ($existing) {
            $isExistingTrusted = (($existing['data_status'] ?? '') === 'completo') && self::isValidOfficialSource((string) ($existing['source_url'] ?? ''));
            $isNewTrusted = self::isValidOfficialSource((string) ($data['source_url'] ?? ''));
            if ($isExistingTrusted && !$isNewTrusted) {
                return 'updated';
            }

            $ok = $wpdb->update($table, $data, ['id' => (int) $existing['id']]);
            return $ok === false ? 'error' : 'updated';
        }

        $ok = $wpdb->insert($table, $data);
        return $ok === false ? 'error' : 'created';
    }

    private static function extractOfficialPhotoUrl(array $documents): string
    {
        foreach ($documents as $doc) {
            if (empty($doc['official'])) {
                continue;
            }

            $html = (string) ($doc['html'] ?? '');
            if ($html === '') {
                continue;
            }

            if (!preg_match('/property=["\']og:image["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $m)) {
                continue;
            }

            $imgUrl = esc_url_raw((string) ($m[1] ?? ''));
            if ($imgUrl === '') {
                continue;
            }

            $scheme = (string) wp_parse_url($imgUrl, PHP_URL_SCHEME);
            if ($scheme !== 'http' && $scheme !== 'https') {
                continue;
            }

            return $imgUrl;
        }

        return '';
    }

    private static function logEvent(string $type, string $municipality, string $step, string $reason, array $sources, array $data = []): void
    {
        $entry = [
            'type' => $type,
            'municipality' => $municipality,
            'step' => $step,
            'reason' => $reason,
            'source' => implode(' | ', $sources),
            'data' => $data,
            'created_at' => current_time('mysql'),
        ];

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
