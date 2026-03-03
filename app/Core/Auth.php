<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function hasRole(array $roles): bool
    {
        $role = $_SESSION['user']['role'] ?? '';
        return in_array($role, $roles, true);
    }

    public static function requireRole(array $roles): void
    {
        if (!self::check() || !self::hasRole($roles)) {
            http_response_code(403);
            exit('Acesso negado');
        }
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['full_name'],
            'role' => $user['role'],
            'username' => $user['username'],
        ];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }
}
