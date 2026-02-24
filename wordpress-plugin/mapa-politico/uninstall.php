<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;
$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'mapa_politico_politicians');
$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'mapa_politico_locations');
$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'mapa_politico_sync_queue');
delete_option('mapa_politico_schema_version');
delete_option('mapa_politico_ai_last_sync');
delete_option('mapa_politico_ai_sync_logs');
delete_option('mapa_politico_deletion_logs');
