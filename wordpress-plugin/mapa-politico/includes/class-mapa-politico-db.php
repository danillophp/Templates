<?php

if (!defined('ABSPATH')) {
    exit;
}

class MapaPoliticoDB
{
    public static function activate(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charsetCollate = $wpdb->get_charset_collate();
        $locationsTable = $wpdb->prefix . 'mapa_politico_locations';
        $politiciansTable = $wpdb->prefix . 'mapa_politico_politicians';

        $sqlLocations = "CREATE TABLE {$locationsTable} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            city VARCHAR(120) NOT NULL,
            state VARCHAR(10) NOT NULL,
            postal_code VARCHAR(20) NULL,
            address VARCHAR(255) NULL,
            latitude DECIMAL(10,7) NOT NULL,
            longitude DECIMAL(10,7) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_city (city)
        ) {$charsetCollate};";

        $sqlPoliticians = "CREATE TABLE {$politiciansTable} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            location_id BIGINT UNSIGNED NOT NULL,
            full_name VARCHAR(180) NOT NULL,
            position VARCHAR(120) NOT NULL,
            party VARCHAR(100) NULL,
            age TINYINT UNSIGNED NULL,
            biography TEXT NULL,
            career_history TEXT NULL,
            phone VARCHAR(30) NULL,
            email VARCHAR(190) NULL,
            photo_id BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_location_id (location_id),
            KEY idx_full_name (full_name)
        ) {$charsetCollate};";

        dbDelta($sqlLocations);
        dbDelta($sqlPoliticians);
    }
}
