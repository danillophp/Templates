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

/**
 * Pesquisa textual para o botão "Pesquisar" ao lado do nome.
 * A resposta deve ser texto coeso (sem JSON/HTML) e conter blocos identificáveis.
 */
function mpg_ai_search_by_name(array $input): array
{
    $apiKey = mpg_get_openai_key();
    if ($apiKey === '') {
        return ['ok' => false, 'message' => 'API key da OpenAI não configurada.'];
    }

    $nome = sanitize_text_field((string) ($input['nome'] ?? ''));
    $cargo = sanitize_text_field((string) ($input['cargo'] ?? ''));
    $municipio = sanitize_text_field((string) ($input['municipio'] ?? ''));
    $estado = sanitize_text_field((string) ($input['estado'] ?? ''));

    if (!mpg_is_valid_person_name($nome)) {
        return ['ok' => false, 'message' => 'Informe um nome completo válido para pesquisar.'];
    }

    $prompt = "Você é analista de dados públicos para prefeitura.\n"
        . "Pesquise APENAS em fontes oficiais e confiáveis (gov.br, TSE, câmaras, prefeituras, ALE, Senado, Câmara dos Deputados).\n"
        . "Não invente dados. Se houver ambiguidade de nomes, diga claramente.\n"
        . "Responda SOMENTE em TEXTO PLANO (sem HTML e sem JSON), com os blocos exatamente abaixo:\n\n"
        . "STATUS: [OK|NAO_ENCONTRADO|AMBIGUO]\n"
        . "QUEM_E: ...\n"
        . "IDADE: ...\n"
        . "PARTIDO_ATUAL: ...\n"
        . "CIDADE_REPRESENTA: ...\n"
        . "HISTORIA_DE_VIDA: ...\n"
        . "HISTORICO_POLITICO: ...\n"
        . "TELEFONE: ...\n"
        . "FOTO_URL: ...\n"
        . "FONTES: ...\n\n"
        . "Entrada do usuário:\n"
        . "Nome: {$nome}\nCargo: {$cargo}\nMunicípio: {$municipio}\nEstado: {$estado}\n";

    $response = wp_remote_post('https://api.openai.com/v1/responses', [
        'timeout' => 20,
        'headers' => [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ],
        'body' => wp_json_encode([
            'model' => 'gpt-4.1-mini',
            'input' => $prompt,
            'max_output_tokens' => 1000,
        ]),
    ]);

    if (is_wp_error($response)) {
        return ['ok' => false, 'message' => 'Erro ao consultar IA: ' . $response->get_error_message()];
    }

    $statusCode = (int) wp_remote_retrieve_response_code($response);
    $body = (string) wp_remote_retrieve_body($response);
    if ($statusCode < 200 || $statusCode >= 300) {
        return ['ok' => false, 'message' => 'Falha de comunicação com IA (' . $statusCode . ').'];
    }

    $parsedResponse = json_decode($body, true);
    if (!is_array($parsedResponse)) {
        return ['ok' => false, 'message' => 'Resposta inválida da IA.'];
    }

    $text = '';
    if (!empty($parsedResponse['output'][0]['content'][0]['text'])) {
        $text = (string) $parsedResponse['output'][0]['content'][0]['text'];
    } elseif (!empty($parsedResponse['output_text'])) {
        $text = (string) $parsedResponse['output_text'];
    }

    if ($text === '') {
        return ['ok' => false, 'message' => 'A IA não retornou conteúdo.'];
    }

    $parsed = mpg_parse_ai_text_blocks($text);
    $status = strtoupper((string) ($parsed['status'] ?? ''));

    if ($status === 'NAO_ENCONTRADO') {
        return ['ok' => false, 'message' => 'Político não encontrado. Informe mais dados (cargo/cidade/estado).'];
    }

    if ($status === 'AMBIGUO') {
        return ['ok' => false, 'message' => 'Nome ambíguo. Informe cargo, município e estado para refinar a pesquisa.'];
    }

    $historico = trim((string) (
        ($parsed['quem_e'] ?? '') . "\n\n"
        . 'Idade: ' . ($parsed['idade'] ?? 'Não informado') . "\n"
        . 'Partido atual: ' . ($parsed['partido_atual'] ?? 'Não informado') . "\n"
        . 'Cidade que representa: ' . ($parsed['cidade_representa'] ?? 'Não informado') . "\n\n"
        . 'História de vida: ' . ($parsed['historia_de_vida'] ?? 'Não informado') . "\n\n"
        . 'Histórico político: ' . ($parsed['historico_politico'] ?? 'Não informado')
    ));

    return [
        'ok' => true,
        'data' => [
            'partido' => sanitize_text_field((string) ($parsed['partido_atual'] ?? '')),
            'idade' => sanitize_text_field((string) ($parsed['idade'] ?? '')),
            'cidade' => sanitize_text_field((string) ($parsed['cidade_representa'] ?? '')),
            'historico_politico' => sanitize_textarea_field($historico),
            'biografia_resumida' => sanitize_textarea_field((string) ($parsed['historia_de_vida'] ?? '')),
            'telefone' => sanitize_text_field((string) ($parsed['telefone'] ?? '')),
            'foto_url' => mpg_normalize_external_photo_url((string) ($parsed['foto_url'] ?? '')),
            'fontes' => [sanitize_text_field((string) ($parsed['fontes'] ?? ''))],
            'raw' => sanitize_textarea_field($text),
        ],
    ];
}

