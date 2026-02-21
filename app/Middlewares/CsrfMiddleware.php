<?php
namespace App\Middlewares;

use App\Core\Csrf;

class CsrfMiddleware
{
    public static function handle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !Csrf::validate($_POST['_csrf'] ?? '')) {
            http_response_code(419);
            echo 'Token CSRF inválido.';
            exit;
        }
    }
}
