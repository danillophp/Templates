<?php

if (!defined('ABSPATH')) {
    exit;
}

function mpg_get_openai_key(): string
{
    if (defined('MPG_OPENAI_API_KEY') && MPG_OPENAI_API_KEY) {
        return (string) MPG_OPENAI_API_KEY;
    }

    $opt = get_option('mpg_openai_api_key', '');
    return is_string($opt) ? trim($opt) : '';
}

function mpg_ai_search_descriptions(array $input): array
{
    $apiKey = mpg_get_openai_key();
    if ($apiKey === '') {
        return ['ok' => false, 'message' => 'API key da OpenAI não configurada.'];
    }

    $nome = sanitize_text_field((string) ($input['nome'] ?? ''));
    $cargo = sanitize_text_field((string) ($input['cargo'] ?? ''));
    $municipio = sanitize_text_field((string) ($input['municipio'] ?? ''));

    if (!mpg_is_valid_person_name($nome)) {
        return ['ok' => false, 'message' => 'Informe nome completo válido para pesquisar.'];
    }

    $prompt = "Você é analista de dados públicos de prefeitura.\n"
        . "Use fontes oficiais e confiáveis. Não invente dados.\n"
        . "Responda APENAS TEXTO PLANO (sem HTML e sem JSON), com os blocos:\n"
        . "STATUS: [OK|NAO_ENCONTRADO|AMBIGUO]\n"
        . "BIOGRAFIA: ...\n"
        . "HISTORICO_POLITICO: ...\n"
        . "FONTES: ...\n\n"
        . "Entrada: Nome={$nome}; Cargo={$cargo}; Município={$municipio}.";

    $response = wp_remote_post('https://api.openai.com/v1/responses', [
        'timeout' => 20,
        'headers' => [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ],
        'body' => wp_json_encode([
            'model' => 'gpt-4.1-mini',
            'input' => $prompt,
            'max_output_tokens' => 900,
        ]),
    ]);

    if (is_wp_error($response)) {
        return ['ok' => false, 'message' => 'Erro na IA: ' . $response->get_error_message()];
    }

    $statusCode = (int) wp_remote_retrieve_response_code($response);
    if ($statusCode < 200 || $statusCode >= 300) {
        return ['ok' => false, 'message' => 'Falha na IA (' . $statusCode . ').'];
    }

    $parsed = json_decode((string) wp_remote_retrieve_body($response), true);
    if (!is_array($parsed)) {
        return ['ok' => false, 'message' => 'Resposta inválida da IA.'];
    }

    $text = '';
    if (!empty($parsed['output'][0]['content'][0]['text'])) {
        $text = (string) $parsed['output'][0]['content'][0]['text'];
    } elseif (!empty($parsed['output_text'])) {
        $text = (string) $parsed['output_text'];
    }

    if ($text === '') {
        return ['ok' => false, 'message' => 'A IA não retornou conteúdo.'];
    }

    $blocks = mpg_parse_ai_description_blocks($text);
    $status = strtoupper((string) ($blocks['status'] ?? ''));
    if ($status === 'NAO_ENCONTRADO') {
        return ['ok' => false, 'message' => 'Nome não encontrado. Informe mais contexto.'];
    }
    if ($status === 'AMBIGUO') {
        return ['ok' => false, 'message' => 'Nome ambíguo. Informe cargo e município.'];
    }

    return [
        'ok' => true,
        'data' => [
            'biografia_resumida' => sanitize_textarea_field((string) ($blocks['biografia'] ?? '')),
            'historico_politico' => sanitize_textarea_field((string) ($blocks['historico_politico'] ?? '')),
            'fontes' => [sanitize_text_field((string) ($blocks['fontes'] ?? ''))],
        ],
    ];
}

function mpg_parse_ai_description_blocks(string $text): array
{
    $map = [
        'status' => '/^STATUS:\s*(.+)$/mi',
        'biografia' => '/^BIOGRAFIA:\s*(.+)$/mi',
        'historico_politico' => '/^HISTORICO_POLITICO:\s*(.+)$/mi',
        'fontes' => '/^FONTES:\s*(.+)$/mi',
    ];

    $out = [];
    foreach ($map as $k => $regex) {
        if (preg_match($regex, $text, $m)) {
            $out[$k] = trim((string) ($m[1] ?? ''));
        }
    }

    return $out;
}

