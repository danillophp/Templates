<?php
namespace App\Core;

use App\Models\UserModel;

class Auth
{
    public static function check(): bool
    {
        return !empty($_SESSION['admin_user']);
    }

    public static function user(): ?array
    {
        return $_SESSION['admin_user'] ?? null;
    }

    public static function attempt(string $login, string $password): bool
    {
        $user = (new UserModel())->findByLogin($login);
        if (!$user || !$user['ativo']) {
            return false;
        }
        if (!password_verify($password, $user['senha_hash'])) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['admin_user'] = [
            'id' => $user['id'],
            'nome' => $user['nome'],
            'usuario' => $user['usuario'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
        return true;
    }

    public static function logout(): void
    {
        unset($_SESSION['admin_user']);
    }
}
