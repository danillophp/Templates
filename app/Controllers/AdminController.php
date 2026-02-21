<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\LogModel;
use App\Models\PointModel;
use App\Models\RequestModel;
use App\Services\EmailService;
use App\Services\TenantService;
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
        $today = (new \DateTimeImmutable('today'))->format('Y-m-d');

        $this->view('admin/dashboard', [
            'summary' => (new RequestModel())->summary($tenantId),
            'points' => (new PointModel())->active($tenantId),
            'csrf' => Csrf::token(),
            'today' => $today,
            'whatsAppReady' => $this->isWhatsAppReady($tenantId),
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
            'date' => $_GET['date'] ?? (new \DateTimeImmutable('today'))->format('Y-m-d'),
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

        if (!$this->isWhatsAppReady($tenantId)) {
            $this->json(['ok' => false, 'message' => 'Conecte o WhatsApp oficial antes de atualizar solicitações.'], 422);
            return;
        }

        $idsRaw = trim((string)($_POST['request_ids'] ?? $_POST['request_id'] ?? ''));
        $ids = array_values(array_filter(array_map('intval', preg_split('/\s*,\s*/', $idsRaw) ?: [])));
        if (empty($ids)) {
            $this->json(['ok' => false, 'message' => 'Nenhuma solicitação selecionada.'], 422);
            return;
        }

        $action = (string)($_POST['action'] ?? '');
        $date = !empty($_POST['pickup_datetime']) ? trim((string)$_POST['pickup_datetime']) : null;
        $model = new RequestModel();

        foreach ($ids as $id) {
            $request = $model->find($id, $tenantId);
            if (!$request) {
                continue;
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
                    $this->json(['ok' => false, 'message' => 'Data inválida. Use data atual ou futura.'], 422);
                    return;
                }
                $model->updateStatus($id, $tenantId, 'ALTERADO', $requestedDate->format('Y-m-d'));
                $statusTexto = 'ALTERADA';
            } elseif ($action === 'delete') {
                $model->delete($id, $tenantId);
                (new LogModel())->register($tenantId, $id, (int)Auth::user()['id'], 'SOLICITACAO_EXCLUIDA', 'Solicitação excluída pelo administrador.');
                continue;
            } else {
                $this->json(['ok' => false, 'message' => 'Ação inválida.'], 422);
                return;
            }

            (new LogModel())->register($tenantId, $id, (int)Auth::user()['id'], 'SOLICITACAO_ATUALIZADA', $statusTexto);
            $mensagem = sprintf('Olá, %s. Sua solicitação %s foi %s. Prefeitura Municipal.', $request['nome'], $request['protocolo'], $statusTexto);
            (new WhatsAppService())->sendMessage($tenantId, (string)$request['telefone'], $mensagem);
            if (!empty($request['email'])) {
                (new EmailService())->sendReceipt($tenantId, (string)$request['email'], [
                    'nome' => (string)$request['nome'],
                    'endereco' => (string)$request['endereco'],
                    'data_solicitada' => (string)$request['data_solicitada'],
                    'telefone' => (string)$request['telefone'],
                    'email' => (string)$request['email'],
                    'protocolo' => (string)$request['protocolo'],
                    'status' => $statusTexto,
                ]);
            }
        }

        $this->json(['ok' => true, 'message' => 'Solicitações atualizadas com sucesso.']);
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
        $rows = (new RequestModel())->list($tenantId, ['date' => $_GET['date'] ?? (new \DateTimeImmutable('today'))->format('Y-m-d')]);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=relatorio_solicitacoes.csv');
        $fp = fopen('php://output', 'wb');
        fputcsv($fp, ['Protocolo', 'Nome', 'Endereço', 'Telefone', 'E-mail', 'Status', 'Data solicitada']);
        foreach ($rows as $row) {
            fputcsv($fp, [$row['protocolo'], $row['nome'], $row['endereco'], $row['telefone'], $row['email'] ?? '', $row['status'], $row['data_solicitada']]);
        }
        fclose($fp);
    }

    public function exportPdf(): void
    {
        $tenantId = $this->guard();
        $rows = (new RequestModel())->list($tenantId, ['date' => $_GET['date'] ?? (new \DateTimeImmutable('today'))->format('Y-m-d')]);
        $chartData = (string)($_POST['chart_image'] ?? '');
        $dir = STORAGE_PATH . '/relatorios/' . date('Y') . '/' . date('m');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $html = '<html><head><meta charset="UTF-8"><style>body{font-family:Arial,sans-serif}table{width:100%;border-collapse:collapse}th,td{border:1px solid #d5deea;padding:6px;font-size:12px}th{background:#eef5fb}</style></head><body>';
        $html .= '<h2>Relatório Cata Treco</h2>';
        if ($chartData !== '') {
            $html .= '<p><img alt="Gráfico" style="max-width:100%;height:auto" src="' . htmlspecialchars($chartData, ENT_QUOTES, 'UTF-8') . '"></p>';
        }
        $html .= '<table><tr><th>Protocolo</th><th>Nome</th><th>Endereço</th><th>Status</th><th>Data</th></tr>';
        foreach ($rows as $row) {
            $html .= '<tr><td>' . htmlspecialchars((string)$row['protocolo']) . '</td><td>' . htmlspecialchars((string)$row['nome']) . '</td><td>' . htmlspecialchars((string)$row['endereco']) . '</td><td>' . htmlspecialchars((string)$row['status']) . '</td><td>' . htmlspecialchars((string)$row['data_solicitada']) . '</td></tr>';
        }
        $html .= '</table></body></html>';

        if (class_exists('Dompdf\\Dompdf')) {
            $filePath = $dir . '/relatorio_' . date('Ymd_His') . '.pdf';
            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', true);
            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            file_put_contents($filePath, $dompdf->output());
        } else {
            $filePath = $dir . '/relatorio_' . date('Ymd_His') . '.html';
            file_put_contents($filePath, $html);
        }

        $relative = str_replace(STORAGE_PATH, APP_BASE_PATH . '/storage', $filePath);
        $this->json(['ok' => true, 'message' => 'Relatório gerado com sucesso.', 'file' => $relative]);
    }

    private function isWhatsAppReady(int $tenantId): bool
    {
        $cfg = TenantService::config($tenantId);
        return !empty($cfg['wa_token']) && !empty($cfg['wa_phone_number_id']);
    }
}
