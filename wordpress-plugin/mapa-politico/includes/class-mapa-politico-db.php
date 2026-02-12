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
            name VARCHAR(150) NOT NULL,
            city VARCHAR(120) NOT NULL,
            state VARCHAR(120) NULL,
            address VARCHAR(255) NULL,
            postal_code VARCHAR(20) NULL,
            latitude DECIMAL(10,7) NOT NULL,
            longitude DECIMAL(10,7) NOT NULL,
            city_info TEXT NULL,
            region_info TEXT NULL,
            ibge_code VARCHAR(12) NULL,
            institution_type VARCHAR(30) NULL,
            source_url TEXT NULL,
            last_synced_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_city (city),
            KEY idx_postal_code (postal_code),
            KEY idx_ibge_code (ibge_code),
            KEY idx_institution_type (institution_type)
        ) {$charsetCollate};";

        $sqlPoliticians = "CREATE TABLE {$politiciansTable} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            location_id BIGINT UNSIGNED NOT NULL,
            full_name VARCHAR(180) NOT NULL,
            position VARCHAR(120) NOT NULL,
            party VARCHAR(100) NOT NULL,
            age TINYINT UNSIGNED NULL,
            biography TEXT NULL,
            career_history TEXT NULL,
            municipality_history TEXT NULL,
            photo_id BIGINT UNSIGNED NULL,
            phone VARCHAR(30) NULL,
            email VARCHAR(190) NULL,
            advisors VARCHAR(255) NULL,
            source_url TEXT NULL,
            source_name VARCHAR(190) NULL,
            data_status VARCHAR(40) NOT NULL DEFAULT 'completo',
            validation_notes TEXT NULL,
            is_auto TINYINT(1) NOT NULL DEFAULT 0,
            municipality_code VARCHAR(12) NULL,
            last_synced_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_location_id (location_id),
            KEY idx_full_name (full_name),
            KEY idx_party (party),
            KEY idx_data_status (data_status),
            KEY idx_is_auto (is_auto),
            KEY idx_municipality_code (municipality_code)
        ) {$charsetCollate};";

        dbDelta($sqlLocations);
        dbDelta($sqlPoliticians);
    }
}
