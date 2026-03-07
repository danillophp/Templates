<?php

declare(strict_types=1);

function app_config(?string $key = null, mixed $default = null): mixed
{
    $config = $GLOBALS['app_config'] ?? [];
    if ($key === null) {
        return $config;
    }

    $segments = explode('.', $key);
    $value = $config;

    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function input(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;
    $value = is_string($value) ? trim($value) : $default;

    return strip_tags($value);
}

function normalize_base_path(string $path): string
{
    $trimmed = '/' . trim($path, '/');
    return $trimmed === '/' ? '' : $trimmed;
}

function app_url(): string
{
    return rtrim((string) app_config('app.url', ''), '/');
}

function base_path(): string
{
    return normalize_base_path((string) app_config('app.base_path', ''));
}

function normalize_relative_path(string $path): string
{
    $path = trim($path);
    if ($path === '' || $path === '/') {
        return '';
    }

    return '/' . ltrim($path, '/');
}

function base_url(string $path = ''): string
{
    return app_url() . normalize_relative_path($path);
}

function public_url(string $path = ''): string
{
    return app_url() . normalize_relative_path($path);
}

function asset_url(string $path): string
{
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    $cleanPath = ltrim($path, '/');

    if (str_starts_with($cleanPath, 'assets/') || str_starts_with($cleanPath, 'uploads/')) {
        return public_url($cleanPath);
    }

    return public_url('assets/' . $cleanPath);
}

function redirect_to(string $path = ''): void
{
    header('Location: ' . base_url($path));
    exit;
}

function redirect(string $path): void
{
    redirect_to($path);
}

function old(string $key, string $default = ''): string
{
    $value = $_SESSION['_old'][$key] ?? $default;
    return e((string) $value);
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf_token(?string $token): bool
{
    return isset($_SESSION['_csrf_token']) && is_string($token) && hash_equals($_SESSION['_csrf_token'], $token);
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }

    $stored = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);

    return $stored;
}

function studio_settings(): array
{
    static $cached = null;
    if (is_array($cached)) {
        return $cached;
    }

    $fallback = [
        'nome_studio' => 'Studio Bruna Nayara',
        'telefone' => '(61) 99176-7582',
        'endereco' => 'Quadra 87 - Centro, Santo Antônio do Descoberto - GO, 72900-198',
        'instagram' => 'https://www.instagram.com/studiobrunanayaraa/',
        'facebook' => 'https://www.facebook.com/studiobrunanayaraa/',
        'link_localizacao' => '',
        'horario_funcionamento' => "segunda-feira: 08:00–19:00\nterça-feira: 08:00–19:00\nquarta-feira: 08:00–19:00\nquinta-feira: 08:00–19:00\nsexta-feira: 08:00–19:00\nsábado: 08:00–19:00\ndomingo: Fechado",
        'logo_path' => '',
        'ativar_convite_google' => 1,
        'texto_convite_google' => 'Gostou do nosso atendimento? Sua avaliação no Google é muito importante para o Studio Bruna Nayara.',
        'link_avaliacao_google' => '',
    ];

    try {
        $dbConfig = require __DIR__ . '/../config/database.php';
        $dsn = sprintf('%s:host=%s;port=%s;dbname=%s;charset=%s', $dbConfig['driver'], $dbConfig['host'], $dbConfig['port'], $dbConfig['database'], $dbConfig['charset']);
        $pdo = new \PDO($dsn, $dbConfig['username'], $dbConfig['password'], [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $stmt = $pdo->query('SELECT * FROM configuracoes_studio WHERE id = 1 LIMIT 1');
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $cached = is_array($row) ? array_merge($fallback, $row) : $fallback;
        return $cached;
    } catch (Throwable) {
        $cached = $fallback;
        return $cached;
    }
}

function studio_logo_url(?array $settings = null): ?string
{
    $settings = $settings ?? studio_settings();
    $logoPath = trim((string) ($settings['logo_path'] ?? ''));

    if ($logoPath === '') {
        return null;
    }

    return asset_url($logoPath);
}
