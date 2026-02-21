<?php
namespace App\Services;

class WhatsAppService
{
    public function send(string $phone, string $message): array
    {
        $api = config('WHATSAPP_API_URL');
        $token = config('WHATSAPP_API_TOKEN');
        if (!$api || !$token) {
            return ['manual' => true, 'link' => 'https://wa.me/' . preg_replace('/\D+/', '', $phone) . '?text=' . urlencode($message)];
        }
        $ch = curl_init($api);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode(['phone' => $phone, 'message' => $message]),
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            throw new \RuntimeException($error);
        }
        return ['manual' => false, 'response' => $response];
    }
}
