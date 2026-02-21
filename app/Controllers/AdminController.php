<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CsrfMiddleware;
use App\Core\Csrf;
use App\Models\RequestModel;
use App\Models\PointModel;
use App\Services\MessageQueueService;
use App\Services\AuditService;
use App\Services\PdfReportService;

class AdminController extends Controller
{
    public function dashboard(): void
    {
        AuthMiddleware::handle();
        $date = $_GET['data'] ?? date('Y-m-d');
        $m = new RequestModel();
        $this->view('admin/dashboard', [
            'requests' => $m->listByDate($date),
            'date' => $date,
            'statusData' => $m->statusCount(),
            'monthData' => $m->monthlyCount(),
            'csrf' => Csrf::token(),
        ]);
    }

    public function requestDetail(): void
    {
        AuthMiddleware::handle();
        $req = (new RequestModel())->find((int)($_GET['id'] ?? 0));
        $this->view('admin/request_detail', ['request' => $req, 'csrf' => Csrf::token()]);
    }

    public function updateRequest(): void
    {
        AuthMiddleware::handle(); CsrfMiddleware::handle();
        $id = (int)$this->input('id'); $action = $this->input('acao');
        $m = new RequestModel(); $before = $m->find($id);
        if ($action === 'aprovar') $m->updateStatus($id, 'APROVADO');
        if ($action === 'recusar') $m->updateStatus($id, 'RECUSADO');
        if ($action === 'alterar') $m->updateDate($id, $this->input('nova_data'));
        if ($action === 'finalizar') $m->updateStatus($id, 'FINALIZADO');
        if ($action === 'excluir') $m->delete($id);
        $after = $m->find($id) ?: ['id'=>$id,'status'=>'EXCLUIDO'];
        (new AuditService())->log('admin_'.$action, 'solicitacoes', $id, $before, $after);
        if ($action !== 'excluir') (new MessageQueueService())->enqueueStatusMessages($after, 'status_update');
        $this->redirect('/admin/dashboard');
    }

    public function points(): void
    {
        AuthMiddleware::handle();
        $this->view('admin/points', ['points' => (new PointModel())->all(), 'csrf' => Csrf::token()]);
    }

    public function pointStore(): void
    {
        AuthMiddleware::handle(); CsrfMiddleware::handle();
        (new PointModel())->create([
            'nome'=>$this->input('nome'),'descricao'=>$this->input('descricao'),'latitude'=>$this->input('latitude'),'longitude'=>$this->input('longitude'),'ativo'=>1
        ]);
        $this->redirect('/admin/points');
    }

    public function reports(): void
    {
        AuthMiddleware::handle();
        $m = new RequestModel();
        if (isset($_GET['export']) && $_GET['export']==='csv') {
            header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename=relatorio.csv');
            $f = fopen('php://output', 'w');
            fputcsv($f, ['Protocolo','Nome','Status','Data']);
            foreach ($m->listByDate($_GET['data'] ?? date('Y-m-d')) as $r) fputcsv($f, [$r['protocolo'],$r['nome'],$r['status'],$r['data_agendada']]);
            fclose($f); return;
        }
        if (isset($_GET['export']) && $_GET['export']==='pdf') {
            $path = (new PdfReportService())->generate('<h1>Relatório Cata Treco</h1>', 'relatorio_' . date('His') . '.pdf');
            header('Content-Type: application/pdf'); readfile($path); return;
        }
        $this->view('admin/reports');
    }

    public function notifications(): void
    {
        AuthMiddleware::handle();
        $this->view('admin/notifications');
    }
}
