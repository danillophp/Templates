<?php

namespace App\Core;

class Response
{
    public static function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function redirect(string $path): void
    {
        $basePath = Config::get('app.base_path', '');
        if ($basePath && str_starts_with($path, '/')) {
            $path = rtrim($basePath, '/') . $path;
        }
        header('Location: ' . $path);
        exit;
    }
}
