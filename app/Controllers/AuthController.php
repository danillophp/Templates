<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Security;
use App\Models\User;
use App\Services\TotpService;

final class AuthController extends Controller
{
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->view('auth/login', ['csrf' => Csrf::token(), 'step2' => isset($_SESSION['2fa_user'])]);
            return;
        }

        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $this->view('auth/login', ['error' => 'Token CSRF inválido', 'csrf' => Csrf::token()]);
            return;
        }

        if (isset($_SESSION['2fa_user'])) {
            $this->complete2fa();
            return;
        }

        $username = Security::cleanString($_POST['username'] ?? '', 80);
        $password = (string) ($_POST['password'] ?? '');
        $ip = Security::ip();
        $userModel = new User();

        $failCount = $userModel->failedAttemptsInWindow($username, $ip, 15);
        if ($failCount >= 5) {
            $this->view('auth/login', ['error' => 'Muitas tentativas. Aguarde e tente novamente.', 'csrf' => Csrf::token()]);
            return;
        }

        $user = $userModel->findAuthUser($username);
        if (!$user || !empty($user['locked_until']) && strtotime((string) $user['locked_until']) > time() || !password_verify($password, $user['password_hash'])) {
            $userModel->registerLoginAttempt($username, $ip, false);
            if ($user) {
                $delay = min(1800, max(30, (int) pow(2, min(10, (int) $user['failed_login_count'] + 1))));
                $userModel->lockUser((int) $user['id'], $delay);
            }
            $this->view('auth/login', ['error' => 'Credenciais inválidas', 'csrf' => Csrf::token()]);
            return;
        }

        if ((int) $user['totp_enabled'] === 1 && !empty($user['totp_secret'])) {
            $_SESSION['2fa_user'] = $user;
            $this->view('auth/login', ['csrf' => Csrf::token(), 'step2' => true]);
            return;
        }

        $userModel->registerLoginAttempt($username, $ip, true);
        $userModel->clearFailures((int) $user['id']);
        Auth::login($user);
        $this->redirect('admin/dashboard');
    }

    private function complete2fa(): void
    {
        $code = (string) ($_POST['totp_code'] ?? '');
        $user = $_SESSION['2fa_user'];

        if (!(new TotpService())->verify((string) $user['totp_secret'], $code)) {
            $this->view('auth/login', ['error' => 'Código 2FA inválido', 'csrf' => Csrf::token(), 'step2' => true]);
            return;
        }

        (new User())->registerLoginAttempt((string) $user['username'], Security::ip(), true);
        (new User())->clearFailures((int) $user['id']);
        unset($_SESSION['2fa_user']);
        Auth::login($user);
        $this->redirect('admin/dashboard');
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('auth/login');
    }
}
