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
    private function guard(): void
    {
        if (!Auth::check() || !Auth::is('funcionario')) {
            $this->redirect('/?r=auth/login');
        }
    }

    public function dashboard(): void
    {
        $this->guard();
        $this->view('employee/dashboard', [
            'requests' => (new RequestModel())->byEmployee((int)Auth::user()['id']),
            'csrf' => Csrf::token(),
        ]);
    }

    public function start(): void
    {
        $this->guard();
        $this->changeStatus('APROVADO', 'Funcionário iniciou deslocamento.');
    }

    public function finish(): void
    {
        $this->guard();
        $this->changeStatus('FINALIZADO', 'Funcionário finalizou o Cata Treco.', true);
    }

    private function changeStatus(string $status, string $logMessage, bool $notify = false): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $this->json(['ok' => false, 'message' => 'Token inválido.'], 422);
            return;
        }

        $id = (int)($_POST['request_id'] ?? 0);
        $model = new RequestModel();
        $request = $model->find($id);

        if (!$request) {
            $this->json(['ok' => false, 'message' => 'Solicitação não encontrada.'], 404);
            return;
        }

        $model->updateStatus($id, $status);
        (new LogModel())->register($id, (int)Auth::user()['id'], $logMessage);

        $wa = null;
        if ($notify) {
            $wa = (new WhatsAppService())->send((string)$request['telefone'], 'Sua coleta de Cata Treco foi finalizada. Prefeitura Municipal.');
        }

        $this->json(['ok' => true, 'message' => 'Status atualizado.', 'whatsapp' => $wa]);
    }
}
