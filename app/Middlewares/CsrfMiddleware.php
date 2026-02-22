<?php
namespace App\Middlewares;

use App\Core\Csrf;

class CsrfMiddleware
{
    public static function verify(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !Csrf::validate($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            exit('CSRF token inválido.');
        }
    }
}
