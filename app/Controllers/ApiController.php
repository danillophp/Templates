<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\RequestModel;
use App\Services\TenantService;

final class ApiController extends Controller
{
    public function solicitacoes(): void
    {
        $tenantId = Auth::tenantId() ?? TenantService::tenantId();
        if (!$tenantId) {
            $this->json(['ok' => false, 'message' => 'Tenant inválido.'], 404);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->json(['ok' => true, 'data' => (new RequestModel())->list((int)$tenantId)]);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            (new CitizenController())->store();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
            parse_str(file_get_contents('php://input'), $payload);
            if (!Csrf::validate($payload['_csrf'] ?? null)) {
                $this->json(['ok' => false, 'message' => 'Token inválido.'], 422);
                return;
            }

            $id = (int)($payload['id'] ?? ($_GET['id'] ?? 0));
            $status = strtoupper(trim((string)($payload['status'] ?? 'PENDENTE')));
            $allowed = ['PENDENTE', 'APROVADO', 'RECUSADO', 'FINALIZADO'];

            if ($id <= 0 || !in_array($status, $allowed, true)) {
                $this->json(['ok' => false, 'message' => 'Dados inválidos para atualização.'], 422);
                return;
            }

            (new RequestModel())->updateStatus($id, (int)$tenantId, $status);
            $this->json(['ok' => true]);
            return;
        }

        $this->json(['ok' => false, 'message' => 'Método não suportado.'], 405);
    }

    public function dashboard(): void
    {
        if (!Auth::check()) {
            $this->json(['ok' => false, 'message' => 'Não autenticado.'], 401);
            return;
        }

        (new AdminController())->dashboardApi();
    }
}
