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

    public static function is(string $role): bool
    {
        return ($_SESSION['user']['tipo'] ?? '') === $role;
    }

    public static function tenantId(): ?int
    {
        return $_SESSION['user']['tenant_id'] ?? null;
    }

    public static function login(array $user): void
    {
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'tenant_id' => $user['tenant_id'] !== null ? (int)$user['tenant_id'] : null,
            'nome' => $user['nome'],
            'tipo' => $user['tipo'],
            'email' => $user['email'],
        ];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }
}
