<?php

if (!defined('ABSPATH')) {
    exit;
}

function mpg_log_event(string $type, string $municipio, string $etapa, string $motivo, array $sources = [], array $data = []): void
{
    $entry = [
        'type' => sanitize_text_field($type),
        'municipio' => sanitize_text_field($municipio),
        'etapa' => sanitize_text_field($etapa),
        'motivo' => sanitize_text_field($motivo),
        'fontes' => sanitize_text_field(implode(' | ', array_map('strval', $sources))),
        'data' => $data,
        'quando' => current_time('mysql'),
    ];

    $logs = get_option('mpg_ai_logs', []);
    if (!is_array($logs)) {
        $logs = [];
    }

    $logs[] = $entry;
    if (count($logs) > 500) {
        $logs = array_slice($logs, -500);
    }

    update_option('mpg_ai_logs', $logs, false);
}
