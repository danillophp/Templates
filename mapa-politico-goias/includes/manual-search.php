<?php

if (!defined('ABSPATH')) {
    exit;
}

function mpg_manual_enqueue_query(string $query, int $userId): array
{
    global $wpdb;
    $table = $wpdb->prefix . 'mpg_manual_search_queue';

    $query = sanitize_textarea_field($query);
    if ($query === '') {
        return ['ok' => false, 'message' => 'Informe um texto para busca.'];
    }

    $ok = $wpdb->insert($table, [
        'user_id' => $userId > 0 ? $userId : null,
        'query_text' => $query,
        'status' => 'pendente',
    ]);

    if ($ok === false) {
        return ['ok' => false, 'message' => 'Falha ao enfileirar busca manual.'];
    }

    return ['ok' => true, 'queue_id' => (int) $wpdb->insert_id, 'message' => 'Busca enfileirada com sucesso.'];
}

function mpg_manual_process_next(): array
{
    global $wpdb;
    $queueTable = $wpdb->prefix . 'mpg_manual_search_queue';
    $prefeitosTable = $wpdb->prefix . 'mpg_prefeitos';

    $item = $wpdb->get_row("SELECT * FROM {$queueTable} WHERE status IN ('pendente','erro') ORDER BY id ASC LIMIT 1", ARRAY_A);
    if (!$item) {
        return ['processed' => 0, 'message' => 'Sem itens na fila manual'];
    }

    $id = (int) $item['id'];
    $query = sanitize_textarea_field((string) $item['query_text']);

    $wpdb->update($queueTable, [
        'status' => 'processando',
        'tentativas' => (int) $item['tentativas'] + 1,
        'iniciado_em' => current_time('mysql'),
    ], ['id' => $id]);

    try {
        $parsed = mpg_manual_parse_query($query);
        if ($parsed['city'] === '') {
            throw new RuntimeException('Cidade não identificada no texto livre.');
        }

        $city = $parsed['city'];
        $code = $parsed['code'];
        $role = $parsed['role'];
        $queryName = $parsed['name'];

        $docs = mpg_collect_documents($city);
        $sources = array_values(array_unique(array_map(static fn($d) => (string) ($d['url'] ?? ''), $docs)));
        if (empty($docs)) {
            throw new RuntimeException('Nenhuma fonte acessível para a cidade informada.');
        }

        $textJoined = mpg_normalize_text(implode(' ', array_map(static fn($d) => wp_strip_all_tags((string) ($d['html'] ?? '')), $docs)));

        $prefeitoCandidates = [];
        $viceCandidates = [];
        $partyCandidates = [];

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
        }

        $prefeito = mpg_pick_cross_validated_name($prefeitoCandidates);
        $vice = mpg_pick_cross_validated_name($viceCandidates);
        $party = mpg_pick_cross_validated_party($partyCandidates);

        $politicianName = $role === 'Vice-prefeito' ? $vice : $prefeito;
        if ($queryName !== '' && mpg_is_valid_person_name($queryName)) {
            if (!str_contains(mb_strtolower($textJoined, 'UTF-8'), mb_strtolower($queryName, 'UTF-8'))) {
                throw new RuntimeException('Nome informado não foi confirmado nas fontes analisadas.');
            }
            $politicianName = $queryName;
        }

        if (!mpg_is_valid_person_name($politicianName)) {
            throw new RuntimeException('Nome do político não encontrado com segurança.');
        }

        $address = mpg_extract_address($textJoined, $city);
        if ($address === '') {
            throw new RuntimeException('Endereço da prefeitura não localizado.');
        }

        $geo = mpg_geocode($address);
        if (!isset($geo['lat'], $geo['lng'])) {
            throw new RuntimeException('Latitude/longitude não localizadas.');
        }

        $phone = mpg_find_phone($textJoined);
        $email = mpg_find_email($textJoined);
        $cep = mpg_find_cep($textJoined);
        $bio = mb_substr($textJoined, 0, 600);
        $history = mb_substr($textJoined, 0, 1200);
        $official = mpg_first_official_source($docs);
        if ($official === '') {
            throw new RuntimeException('Fonte oficial não identificada.');
        }

        $photo = mpg_extract_official_photo_url($docs);

        // Duplicidade: Nome + Cidade
        $existingId = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$prefeitosTable} WHERE prefeito_nome = %s AND municipio_nome = %s LIMIT 1",
            $politicianName,
            $city
        ));

        $payload = [
            'municipio_nome' => $city,
            'municipio_codigo' => $code !== '' ? $code : sanitize_title($city),
            'prefeito_nome' => $politicianName,
            'vice_nome' => mpg_is_valid_person_name($vice) ? $vice : '',
            'cargo' => $role,
            'estado' => 'Goiás',
            'partido' => $party,
            'telefone' => $phone,
            'email' => $email,
            'endereco_prefeitura' => $address,
            'cep' => $cep,
            'latitude' => (float) $geo['lat'],
            'longitude' => (float) $geo['lng'],
            'site_oficial' => mpg_discover_prefeitura_url($city),
            'fonte_primaria' => $official,
            'fontes_json' => wp_json_encode($sources),
            'foto_url' => $photo,
            'biografia_resumida' => $bio,
            'historico_politico' => $history,
            'mandato' => mpg_extract_mandato($textJoined),
        ];

        if ($existingId > 0) {
            $ok = $wpdb->update($prefeitosTable, $payload, ['id' => $existingId]);
            if ($ok === false) {
                throw new RuntimeException('Falha ao atualizar cadastro existente.');
            }
            $savedId = $existingId;
        } else {
            $ok = $wpdb->insert($prefeitosTable, $payload);
            if ($ok === false) {
                throw new RuntimeException('Falha ao criar novo cadastro.');
            }
            $savedId = (int) $wpdb->insert_id;
        }

        $wpdb->update($queueTable, [
            'status' => 'concluido',
            'resultado_id' => $savedId,
            'ultimo_erro' => null,
            'finalizado_em' => current_time('mysql'),
        ], ['id' => $id]);

        mpg_log_event('sucesso', $city, 'busca_manual_ia', 'Político cadastrado via busca manual', $sources, [
            'query' => $query,
            'nome' => $politicianName,
            'cargo' => $role,
        ]);

        return ['processed' => 1, 'message' => 'Cadastrado com sucesso', 'record_id' => $savedId];
    } catch (Throwable $e) {
        $wpdb->update($queueTable, [
            'status' => 'erro',
            'ultimo_erro' => $e->getMessage(),
            'finalizado_em' => current_time('mysql'),
        ], ['id' => $id]);

        mpg_log_event('erro', 'N/A', 'busca_manual_ia', $e->getMessage(), [], ['query' => $query]);

        return ['processed' => 1, 'message' => 'Erro: ' . $e->getMessage()];
    }
}

