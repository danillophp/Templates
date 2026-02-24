<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Core\Whatsapp;
use App\Models\AuditLog;
use App\Models\PickupRequest;
use App\Models\User;

class AdminController extends BaseController
{
    public function dashboard(Request $request): void
    {
        $this->requireAuth();
        if (!Auth::isAdmin()) {
            Response::redirect('/funcionario');
        }

        $filters = [
            'status' => (string) $request->input('status', ''),
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
            'locality' => (string) $request->input('locality', ''),
        ];

        $model = new PickupRequest();
        View::render('admin/dashboard/index', [
            'pageTitle' => 'Dashboard Cata Treco',
            'user' => Auth::user(),
            'counts' => $model->dashboardCounts($filters),
            'requests' => $model->list($filters),
            'employees' => (new User())->allEmployees(),
            'filters' => $filters,
            'logs' => (new AuditLog())->latest(20),
            'csrfField' => $this->csrfField(),
        ], 'layouts/admin');
    }

    public function updateRequest(Request $request): void
    {
        $this->requireAdmin();
        $this->requireCsrf((string) $request->input($this->csrfTokenName()));

        $id = (int) $request->input('id');
        $status = (string) $request->input('status');
        $scheduledAt = (string) $request->input('scheduled_at');
        $assigned = (int) $request->input('assigned_user_id');
        $notes = trim((string) $request->input('admin_notes'));

        if ($assigned > 0 && $status !== 'FINALIZADA' && $status !== 'RECUSADA') {
            $status = 'EM_ANDAMENTO';
        }

        $model = new PickupRequest();
        $existing = $model->find($id);
        if (!$existing) {
            Response::redirect('/admin');
        }

        $model->updateAdmin($id, [
            'status' => $status,
            'scheduled_at' => $scheduledAt,
            'assigned_user_id' => $assigned ?: null,
            'admin_notes' => $notes,
        ]);
        $model->addStatusHistory($id, $status, (int) Auth::user()['id'], $notes);

        $msg = "Olá {$existing['citizen_name']}, sua solicitação Cata Treco está {$status}. Data/Hora prevista: {$scheduledAt}.";
        $whats = Whatsapp::notify($existing['whatsapp'], $msg);

        $this->audit('admin_update_request', ['request_id' => $id, 'status' => $status, 'whatsapp' => $whats]);
        $_SESSION['flash_success'] = 'Solicitação atualizada com sucesso.';
        Response::redirect('/admin');
    }
}
