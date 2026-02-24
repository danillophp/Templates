<?php

namespace App\Core;

class Whatsapp
{
    public static function notify(string $phone, string $message): array
    {
        $token = Config::get('app.whatsapp_token', '');
        $phoneNumberId = Config::get('app.whatsapp_phone_number_id', '');

        if ($token && $phoneNumberId) {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => preg_replace('/\D+/', '', $phone),
                'type' => 'text',
                'text' => ['preview_url' => false, 'body' => $message],
            ];
            $ch = curl_init('https://graph.facebook.com/v19.0/' . $phoneNumberId . '/messages');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 20,
            ]);
            $res = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);

            if ($status >= 200 && $status < 300) {
                return ['channel' => 'cloud_api', 'ok' => true, 'response' => $res];
            }
        }

        $number = preg_replace('/\D+/', '', $phone);
        return [
            'channel' => 'wa.me',
            'ok' => true,
            'url' => 'https://wa.me/55' . ltrim($number, '0') . '?text=' . rawurlencode($message),
        ];
    }
}
