<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\AvailabilityController;
use App\Controllers\ClientController;
use App\Controllers\DashboardController;
use App\Controllers\PublicBookingController;
use App\Controllers\ServiceCategoryController;
use App\Controllers\ServiceController;
use App\Controllers\ScheduleConfigController;
use App\Controllers\StudioSettingsController;
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
$GLOBALS['app_config'] = $config;

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

$guestOnly = [static fn() => Auth::requireGuest()];
$authOnly = [static fn() => Auth::requireAuth()];

$router->get('/', static fn() => redirect_to('/login'));
$router->get('/agendamento', [PublicBookingController::class, 'index']);
$router->post('/agendamento/reservar', [PublicBookingController::class, 'reserve']);
$router->get('/agendamento/pagamento', [PublicBookingController::class, 'payment']);
$router->post('/agendamento/pagamento/confirmar', [PublicBookingController::class, 'confirmPayment']);
$router->get('/login', [AuthController::class, 'showLogin'], $guestOnly);
$router->post('/login', [AuthController::class, 'login'], $guestOnly);
$router->post('/logout', [AuthController::class, 'logout'], $authOnly);
$router->get('/dashboard', [DashboardController::class, 'index'], $authOnly);

// CRUD clientes
$router->get('/clientes', [ClientController::class, 'index'], $authOnly);
$router->get('/clientes/buscar', [ClientController::class, 'index'], $authOnly);
$router->get('/clientes/historico', [ClientController::class, 'history'], $authOnly);
$router->get('/clientes/criar', [ClientController::class, 'create'], $authOnly);
$router->post('/clientes/salvar', [ClientController::class, 'store'], $authOnly);
$router->get('/clientes/editar', [ClientController::class, 'edit'], $authOnly);
$router->post('/clientes/atualizar', [ClientController::class, 'update'], $authOnly);
$router->post('/clientes/excluir', [ClientController::class, 'destroy'], $authOnly);

// CRUD categorias
$router->get('/categorias', [ServiceCategoryController::class, 'index'], $authOnly);
$router->get('/categorias/criar', [ServiceCategoryController::class, 'create'], $authOnly);
$router->post('/categorias/salvar', [ServiceCategoryController::class, 'store'], $authOnly);
$router->get('/categorias/editar', [ServiceCategoryController::class, 'edit'], $authOnly);
$router->post('/categorias/atualizar', [ServiceCategoryController::class, 'update'], $authOnly);
$router->post('/categorias/status', [ServiceCategoryController::class, 'toggleStatus'], $authOnly);
$router->post('/categorias/excluir', [ServiceCategoryController::class, 'destroy'], $authOnly);

// CRUD serviços
$router->get('/servicos', [ServiceController::class, 'index'], $authOnly);
$router->get('/servicos/criar', [ServiceController::class, 'create'], $authOnly);
$router->post('/servicos/salvar', [ServiceController::class, 'store'], $authOnly);
$router->get('/servicos/editar', [ServiceController::class, 'edit'], $authOnly);
$router->post('/servicos/atualizar', [ServiceController::class, 'update'], $authOnly);
$router->post('/servicos/status', [ServiceController::class, 'toggleStatus'], $authOnly);
$router->post('/servicos/excluir', [ServiceController::class, 'destroy'], $authOnly);

// Configuração da agenda
$router->get('/agenda/configuracoes', [ScheduleConfigController::class, 'edit'], $authOnly);
$router->post('/agenda/configuracoes/salvar', [ScheduleConfigController::class, 'save'], $authOnly);
$router->get('/configuracoes/studio', [StudioSettingsController::class, 'edit'], $authOnly);
$router->post('/configuracoes/studio/salvar', [StudioSettingsController::class, 'save'], $authOnly);
$router->get('/api/agenda/horarios-disponiveis', [AvailabilityController::class, 'availableSlots']);

return $router;
