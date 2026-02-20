<?php

declare(strict_types=1);

namespace App\Core;

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\CitizenController;
use App\Controllers\EmployeeController;

final class Router
{
    public function dispatch(): void
    {
        $route = $_GET['r'] ?? 'citizen/home';

        switch ($route) {
            case 'citizen/home': (new CitizenController())->home(); break;
            case 'auth/login': (new AuthController())->login(); break;
            case 'auth/logout': (new AuthController())->logout(); break;
            case 'admin/dashboard': (new AdminController())->dashboard(); break;
            case 'employee/dashboard': (new EmployeeController())->dashboard(); break;
            case 'api/citizen/points': (new CitizenController())->points(); break;
            case 'api/citizen/create': (new CitizenController())->store(); break;
            case 'api/admin/point/create': (new AdminController())->createPoint(); break;
            case 'api/admin/requests': (new AdminController())->requests(); break;
            case 'api/admin/update': (new AdminController())->update(); break;
            case 'api/employee/start': (new EmployeeController())->start(); break;
            case 'api/employee/finish': (new EmployeeController())->finish(); break;
            default:
                http_response_code(404);
                echo 'Rota não encontrada';
        }
    }
}
