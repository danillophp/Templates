<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\EmployeeController;
use App\Controllers\PublicController;
use App\Core\Config;
use App\Core\Request;
use App\Core\Router;

require __DIR__ . '/../app/bootstrap.php';

Config::load();
date_default_timezone_set(Config::get('app.timezone', 'UTC'));

$router = new Router();

$router->get('/', [PublicController::class, 'index']);
$router->post('/api/solicitacoes', [PublicController::class, 'submit']);

$router->get('/admin/login', [AuthController::class, 'showLogin']);
$router->post('/admin/login', [AuthController::class, 'login']);
$router->post('/admin/logout', [AuthController::class, 'logout']);

$router->get('/admin', [AdminController::class, 'dashboard']);
$router->post('/admin/solicitacao/update', [AdminController::class, 'updateRequest']);

$router->get('/funcionario', [EmployeeController::class, 'panel']);
$router->post('/funcionario/iniciar', [EmployeeController::class, 'start']);
$router->post('/funcionario/finalizar', [EmployeeController::class, 'finish']);

$router->dispatch(new Request());
