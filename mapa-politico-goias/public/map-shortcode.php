<?php

if (!defined('ABSPATH')) {
    exit;
}

function mpg_public_shortcode_init(): void
{
    add_shortcode('mapa_politico_goias', 'mpg_render_shortcode');
    add_action('wp_enqueue_scripts', 'mpg_register_public_assets');
    add_action('wp_ajax_mpg_map_data', 'mpg_ajax_map_data');
    add_action('wp_ajax_nopriv_mpg_map_data', 'mpg_ajax_map_data');
}

function mpg_register_public_assets(): void
{
    wp_register_style('mpg-leaflet-css', 'https://unpkg.com/leaflet/dist/leaflet.css', [], '1.9.4');
    wp_register_script('mpg-leaflet-js', 'https://unpkg.com/leaflet/dist/leaflet.js', [], '1.9.4', true);

    wp_register_style('mpg-map-css', MPG_URL . 'public/map-style.css', ['mpg-leaflet-css'], MPG_VERSION);
    wp_register_script('mpg-map-js', MPG_URL . 'public/leaflet-map.js', ['mpg-leaflet-js'], MPG_VERSION, true);
}

function mpg_render_shortcode(): string
{
    wp_enqueue_style('mpg-map-css');
    wp_enqueue_script('mpg-map-js');

    wp_localize_script('mpg-map-js', 'MPGMapConfig', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mpg_map_nonce'),
        'tilesUrl' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        'tilesAttribution' => '&copy; OpenStreetMap contributors',
        'goiasCenter' => ['lat' => -15.8270, 'lng' => -49.8362],
    ]);

    ob_start();
    ?>
    <section class="mpg-wrap">
        <h2>Mapa Político Goiás</h2>
        <div class="mpg-filters">
            <input id="mpg-q-name" type="text" placeholder="Nome do prefeito">
            <input id="mpg-q-party" type="text" placeholder="Partido">
            <input id="mpg-q-city" type="text" placeholder="Município">
            <input id="mpg-q-cep" type="text" placeholder="CEP">
            <button type="button" id="mpg-q-clear">Limpar</button>
        </div>
        <div class="mpg-layout">
            <div id="mpg-map"></div>
            <aside id="mpg-results"></aside>
        </div>
    </section>
    <?php
    return (string) ob_get_clean();
}

function mpg_ajax_map_data(): void
{
    check_ajax_referer('mpg_map_nonce', 'nonce');

    global $wpdb;
    $table = $wpdb->prefix . 'mpg_prefeitos';

    $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY prefeito_nome ASC", ARRAY_A);
    if ($rows === null) {
        wp_send_json_error(['message' => 'Erro ao buscar dados'], 500);
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
            'foto_url' => !empty($row['foto_url']) ? (string) $row['foto_url'] : null,
        ];
    }

    wp_send_json_success(['entries' => $entries]);
}
