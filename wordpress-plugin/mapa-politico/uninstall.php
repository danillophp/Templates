<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;
$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'mapa_politico_politicians');
$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'mapa_politico_locations');
delete_option('mapa_politico_schema_version');
