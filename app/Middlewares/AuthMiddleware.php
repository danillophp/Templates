<?php
namespace App\Middlewares;

use App\Core\Auth;

class AuthMiddleware
{
    public static function handle(): void
    {
        if (!Auth::check()) {
            header('Location: ' . ($_ENV['APP_BASE_PATH'] ?? '/catatreco') . '/login');
            exit;
        }
    }
}
