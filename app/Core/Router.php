<?php

declare(strict_types=1);

namespace App\Core;

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\CitizenController;
use App\Controllers\EmployeeController;
use App\Controllers\Festival\AdminController as FestivalAdminController;
use App\Controllers\Festival\PublicController as FestivalPublicController;
use App\Controllers\Festival\VoteController as FestivalVoteController;

final class Router
{
    public function dispatch(): void
    {
        $route = $_GET['r'] ?? 'festival/home';

        switch ($route) {
            // Projeto legado
            case 'citizen/home': (new CitizenController())->home(); break;
            case 'auth/login': (new AuthController())->login(); break;
            case 'auth/logout': (new AuthController())->logout(); break;
            case 'admin/dashboard': (new AdminController())->dashboard(); break;
            case 'employee/dashboard': (new EmployeeController())->dashboard(); break;
            case 'api/citizen/create': (new CitizenController())->store(); break;
            case 'api/admin/requests': (new AdminController())->requests(); break;
            case 'api/admin/update': (new AdminController())->update(); break;
            case 'api/employee/start': (new EmployeeController())->start(); break;
            case 'api/employee/finish': (new EmployeeController())->finish(); break;

            // Festival público
            case 'festival/home': (new FestivalPublicController())->home(); break;
            case 'api/festival/vote': (new FestivalVoteController())->store(); break;
            case 'api/festival/ranking': (new FestivalVoteController())->ranking(); break;

            // Festival admin
            case 'festival-admin/login': (new FestivalAdminController())->login(); break;
            case 'festival-admin/logout': (new FestivalAdminController())->logout(); break;
            case 'festival-admin/dashboard': (new FestivalAdminController())->dashboard(); break;
            case 'festival-admin/candidates': (new FestivalAdminController())->candidates(); break;
            case 'festival-admin/votes': (new FestivalAdminController())->votes(); break;
            case 'festival-admin/fraud': (new FestivalAdminController())->fraud(); break;
            case 'festival-admin/settings': (new FestivalAdminController())->settings(); break;
            case 'api/festival-admin/candidates/save': (new FestivalAdminController())->saveCandidate(); break;
            case 'api/festival-admin/settings/save': (new FestivalAdminController())->saveSettings(); break;

            default:
                http_response_code(404);
                echo 'Rota não encontrada';
        }
    }
}
