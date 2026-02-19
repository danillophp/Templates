<?php

declare(strict_types=1);

namespace App\Services;

final class WhatsAppService
{
    public function send(string $phone, string $message): array
    {
        $cleanPhone = preg_replace('/\D+/', '', $phone) ?? '';

        if (!WA_API_ENABLED || WA_TOKEN === '' || WA_PHONE_NUMBER_ID === '') {
            return [
                'mode' => 'fallback',
                'url' => 'https://wa.me/55' . $cleanPhone . '?text=' . rawurlencode($message),
            ];
        }

        $endpoint = sprintf('https://graph.facebook.com/%s/%s/messages', WA_API_VERSION, WA_PHONE_NUMBER_ID);
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => '55' . $cleanPhone,
            'type' => 'text',
            'text' => ['body' => $message],
        ];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . WA_TOKEN,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        return ['mode' => 'cloud_api', 'response' => $response, 'error' => $error];
    }
}
