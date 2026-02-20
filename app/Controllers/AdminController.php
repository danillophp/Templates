<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\LogModel;
use App\Models\PointModel;
use App\Models\RequestModel;
use App\Models\SubscriptionModel;
use App\Models\User;
use App\Services\WhatsAppService;

final class AdminController extends Controller
{
    private function guard(): int
    {
        if (!Auth::check() || !Auth::is('admin')) {
            $this->redirect('/?r=auth/login');
        }
        return (int)Auth::tenantId();
    }

    public function dashboard(): void
    {
        $tenantId = $this->guard();
        $model = new RequestModel();
        $subscription = (new SubscriptionModel())->activeWithPlan($tenantId);

        $this->view('admin/dashboard', [
            'summary' => $model->summary($tenantId),
            'employees' => (new User())->employees($tenantId),
            'points' => (new PointModel())->active($tenantId),
            'subscription' => $subscription,
            'csrf' => Csrf::token(),
        ]);
    }

    public function createPoint(): void
    {
        $tenantId = $this->guard();
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

        (new PointModel())->create($tenantId, $titulo, $latitude, $longitude);
        (new LogModel())->register($tenantId, null, (int)Auth::user()['id'], 'PONTO_CRIADO', 'Novo ponto de coleta cadastrado.');
        $this->json(['ok' => true, 'message' => 'Ponto cadastrado com sucesso.']);
    }

    public function requestDetail(): void
    {
        $tenantId = $this->guard();
        $id = (int)($_GET['id'] ?? 0);
        $request = (new RequestModel())->find($id, $tenantId);

        if (!$request) {
            http_response_code(404);
            echo 'Solicitação não encontrada';
            return;
        }

        $this->view('admin/request-detail', [
            'request' => $request,
            'csrf' => Csrf::token(),
        ]);
    }

    public function requests(): void
    {
        $tenantId = $this->guard();
        $filters = [
            'status' => $_GET['status'] ?? '',
            'date' => $_GET['date'] ?? '',
        ];
        $this->json(['ok' => true, 'data' => (new RequestModel())->list($tenantId, $filters)]);
    }

    public function update(): void
    {
        $tenantId = $this->guard();
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $this->json(['ok' => false, 'message' => 'Token inválido.'], 422);
            return;
        }

        $id = (int)($_POST['request_id'] ?? 0);
        $action = (string)($_POST['action'] ?? '');
        $date = !empty($_POST['pickup_datetime']) ? trim((string)$_POST['pickup_datetime']) : null;
        $employeeId = !empty($_POST['employee_id']) ? (int)$_POST['employee_id'] : null;

        $model = new RequestModel();
        $request = $model->find($id, $tenantId);
        if (!$request) {
            $this->json(['ok' => false, 'message' => 'Solicitação não encontrada.'], 404);
            return;
        }

        $statusTexto = 'ATUALIZADA';
        if ($action === 'approve') {
            $model->updateStatus($id, $tenantId, 'APROVADO');
            $statusTexto = 'APROVADA';
        } elseif ($action === 'reject') {
            $model->updateStatus($id, $tenantId, 'RECUSADO');
            $statusTexto = 'RECUSADA';
        } elseif ($action === 'schedule') {
            $requestedDate = $date ? \DateTimeImmutable::createFromFormat('Y-m-d', $date) : false;
            if (!$requestedDate || $requestedDate->format('Y-m-d') !== $date || $requestedDate < new \DateTimeImmutable('today')) {
                $this->json(['ok' => false, 'message' => 'Data inválida. Use uma data atual ou futura.'], 422);
                return;
            }
            $model->updateStatus($id, $tenantId, 'ALTERADO', $requestedDate->format('Y-m-d'));
            $statusTexto = 'ALTERADA';
        } elseif ($action === 'assign') {
            if (!$employeeId) {
                $this->json(['ok' => false, 'message' => 'Selecione um funcionário.'], 422);
                return;
            }
            $model->updateStatus($id, $tenantId, 'APROVADO', null, $employeeId);
            $statusTexto = 'ATRIBUÍDA';
        } elseif ($action === 'delete') {
            $model->delete($id, $tenantId);
            (new LogModel())->register($tenantId, $id, (int)Auth::user()['id'], 'SOLICITACAO_EXCLUIDA', 'Solicitação excluída pelo administrador.');
            $this->json(['ok' => true, 'message' => 'Solicitação excluída com sucesso.']);
            return;
        } else {
            $this->json(['ok' => false, 'message' => 'Ação inválida.'], 422);
            return;
        }

        (new LogModel())->register($tenantId, $id, (int)Auth::user()['id'], 'SOLICITACAO_ATUALIZADA', $statusTexto);
        $mensagem = sprintf('Olá, %s. Sua solicitação %s foi %s. Prefeitura Municipal.', $request['nome'], $request['protocolo'], $statusTexto);
        $wa = (new WhatsAppService())->sendMessage($tenantId, (string)$request['telefone'], $mensagem);

        $this->json(['ok' => true, 'message' => 'Solicitação atualizada.', 'whatsapp' => $wa]);
    }

    public function dashboardApi(): void
    {
        $tenantId = $this->guard();
        $chart = (new RequestModel())->chartByMonth($tenantId);
        $this->json(['ok' => true, 'data' => $chart]);
    }

    public function exportCsv(): void
    {
        $tenantId = $this->guard();
        $rows = (new RequestModel())->list($tenantId);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=relatorio_solicitacoes.csv');
        $fp = fopen('php://output', 'wb');
        fputcsv($fp, ['Protocolo', 'Nome', 'Endereço', 'Telefone', 'Status', 'Data solicitada']);
        foreach ($rows as $row) {
            fputcsv($fp, [$row['protocolo'], $row['nome'], $row['endereco'], $row['telefone'], $row['status'], $row['data_solicitada']]);
        }
        fclose($fp);
    }
}
