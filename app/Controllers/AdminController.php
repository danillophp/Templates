<?php
namespace App\Controllers;

use App\Middlewares\AuthMiddleware;
use App\Core\Csrf;
use App\Models\RequestModel;
use App\Models\PointModel;
use App\Services\AuditService;
use App\Services\MessageQueueService;

class AdminController
{
    public function dashboard(): void
    {
        AuthMiddleware::handle();
        $date = $_GET['date'] ?? date('Y-m-d');
        $requests = (new RequestModel())->listByDate($date);
        require __DIR__ . '/../../resources/views/admin/dashboard.php';
    }

    public function requests(): void
    {
        $this->dashboard();
    }

    public function detail(): void
    {
        AuthMiddleware::handle();
        $id = (int)($_GET['id'] ?? 0);
        $request = (new RequestModel())->find($id);
        require __DIR__ . '/../../resources/views/admin/request_detail.php';
    }

    public function updateBatch(): void
    {
        AuthMiddleware::handle();
        if (!Csrf::validate($_POST['_csrf'] ?? null)) exit('CSRF inválido');
        $ids = array_map('intval', $_POST['ids'] ?? []);
        $action = $_POST['action'] ?? '';
        $model = new RequestModel();
        $queue = new MessageQueueService();
        foreach ($ids as $id) {
            $req = $model->find($id);
            if (!$req) continue;
            if ($action === 'excluir') {
                $model->deleteByIds([$id]);
                (new AuditService())->log('excluir', 'solicitacoes', $id, $req, null);
                continue;
            }
            $status = match($action) {
                'aprovar' => 'APROVADO',
                'recusar' => 'RECUSADO',
                'alterar' => 'ALTERADO',
                'finalizar' => 'FINALIZADO',
                default => 'PENDENTE'
            };
            $newDate = $action === 'alterar' ? ($_POST['nova_data'] ?? null) : null;
            $model->updateStatus([$id], $status, $newDate);
            $after = $model->find($id);
            (new AuditService())->log('atualizar_status', 'solicitacoes', $id, $req, $after);
            $queue->enqueueStatusEmail($id, $req['email'], $status, ['protocolo' => $req['protocolo'], 'nova_data' => $newDate]);
        }
        header('Location: ' . $_ENV['APP_BASE_PATH'] . '/admin/dashboard');
    }

    public function points(): void
    {
        AuthMiddleware::handle();
        $points = (new PointModel())->allActive();
        require __DIR__ . '/../../resources/views/admin/points.php';
    }

    public function reports(): void
    {
        AuthMiddleware::handle();
        require __DIR__ . '/../../resources/views/admin/reports.php';
    }
}
