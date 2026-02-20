<?php

declare(strict_types=1);

namespace App\Core;

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\CitizenController;
use App\Controllers\EmployeeController;
use App\WordPress\Capabilities;

final class Router
{
    public function dispatch(): void
    {
        $route = $_GET['r'] ?? 'citizen/home';

        switch ($route) {
            case 'citizen/home': (new CitizenController())->home(); break;
            case 'auth/login': (new AuthController())->login(); break;
            case 'auth/logout': (new AuthController())->logout(); break;
            case 'admin/dashboard':
                $this->authorize(Capabilities::ACCESS_ADMIN_PANEL);
                (new AdminController())->dashboard();
                break;
            case 'employee/dashboard':
                $this->authorize(Capabilities::ACCESS_EMPLOYEE_PANEL);
                (new EmployeeController())->dashboard();
                break;
            case 'api/citizen/create': (new CitizenController())->store(); break;
            case 'api/admin/requests':
                $this->authorize(Capabilities::VIEW_ALL_REQUESTS, true);
                (new AdminController())->requests();
                break;
            case 'api/admin/update':
                $this->authorize(Capabilities::APPROVE_REQUESTS, true);
                (new AdminController())->update();
                break;
            case 'api/employee/start':
                $this->authorize(Capabilities::ACCESS_EMPLOYEE_PANEL, true);
                (new EmployeeController())->start();
                break;
            case 'api/employee/finish':
                $this->authorize(Capabilities::FINISH_OWN_REQUESTS, true);
                (new EmployeeController())->finish();
                break;
            default:
                http_response_code(404);
                echo 'Rota não encontrada';
        }
    }

    private function authorize(string $capability, bool $json = false): void
    {
        if (!function_exists('current_user_can')) {
            return;
        }

        if (current_user_can($capability)) {
            return;
        }

        if ($json) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'Sem permissão.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        wp_die('Sem permissão para acessar esta página.');
    }
}
