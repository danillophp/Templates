<?php
/**
 * Plugin Name: Mapa Político Mundial
 * Description: Plugin WordPress para mapa mundial com cadastro de localizações e figuras políticas.
 * Version: 1.0.0
 * Author: Equipe Mapa Político
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('MAPA_POLITICO_VERSION', '1.0.0');
define('MAPA_POLITICO_FILE', __FILE__);
define('MAPA_POLITICO_PATH', plugin_dir_path(__FILE__));
define('MAPA_POLITICO_URL', plugin_dir_url(__FILE__));

require_once MAPA_POLITICO_PATH . 'includes/class-mapa-politico-db.php';
require_once MAPA_POLITICO_PATH . 'includes/class-mapa-politico-admin.php';
require_once MAPA_POLITICO_PATH . 'includes/class-mapa-politico-public.php';

register_activation_hook(__FILE__, ['MapaPoliticoDB', 'activate']);

add_action('plugins_loaded', static function (): void {
    MapaPoliticoAdmin::init();
    MapaPoliticoPublic::init();
});
