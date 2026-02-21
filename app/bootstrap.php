<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$appConfig = require __DIR__ . '/../config/app.php';
date_default_timezone_set($appConfig['TIMEZONE']);

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }
    $path = __DIR__ . '/' . str_replace('App\\', '', $class) . '.php';
    $path = str_replace('\\', '/', $path);
    if (file_exists($path)) {
        require_once $path;
    }
});

function config(string $key, $default = null)
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require __DIR__ . '/../config/app.php';
    }
    return $cfg[$key] ?? $default;
}

function base_path(string $path = ''): string
{
    return rtrim(config('APP_BASE_PATH', '/catatreco'), '/') . '/' . ltrim($path, '/');
}

function asset_url(string $path): string
{
    return base_path('resources/assets/' . ltrim($path, '/'));
}
