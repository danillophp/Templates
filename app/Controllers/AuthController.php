<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Models\UserModel;
use App\Services\AuditService;
use App\Services\EmailService;

class AuthController
{
    private function consumeLoginAttempt(string $login): bool
    {
        $now = time();
        $_SESSION['login_rl'] ??= [];
        $_SESSION['login_rl'][$login] ??= ['count' => 0, 'reset_at' => $now + 900];
        if ($_SESSION['login_rl'][$login]['reset_at'] < $now) {
            $_SESSION['login_rl'][$login] = ['count' => 0, 'reset_at' => $now + 900];
        }
        if ($_SESSION['login_rl'][$login]['count'] >= 5) {
            return false;
        }
        $_SESSION['login_rl'][$login]['count']++;
        return true;
    }

    private function clearLoginAttempts(string $login): void
    {
        unset($_SESSION['login_rl'][$login]);
    }

    public function loginForm(): void { require __DIR__ . '/../../resources/views/auth/login.php'; }

    public function login(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) exit('CSRF inválido');

        $login = trim($_POST['login'] ?? '');
        if (!$this->consumeLoginAttempt($login)) {
            $_SESSION['error'] = 'Muitas tentativas. Aguarde 15 minutos.';
            header('Location: ' . $_ENV['APP_BASE_PATH'] . '/login');
            return;
        }

        $user = (new UserModel())->findByLogin($login);
        if (!$user || !password_verify($_POST['senha'] ?? '', $user['senha_hash'])) {
            $_SESSION['error'] = 'Credenciais inválidas';
            header('Location: ' . $_ENV['APP_BASE_PATH'] . '/login');
            return;
        }

        $this->clearLoginAttempts($login);
        Auth::login(['id' => $user['id'], 'nome' => $user['nome'], 'role' => $user['role']]);
        header('Location: ' . $_ENV['APP_BASE_PATH'] . '/admin/dashboard');
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: ' . $_ENV['APP_BASE_PATH'] . '/login');
    }

    public function forgotForm(): void { require __DIR__ . '/../../resources/views/auth/forgot_password.php'; }

    public function forgot(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) exit('CSRF inválido');
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        if (!$email) exit('E-mail inválido');
        $u = (new UserModel())->findByEmail($email);
        if ($u) {
            $temp = bin2hex(random_bytes(6)) . 'A!';
            (new UserModel())->updatePassword((int)$u['id'], password_hash($temp, PASSWORD_BCRYPT));
            (new EmailService())->send($email, 'Nova senha temporária', '<p>Senha temporária: <strong>' . $temp . '</strong></p>');
            (new AuditService())->log('reset_senha', 'usuarios', (int)$u['id']);
        }
        $_SESSION['ok'] = 'Se o e-mail existir, uma senha temporária foi enviada.';
        header('Location: ' . $_ENV['APP_BASE_PATH'] . '/forgot-password');
    }
}
