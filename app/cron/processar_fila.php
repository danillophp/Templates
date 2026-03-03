<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Models\WhatsAppDeliveryLogModel;
use App\Services\MessageQueueService;
use App\Services\WhatsAppService;

$fila = new MessageQueueService();
$wa = new WhatsAppService();
$logs = new WhatsAppDeliveryLogModel();

$rows = $fila->reservePending(10);
foreach ($rows as $row) {
    $id = (int)$row['id'];
    $solicitacaoId = isset($row['solicitacao_id']) ? (int)$row['solicitacao_id'] : null;
    $payload = json_decode((string)$row['payload_json'], true);

    if (!is_array($payload) || empty($payload['mensagem'])) {
        $fila->markError($id, 'Payload inválido.');
        continue;
    }

    $to = (string)($payload['to'] ?? $row['destino'] ?? '');
    $message = (string)$payload['mensagem'];
    $evento = (string)($payload['evento'] ?? 'teste');

    $logId = $logs->create([
        'solicitacao_id' => $solicitacaoId,
        'evento' => $evento,
        'destino' => $to,
        'mensagem' => $message,
        'canal' => 'cloud_api',
        'status' => 'queued',
        'tentativas' => (int)($row['tentativas'] ?? 0),
    ]);

    if (!$wa->isConfigured()) {
        $link = $wa->buildFallbackLink($to, $message);
        $fila->markManual($id, 'Cloud API não configurada. Envio manual necessário: ' . $link);
        $logs->updateById($logId, [
            'canal' => 'fallback_wa_me',
            'status' => 'manual',
            'erro' => 'Cloud API não configurada',
            'response_body' => $link,
            'tentativas' => (int)($row['tentativas'] ?? 0) + 1,
            'http_status' => null,
        ]);
        continue;
    }

    $result = $wa->sendText($to, $message, ['evento' => $evento, 'solicitacao_id' => $solicitacaoId]);
    if ($result['success']) {
        $fila->markSent($id);
        $logs->updateById($logId, [
            'canal' => 'cloud_api',
            'status' => 'sent',
            'http_status' => $result['http_status'],
            'response_body' => (string)($result['response'] ?? ''),
            'erro' => null,
            'tentativas' => (int)($row['tentativas'] ?? 0) + 1,
        ]);
        continue;
    }

    $fila->markError($id, (string)($result['error'] ?? 'Falha no envio'));
    $logs->updateById($logId, [
        'canal' => 'cloud_api',
        'status' => 'failed',
        'http_status' => $result['http_status'] ?? null,
        'response_body' => (string)($result['response'] ?? ''),
        'erro' => (string)($result['error'] ?? 'Falha no envio'),
        'tentativas' => (int)($row['tentativas'] ?? 0) + 1,
    ]);
}

echo 'Fila processada: ' . count($rows) . PHP_EOL;
