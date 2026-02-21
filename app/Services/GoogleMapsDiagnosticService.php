<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class GoogleMapsDiagnosticService
{
    public function logError(string $code, string $message, string $domain, string $apiKey): void
    {
        $stmt = Database::connection()->prepare('INSERT INTO google_maps_diagnostico (erro_codigo, erro_mensagem, dominio_detectado, ip_servidor, api_key_usada, criado_em)
            VALUES (:erro_codigo, :erro_mensagem, :dominio_detectado, :ip_servidor, :api_key_usada, NOW())');
        $stmt->execute([
            'erro_codigo' => mb_substr($code, 0, 80),
            'erro_mensagem' => mb_substr($message, 0, 1000),
            'dominio_detectado' => mb_substr($domain, 0, 180),
            'ip_servidor' => (string)($_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname() ?: 'localhost')),
            'api_key_usada' => $this->maskKey($apiKey),
        ]);
    }

    public function testApiKey(string $apiKey, string $host): array
    {
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?address=Santo+Antonio+do+Descoberto+GO&key=' . rawurlencode($apiKey);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error !== '') {
            $this->logError('CURL_ERROR', $error, $host, $apiKey);
            return ['ok' => false, 'status' => 'CURL_ERROR', 'message' => $error];
        }

        $json = json_decode((string)$response, true);
        if (!is_array($json)) {
            $this->logError('INVALID_JSON', 'Resposta inválida da API Google', $host, $apiKey);
            return ['ok' => false, 'status' => 'INVALID_JSON', 'message' => 'Resposta inválida'];
        }

        $status = (string)($json['status'] ?? 'UNKNOWN');
        $errorMsg = (string)($json['error_message'] ?? '');
        $allowed = array_filter(array_map('trim', explode(',', (string)(GOOGLE_MAPS_ALLOWED_HOSTS ?? ''))));
        $domainOk = empty($allowed) ? true : in_array($host, $allowed, true);

        if (!$domainOk) {
            $this->logError('DOMAIN_NOT_ALLOWED', 'Domínio detectado não está na lista permitida', $host, $apiKey);
        }

        if ($status !== 'OK') {
            $code = $status;
            if (stripos($errorMsg, 'billing') !== false || stripos($errorMsg, 'not authorized') !== false) {
                $code = 'BILLING_DESATIVADO';
            }
            $this->logError($code, $errorMsg !== '' ? $errorMsg : 'Falha no Geocoding', $host, $apiKey);
            return ['ok' => false, 'status' => $code, 'message' => $errorMsg !== '' ? $errorMsg : $status, 'http' => $http, 'domain_ok' => $domainOk];
        }

        return ['ok' => true, 'status' => 'OK', 'message' => 'API key e Geocoding funcionando', 'http' => $http, 'domain_ok' => $domainOk];
    }

    public function latest(): ?array
    {
        $stmt = Database::connection()->query('SELECT * FROM google_maps_diagnostico ORDER BY id DESC LIMIT 1');
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function maskKey(string $key): string
    {
        if ($key === '') {
            return '';
        }
        return substr($key, 0, 6) . str_repeat('*', max(0, strlen($key) - 10)) . substr($key, -4);
    }
}