function mpg_save_manual_politico(array $payload): array
{
    global $wpdb;
    $table = $wpdb->prefix . 'mpg_prefeitos';

    $nome = mpg_normalize_person_name((string) ($payload['nome_completo'] ?? ''));
    $cidade = sanitize_text_field((string) ($payload['cidade'] ?? ''));
    $cargo = sanitize_text_field((string) ($payload['cargo'] ?? ''));

    if (!mpg_is_valid_person_name($nome)) {
        return ['ok' => false, 'message' => 'Nome inválido para cadastro.'];
    }
    if ($cidade === '') {
        return ['ok' => false, 'message' => 'Município é obrigatório.'];
    }

    $ruaQuadra = sanitize_text_field((string) ($payload['rua_quadra'] ?? ''));
    $lote = sanitize_text_field((string) ($payload['lote'] ?? ''));
    $address = trim($ruaQuadra . ($lote !== '' ? ' - Lote ' . $lote : ''));

    $lat = is_numeric($payload['latitude'] ?? null) ? (float) $payload['latitude'] : null;
    $lng = is_numeric($payload['longitude'] ?? null) ? (float) $payload['longitude'] : null;
    if (($lat === null || $lng === null) && $address !== '') {
        $geo = mpg_geocode($address . ', ' . $cidade);
        if (isset($geo['lat'], $geo['lng'])) {
            $lat = (float) $geo['lat'];
            $lng = (float) $geo['lng'];
        }
    }

    if ($lat === null || $lng === null) {
        return ['ok' => false, 'message' => 'Latitude/longitude obrigatórias.'];
    }

    $record = [
        'municipio_nome' => $cidade,
        'municipio_codigo' => sanitize_text_field((string) ($payload['municipio_codigo'] ?? sanitize_title($cidade))),
        'prefeito_nome' => $nome,
        'vice_nome' => sanitize_text_field((string) ($payload['vice_nome'] ?? '')),
        'cargo' => $cargo,
        'estado' => sanitize_text_field((string) ($payload['estado'] ?? 'GO')),
        'partido' => sanitize_text_field((string) ($payload['partido'] ?? '')),
        'idade' => sanitize_text_field((string) ($payload['idade'] ?? '')),
        'telefone' => sanitize_text_field((string) ($payload['telefone'] ?? '')),
        'email' => sanitize_email((string) ($payload['email'] ?? '')),
        'endereco_prefeitura' => $address,
        'cep' => sanitize_text_field((string) ($payload['cep'] ?? '')),
        'latitude' => $lat,
        'longitude' => $lng,
        'site_oficial' => esc_url_raw((string) ($payload['site_oficial'] ?? '')),
        'fonte_primaria' => 'https://api.openai.com',
        'fontes_json' => wp_json_encode(is_array($payload['fontes'] ?? null) ? $payload['fontes'] : []),
        'foto_attachment_id' => isset($payload['foto_attachment_id']) ? (int) $payload['foto_attachment_id'] : null,
        'biografia_resumida' => sanitize_textarea_field((string) ($payload['biografia_resumida'] ?? '')),
        'historico_politico' => sanitize_textarea_field((string) ($payload['historico_politico'] ?? '')),
        'mandato' => sanitize_text_field((string) ($payload['mandato'] ?? '')),
    ];

    $existingId = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$table} WHERE prefeito_nome = %s AND cargo = %s AND municipio_nome = %s LIMIT 1",
        $record['prefeito_nome'],
        $record['cargo'],
        $record['municipio_nome']
    ));

    if ($existingId > 0) {
        $ok = $wpdb->update($table, $record, ['id' => $existingId]);
        if ($ok === false) {
            return ['ok' => false, 'message' => 'Falha ao atualizar cadastro existente.'];
        }
        return ['ok' => true, 'message' => 'Cadastro atualizado com sucesso.', 'id' => $existingId];
    }

    $ok = $wpdb->insert($table, $record);
    if ($ok === false) {
        return ['ok' => false, 'message' => 'Falha ao criar cadastro.'];
    }

    return ['ok' => true, 'message' => 'Cadastro criado com sucesso.', 'id' => (int) $wpdb->insert_id];
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

    return true;
}

function mpg_geocode(string $query): array
{
    $url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . rawurlencode($query);

    $response = wp_remote_get($url, [
        'timeout' => 15,
        'headers' => ['User-Agent' => 'MapaPoliticoGoias/1.2'],
    ]);

    if (is_wp_error($response)) {
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
