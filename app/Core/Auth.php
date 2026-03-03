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

    public static function can(string $permission): bool
    {
        $permissions = $_SESSION['user']['permissions'] ?? [];
        return in_array($permission, $permissions, true);
    }

    public static function requireRole(array $roles): void
    {
        if (!self::check() || !self::hasRole($roles)) {
            http_response_code(403);
            exit('Acesso negado');
        }
    }

    public static function requirePermission(string $permission): void
    {
        if (!self::check() || !self::can($permission)) {
            http_response_code(403);
            exit('Permissão insuficiente');
        }
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['full_name'],
            'role' => $user['role_name'],
            'username' => $user['username'],
            'permissions' => $user['permissions'] ?? [],
        ];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}