function mpg_parse_ai_text_blocks(string $text): array
{
    $map = [
        'status' => '/^STATUS:\s*(.+)$/mi',
        'quem_e' => '/^QUEM_E:\s*(.+)$/mi',
        'idade' => '/^IDADE:\s*(.+)$/mi',
        'partido_atual' => '/^PARTIDO_ATUAL:\s*(.+)$/mi',
        'cidade_representa' => '/^CIDADE_REPRESENTA:\s*(.+)$/mi',
        'historia_de_vida' => '/^HISTORIA_DE_VIDA:\s*(.+)$/mi',
        'historico_politico' => '/^HISTORICO_POLITICO:\s*(.+)$/mi',
        'telefone' => '/^TELEFONE:\s*(.+)$/mi',
        'foto_url' => '/^FOTO_URL:\s*(.+)$/mi',
        'fontes' => '/^FONTES:\s*(.+)$/mi',
    ];

    $out = [];
    foreach ($map as $key => $regex) {
        if (preg_match($regex, $text, $m)) {
            $out[$key] = trim((string) ($m[1] ?? ''));
        }
    }

    return $out;
}

function mpg_save_manual_politico(array $payload): array
{
    global $wpdb;
    $table = $wpdb->prefix . 'mpg_prefeitos';

    $nome = mpg_normalize_person_name((string) ($payload['nome_completo'] ?? ''));
    $cargo = sanitize_text_field((string) ($payload['cargo'] ?? ''));
    $cidade = sanitize_text_field((string) ($payload['cidade'] ?? ''));

    if (!mpg_is_valid_person_name($nome)) {
        return ['ok' => false, 'message' => 'Nome inválido para cadastro.'];
    }
    if ($cidade === '') {
        return ['ok' => false, 'message' => 'Cidade é obrigatória.'];
    }

    $fotoUrl = mpg_normalize_external_photo_url((string) ($payload['foto_url'] ?? ''));

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
        return ['ok' => false, 'message' => 'Latitude/longitude ausentes para salvar no mapa.'];
    }

    $record = [
        'municipio_nome' => $cidade,
        'municipio_codigo' => sanitize_text_field((string) ($payload['municipio_codigo'] ?? sanitize_title($cidade))),
        'prefeito_nome' => $nome,
        'vice_nome' => sanitize_text_field((string) ($payload['vice_nome'] ?? '')),
        'cargo' => $cargo !== '' ? $cargo : 'Prefeito',
        'estado' => sanitize_text_field((string) ($payload['estado'] ?? 'GO')),
        'partido' => mpg_normalize_party((string) ($payload['partido'] ?? '')),
        'idade' => sanitize_text_field((string) ($payload['idade'] ?? '')),
        'numero_votos' => sanitize_text_field((string) ($payload['numero_votos'] ?? '')),
        'telefone' => sanitize_text_field((string) ($payload['telefone'] ?? '')),
        'email' => sanitize_email((string) ($payload['email'] ?? '')),
        'endereco_prefeitura' => $address,
        'cep' => sanitize_text_field((string) ($payload['cep'] ?? '')),
        'latitude' => $lat,
        'longitude' => $lng,
        'site_oficial' => esc_url_raw((string) ($payload['site_oficial'] ?? '')),
        'fonte_primaria' => esc_url_raw((string) ($payload['fonte_primaria'] ?? '')),
        'fontes_json' => wp_json_encode(is_array($payload['fontes'] ?? null) ? $payload['fontes'] : []),
        'foto_url' => $fotoUrl,
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

function mpg_normalize_external_photo_url(string $url): string
{
    $url = esc_url_raw($url);
    if ($url === '') {
        return '';
    }

    $scheme = (string) wp_parse_url($url, PHP_URL_SCHEME);
    if ($scheme !== 'http' && $scheme !== 'https') {
        return '';
    }

    return $url;
}
