<?php
namespace App\Models;

use App\Core\Database;

class UserModel
{
    public function findByLogin(string $login): ?array
    {
        $sql = 'SELECT * FROM usuarios WHERE (usuario = :login OR email = :login) LIMIT 1';
        $st = Database::connection()->prepare($sql);
        $st->execute(['login' => $login]);
        return $st->fetch() ?: null;
    }

    public function updatePasswordByEmail(string $email, string $hash): bool
    {
        $st = Database::connection()->prepare('UPDATE usuarios SET senha_hash = :h WHERE email = :e');
        return $st->execute(['h' => $hash, 'e' => $email]);
    }
}
