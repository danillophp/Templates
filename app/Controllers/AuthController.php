<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\User;

final class AuthController extends Controller
{
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->view('auth/login', ['csrf' => Csrf::token()]);
            return;
        }

        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $this->view('auth/login', ['error' => 'Token CSRF inválido', 'csrf' => Csrf::token()]);
            return;
        }

        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $user = (new User())->findByUsername($username);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->view('auth/login', ['error' => 'Credenciais inválidas', 'csrf' => Csrf::token()]);
            return;
        }

        Auth::login($user);
        $this->redirect('admin/dashboard');
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('auth/login');
    }
}
