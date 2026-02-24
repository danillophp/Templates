<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;

class AuthController extends BaseController
{
    public function showLogin(Request $request): void
    {
        if (Auth::check()) {
            Response::redirect('/admin');
        }

        View::render('admin/auth/login', [
            'pageTitle' => 'Login - Cata Treco',
            'error' => $_SESSION['flash_error'] ?? null,
            'csrfField' => $this->csrfField(),
        ], 'layouts/admin');
        unset($_SESSION['flash_error']);
    }

    public function login(Request $request): void
    {
        $this->requireCsrf((string) $request->input($this->csrfTokenName()));

        $username = trim((string) $request->input('username'));
        $password = (string) $request->input('password');

        if ($username === '' || $password === '' || !Auth::attempt($username, $password)) {
            $_SESSION['flash_error'] = 'Usuário ou senha inválidos.';
            Response::redirect('/admin/login');
        }

        $this->audit('login_sucesso', ['username' => $username]);
        Response::redirect('/admin');
    }

    public function logout(Request $request): void
    {
        $this->requireAuth();
        $this->requireCsrf((string) $request->input($this->csrfTokenName()));
        $this->audit('logout');
        Auth::logout();
        Response::redirect('/admin/login');
    }
}
