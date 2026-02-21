<?php

declare(strict_types=1);

namespace App\Core;

use App\Controllers\AdminController;
use App\Controllers\ApiController;
use App\Controllers\AuthController;
use App\Controllers\CitizenController;
use App\Controllers\EmployeeController;
use App\Controllers\SuperAdminController;

final class Router
{
    private const PATH_ROUTES = [
        'api/citizen/points',
        'api/citizen/create',
        'api/citizen/track',
        'api/superadmin/tenant/create',
        'api/admin/point/create',
        'api/admin/requests',
        'api/admin/update',
        'api/admin/dashboard',
        'api/admin/comm-report',
        'api/employee/start',
        'api/employee/finish',
        'api/solicitacoes',
        'api/dashboard',
        'admin/reports/csv',
        'admin/reports/pdf',
        'admin/reports/comm-csv',
    ];

    public function dispatch(): void
    {
        $route = $_GET['r'] ?? $this->detectRouteByPath();

        switch ($route) {
            case 'citizen/home': (new CitizenController())->home(); break;
            case 'auth/login': (new AuthController())->login(); break;
            case 'citizen/track': (new CitizenController())->trackPage(); break;
            case 'auth/logout': (new AuthController())->logout(); break;
            case 'superadmin/dashboard': (new SuperAdminController())->dashboard(); break;
            case 'admin/dashboard': (new AdminController())->dashboard(); break;
            case 'admin/request': (new AdminController())->requestDetail(); break;
            case 'employee/dashboard': (new EmployeeController())->dashboard(); break;

            case 'api/citizen/points': (new CitizenController())->points(); break;
            case 'api/citizen/create': (new CitizenController())->store(); break;
            case 'api/citizen/track': (new CitizenController())->track(); break;

            case 'api/superadmin/tenant/create': (new SuperAdminController())->createTenant(); break;

            case 'api/admin/point/create': (new AdminController())->createPoint(); break;
            case 'api/admin/requests': (new AdminController())->requests(); break;
            case 'api/admin/update': (new AdminController())->update(); break;
            case 'api/admin/dashboard': (new AdminController())->dashboardApi(); break;
            case 'api/admin/comm-report': (new AdminController())->commReportApi(); break;
            case 'admin/reports/csv': (new AdminController())->exportCsv(); break;
            case 'admin/reports/pdf': (new AdminController())->exportPdf(); break;
            case 'admin/reports/comm-csv': (new AdminController())->exportCommCsv(); break;

            case 'api/employee/start': (new EmployeeController())->start(); break;
            case 'api/employee/finish': (new EmployeeController())->finish(); break;

            case 'api/solicitacoes': (new ApiController())->solicitacoes(); break;
            case 'api/dashboard': (new ApiController())->dashboard(); break;

            default:
                http_response_code(404);
                echo 'Rota não encontrada';
        }
    }

    private function detectRouteByPath(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = rtrim(APP_BASE_PATH, '/');
        if ($base !== '' && strpos($path, $base) === 0) {
            $path = substr($path, strlen($base));
        }

        $path = '/' . ltrim($path, '/');
        $normalized = trim($path, '/');

        if (in_array($normalized, self::PATH_ROUTES, true)) {
            return $normalized;
        }

        if (preg_match('#^api/solicitacoes/(\d+)$#', $normalized, $matches) === 1) {
            $_GET['id'] = $matches[1];
            return 'api/solicitacoes';
        }

        return match ($path) {
            '/', '/index.php' => 'citizen/home',
            '/admin' => 'admin/dashboard',
            '/admin/solicitacao' => 'admin/request',
            '/login' => 'auth/login',
            '/consultar' => 'citizen/track',
            '/funcionario' => 'employee/dashboard',
            default => 'citizen/home',
        };
    }
}
