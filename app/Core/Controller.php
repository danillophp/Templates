<?php

declare(strict_types=1);

namespace App\Core;

class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'layouts/main'): void
    {
        extract($data, EXTR_SKIP);
        $viewPath = __DIR__ . '/../Views/' . $view . '.php';
        require __DIR__ . '/../Views/' . $layout . '.php';
    }

    protected function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . APP_BASE_PATH . '/index.php?r=' . ltrim($path, '/'));
        exit;
    }

    protected function applyHttpCacheHeaders(string $etag, string $lastModified): bool
    {
        header('ETag: "' . $etag . '"');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', strtotime($lastModified)) . ' GMT');
        header('Cache-Control: public, max-age=60, stale-while-revalidate=120');

        $ifNoneMatch = trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? ''), '"');
        $ifModifiedSince = (string) ($_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '');
        $imsTime = $ifModifiedSince ? strtotime($ifModifiedSince) : false;

        if ($ifNoneMatch === $etag || ($imsTime !== false && $imsTime >= strtotime($lastModified))) {
            http_response_code(304);
            return true;
        }

        return false;
    }
}
