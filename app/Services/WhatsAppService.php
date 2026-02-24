<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\WhatsAppConfigModel;

final class WhatsAppService
{
    private WhatsAppConfigModel $configModel;

    public function __construct()
    {
        $this->configModel = new WhatsAppConfigModel();
    }

    public function isConfigured(): bool
    {
        $cfg = $this->configModel->getActive();
        return is_array($cfg)
            && !empty($cfg['ativo'])
            && trim((string)($cfg['phone_number_id'] ?? '')) !== ''
            && trim((string)($cfg['access_token'] ?? '')) !== '';
    }

    public function getMaskedConfig(): ?array
    {
        $cfg = $this->configModel->getLatest();
        if (!$cfg) {
            return null;
        }
        $token = (string)($cfg['access_token'] ?? '');
        $cfg['access_token_masked'] = $token === '' ? '' : substr($token, 0, 6) . str_repeat('*', max(0, strlen($token) - 10)) . substr($token, -4);
        unset($cfg['access_token']);
        return $cfg;
    }

    public function saveConfig(array $data): void
    {
        $this->configModel->save($data);
    }

    public function sendText(string $toE164, string $message, array $meta = []): array
    {
        $cfg = $this->configModel->getActive();
        $to = preg_replace('/\D+/', '', $toE164) ?? '';
        if ($to === '') {
            return ['success' => false, 'http_status' => null, 'response' => null, 'error' => 'Destino inválido'];
        }

        if (!$cfg || empty($cfg['ativo']) || empty($cfg['phone_number_id']) || empty($cfg['access_token'])) {
            return ['success' => false, 'http_status' => null, 'response' => null, 'error' => 'WhatsApp Cloud API não configurada'];
        }

        $apiVersion = (string)($cfg['api_version'] ?? WA_API_VERSION);
        $endpoint = sprintf('https://graph.facebook.com/%s/%s/messages', $apiVersion, (string)$cfg['phone_number_id']);

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $message],
        ];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . (string)$cfg['access_token'],
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error !== '') {
            return ['success' => false, 'http_status' => $httpStatus ?: null, 'response' => $response, 'error' => $error];
        }

        if ($httpStatus < 200 || $httpStatus >= 300) {
            return ['success' => false, 'http_status' => $httpStatus, 'response' => $response, 'error' => 'HTTP ' . $httpStatus];
        }

        return ['success' => true, 'http_status' => $httpStatus, 'response' => $response, 'error' => null];
    }

    public function buildFallbackLink(string $toE164, string $message): string
    {
        $to = preg_replace('/\D+/', '', $toE164) ?? '';
        return 'https://wa.me/' . $to . '?text=' . rawurlencode($message);
    }
}
