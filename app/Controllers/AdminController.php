<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\LogModel;
use App\Models\PointModel;
use App\Models\RequestModel;
use App\Models\WhatsAppDeliveryLogModel;
use App\Services\AuditoriaService;
use App\Services\MessageQueueService;
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
        $report = (new MessageQueueService())->report($tenantId);
        $waService = new WhatsAppService();

        $this->view('admin/dashboard', [
            'summary' => (new RequestModel())->summary($tenantId),
            'points' => (new PointModel())->active($tenantId),
            'csrf' => Csrf::token(),
            'today' => $today,
            'whatsAppReady' => $waService->isConfigured(),
            'waConfig' => $waService->getMaskedConfig(),
            'commReport' => $report,
        ]);
    }

    public function whatsappSettings(): void
    {
        $this->guard();
        $service = new WhatsAppService();
        $this->view('admin/whatsapp-settings', [
            'csrf' => Csrf::token(),
            'config' => $service->getMaskedConfig(),
            'enabled' => $service->isConfigured(),
        ]);
    }

    public function saveWhatsAppConfig(): void
    {
        $this->guard();
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $this->json(['ok' => false, 'message' => 'Token inválido.'], 422);
            return;
        }

        $active = (int)($_POST['ativo'] ?? 0) === 1;
        $phoneNumberId = trim((string)($_POST['phone_number_id'] ?? ''));
        $token = trim((string)($_POST['access_token'] ?? ''));
        $apiVersion = trim((string)($_POST['api_version'] ?? WA_API_VERSION));
        $businessId = trim((string)($_POST['business_account_id'] ?? ''));
        $senderName = trim((string)($_POST['sender_name'] ?? ''));

        if ($active && ($phoneNumberId === '' || $token === '')) {
            $this->json(['ok' => false, 'message' => 'Para ativar, informe phone_number_id e access_token.'], 422);
            return;
        }

        (new WhatsAppService())->saveConfig([
            'ativo' => $active,
            'phone_number_id' => $phoneNumberId,
            'access_token' => $token,
            'api_version' => $apiVersion !== '' ? $apiVersion : WA_API_VERSION,
            'business_account_id' => $businessId,
            'sender_name' => $senderName,
        ]);

        $this->json(['ok' => true, 'message' => 'Configuração de WhatsApp Cloud API salva com sucesso.']);
    }

    public function whatsappTest(): void
    {
        $this->guard();
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $this->json(['ok' => false, 'message' => 'Token inválido.'], 422);
            return;
        }

        $to = preg_replace('/\D+/', '', (string)($_POST['to'] ?? '')) ?? '';
        if ($to === '') {
            $this->json(['ok' => false, 'message' => 'Informe telefone destino.'], 422);
            return;
        }

        $message = 'Teste WhatsApp Cloud API - Cata Treco em ' . date('d/m/Y H:i');
        $service = new WhatsAppService();
        $result = $service->sendText($to, $message, ['evento' => 'teste']);

        $logs = new WhatsAppDeliveryLogModel();
        $logs->create([
            'solicitacao_id' => null,
            'evento' => 'teste',
            'destino' => $to,
            'mensagem' => $message,
            'canal' => $result['success'] ? 'cloud_api' : 'fallback_wa_me',
            'status' => $result['success'] ? 'sent' : 'failed',
            'http_status' => $result['http_status'],
            'response_body' => (string)($result['response'] ?? ''),
            'erro' => $result['error'] ? (string)$result['error'] : null,
            'tentativas' => 1,
        ]);

        if (!$result['success']) {
            $link = $service->buildFallbackLink($to, $message);
            $this->json(['ok' => false, 'message' => 'Falha no envio automático.', 'fallback_link' => $link, 'detail' => $result['error']]);
            return;
        }

        $this->json(['ok' => true, 'message' => 'Mensagem de teste enviada com sucesso via Cloud API.']);
    }

    public function whatsappLogs(): void
    {
        $this->guard();
        $filters = [
            'from' => (string)($_GET['from'] ?? ''),
            'to' => (string)($_GET['to'] ?? ''),
            'destino' => (string)($_GET['destino'] ?? ''),
            'status' => (string)($_GET['status'] ?? ''),
            'evento' => (string)($_GET['evento'] ?? ''),
        ];
        $rows = (new WhatsAppDeliveryLogModel())->list($filters);
        $this->view('admin/whatsapp-logs', ['rows' => $rows, 'filters' => $filters]);
    }

    public function whatsappLogsCsv(): void
    {
        $this->guard();
        $filters = [
            'from' => (string)($_GET['from'] ?? ''),
            'to' => (string)($_GET['to'] ?? ''),
            'destino' => (string)($_GET['destino'] ?? ''),
            'status' => (string)($_GET['status'] ?? ''),
            'evento' => (string)($_GET['evento'] ?? ''),
        ];
        $rows = (new WhatsAppDeliveryLogModel())->list($filters);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=whatsapp_logs.csv');
        $fp = fopen('php://output', 'wb');
        fputcsv($fp, ['Data', 'Evento', 'Destino', 'Canal', 'Status', 'HTTP', 'Erro']);
        foreach ($rows as $row) {
            fputcsv($fp, [$row['created_at'], $row['evento'], $row['destino'], $row['canal'], $row['status'], $row['http_status'], $row['erro']]);
        }
        fclose($fp);
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
        (new AuditoriaService())->registrar((int)Auth::user()['id'], 'CRIAR', 'pontos_mapa', 0, null, ['titulo' => $titulo, 'lat' => $latitude, 'lng' => $longitude]);
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

        $this->view('admin/request-detail', ['request' => $request, 'csrf' => Csrf::token()]);
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

        $idsRaw = trim((string)($_POST['request_ids'] ?? $_POST['request_id'] ?? ''));
        $ids = array_values(array_filter(array_map('intval', preg_split('/\s*,\s*/', $idsRaw) ?: [])));
        if (empty($ids)) {
            $this->json(['ok' => false, 'message' => 'Nenhuma solicitação selecionada.'], 422);
            return;
        }

        $action = (string)($_POST['action'] ?? '');
        $date = !empty($_POST['pickup_datetime']) ? trim((string)$_POST['pickup_datetime']) : null;
        $model = new RequestModel();
        $fila = new MessageQueueService();
        $audit = new AuditoriaService();
        $logs = new LogModel();
        $waLogs = new WhatsAppDeliveryLogModel();
        $whatsAppMessages = [];

        foreach ($ids as $id) {
            $before = $model->find($id, $tenantId);
            if (!$before) {
                continue;
            }

            $statusTexto = 'ATUALIZADA';
            $evento = 'alterado_data';
            if ($action === 'approve') {
                $model->updateStatus($id, $tenantId, 'APROVADO');
                $statusTexto = 'APROVADA';
                $evento = 'aprovado';
            } elseif ($action === 'reject') {
                $model->updateStatus($id, $tenantId, 'RECUSADO');
                $statusTexto = 'RECUSADA';
                $evento = 'recusado';
            } elseif ($action === 'schedule') {
                $requestedDate = $date ? \DateTimeImmutable::createFromFormat('Y-m-d', $date) : false;
                if (!$requestedDate || $requestedDate->format('Y-m-d') !== $date || $requestedDate < new \DateTimeImmutable('today')) {
                    $this->json(['ok' => false, 'message' => 'Data inválida. Use data atual ou futura.'], 422);
                    return;
                }
                $model->updateStatus($id, $tenantId, 'ALTERADO', $requestedDate->format('Y-m-d'));
                $statusTexto = 'ALTERADA';
                $evento = 'alterado_data';
            } elseif ($action === 'delete') {
                $model->delete($id, $tenantId);
                $audit->registrar((int)Auth::user()['id'], 'EXCLUIR', 'solicitacoes', $id, $before, null);
                continue;
            } else {
                $this->json(['ok' => false, 'message' => 'Ação inválida.'], 422);
                return;
            }

            $after = $model->find($id, $tenantId);
            $audit->registrar((int)Auth::user()['id'], strtoupper($action), 'solicitacoes', $id, $before, $after);

            $mensagem = sprintf('Olá, %s. Sua solicitação %s foi %s. Prefeitura Municipal.', $before['nome'], $before['protocolo'], $statusTexto);
            $telefone = preg_replace('/\D+/', '', (string)$before['telefone']) ?? '';
            $waLink = (new WhatsAppService())->buildFallbackLink('55' . $telefone, $mensagem);

            $fila->enqueue($tenantId, $id, '55' . $telefone, $evento, [
                'to' => '55' . $telefone,
                'mensagem' => $mensagem,
                'evento' => $evento,
                'solicitacao_id' => $id,
            ]);

            $waLogs->create([
                'solicitacao_id' => $id,
                'evento' => $evento,
                'destino' => '55' . $telefone,
                'mensagem' => $mensagem,
                'canal' => 'cloud_api',
                'status' => 'queued',
                'tentativas' => 0,
            ]);

            $whatsAppMessages[] = [
                'id' => $id,
                'nome' => (string)$before['nome'],
                'telefone' => '55' . $telefone,
                'mensagem' => $mensagem,
                'wa_link' => $waLink,
            ];
            $logs->register($tenantId, $id, (int)Auth::user()['id'], 'WHATSAPP_MENSAGEM_GERADA', 'Mensagem pronta para WhatsApp Web.');
        }

        $this->json([
            'ok' => true,
            'message' => 'Solicitações atualizadas. Mensagens enfileiradas para envio WhatsApp.',
            'whatsapp_messages' => $whatsAppMessages,
        ]);
    }

    public function dashboardApi(): void
    {
        $tenantId = $this->guard();
        $this->json(['ok' => true, 'data' => (new RequestModel())->chartByMonth($tenantId)]);
    }

    public function commReportApi(): void
    {
        $tenantId = $this->guard();
        $this->json(['ok' => true, 'data' => (new MessageQueueService())->report($tenantId)]);
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

    public function exportCommCsv(): void
    {
        $tenantId = $this->guard();
        $report = (new MessageQueueService())->report($tenantId);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=relatorio_comunicacao.csv');
        $fp = fopen('php://output', 'wb');
        fputcsv($fp, ['Enviadas', 'Erros']);
        fputcsv($fp, [$report['enviadas'], $report['erros']]);
        fputcsv($fp, []);
        fputcsv($fp, ['ID fila', 'Solicitação', 'Telefone', 'Tentativas', 'Erro', 'Última tentativa']);
        foreach ($report['falhas'] as $f) {
            fputcsv($fp, [$f['id'], $f['solicitacao_id'], $f['telefone_destino'], $f['tentativas'], $f['erro_mensagem'], $f['ultima_tentativa_em']]);
        }
        fclose($fp);
    }

    public function exportPdf(): void
    {
        $tenantId = $this->guard();
        $rows = (new RequestModel())->list($tenantId, ['date' => $_GET['date'] ?? (new \DateTimeImmutable('today'))->format('Y-m-d')]);
        $chartData = (string)($_POST['chart_image'] ?? '');
        $dir = STORAGE_PATH . '/reports/' . date('Y') . '/' . date('m');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $html = '<html><head><meta charset="UTF-8"><style>body{font-family:Arial,sans-serif}table{width:100%;border-collapse:collapse}th,td{border:1px solid #d5deea;padding:6px;font-size:12px}th{background:#eef5fb}</style></head><body>';
        $html .= '<h2>Relatório Cata Treco</h2>';
        if ($chartData !== '') {
            $html .= '<p><img alt="Gráfico" style="max-width:100%;height:auto" src="' . htmlspecialchars($chartData, ENT_QUOTES, 'UTF-8') . '"></p>';
        }
        $html .= '<table><tr><th>Protocolo</th><th>Nome</th><th>Email</th><th>Telefone</th><th>CEP</th><th>Bairro</th><th>Endereço</th><th>Data agendada</th><th>Status</th><th>Criado em</th><th>Atualizado em</th><th>Latitude</th><th>Longitude</th><th>Foto</th></tr>';
        foreach ($rows as $row) {
            $foto = (string)($row['foto'] ?? '');
            $fotoRef = $foto !== '' ? (APP_BASE_PATH . '/uploads/' . $foto) : '-';
            $html .= '<tr>'
                . '<td>' . htmlspecialchars((string)$row['protocolo']) . '</td>'
                . '<td>' . htmlspecialchars((string)$row['nome']) . '</td>'
                . '<td>' . htmlspecialchars((string)($row['email'] ?? '')) . '</td>'
                . '<td>' . htmlspecialchars((string)($row['telefone'] ?? '')) . '</td>'
                . '<td>' . htmlspecialchars((string)($row['cep'] ?? '')) . '</td>'
                . '<td>' . htmlspecialchars((string)($row['bairro'] ?? '')) . '</td>'
                . '<td>' . htmlspecialchars((string)$row['endereco']) . '</td>'
                . '<td>' . htmlspecialchars((string)$row['data_solicitada']) . '</td>'
                . '<td>' . htmlspecialchars((string)$row['status']) . '</td>'
                . '<td>' . htmlspecialchars((string)($row['criado_em'] ?? '')) . '</td>'
                . '<td>' . htmlspecialchars((string)($row['atualizado_em'] ?? '')) . '</td>'
                . '<td>' . htmlspecialchars((string)($row['latitude'] ?? '')) . '</td>'
                . '<td>' . htmlspecialchars((string)($row['longitude'] ?? '')) . '</td>'
                . '<td>' . htmlspecialchars($fotoRef) . '</td>'
                . '</tr>';
        }
        $html .= '</table></body></html>';

        if (class_exists('Dompdf\\Dompdf')) {
            $filePath = $dir . '/relatorio_' . date('Ymd_His') . '.pdf';
            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', true);
            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            file_put_contents($filePath, $dompdf->output());
        } else {
            $filePath = $dir . '/relatorio_' . date('Ymd_His') . '.html';
            file_put_contents($filePath, $html);
        }

        $relative = str_replace(STORAGE_PATH, APP_BASE_PATH . '/storage', $filePath);
        $this->json(['ok' => true, 'message' => 'Relatório gerado com sucesso.', 'file' => $relative]);
    }
}
