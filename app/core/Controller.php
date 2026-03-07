<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Controller base com helpers de renderização e redirecionamento.
 */
abstract class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'layouts/app'): void
    {
        extract($data, EXTR_SKIP);

        $viewFile = __DIR__ . '/../views/' . $view . '.php';
        $layoutFile = __DIR__ . '/../views/' . $layout . '.php';

        if (!file_exists($viewFile)) {
            http_response_code(404);
            exit('View não encontrada.');
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        require $layoutFile;
    }

    protected function redirect(string $path): void
    {
        redirect_to($path);
    }
}
