<?php
declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    define('APP_BOOTSTRAPPED', true);

    define('APP_NAME', 'Sistema de Votação Municipal');
    define('APP_ENV', 'production');
    define('APP_DEBUG', false);
    define('APP_URL', 'https://seudominio.com.br');
    define('APP_BASE_PATH', '/votacao');

    define('BASE_DIR', __DIR__);
    define('STORAGE_PATH', BASE_DIR . '/storage');
    define('RESULTADOS_JSON', STORAGE_PATH . '/resultados.json');
    define('CONFIG_CACHE_JSON', STORAGE_PATH . '/config_cache.json');
    define('CACHE_TTL', 120);

    date_default_timezone_set('America/Sao_Paulo');

    @ini_set('session.cookie_httponly', '1');
    @ini_set('session.use_strict_mode', '1');
    @ini_set('session.cookie_samesite', 'Lax');
    @ini_set('session.gc_maxlifetime', '7200');
    session_name('votacao_session');
    session_save_path(STORAGE_PATH . '/sessions');

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    foreach ([
        STORAGE_PATH,
        STORAGE_PATH . '/logs',
        STORAGE_PATH . '/rate_limit',
        STORAGE_PATH . '/sessions'
    ] as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }

    define('DB_HOST', 'localhost');
    define('DB_NAME', 'votacao_db');
    define('DB_USER', 'votacao_user');
    define('DB_PASS', 'trocar_senha_forte');

    function db(): PDO
    {
        static $pdo = null;

        if ($pdo === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        }

        return $pdo;
    }

    function app_path(string $path = ''): string
    {
        return rtrim(APP_BASE_PATH, '/') . '/' . ltrim($path, '/');
    }

    function app_url(string $path = ''): string
    {
        return rtrim(APP_URL, '/') . app_path($path);
    }

    function json_response(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    function app_log(string $channel, string $message, array $context = []): void
    {
        $file = STORAGE_PATH . '/logs/' . preg_replace('/[^a-z0-9_\-]/i', '', $channel) . '.log';
        $line = sprintf(
            "[%s] %s %s\n",
            date('Y-m-d H:i:s'),
            $message,
            $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''
        );
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }
}
