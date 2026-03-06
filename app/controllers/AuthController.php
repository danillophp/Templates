<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        $this->view('auth/login', [
            'title' => 'Login',
        ], 'layouts/auth');
    }

    public function login(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            http_response_code(419);
            exit('Token CSRF inválido.');
        }

        $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
        $password = (string) ($_POST['password'] ?? '');

        $_SESSION['_old'] = ['email' => (string) ($_POST['email'] ?? '')];

        if (!$email || $password === '') {
            flash('error', 'E-mail e senha são obrigatórios.');
            $this->redirect('/login');
        }

        $dbConfig = require __DIR__ . '/../config/database.php';
        $userModel = new User(Database::getConnection($dbConfig));
        $user = $userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            flash('error', 'Credenciais inválidas.');
            $this->redirect('/login');
        }

        unset($_SESSION['_old']);
        Auth::login($user);
        flash('success', 'Login efetuado com sucesso!');

        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            http_response_code(419);
            exit('Token CSRF inválido.');
        }

        Auth::logout();
        session_start();
        flash('success', 'Sessão encerrada com sucesso.');
        $this->redirect('/login');
    }
}
