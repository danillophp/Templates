<?php
/**
 * Plugin Name: Mapa Político Goiás
 * Description: Cadastro manual de políticos com apoio de IA e mapa Leaflet/OSM.
 * Version: 1.2.0
 * Author: Mapa Político Goiás
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('MPG_VERSION', '1.2.0');
define('MPG_FILE', __FILE__);
define('MPG_PATH', plugin_dir_path(__FILE__));
define('MPG_URL', plugin_dir_url(__FILE__));

require_once MPG_PATH . 'includes/database.php';
require_once MPG_PATH . 'includes/logs.php';
require_once MPG_PATH . 'includes/manual-cadastro.php';

require_once MPG_PATH . 'admin/admin-menu.php';
require_once MPG_PATH . 'admin/admin-cadastro.php';
require_once MPG_PATH . 'admin/admin-logs.php';
require_once MPG_PATH . 'admin/admin-delete.php';

require_once MPG_PATH . 'public/map-shortcode.php';

register_activation_hook(__FILE__, static function (): void {
    mpg_db_activate();
    update_option('mpg_version', MPG_VERSION);
});

add_action('plugins_loaded', static function (): void {
    $installed = get_option('mpg_version', '0.0.0');
    if (version_compare((string) $installed, MPG_VERSION, '<')) {
        mpg_db_activate();
        update_option('mpg_version', MPG_VERSION);
    }

    mpg_admin_menu_init();
    mpg_admin_cadastro_init();
    mpg_admin_logs_init();
    mpg_admin_delete_init();
    mpg_public_shortcode_init();
});
