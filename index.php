<?php
session_start();

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $path = __DIR__ . '/app/' . str_replace('App\\', '', $class);
    $path = str_replace('\\', '/', $path) . '.php';
    if (file_exists($path)) require $path;
});

$app = require __DIR__ . '/config/app.php';
foreach ($app as $k => $v) $_ENV[$k] = $v;
date_default_timezone_set($app['TIMEZONE']);

\App\Core\ErrorHandler::register($app);

$router = new \App\Core\Router();
$router->get('/', [\App\Controllers\PublicController::class, 'home']);
$router->post('/solicitar', [\App\Controllers\PublicController::class, 'submitRequest']);
$router->get('/comprovante', [\App\Controllers\PublicController::class, 'comprovante']);
$router->get('/protocolo', [\App\Controllers\PublicController::class, 'protocolo']);
$router->get('/login', [\App\Controllers\AuthController::class, 'loginForm']);
$router->post('/login', [\App\Controllers\AuthController::class, 'login']);
$router->get('/logout', [\App\Controllers\AuthController::class, 'logout']);
$router->get('/forgot-password', [\App\Controllers\AuthController::class, 'forgotForm']);
$router->post('/forgot-password', [\App\Controllers\AuthController::class, 'forgot']);
$router->get('/admin/dashboard', [\App\Controllers\AdminController::class, 'dashboard']);
$router->get('/admin/requests', [\App\Controllers\AdminController::class, 'requests']);
$router->get('/admin/request-detail', [\App\Controllers\AdminController::class, 'detail']);
$router->post('/admin/requests/action', [\App\Controllers\AdminController::class, 'updateBatch']);
$router->get('/admin/points', [\App\Controllers\AdminController::class, 'points']);
$router->get('/admin/reports', [\App\Controllers\AdminController::class, 'reports']);
$router->get('/api/poll-novos-agendamentos', [\App\Controllers\ApiController::class, 'poll']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
