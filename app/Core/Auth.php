<?php
namespace App\Core;

class Auth
{
    public static function user(): ?array { return $_SESSION['auth_user'] ?? null; }
    public static function check(): bool { return isset($_SESSION['auth_user']); }
    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['auth_user'] = $user;
    }
    public static function logout(): void { unset($_SESSION['auth_user']); }
}
