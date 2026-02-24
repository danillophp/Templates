<?php

if (!defined('ABSPATH')) {
    exit;
}

class MapaPoliticoPublic
{
    public static function init(): void
    {
        add_action('wp_enqueue_scripts', [self::class, 'registerAssets']);
        add_shortcode('mapa_politico', [self::class, 'renderShortcode']);
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
            'tilesAttribution' => '&copy; OpenStreetMap contributors',
        ]);

        ob_start();
        ?>
        <section class="mapa-politico-wrapper">
            <h2>Mapa Político</h2>
            <div class="mapa-politico-layout">
                <div id="mapa-politico-map"></div>
                <aside class="mapa-politico-results"><div id="mapa-politico-results-list"></div></aside>
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
            "SELECT p.id AS politician_id, p.full_name, p.position, p.party, p.biography, p.career_history,
                    p.phone, p.email, p.photo_id,
                    l.id AS location_id, l.city, l.state, l.postal_code, l.latitude, l.longitude, l.address
             FROM {$politiciansTable} p
             INNER JOIN {$locationsTable} l ON l.id = p.location_id
             ORDER BY p.full_name ASC",
            ARRAY_A
        );

        if ($rows === null) {
            wp_send_json_error(['message' => 'Erro ao consultar mapa.'], 500);
        }

        $entries = [];
        foreach ($rows as $row) {
            $entries[] = [
                'politician_id' => (int) $row['politician_id'],
                'full_name' => (string) $row['full_name'],
                'position' => (string) $row['position'],
                'party' => (string) $row['party'],
                'biography' => (string) $row['biography'],
                'career_history' => (string) $row['career_history'],
                'phone' => (string) $row['phone'],
                'email' => (string) $row['email'],
                'photo_url' => !empty($row['photo_id']) ? wp_get_attachment_image_url((int) $row['photo_id'], 'medium') : null,
                'location' => [
                    'id' => (int) $row['location_id'],
                    'city' => (string) $row['city'],
                    'state' => (string) $row['state'],
                    'postal_code' => (string) $row['postal_code'],
                    'latitude' => (float) $row['latitude'],
                    'longitude' => (float) $row['longitude'],
                    'address' => (string) $row['address'],
                ],
            ];
        }

        wp_send_json_success(['entries' => $entries]);
    }
}
