<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Response;
use App\Models\AuditLog;

abstract class BaseController
{
    protected function requireAuth(): void
    {
        if (!Auth::check()) {
            Response::redirect('/admin/login');
        }
    }

    protected function requireAdmin(): void
    {
        $this->requireAuth();
        if (!Auth::isAdmin()) {
            http_response_code(403);
            exit('Acesso restrito ao administrador.');
        }
    }

    protected function requireCsrf(string $token): void
    {
        if (!Csrf::validate($token)) {
            http_response_code(419);
            exit('Token CSRF inválido.');
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

    protected function audit(string $action, array $meta = []): void
    {
        $user = Auth::user();
        (new AuditLog())->log((int) ($user['id'] ?? 0), $action, $meta);
    }
}
