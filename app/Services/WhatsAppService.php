<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class WhatsAppService
{
    public function sendMessage(int $tenantId, string $phone, string $message, ?string $template = null): array
    {
        $cleanPhone = preg_replace('/\D+/', '', $phone) ?? '';
        $config = $this->tenantWhatsAppConfig($tenantId);

        if (!$config || empty($config['wa_token']) || empty($config['wa_phone_number_id'])) {
            $fallback = ['mode' => 'fallback', 'ok' => false, 'url' => 'https://wa.me/55' . $cleanPhone . '?text=' . rawurlencode($message), 'error' => 'WhatsApp não configurado'];
            $this->logMessage($tenantId, $cleanPhone, 'FALLBACK', json_encode($fallback));
            return $fallback;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => '55' . $cleanPhone,
            'type' => $template ? 'template' : 'text',
        ];

        if ($template) {
            $payload['template'] = ['name' => $template, 'language' => ['code' => 'pt_BR']];
        } else {
            $payload['text'] = ['body' => $message];
        }

        $result = $this->retrySimples((string)$config['wa_token'], (string)$config['wa_phone_number_id'], $payload, 2);
        if (empty($result['error'])) {
            $this->logMessage($tenantId, $cleanPhone, 'ENVIADO', json_encode($result));
            return ['mode' => 'cloud_api', 'ok' => true, 'response' => $result['response']];
        }

        $fallback = ['mode' => 'fallback', 'ok' => false, 'url' => 'https://wa.me/55' . $cleanPhone . '?text=' . rawurlencode($message), 'error' => $result['error'] ?? 'Falha no envio'];
        $this->logMessage($tenantId, $cleanPhone, 'ERRO', json_encode($fallback));
        return $fallback;
    }

    public function retrySimples(string $token, string $phoneNumberId, array $payload, int $maxRetries = 2): array
    {
        $attempt = 0;
        $result = ['response' => null, 'error' => 'Falha no envio'];

        do {
            $attempt++;
            $result = $this->callCloudApi($token, $phoneNumberId, $payload);
            if (empty($result['error'])) {
                return $result;
            }
        } while ($attempt <= $maxRetries);

        return $result;
    }

    public function logMessage(int $tenantId, string $telefone, string $status, string $resposta): void
    {
        $stmt = Database::connection()->prepare('INSERT INTO notificacoes (tenant_id, canal, destino, status, resposta, criado_em) VALUES (:tenant_id, "whatsapp", :destino, :status, :resposta, NOW())');
        $stmt->execute([
            'tenant_id' => $tenantId,
            'destino' => $telefone,
            'status' => $status,
            'resposta' => $resposta,
        ]);
    }

    private function tenantWhatsAppConfig(int $tenantId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT wa_token, wa_phone_number_id FROM configuracoes WHERE tenant_id = :tenant_id LIMIT 1');
        $stmt->execute(['tenant_id' => $tenantId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function callCloudApi(string $token, string $phoneNumberId, array $payload): array
    {
        $endpoint = sprintf('https://graph.facebook.com/%s/%s/messages', WA_API_VERSION, $phoneNumberId);
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error !== '') {
            return ['response' => $response, 'error' => $error];
        }

        if ($status < 200 || $status >= 300) {
            return ['response' => $response, 'error' => 'HTTP ' . $status];
        }

        return ['response' => $response, 'error' => null];
    }
}
