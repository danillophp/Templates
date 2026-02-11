<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\LocationController;
use App\Controllers\PoliticianController;
use App\Controllers\PublicController;
use App\Core\Config;
use App\Core\Request;
use App\Core\Router;

require __DIR__ . '/../app/bootstrap.php';

Config::load();
date_default_timezone_set(Config::get('app.timezone', 'UTC'));

$router = new Router();

$router->get('/', [PublicController::class, 'index']);
$router->get('/api/map-data', [PublicController::class, 'mapData']);

$router->get('/admin/login', [AuthController::class, 'showLogin']);
$router->post('/admin/login', [AuthController::class, 'login']);
$router->post('/admin/logout', [AuthController::class, 'logout']);
$router->get('/admin', [AdminController::class, 'dashboard']);

$router->get('/admin/locations', [LocationController::class, 'index']);
$router->get('/admin/locations/edit', [LocationController::class, 'edit']);
$router->post('/admin/locations/create', [LocationController::class, 'create']);
$router->post('/admin/locations/update', [LocationController::class, 'update']);
$router->post('/admin/locations/delete', [LocationController::class, 'delete']);

$router->get('/admin/politicians', [PoliticianController::class, 'index']);
$router->get('/admin/politicians/edit', [PoliticianController::class, 'edit']);
$router->post('/admin/politicians/create', [PoliticianController::class, 'create']);
$router->post('/admin/politicians/update', [PoliticianController::class, 'update']);
$router->post('/admin/politicians/delete', [PoliticianController::class, 'delete']);

$router->dispatch(new Request());
