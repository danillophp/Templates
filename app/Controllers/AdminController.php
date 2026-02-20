<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\LogModel;
use App\Models\PointModel;
use App\Models\RequestModel;
use App\Models\User;
use App\Services\WhatsAppService;

final class AdminController extends Controller
{
    private function guard(): void
    {
        if (!Auth::check() || !Auth::is('admin')) {
            $this->redirect('/?r=auth/login');
        }
    }

    public function dashboard(): void
    {
        $this->guard();
        $model = new RequestModel();

        $this->view('admin/dashboard', [
            'summary' => $model->summary(),
            'employees' => (new User())->employees(),
            'points' => (new PointModel())->active(),
            'csrf' => Csrf::token(),
        ]);
    }

    public function createPoint(): void
    {
        $this->guard();
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $this->json(['ok' => false, 'message' => 'Token inválido.'], 422);
            return;
        }

        $titulo = trim((string)($_POST['titulo'] ?? ''));
        $latitude = (float)($_POST['latitude'] ?? 0);
        $longitude = (float)($_POST['longitude'] ?? 0);

        if ($titulo === '' || $latitude === 0.0 || $longitude === 0.0) {
            $this->json(['ok' => false, 'message' => 'Preencha título e coordenadas do ponto.'], 422);
            return;
        }

        (new PointModel())->create($titulo, $latitude, $longitude);
        (new LogModel())->register(null, (int)Auth::user()['id'], 'Novo ponto de coleta cadastrado.');
        $this->json(['ok' => true, 'message' => 'Ponto cadastrado com sucesso.']);
    }

    public function requests(): void
    {
        $this->guard();
        $filters = [
            'status' => $_GET['status'] ?? '',
            'date' => $_GET['date'] ?? '',
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
        $date = !empty($_POST['pickup_datetime']) ? date('Y-m-d H:i:s', strtotime((string)$_POST['pickup_datetime'])) : null;
        $employeeId = !empty($_POST['employee_id']) ? (int)$_POST['employee_id'] : null;

        $model = new RequestModel();
        $request = $model->find($id);

        if (!$request) {
            $this->json(['ok' => false, 'message' => 'Solicitação não encontrada.'], 404);
            return;
        }

        if ($action === 'approve') {
            $model->updateStatus($id, 'APROVADO');
            $statusTexto = 'APROVADA';
        } elseif ($action === 'reject') {
            $model->updateStatus($id, 'RECUSADO');
            $statusTexto = 'RECUSADA';
        } elseif ($action === 'schedule') {
            if (!$date || $date === '1970-01-01 00:00:00') {
                $this->json(['ok' => false, 'message' => 'Data inválida.'], 422);
                return;
            }
            $model->updateStatus($id, (string)$request['status'], $date);
            $statusTexto = 'REAGENDADA';
        } elseif ($action === 'assign') {
            if (!$employeeId) {
                $this->json(['ok' => false, 'message' => 'Selecione um funcionário.'], 422);
                return;
            }
            $model->updateStatus($id, 'APROVADO', null, $employeeId);
            $statusTexto = 'ATRIBUÍDA';
        } else {
            $this->json(['ok' => false, 'message' => 'Ação inválida.'], 422);
            return;
        }

        (new LogModel())->register($id, (int)Auth::user()['id'], 'Admin atualizou a solicitação.');

        $mensagem = sprintf(
            'Olá, %s. Sua solicitação de Cata Treco foi %s para o dia %s. Prefeitura Municipal.',
            $request['nome'],
            $statusTexto,
            date('d/m/Y', strtotime($date ?? (string)$request['data_solicitada']))
        );

        $wa = (new WhatsAppService())->send((string)$request['telefone'], $mensagem);
        $this->json(['ok' => true, 'message' => 'Solicitação atualizada.', 'whatsapp' => $wa]);
    }
}
