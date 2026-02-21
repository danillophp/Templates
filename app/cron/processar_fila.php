<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Services\EmailService;
use App\Services\FilaMensagemService;
use App\Services\WhatsAppService;

$fila = new FilaMensagemService();
$wa = new WhatsAppService();
$email = new EmailService();

$rows = $fila->reservePending(30);
foreach ($rows as $row) {
    $id = (int)$row['id'];
    $tenantId = (int)$row['tenant_id'];
    $payload = json_decode((string)$row['payload_json'], true);

    if (!is_array($payload) || empty($payload['mensagem'])) {
        $fila->markError($id, 'Payload inválido.');
        continue;
    }

    try {
        $resp = $wa->sendMessage($tenantId, (string)$row['telefone_destino'], (string)$payload['mensagem']);
        if (!($resp['ok'] ?? false)) {
            $erro = (string)($resp['error'] ?? 'Falha de envio');
            if (!empty($resp['url'])) {
                $erro .= ' | Envio manual necessário: ' . $resp['url'];
            }
            $fila->markError($id, $erro);
            continue;
        }

        if (!empty($payload['email'])) {
            $email->sendReceipt($tenantId, (string)$payload['email'], [
                'nome' => (string)($payload['nome'] ?? ''),
                'endereco' => (string)($payload['endereco'] ?? ''),
                'data_solicitada' => (string)($payload['data_solicitada'] ?? ''),
                'telefone' => (string)($payload['telefone'] ?? ''),
                'email' => (string)$payload['email'],
                'protocolo' => (string)($payload['protocolo'] ?? ''),
                'status' => (string)($payload['status'] ?? ''),
            ]);
        }

        $fila->markSent($id);
    } catch (Throwable $e) {
        $fila->markError($id, $e->getMessage());
    }
}

echo 'Fila processada: ' . count($rows) . PHP_EOL;
