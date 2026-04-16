<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function post(string $key, string $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $default));
}

function get(string $key, string $default = ''): string
{
    return trim((string) ($_GET[$key] ?? $default));
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function get_client_ip(): string
{
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $value = explode(',', (string) $_SERVER[$key])[0];
            return trim($value);
        }
    }
    return '0.0.0.0';
}

function normalize_token(string $token): string
{
    return strtoupper(preg_replace('/[^A-Z0-9]/i', '', $token));
}

function valid_whatsapp(string $phone): bool
{
    $digits = preg_replace('/\D+/', '', $phone);
    return strlen($digits) >= 10 && strlen($digits) <= 13;
}

function rate_limit_check(string $key, int $maxAttempts, int $windowSec): array
{
    $safeKey = preg_replace('/[^a-z0-9_\-]/i', '_', $key);
    $file = STORAGE_PATH . '/rate_limit/' . $safeKey . '.json';

    $now = time();
    $data = ['attempts' => []];

    if (is_file($file)) {
        $raw = file_get_contents($file);
        $decoded = json_decode((string) $raw, true);
        if (is_array($decoded) && isset($decoded['attempts']) && is_array($decoded['attempts'])) {
            $data = $decoded;
        }
    }

    $attempts = array_values(array_filter($data['attempts'], static fn($t) => ($now - (int) $t) < $windowSec));

    if (count($attempts) >= $maxAttempts) {
        return [
            'allowed' => false,
            'remaining' => 0,
            'retry_in' => $windowSec - ($now - (int) $attempts[0]),
        ];
    }

    $attempts[] = $now;
    file_put_contents($file, json_encode(['attempts' => $attempts]), LOCK_EX);

    return [
        'allowed' => true,
        'remaining' => max(0, $maxAttempts - count($attempts)),
        'retry_in' => 0,
    ];
}

function browser_name(string $ua): string
{
    $map = ['Edg' => 'Edge', 'Chrome' => 'Chrome', 'Firefox' => 'Firefox', 'Safari' => 'Safari'];
    foreach ($map as $needle => $name) {
        if (stripos($ua, $needle) !== false) {
            return $name;
        }
    }
    return 'Outro';
}

function device_name(string $ua): string
{
    if (preg_match('/mobile|android|iphone/i', $ua)) {
        return 'Mobile';
    }
    if (preg_match('/ipad|tablet/i', $ua)) {
        return 'Tablet';
    }
    return 'Desktop';
}

function get_config(string $key, ?string $default = null): ?string
{
    static $cfg = null;

    if ($cfg === null) {
        $cfg = [];
        $stmt = db()->query('SELECT chave, valor FROM configuracoes');
        foreach ($stmt as $row) {
            $cfg[$row['chave']] = $row['valor'];
        }
    }

    return $cfg[$key] ?? $default;
}

function maintenance_active(): bool
{
    return get_config('modo_manutencao', '0') === '1';
}

function save_access_log(string $rota): void
{
    $sql = 'INSERT INTO acessos (ip, rota, metodo, user_agent, fingerprint) VALUES (:ip,:rota,:metodo,:ua,:fp)';
    $stmt = db()->prepare($sql);
    $stmt->execute([
        ':ip' => get_client_ip(),
        ':rota' => $rota,
        ':metodo' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
        ':ua' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ':fp' => substr((string) ($_POST['fingerprint'] ?? $_GET['fingerprint'] ?? ''), 0, 64),
    ]);
}

function load_json_file(string $file, array $fallback = []): array
{
    if (!is_file($file)) {
        return $fallback;
    }
    $data = json_decode((string) file_get_contents($file), true);
    return is_array($data) ? $data : $fallback;
}

function write_json_file(string $file, array $data): bool
{
    return (bool) file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
}
