<?php

if (!defined('ABSPATH')) {
    exit;
}

function mpg_ia_collect_municipio(string $municipioNome, string $municipioCodigo): array
{
    $municipioNome = sanitize_text_field($municipioNome);
    $municipioCodigo = sanitize_text_field($municipioCodigo);

    $docs = mpg_collect_documents($municipioNome);
    $sources = array_values(array_unique(array_map(static fn($d) => (string) ($d['url'] ?? ''), $docs)));
    if (empty($docs)) {
        return ['ok' => false, 'error' => 'Nenhuma fonte acessível', 'sources' => $sources];
    }

    $prefeitoCandidates = [];
    $viceCandidates = [];
    $partyCandidates = [];
    $address = '';
    $phone = '';
    $email = '';
    $cep = '';
    $mandato = '';

    foreach ($docs as $doc) {
        $text = mpg_normalize_text(wp_strip_all_tags((string) ($doc['html'] ?? '')));
        $source = (string) ($doc['type'] ?? 'fonte');

        foreach (mpg_extract_names_by_roles($text, ['prefeito', 'prefeito municipal', 'prefeita']) as $name) {
            $prefeitoCandidates[] = ['name' => $name, 'source' => $source];
        }

        foreach (mpg_extract_names_by_roles($text, ['vice-prefeito', 'vice prefeito', 'vice-prefeita', 'vice prefeita']) as $name) {
            $viceCandidates[] = ['name' => $name, 'source' => $source];
        }

        $party = mpg_extract_party($text);
        if ($party !== '') {
            $partyCandidates[] = ['party' => $party, 'source' => $source];
        }

        if ($address === '') {
            $address = mpg_extract_address($text, $municipioNome);
        }
        if ($phone === '') {
            $phone = mpg_find_phone($text);
        }
        if ($email === '') {
            $email = mpg_find_email($text);
        }
        if ($cep === '') {
            $cep = mpg_find_cep($text);
        }
        if ($mandato === '') {
            $mandato = mpg_extract_mandato($text);
        }
    }

    $prefeito = mpg_pick_cross_validated_name($prefeitoCandidates);
    $vice = mpg_pick_cross_validated_name($viceCandidates);
    $party = mpg_pick_cross_validated_party($partyCandidates);

    if (!mpg_is_valid_person_name($prefeito)) {
        return ['ok' => false, 'error' => 'Nome do prefeito não encontrado com segurança', 'sources' => $sources];
    }

    if (!mpg_is_valid_person_name($vice)) {
        return ['ok' => false, 'error' => 'Nome do vice-prefeito não encontrado com segurança', 'sources' => $sources];
    }

    if ($party === '') {
        return ['ok' => false, 'error' => 'Partido não encontrado com segurança', 'sources' => $sources];
    }

    if ($address === '') {
        return ['ok' => false, 'error' => 'Endereço da prefeitura não encontrado', 'sources' => $sources];
    }

    $geo = mpg_geocode($address);
    if (!isset($geo['lat'], $geo['lng'])) {
        return ['ok' => false, 'error' => 'Latitude/longitude não localizadas', 'sources' => $sources];
    }

    $official = mpg_first_official_source($docs);
    if ($official === '') {
        return ['ok' => false, 'error' => 'Fonte oficial não identificada', 'sources' => $sources];
    }

    $photoUrl = mpg_extract_official_photo_url($docs);

    return [
        'ok' => true,
        'data' => [
            'municipio_nome' => $municipioNome,
            'municipio_codigo' => $municipioCodigo,
            'prefeito_nome' => $prefeito,
            'vice_nome' => $vice,
            'partido' => $party,
            'telefone' => $phone,
            'email' => $email,
            'endereco_prefeitura' => $address,
            'cep' => $cep,
            'latitude' => (float) $geo['lat'],
            'longitude' => (float) $geo['lng'],
            'site_oficial' => mpg_discover_prefeitura_url($municipioNome),
            'fonte_primaria' => $official,
            'fontes_json' => wp_json_encode($sources),
            'foto_url' => $photoUrl,
            'mandato' => $mandato,
        ],
        'sources' => $sources,
    ];
}

