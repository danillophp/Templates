<?php
namespace App\Core;

class View
{
    public static function render(string $view, array $data = []): void
    {
        extract($data);
        $viewFile = __DIR__ . '/../../resources/views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            http_response_code(404);
            require __DIR__ . '/../../resources/views/errors/404.php';
            return;
        }
        require $viewFile;
    }
}
