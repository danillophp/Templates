<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Core\Auth;
use App\Core\Router;

require_once __DIR__ . '/helpers/helpers.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $segments = explode('\\', $relativeClass);
    $segments[0] = strtolower($segments[0]);
    $file = $baseDir . implode('/', $segments) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

$config = require __DIR__ . '/config/config.php';

date_default_timezone_set($config['app']['timezone']);

session_name($config['session']['name']);
session_set_cookie_params([
    'lifetime' => $config['session']['lifetime'],
    'path' => '/',
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$router = new Router();

$router->get('/', static fn() => redirect('/login'));
$router->get('/login', [AuthController::class, 'showLogin'], [static fn() => Auth::requireGuest()]);
$router->post('/login', [AuthController::class, 'login'], [static fn() => Auth::requireGuest()]);
$router->post('/logout', [AuthController::class, 'logout'], [static fn() => Auth::requireAuth()]);
$router->get('/dashboard', [DashboardController::class, 'index'], [static fn() => Auth::requireAuth()]);

return $router;
