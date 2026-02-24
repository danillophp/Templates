<?php

if (!defined('ABSPATH')) {
    exit;
}

function mpg_public_shortcode_init(): void
{
    add_shortcode('mapa_politico_goias', 'mpg_render_map_shortcode');
    add_shortcode('mapa_politico', 'mpg_render_map_shortcode');
    add_action('wp_enqueue_scripts', 'mpg_public_enqueue_assets');
    add_action('wp_ajax_mpg_map_data', 'mpg_ajax_map_data');
    add_action('wp_ajax_nopriv_mpg_map_data', 'mpg_ajax_map_data');
}

function mpg_public_enqueue_assets(): void
{
    wp_register_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4');
    wp_register_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);

    wp_register_style('mpg-map-style', MPG_URL . 'public/map-style.css', ['leaflet'], MPG_VERSION);
    wp_register_script('mpg-map-script', MPG_URL . 'public/leaflet-map.js', ['leaflet'], MPG_VERSION, true);

    wp_localize_script('mpg-map-script', 'MPG_MAP', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mpg_map_nonce'),
    ]);
}

function mpg_render_map_shortcode(): string
{
    wp_enqueue_style('leaflet');
    wp_enqueue_style('mpg-map-style');
    wp_enqueue_script('leaflet');
    wp_enqueue_script('mpg-map-script');

    ob_start();
    ?>
    <div class="mpg-layout">
        <div id="mpg-map" style="height:560px;"></div>
    </div>
    <?php
    return (string) ob_get_clean();
}

function mpg_ajax_map_data(): void
{
    check_ajax_referer('mpg_map_nonce', 'nonce');

    global $wpdb;
    $table = $wpdb->prefix . 'mpg_prefeitos';
    $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY municipio_nome ASC, prefeito_nome ASC", ARRAY_A);

    if ($rows === null) {
        wp_send_json_error(['message' => 'Erro ao consultar dados do mapa.'], 500);
    }

    $entries = [];
    foreach ($rows as $row) {
        $entries[] = [
            'id' => (int) $row['id'],
            'prefeito_nome' => (string) $row['prefeito_nome'],
            'vice_nome' => (string) $row['vice_nome'],
            'partido' => (string) $row['partido'],
            'municipio_nome' => (string) $row['municipio_nome'],
            'cep' => (string) $row['cep'],
            'telefone' => (string) $row['telefone'],
            'email' => (string) $row['email'],
            'endereco_prefeitura' => (string) $row['endereco_prefeitura'],
            'latitude' => (float) $row['latitude'],
            'longitude' => (float) $row['longitude'],
            'site_oficial' => (string) $row['site_oficial'],
            'cargo' => (string) ($row['cargo'] ?? 'Prefeito'),
            'biografia_resumida' => (string) ($row['biografia_resumida'] ?? ''),
            'historico_politico' => (string) ($row['historico_politico'] ?? ''),
            'foto_url' => !empty($row['foto_attachment_id']) ? wp_get_attachment_image_url((int) $row['foto_attachment_id'], 'medium') : null,
        ];
    }

    wp_send_json_success(['entries' => $entries]);
}
