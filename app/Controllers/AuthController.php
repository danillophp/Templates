<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Csrf;
use App\Services\EmailService;
use App\Services\AuditService;
use App\Models\UserModel;

class AuthController extends Controller
{
    public function loginForm(): void { $this->view('auth/login', ['csrf' => Csrf::token()]); }

    public function login(): void
    {
        \App\Middlewares\CsrfMiddleware::handle();
        if (Auth::attempt($this->input('login'), $this->input('senha'))) {
            $this->redirect('/admin/dashboard');
        }
        $this->view('auth/login', ['error' => 'Credenciais inválidas', 'csrf' => Csrf::token()]);
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/login');
    }

    public function forgotForm(): void { $this->view('auth/forgot_password', ['csrf' => Csrf::token()]); }

    public function forgotSend(): void
    {
        \App\Middlewares\CsrfMiddleware::handle();
        $email = trim($this->input('email'));
        $temp = bin2hex(random_bytes(5)) . 'A!9';
        $ok = (new UserModel())->updatePasswordByEmail($email, password_hash($temp, PASSWORD_BCRYPT));
        (new AuditService())->log('reset_password', 'usuarios', null, null, ['email'=>$email,'sucesso'=>$ok]);
        if ($ok) {
            (new EmailService())->send($email, 'Nova senha temporária', 'Senha temporária: <b>' . htmlspecialchars($temp) . '</b>');
        }
        $this->view('auth/forgot_password', ['success' => 'Se o email existir, uma nova senha foi enviada.', 'csrf' => Csrf::token()]);
    }
}
