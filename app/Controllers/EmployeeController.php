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
        if (!Auth::check() || !Auth::is('FUNCIONARIO')) {
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
        $this->changeStatus('EM_ANDAMENTO', 'INICIO_COLETA');
    }

    public function finish(): void
    {
        $this->guard();
        $this->changeStatus('FINALIZADO', 'FINALIZA_COLETA', true);
    }

    private function changeStatus(string $status, string $action, bool $notify = false): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $this->json(['ok' => false], 422);
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
        (new LogModel())->register($id, (int)Auth::user()['id'], 'FUNCIONARIO', $action, 'Ação do funcionário.');

        $wa = null;
        if ($notify) {
            $wa = (new WhatsAppService())->send($request['whatsapp'], "Sua coleta Cata Treco #{$id} foi finalizada.");
        }
        $this->json(['ok' => true, 'whatsapp' => $wa]);
    }
}