function mpg_manual_parse_query(string $query): array
{
    $query = mpg_normalize_text($query);
    $municipios = mpg_get_municipios_goias();

    $city = '';
    $code = '';
    foreach ($municipios as $m) {
        $nome = (string) ($m['nome'] ?? '');
        if ($nome !== '' && str_contains(mb_strtolower($query, 'UTF-8'), mb_strtolower($nome, 'UTF-8'))) {
            $city = $nome;
            $code = (string) ($m['codigo'] ?? '');
            break;
        }
    }

    $role = 'Prefeito';
    $qLower = mb_strtolower($query, 'UTF-8');
    if (str_contains($qLower, 'vice')) {
        $role = 'Vice-prefeito';
    } elseif (str_contains($qLower, 'governador')) {
        $role = 'Governador';
    }

    $name = '';
    if (preg_match('/([A-ZÁÉÍÓÚÂÊÔÃÕÇ][A-Za-zÁÉÍÓÚÂÊÔÃÕÇ\s]{6,120})/u', $query, $m)) {
        $candidate = mpg_normalize_person_name((string) $m[1]);
        if (mpg_is_valid_person_name($candidate)) {
            $name = $candidate;
        }
    }

    return ['city' => $city, 'code' => $code, 'role' => $role, 'name' => $name];
}
