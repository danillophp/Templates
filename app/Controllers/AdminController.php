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
use App\WordPress\Capabilities;

final class AdminController extends Controller
{
    private function guard(): void
    {
        if (function_exists('current_user_can')) {
            if (!current_user_can(Capabilities::ACCESS_ADMIN_PANEL)) {
                wp_die('Sem permissão para acessar o painel administrativo.');
            }
            return;
        }

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
        if (function_exists('current_user_can') && !current_user_can(Capabilities::VIEW_ALL_REQUESTS)) {
            $this->json(['ok' => false, 'message' => 'Sem permissão para visualizar solicitações.'], 403);
            return;
        }

        $filters = [
            'status' => $_GET['status'] ?? '',
            'date' => $_GET['date'] ?? '',
            'district' => $_GET['district'] ?? '',
        ];
        $this->json(['ok' => true, 'data' => (new RequestModel())->list($filters)]);
    }

    public function update(): void
    {
        $this->guard();
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $this->json(['ok' => false, 'message' => 'Token inválido.'], 422);
            return;
        }

        $id = (int)($_POST['request_id'] ?? 0);
        $action = (string)($_POST['action'] ?? '');

        if (function_exists('current_user_can')) {
            $capability = $action === 'assign' ? Capabilities::ASSIGN_REQUESTS : Capabilities::APPROVE_REQUESTS;
            if (!current_user_can($capability)) {
                $this->json(['ok' => false, 'message' => 'Sem permissão para esta ação.'], 403);
                return;
            }
        }

        $pickup = !empty($_POST['pickup_datetime']) ? date('Y-m-d H:i:s', strtotime((string)$_POST['pickup_datetime'])) : null;
        $employeeId = !empty($_POST['employee_id']) ? (int)$_POST['employee_id'] : null;

        $model = new RequestModel();
        $request = $model->find($id);
        if (!$request) {
            $this->json(['ok' => false, 'message' => 'Solicitação não encontrada.'], 404);
            return;
        }

        $status = $request['status'];
        $detail = '';

        if ($action === 'approve') {
            $status = 'APROVADO';
            $detail = 'Solicitação aprovada.';
            $model->updateStatus($id, $status);
        } elseif ($action === 'reject') {
            $status = 'RECUSADO';
            $detail = 'Solicitação recusada.';
            $model->updateStatus($id, $status);
        } elseif ($action === 'schedule') {
            if (!$pickup || $pickup === '1970-01-01 00:00:00') {
                $this->json(['ok' => false, 'message' => 'Informe uma nova data/hora válida.'], 422);
                return;
            }
            $detail = 'Data/hora alterada para ' . date('d/m/Y H:i', strtotime($pickup)) . '.';
            $model->updateStatus($id, $status, $pickup);
        } elseif ($action === 'assign') {
            if (!$employeeId) {
                $this->json(['ok' => false, 'message' => 'Selecione um funcionário.'], 422);
                return;
            }
            $status = 'EM_ANDAMENTO';
            $detail = 'Solicitação atribuída ao funcionário #' . $employeeId . '.';
            $model->updateStatus($id, $status, null, $employeeId);
        } else {
            $this->json(['ok' => false, 'message' => 'Ação inválida.'], 422);
            return;
        }

        (new LogModel())->register($id, (int)Auth::user()['id'], 'ADMIN', 'UPDATE_STATUS', $detail);

        $message = $this->buildCitizenMessage((string)$request['full_name'], $id, $status, $pickup);
        $template = null;
        if ($action === 'approve') { $template = WA_TEMPLATE_APPROVED; }
        if ($action === 'schedule') { $template = WA_TEMPLATE_RESCHEDULED; }

        $wa = null;
        if (!function_exists('current_user_can') || current_user_can(Capabilities::CONFIGURE_WHATSAPP)) {
            $wa = (new WhatsAppService())->send((string)$request['whatsapp'], $message, $template);
        }

        $this->json(['ok' => true, 'message' => 'Atualização concluída.', 'whatsapp' => $wa]);
    }

    private function buildCitizenMessage(string $name, int $id, string $status, ?string $pickup): string
    {
        $base = "Olá {$name}, Prefeitura de Santo André (Cata Treco): solicitação #{$id}.";
        return match ($status) {
            'APROVADO' => $base . ' Sua solicitação foi APROVADA.',
            'FINALIZADO' => $base . ' Sua coleta foi FINALIZADA. Obrigado!',
            'RECUSADO' => $base . ' Sua solicitação foi RECUSADA. Em caso de dúvida, contate a central.',
            default => $pickup
                ? $base . ' Sua coleta foi reagendada para ' . date('d/m/Y H:i', strtotime($pickup)) . '.'
                : $base . ' Sua solicitação está em atualização. Status: ' . $status . '.',
        };
    }
}
