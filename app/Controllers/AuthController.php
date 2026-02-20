<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Middlewares\RateLimitMiddleware;
use App\Models\LogModel;
use App\Models\User;
use App\Services\TenantService;

final class AuthController extends Controller
{
    public function login(): void
    {
        $tenantId = TenantService::tenantId();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['_csrf'] ?? null)) {
                $this->view('auth/login', ['error' => 'Token inválido.']);
                return;
            }

            $email = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_SANITIZE_EMAIL);
            $senha = (string)($_POST['senha'] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->view('auth/login', ['error' => 'Informe um e-mail válido.']);
                return;
            }
            $rateKey = 'login_' . md5($email . '|' . (string)$tenantId);
            $rate = RateLimitMiddleware::check($rateKey);

            if (!$rate['allowed']) {
                $this->view('auth/login', ['error' => 'Acesso bloqueado temporariamente. Tente em ' . $rate['retry_after'] . ' segundos.']);
                return;
            }

            $user = (new User())->findByEmail($email, $tenantId);
            if ($user && password_verify($senha, (string)$user['senha'])) {
                RateLimitMiddleware::clear($rateKey);
                Auth::login($user);
                if ($tenantId) {
                    (new LogModel())->register($tenantId, null, (int)$user['id'], 'LOGIN', 'Login efetuado.');
                }
                if ($user['tipo'] === 'super_admin') {
                    $this->redirect('/?r=superadmin/dashboard');
                }
                $this->redirect($user['tipo'] === 'admin' ? '/?r=admin/dashboard' : '/?r=employee/dashboard');
                return;
            }

            RateLimitMiddleware::fail($rateKey);
            $this->view('auth/login', ['error' => 'Usuário ou senha inválidos.']);
            return;
        }

        $this->view('auth/login');
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/?r=auth/login');
    }
}
