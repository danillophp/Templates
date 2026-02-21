<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Middlewares\RateLimitMiddleware;
use App\Models\LogModel;
use App\Models\User;
use App\Services\PasswordRecoveryService;
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

            $identifier = trim((string)($_POST['identifier'] ?? ''));
            $senha = (string)($_POST['senha'] ?? '');
            if ($identifier === '' || $senha === '') {
                $this->view('auth/login', ['error' => 'Informe usuário/e-mail e senha.']);
                return;
            }

            $rateKey = 'login_' . md5($identifier . '|' . (string)$tenantId);
            $rate = RateLimitMiddleware::check($rateKey);
            if (!$rate['allowed']) {
                $this->view('auth/login', ['error' => 'Acesso bloqueado temporariamente. Tente em ' . $rate['retry_after'] . ' segundos.']);
                return;
            }

            $user = (new User())->findByIdentifier($identifier, $tenantId);
            if ($user && password_verify($senha, (string)$user['senha'])) {
                if ($user['tipo'] === 'admin') {
                    $cfg = TenantService::config($tenantId);
                    if (empty($cfg['wa_token']) || empty($cfg['wa_phone_number_id'])) {
                        $this->view('auth/login', ['error' => 'Conecte o WhatsApp oficial da prefeitura antes de acessar o painel administrativo.']);
                        return;
                    }
                }

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
            $this->view('auth/login', ['error' => 'Usuário/e-mail ou senha inválidos.']);
            return;
        }

        $this->view('auth/login');
    }

    public function forgot(): void
    {
        $tenantId = TenantService::tenantId();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=auth/login');
        }

        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $this->view('auth/login', ['error' => 'Token inválido.']);
            return;
        }

        $email = (string)($_POST['email'] ?? '');
        $result = (new PasswordRecoveryService())->recover($tenantId, $email);
        $this->view('auth/login', [$result['ok'] ? 'success' : 'error' => $result['message']]);
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/?r=auth/login');
    }
}
