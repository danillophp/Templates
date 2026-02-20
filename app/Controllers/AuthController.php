<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\LogModel;
use App\Models\User;

final class AuthController extends Controller
{
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['_csrf'] ?? null)) {
                $this->view('auth/login', ['error' => 'Token inválido.']);
                return;
            }

            $model = new User();
            $user = $model->findByEmail(trim((string)($_POST['email'] ?? '')));

            if ($user && password_verify((string)($_POST['senha'] ?? ''), (string)$user['senha'])) {
                Auth::login($user);
                (new LogModel())->register(null, (int)$user['id'], 'Login efetuado no sistema.');
                $this->redirect($user['tipo'] === 'admin' ? '/?r=admin/dashboard' : '/?r=employee/dashboard');
                return;
            }

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
