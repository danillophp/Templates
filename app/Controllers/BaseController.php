<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Response;

abstract class BaseController
{
    protected function requireAuth(): void
    {
        if (!Auth::check()) {
            Response::redirect('/admin/login');
        }
    }

    protected function requireCsrf(string $token): void
    {
        if (!Csrf::validate($token)) {
            http_response_code(419);
            exit('Token CSRF inválido. Recarregue a página e tente novamente.');
        }
    }

    protected function csrfField(): string
    {
        $name = Config::get('app.csrf_token_name');
        $token = Csrf::token();
        return sprintf('<input type="hidden" name="%s" value="%s">', $name, htmlspecialchars($token, ENT_QUOTES));
    }

    protected function csrfTokenName(): string
    {
        return Config::get('app.csrf_token_name');
    }
}
