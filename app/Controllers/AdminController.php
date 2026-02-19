<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\LogModel;
use App\Models\RequestModel;
use App\Models\User;
use App\Services\WhatsAppService;

final class AdminController extends Controller
{
    private function guard(): void
    {
        if (!Auth::check() || !Auth::is('ADMIN')) {
            $this->redirect('/?r=auth/login');
        }
    }

    public function dashboard(): void
    {
        $this->guard();
        $requestModel = new RequestModel();
        $this->view('admin/dashboard', [
            'summary' => $requestModel->summary(),
            'employees' => (new User())->employees(),
            'csrf' => Csrf::token(),
        ]);
    }

    public function requests(): void
    {
        $this->guard();
        $filters = [
            'status' => $_GET['status'] ?? '',
            'date' => $_GET['date'] ?? '',
            'district' => $_GET['district'] ?? '',
        ];
        $items = (new RequestModel())->list($filters);
        $this->json(['ok' => true, 'data' => $items]);
    }

    public function update(): void
    {
        $this->guard();
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $this->json(['ok' => false, 'message' => 'Token inválido.'], 422);
            return;
        }

        $id = (int)($_POST['request_id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $pickup = !empty($_POST['pickup_datetime']) ? date('Y-m-d H:i:s', strtotime($_POST['pickup_datetime'])) : null;
        $employeeId = !empty($_POST['employee_id']) ? (int)$_POST['employee_id'] : null;

        $model = new RequestModel();
        $request = $model->find($id);
        if (!$request) {
            $this->json(['ok' => false, 'message' => 'Solicitação não encontrada.'], 404);
            return;
        }

        $status = match ($action) {
            'approve' => 'APROVADO',
            'reject' => 'RECUSADO',
            'schedule' => $request['status'],
            'assign' => 'EM_ANDAMENTO',
            default => null,
        };

        if ($status === null) {
            $this->json(['ok' => false, 'message' => 'Ação inválida.'], 422);
            return;
        }

        $model->updateStatus($id, $status, $pickup, $employeeId);
        $detail = strtoupper($action) . ' executada';
        (new LogModel())->register($id, (int)Auth::user()['id'], 'ADMIN', 'UPDATE_STATUS', $detail);

        $msg = "Olá {$request['full_name']}, sua solicitação #{$id} foi atualizada para {$status}.";
        $wa = (new WhatsAppService())->send($request['whatsapp'], $msg);

        $this->json(['ok' => true, 'message' => 'Atualizado com sucesso.', 'whatsapp' => $wa]);
    }
}
