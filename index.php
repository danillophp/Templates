<?php
require __DIR__ . '/app/bootstrap.php';

use App\Core\Router;
use App\Core\ErrorHandler;
use App\Controllers\PublicController;
use App\Controllers\AuthController;
use App\Controllers\AdminController;
use App\Controllers\ApiController;

ErrorHandler::register();
$base = rtrim(config('APP_BASE_PATH', '/catatreco'), '/');
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
if ($base && strpos($uri, $base) === 0) {
    $uri = substr($uri, strlen($base));
}
$uri = $uri ?: '/';

$router = new Router();
$router->get('/', [PublicController::class, 'home']);
$router->post('/solicitar', [PublicController::class, 'storeRequest']);
$router->get('/comprovante', [PublicController::class, 'comprovante']);
$router->get('/protocolo', [PublicController::class, 'protocolo']);
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/forgot-password', [AuthController::class, 'forgotForm']);
$router->post('/forgot-password', [AuthController::class, 'forgotSend']);
$router->get('/admin/dashboard', [AdminController::class, 'dashboard']);
$router->get('/admin/request', [AdminController::class, 'requestDetail']);
$router->post('/admin/request/update', [AdminController::class, 'updateRequest']);
$router->get('/admin/points', [AdminController::class, 'points']);
$router->post('/admin/points', [AdminController::class, 'pointStore']);
$router->get('/admin/reports', [AdminController::class, 'reports']);
$router->get('/admin/notifications', [AdminController::class, 'notifications']);
$router->get('/api/poll', [ApiController::class, 'poll']);
$router->dispatch($_SERVER['REQUEST_METHOD'], $uri);
