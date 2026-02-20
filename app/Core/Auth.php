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

    public static function login(array $user): void
    {
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
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
