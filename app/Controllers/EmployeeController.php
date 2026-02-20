<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\LogModel;
use App\Models\RequestModel;
use App\Services\WhatsAppService;

final class EmployeeController extends Controller
{
    private function guard(): int
    {
        if (!Auth::check() || !Auth::is('funcionario')) {
            $this->redirect('/?r=auth/login');
        }
        return (int)Auth::tenantId();
    }

    public function dashboard(): void
    {
        $tenantId = $this->guard();
        $this->view('employee/dashboard', [
            'requests' => (new RequestModel())->byEmployee((int)Auth::user()['id'], $tenantId),
            'csrf' => Csrf::token(),
        ]);
    }

    public function start(): void
    {
        $this->guard();
        $this->changeStatus('APROVADO', 'COLETA_INICIADA');
    }

    public function finish(): void
    {
        $this->guard();
        $this->changeStatus('FINALIZADO', 'COLETA_FINALIZADA', true);
    }

    private function changeStatus(string $status, string $logAction, bool $notify = false): void
    {
        $tenantId = (int)Auth::tenantId();
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $this->json(['ok' => false, 'message' => 'Token inválido.'], 422);
            return;
        }

        $id = (int)($_POST['request_id'] ?? 0);
        $model = new RequestModel();
        $request = $model->find($id, $tenantId);

        if (!$request) {
            $this->json(['ok' => false, 'message' => 'Solicitação não encontrada.'], 404);
            return;
        }

        $model->updateStatus($id, $tenantId, $status);
        (new LogModel())->register($tenantId, $id, (int)Auth::user()['id'], $logAction, 'Ação do funcionário.');

        $wa = null;
        if ($notify) {
            $wa = (new WhatsAppService())->send($tenantId, (string)$request['telefone'], 'Sua coleta de Cata Treco foi finalizada. Prefeitura Municipal.');
        }

        $this->json(['ok' => true, 'message' => 'Status atualizado.', 'whatsapp' => $wa]);
    }
}
