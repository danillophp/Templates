<?php

if (!defined('ABSPATH')) {
    exit;
}

class MapaPoliticoPublic
{
    public static function init(): void
    {
        add_shortcode('mapa_politico', [self::class, 'renderShortcode']);
        add_action('wp_enqueue_scripts', [self::class, 'registerAssets']);
        add_action('wp_ajax_mapa_politico_data', [self::class, 'ajaxData']);
        add_action('wp_ajax_nopriv_mapa_politico_data', [self::class, 'ajaxData']);
    }

    public static function registerAssets(): void
    {
        wp_register_style('mapa-politico-leaflet-css', 'https://unpkg.com/leaflet/dist/leaflet.css', [], '1.9.4');
        wp_register_script('mapa-politico-leaflet-js', 'https://unpkg.com/leaflet/dist/leaflet.js', [], '1.9.4', true);

        wp_register_style('mapa-politico-css', MAPA_POLITICO_URL . 'assets/css/mapa-politico.css', ['mapa-politico-leaflet-css'], MAPA_POLITICO_VERSION);
        wp_register_script('mapa-politico-js', MAPA_POLITICO_URL . 'assets/js/mapa-politico-public.js', ['mapa-politico-leaflet-js'], MAPA_POLITICO_VERSION, true);
    }

    public static function renderShortcode(): string
    {
        wp_enqueue_style('mapa-politico-css');
        wp_enqueue_script('mapa-politico-js');

        wp_localize_script('mapa-politico-js', 'MapaPoliticoConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mapa_politico_public_nonce'),
            'tilesUrl' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            'tilesAttribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        ]);

        ob_start();
        ?>
        <section class="mapa-politico-wrapper">
            <h2>Mapa Político</h2>
            <p>Pesquise por nome, partido, cidade ou CEP e clique no resultado para centralizar no mapa.</p>

            <div class="mapa-politico-filters">
                <input type="text" id="filtro-nome" placeholder="Pesquisar por nome do político">
                <input type="text" id="filtro-partido" placeholder="Pesquisar por partido">
                <input type="text" id="filtro-cidade" placeholder="Pesquisar por cidade">
                <input type="text" id="filtro-cep" placeholder="Pesquisar por CEP">
                <button type="button" id="filtro-limpar">Limpar</button>
            </div>

            <div class="mapa-politico-layout">
                <div id="mapa-politico-map"></div>
                <aside class="mapa-politico-results">
                    <h3>Resultados</h3>
                    <div id="mapa-politico-results-list"></div>
                </aside>
            </div>
        </section>

        <div id="mapa-politico-modal" aria-hidden="true">
            <div class="mapa-politico-modal-content">
                <button type="button" id="mapa-politico-close">×</button>
                <div id="mapa-politico-modal-body"></div>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    public static function ajaxData(): void
    {
        check_ajax_referer('mapa_politico_public_nonce', 'nonce');

        global $wpdb;
        $locationsTable = $wpdb->prefix . 'mapa_politico_locations';
        $politiciansTable = $wpdb->prefix . 'mapa_politico_politicians';

        $rows = $wpdb->get_results(
            "SELECT p.id AS politician_id, p.full_name, p.position, p.party, p.age, p.biography, p.career_history,
                    p.municipality_history, p.phone, p.email, p.advisors, p.photo_id,
                    l.id AS location_id, l.name AS location_name, l.city, l.state, l.postal_code, l.latitude, l.longitude, l.city_info, l.region_info
             FROM {$politiciansTable} p
             INNER JOIN {$locationsTable} l ON l.id = p.location_id
             ORDER BY p.full_name ASC",
            ARRAY_A
        );

        if ($rows === null) {
            error_log('[MapaPolitico] ajaxData error: ' . $wpdb->last_error);
            wp_send_json_error(['message' => 'Erro ao consultar dados do mapa.'], 500);
        }

        $entries = [];
        foreach ($rows as $row) {
            $entries[] = [
                'politician_id' => (int) $row['politician_id'],
                'full_name' => $row['full_name'],
                'position' => $row['position'],
                'party' => $row['party'],
                'age' => $row['age'],
                'biography' => $row['biography'],
                'career_history' => $row['career_history'],
                'municipality_history' => $row['municipality_history'],
                'phone' => $row['phone'],
                'email' => $row['email'],
                'advisors' => $row['advisors'],
                'photo_url' => !empty($row['photo_id']) ? wp_get_attachment_image_url((int) $row['photo_id'], 'medium') : null,
                'location' => [
                    'id' => (int) $row['location_id'],
                    'name' => $row['location_name'],
                    'city' => $row['city'],
                    'state' => $row['state'],
                    'postal_code' => $row['postal_code'],
                    'latitude' => (float) $row['latitude'],
                    'longitude' => (float) $row['longitude'],
                    'city_info' => $row['city_info'],
                    'region_info' => $row['region_info'],
                ],
            ];
        }

        wp_send_json_success(['entries' => $entries]);
    }
}
