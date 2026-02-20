<?php
/**
 * Plugin Name: Cata Treco
 * Description: Gestão municipal de coleta de resíduos volumosos.
 * Version: 1.0.0
 */

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\WordPress\CataTrecoPlugin;

if (function_exists('register_activation_hook')) {
    register_activation_hook(__FILE__, [CataTrecoPlugin::class, 'activate']);
}

CataTrecoPlugin::init();