function mpg_collect_documents(string $municipioNome): array
{
    $slug = sanitize_title($municipioNome);
    $sources = [
        ['type' => 'prefeitura', 'url' => mpg_discover_prefeitura_url($municipioNome), 'official' => true],
        ['type' => 'ibge', 'url' => 'https://cidades.ibge.gov.br/brasil/go/' . $slug . '/panorama', 'official' => true],
        ['type' => 'goias', 'url' => 'https://goias.gov.br', 'official' => true],
    ];

    $docs = [];
    foreach ($sources as $source) {
        $url = (string) ($source['url'] ?? '');
        if ($url === '') {
            continue;
        }
        $html = mpg_safe_get_html($url, (bool) ($source['official'] ?? false));
        if ($html === '') {
            continue;
        }

        $docs[] = [
            'type' => (string) $source['type'],
            'url' => $url,
            'official' => (bool) ($source['official'] ?? false),
            'html' => $html,
        ];
    }

    return $docs;
}

function mpg_discover_prefeitura_url(string $municipioNome): string
{
    $slug = sanitize_title($municipioNome);
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
            'user-agent' => 'MapaPoliticoGoias/1.0',
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

function mpg_safe_get_html(string $url, bool $mustBeOfficial): string
{
    $host = (string) wp_parse_url($url, PHP_URL_HOST);
    if ($host === '') {
        return '';
    }

    if ($mustBeOfficial && !mpg_is_official_host($host)) {
        return '';
    }

    $response = wp_remote_get($url, [
        'timeout' => 18,
        'redirection' => 4,
        'user-agent' => 'MapaPoliticoGoias/1.0',
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

function mpg_is_official_host(string $host): bool
{
    return str_ends_with($host, '.gov.br') || str_ends_with($host, '.ibge.gov.br') || $host === 'cidades.ibge.gov.br';
}

function mpg_extract_names_by_roles(string $text, array $roles): array
{
    $results = [];
    foreach ($roles as $role) {
        $p1 = "/(?:" . preg_quote($role, '/') . ")\\s*[:\\-–]\\s*([A-ZÁÉÍÓÚÂÊÔÃÕÇ][A-Za-zÁÉÍÓÚÂÊÔÃÕÇ'\\s]{5,120})/iu";
        if (preg_match_all($p1, $text, $m1)) {
            foreach ($m1[1] as $candidate) {
                $name = mpg_normalize_person_name((string) $candidate);
                if (mpg_is_valid_person_name($name)) {
                    $results[] = $name;
                }
            }
        }
    }

    return array_values(array_unique($results));
}

function mpg_pick_cross_validated_name(array $candidates): string
{
    $votes = [];
    foreach ($candidates as $c) {
        $name = (string) ($c['name'] ?? '');
        $source = (string) ($c['source'] ?? '');
        if ($name === '' || $source === '') {
            continue;
        }
        $votes[$name][$source] = true;
    }

    $best = '';
    $count = 0;
    foreach ($votes as $name => $sources) {
        $c = count($sources);
        if ($c > $count) {
            $best = $name;
            $count = $c;
        }
    }

    return $count >= 2 ? $best : '';
}

function mpg_extract_party(string $text): string
{
    if (preg_match('/\b(MDB|PL|PSDB|PT|PSD|PP|PSB|REPUBLICANOS|PODEMOS|PDT|PV|CIDADANIA|AVANTE|UNI[ÃA]O BRASIL|SOLIDARIEDADE)\b/iu', $text, $m)) {
        return mpg_normalize_party((string) $m[1]);
    }

    return '';
}

function mpg_pick_cross_validated_party(array $candidates): string
{
    $votes = [];
    foreach ($candidates as $c) {
        $party = mpg_normalize_party((string) ($c['party'] ?? ''));
        $source = (string) ($c['source'] ?? '');
        if ($party === '' || $source === '') {
            continue;
        }
        $votes[$party][$source] = true;
    }

    $best = '';
    $count = 0;
    foreach ($votes as $party => $sources) {
        $c = count($sources);
        if ($c > $count) {
            $best = $party;
            $count = $c;
        }
    }

    return $count >= 2 ? $best : '';
}

function mpg_extract_address(string $text, string $municipioNome): string
{
    $patterns = [
        '/(?:endere[cç]o|sede)\s*[:\-]\s*([^\n\r]{12,190})/iu',
        '/(Rua\s+[^\n\r]{8,180})/iu',
        '/(Avenida\s+[^\n\r]{8,180})/iu',
        '/(Pra[cç]a\s+[^\n\r]{8,180})/iu',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $text, $m)) {
            $raw = sanitize_text_field(trim((string) ($m[1] ?? '')));
            if ($raw !== '') {
                return $raw . ', ' . $municipioNome . ', Goiás, Brasil';
            }
        }
    }

    return '';
}

function mpg_extract_mandato(string $text): string
{
    if (preg_match('/(20\d{2}\s*[\-–/]\s*20\d{2})/u', $text, $m)) {
        return sanitize_text_field((string) $m[1]);
    }

    return '';
}

function mpg_find_email(string $text): string
{
    if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu', $text, $m)) {
        return sanitize_email((string) $m[0]);
    }

    return '';
}

function mpg_find_phone(string $text): string
{
    if (preg_match('/(?:\+?55\s*)?(?:\(?\d{2}\)?\s*)?(?:9?\d{4})[-\s]?\d{4}/', $text, $m)) {
        $digits = preg_replace('/[^0-9]/', '', (string) $m[0]);
        return $digits !== '' ? '+' . $digits : '';
    }

    return '';
}

function mpg_find_cep(string $text): string
{
    if (preg_match('/\b\d{5}-?\d{3}\b/', $text, $m)) {
        return sanitize_text_field((string) $m[0]);
    }

    return '';
}

function mpg_geocode(string $query): array
{
    $url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=' . rawurlencode($query);
    $response = wp_remote_get($url, [
        'timeout' => 20,
        'user-agent' => 'MapaPoliticoGoias/1.0',
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

function mpg_extract_official_photo_url(array $docs): string
{
    foreach ($docs as $doc) {
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

function mpg_first_official_source(array $docs): string
{
    foreach ($docs as $doc) {
        if (!empty($doc['official']) && !empty($doc['url'])) {
            return (string) $doc['url'];
        }
    }

    return '';
}

function mpg_normalize_text(string $text): string
{
    return trim((string) preg_replace('/\s+/u', ' ', $text));
}

function mpg_normalize_person_name(string $name): string
{
    $name = mpg_normalize_text(sanitize_text_field($name));
    if ($name === '') {
        return '';
    }

    return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
}

function mpg_is_valid_person_name(string $name): bool
{
    $name = mpg_normalize_text($name);
    if ($name === '' || mb_strlen($name) < 7 || preg_match('/\s/u', $name) !== 1) {
        return false;
    }

    $blocked = ['prefeito', 'prefeito municipal', 'vice-prefeito', 'vice prefeito', 'gabinete do prefeito'];
    $lower = mb_strtolower($name, 'UTF-8');
    foreach ($blocked as $b) {
        if ($lower === $b || str_contains($lower, $b)) {
            return false;
        }
    }

    return true;
}

function mpg_normalize_party(string $party): string
{
    $party = strtoupper(mpg_normalize_text(sanitize_text_field($party)));
    $party = str_replace(['UNIAO BRASIL', 'UNIÃO BRASIL'], 'UNIÃO BRASIL', $party);

    $valid = ['MDB', 'PL', 'PSDB', 'PT', 'PSD', 'PP', 'PSB', 'REPUBLICANOS', 'PODEMOS', 'PDT', 'PV', 'CIDADANIA', 'AVANTE', 'UNIÃO BRASIL', 'SOLIDARIEDADE'];
    return in_array($party, $valid, true) ? $party : '';
}
