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
        wp_register_style('mapa-politico-css', MAPA_POLITICO_URL . 'assets/css/mapa-politico.css', [], MAPA_POLITICO_VERSION);
        wp_register_script('mapa-politico-js', MAPA_POLITICO_URL . 'assets/js/mapa-politico-public.js', [], MAPA_POLITICO_VERSION, true);
    }

    public static function renderShortcode(): string
    {
        wp_enqueue_style('mapa-politico-css');
        wp_enqueue_script('mapa-politico-js');

        wp_localize_script('mapa-politico-js', 'MapaPoliticoConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mapa_politico_public_nonce'),
            'googleMapsApiKey' => get_option('mapa_politico_google_maps_api_key', ''),
        ]);

        ob_start();
        ?>
        <section class="mapa-politico-wrapper">
            <h2>Mapa mundial de representantes políticos</h2>
            <p>Clique na marcação para visualizar os detalhes da localidade e das figuras políticas.</p>
            <div id="mapa-politico-map"></div>
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
            "SELECT l.id AS location_id, l.name AS location_name, l.latitude, l.longitude, l.city_info, l.region_info,
                    p.id AS politician_id, p.full_name, p.position, p.party, p.age, p.biography, p.career_history,
                    p.municipality_history, p.phone, p.email, p.advisors, p.photo_id
             FROM {$locationsTable} l
             LEFT JOIN {$politiciansTable} p ON p.location_id = l.id
             ORDER BY l.name ASC, p.full_name ASC",
            ARRAY_A
        );

        $grouped = [];
        foreach ($rows as $row) {
            $locationId = (int) $row['location_id'];
            if (!isset($grouped[$locationId])) {
                $grouped[$locationId] = [
                    'location_id' => $locationId,
                    'location_name' => $row['location_name'],
                    'latitude' => (float) $row['latitude'],
                    'longitude' => (float) $row['longitude'],
                    'city_info' => $row['city_info'],
                    'region_info' => $row['region_info'],
                    'politicians' => [],
                ];
            }

            if (!empty($row['politician_id'])) {
                $grouped[$locationId]['politicians'][] = [
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
                ];
            }
        }

        wp_send_json_success(['locations' => array_values($grouped)]);
    }
}
