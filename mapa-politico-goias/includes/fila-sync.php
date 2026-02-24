<?php

if (!defined('ABSPATH')) {
    exit;
}

function mpg_queue_enqueue_all(): array
{
    $municipios = mpg_get_municipios_goias();
    $enqueued = 0;

    foreach ($municipios as $m) {
        if (mpg_queue_enqueue_one((string) $m['nome'], (string) $m['codigo'])) {
            $enqueued++;
        }
    }

    return ['enqueued' => $enqueued];
}

function mpg_queue_enqueue_one(string $municipioNome, string $municipioCodigo): bool
{
    global $wpdb;
    $table = $wpdb->prefix . 'mpg_fila_sync';

    $municipioNome = sanitize_text_field($municipioNome);
    $municipioCodigo = sanitize_text_field($municipioCodigo);
    if ($municipioNome === '' || $municipioCodigo === '') {
        return false;
    }

    $exists = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE municipio_codigo = %s LIMIT 1", $municipioCodigo));
    if ($exists > 0) {
        return false;
    }

    $ok = $wpdb->insert($table, [
        'municipio_nome' => $municipioNome,
        'municipio_codigo' => $municipioCodigo,
        'status' => 'pendente',
    ]);

    return $ok !== false;
}

function mpg_queue_enqueue_by_search(string $name): array
{
    $name = sanitize_text_field($name);
    if ($name === '') {
        return ['ok' => false, 'message' => 'Município inválido'];
    }

    $mun = mpg_get_municipios_goias();
    foreach ($mun as $m) {
        if (mb_strtolower((string) $m['nome'], 'UTF-8') !== mb_strtolower($name, 'UTF-8')) {
            continue;
        }

        $ok = mpg_queue_enqueue_one((string) $m['nome'], (string) $m['codigo']);
        return ['ok' => $ok, 'message' => $ok ? 'Município enfileirado' : 'Município já estava na fila'];
    }

    return ['ok' => false, 'message' => 'Município não encontrado em Goiás'];
}

function mpg_queue_process_next(): array
{
    global $wpdb;
    $fila = $wpdb->prefix . 'mpg_fila_sync';
    $prefeitos = $wpdb->prefix . 'mpg_prefeitos';

    $item = $wpdb->get_row("SELECT * FROM {$fila} WHERE status IN ('pendente','erro') ORDER BY id ASC LIMIT 1", ARRAY_A);
    if (!$item) {
        return ['processed' => 0, 'message' => 'Fila vazia'];
    }

    $id = (int) $item['id'];
    $municipioNome = sanitize_text_field((string) $item['municipio_nome']);
    $municipioCodigo = sanitize_text_field((string) $item['municipio_codigo']);

    $wpdb->update($fila, [
        'status' => 'processando',
        'tentativas' => (int) $item['tentativas'] + 1,
        'iniciado_em' => current_time('mysql'),
    ], ['id' => $id]);

    $result = mpg_ia_collect_municipio($municipioNome, $municipioCodigo);
    if (!$result['ok']) {
        $wpdb->update($fila, [
            'status' => 'erro',
            'ultimo_erro' => sanitize_text_field((string) ($result['error'] ?? 'Erro desconhecido')),
            'ultima_fonte' => sanitize_text_field(implode(' | ', (array) ($result['sources'] ?? []))),
            'finalizado_em' => current_time('mysql'),
        ], ['id' => $id]);

        mpg_log_event('erro', $municipioNome, 'coleta_ia', (string) ($result['error'] ?? 'Erro IA'), (array) ($result['sources'] ?? []));
        return ['processed' => 1, 'message' => 'Erro no município'];
    }

    $data = (array) ($result['data'] ?? []);
    $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$prefeitos} WHERE municipio_codigo = %s LIMIT 1", $municipioCodigo));

    if ($existing) {
        $wpdb->update($prefeitos, $data, ['id' => (int) $existing]);
    } else {
        $wpdb->insert($prefeitos, $data);
    }

    $wpdb->update($fila, [
        'status' => 'concluido',
        'ultimo_erro' => null,
        'ultima_fonte' => sanitize_text_field(implode(' | ', (array) ($result['sources'] ?? []))),
        'finalizado_em' => current_time('mysql'),
    ], ['id' => $id]);

    mpg_log_event('sucesso', $municipioNome, 'coleta_ia', 'Cadastro atualizado com sucesso', (array) ($result['sources'] ?? []), [
        'prefeito' => (string) ($data['prefeito_nome'] ?? ''),
        'vice' => (string) ($data['vice_nome'] ?? ''),
        'partido' => (string) ($data['partido'] ?? ''),
    ]);

    update_option('mpg_last_sync', current_time('mysql'));

    return ['processed' => 1, 'message' => 'Município processado'];
}

function mpg_queue_rows(int $limit = 200): array
{
    global $wpdb;
    $table = $wpdb->prefix . 'mpg_fila_sync';
    $limit = max(1, min(1000, $limit));
    $sql = $wpdb->prepare("SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", $limit);
    $rows = $wpdb->get_results($sql, ARRAY_A);
    return is_array($rows) ? $rows : [];
}

function mpg_log_event(string $type, string $municipio, string $etapa, string $motivo, array $sources, array $data = []): void
{
    $logs = get_option('mpg_ai_logs', []);
    if (!is_array($logs)) {
        $logs = [];
    }

    $logs[] = [
        'type' => sanitize_text_field($type),
        'municipio' => sanitize_text_field($municipio),
        'etapa' => sanitize_text_field($etapa),
        'motivo' => sanitize_text_field($motivo),
        'fontes' => implode(' | ', array_map('sanitize_text_field', $sources)),
        'dados' => $data,
        'quando' => current_time('mysql'),
    ];

    if (count($logs) > 500) {
        $logs = array_slice($logs, -500);
    }

    update_option('mpg_ai_logs', $logs, false);
}
