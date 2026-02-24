<?php
/**
 * Plugin Name: Mapa Político Goiás
 * Description: Sincroniza prefeitos e vice-prefeitos de Goiás com fila, logs e mapa Leaflet/OSM.
 * Version: 1.1.0
 * Author: Mapa Político Goiás
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('MPG_VERSION', '1.1.0');
define('MPG_FILE', __FILE__);
define('MPG_PATH', plugin_dir_path(__FILE__));
define('MPG_URL', plugin_dir_url(__FILE__));

define('MPG_QUEUE_HOOK', 'mpg_process_queue_event');

require_once MPG_PATH . 'includes/database.php';
require_once MPG_PATH . 'includes/municipios-goias.php';
require_once MPG_PATH . 'includes/ia-coleta.php';
require_once MPG_PATH . 'includes/fila-sync.php';
require_once MPG_PATH . 'includes/manual-search.php';
require_once MPG_PATH . 'includes/manual-cadastro.php';

require_once MPG_PATH . 'admin/admin-menu.php';
require_once MPG_PATH . 'admin/admin-sync.php';
require_once MPG_PATH . 'admin/admin-logs.php';
require_once MPG_PATH . 'admin/admin-manual-search.php';
require_once MPG_PATH . 'admin/admin-cadastro.php';
require_once MPG_PATH . 'admin/admin-delete.php';

require_once MPG_PATH . 'public/map-shortcode.php';

register_activation_hook(__FILE__, static function (): void {
    mpg_db_activate();
    if (!wp_next_scheduled(MPG_QUEUE_HOOK)) {
        wp_schedule_event(time() + 120, 'mpg_five_minutes', MPG_QUEUE_HOOK);
    }
    update_option('mpg_version', MPG_VERSION);
});

register_deactivation_hook(__FILE__, static function (): void {
    $timestamp = wp_next_scheduled(MPG_QUEUE_HOOK);
    if ($timestamp) {
        wp_unschedule_event($timestamp, MPG_QUEUE_HOOK);
    }
});

add_filter('cron_schedules', static function (array $schedules): array {
    if (!isset($schedules['mpg_five_minutes'])) {
        $schedules['mpg_five_minutes'] = [
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display' => 'A cada 5 minutos (Mapa Político Goiás)',
        ];
    }
    return $schedules;
});

add_action('plugins_loaded', static function (): void {
    $installed = get_option('mpg_version', '0.0.0');
    if (version_compare((string) $installed, MPG_VERSION, '<')) {
        mpg_db_activate();
        update_option('mpg_version', MPG_VERSION);
    }

    mpg_admin_menu_init();
    mpg_admin_sync_init();
    mpg_admin_logs_init();
    mpg_admin_manual_search_init();
    mpg_admin_cadastro_init();
    mpg_admin_delete_init();
    mpg_public_shortcode_init();
});

add_action(MPG_QUEUE_HOOK, static function (): void {
    mpg_queue_process_next();
    mpg_manual_process_next();
});
