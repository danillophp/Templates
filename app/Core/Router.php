<?php

declare(strict_types=1);

namespace App\Core;

use App\Controllers\AdminController;
use App\Controllers\ApiController;
use App\Controllers\AuthController;
use App\Controllers\PublicController;

final class Router
{
    public function dispatch(): void
    {
        $route = $_GET['r'] ?? 'home';

        switch ($route) {
            case 'home': (new PublicController())->home(); break;
            case 'secretaria': (new PublicController())->secretaria(); break;
            case 'noticias': (new PublicController())->noticias(); break;
            case 'documentos': (new PublicController())->documentos(); break;
            case 'transparencia': (new PublicController())->transparencia(); break;
            case 'primeira-infancia': (new PublicController())->primeiraInfancia(); break;
            case 'contato': (new PublicController())->contato(); break;
            case 'mapa-publico': (new PublicController())->mapaPublico(); break;
            case 'contato/enviar': (new PublicController())->salvarContato(); break;

            case 'auth/login': (new AuthController())->login(); break;
            case 'auth/logout': (new AuthController())->logout(); break;

            case 'admin/dashboard': (new AdminController())->dashboard(); break;
            case 'admin/escolas': (new AdminController())->escolas(); break;
            case 'admin/escolas/salvar': (new AdminController())->salvarEscola(); break;
            case 'admin/noticias': (new AdminController())->noticias(); break;
            case 'admin/noticias/salvar': (new AdminController())->salvarNoticia(); break;
            case 'admin/documentos': (new AdminController())->documentos(); break;
            case 'admin/documentos/salvar': (new AdminController())->salvarDocumento(); break;
            case 'admin/estoque': (new AdminController())->estoque(); break;
            case 'admin/estoque/movimentar': (new AdminController())->movimentarEstoque(); break;

            case 'api/public/escolas': (new ApiController())->publicSchools(); break;
            case 'api/public/noticias': (new ApiController())->publicNews(); break;
            case 'api/admin/estoque/alertas': (new ApiController())->adminStockAlerts(); break;

            default:
                http_response_code(404);
                echo 'Rota não encontrada';
        }
    }
}
