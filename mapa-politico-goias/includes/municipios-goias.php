<?php

if (!defined('ABSPATH')) {
    exit;
}

function mpg_get_municipios_goias(): array
{
    $url = 'https://servicodados.ibge.gov.br/api/v1/localidades/estados/52/municipios';
    $response = wp_remote_get($url, [
        'timeout' => 25,
        'user-agent' => 'MapaPoliticoGoias/1.0',
    ]);

    if (is_wp_error($response)) {
        return [];
    }

    $status = (int) wp_remote_retrieve_response_code($response);
    if ($status < 200 || $status >= 300) {
        return [];
    }

    $body = json_decode((string) wp_remote_retrieve_body($response), true);
    if (!is_array($body)) {
        return [];
    }

    $out = [];
    foreach ($body as $item) {
        $nome = sanitize_text_field((string) ($item['nome'] ?? ''));
        $codigo = sanitize_text_field((string) ($item['id'] ?? ''));
        if ($nome === '' || $codigo === '') {
            continue;
        }

        $out[] = ['nome' => $nome, 'codigo' => $codigo];
    }

    return $out;
}
