<?php

declare(strict_types=1);

namespace App\Core;

class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $viewPath = __DIR__ . '/../Views/' . $view . '.php';
        require __DIR__ . '/../Views/layouts/header.php';
        require $viewPath;
        require __DIR__ . '/../Views/layouts/footer.php';
    }

    protected function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function redirect(string $path): void
    {
        $location = preg_match('#^https?://#i', $path) ? $path : APP_BASE_PATH . $path;
        header('Location: ' . $location);
        exit;
    }
}
