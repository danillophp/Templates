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
            'pageTitle' => 'Login Admin',
            'error' => $_SESSION['flash_error'] ?? null,
            'csrfField' => $this->csrfField(),
        ], 'layouts/admin');

        unset($_SESSION['flash_error']);
    }

    public function login(Request $request): void
    {
        $this->requireCsrf((string) $request->input($this->csrfTokenName()));

        $email = filter_var((string) $request->input('email'), FILTER_VALIDATE_EMAIL);
        $password = (string) $request->input('password');

        if (!$email || $password === '' || !Auth::attempt($email, $password)) {
            $_SESSION['flash_error'] = 'Credenciais inválidas.';
            Response::redirect('/admin/login');
        }

        Response::redirect('/admin');
    }

    public function logout(Request $request): void
    {
        $this->requireAuth();
        $this->requireCsrf((string) $request->input($this->csrfTokenName()));
        Auth::logout();
        Response::redirect('/admin/login');
    }
}
