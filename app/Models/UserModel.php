<?php
namespace App\Models;

use App\Core\Database;

class UserModel
{
    public function findByLogin(string $login): ?array
    {
        $sql = 'SELECT * FROM usuarios WHERE (usuario = :login OR email = :login) AND ativo = 1 LIMIT 1';
        $st = Database::connection()->prepare($sql);
        $st->execute(['login' => $login]);
        return $st->fetch() ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $st = Database::connection()->prepare('SELECT * FROM usuarios WHERE email = :email LIMIT 1');
        $st->execute(['email' => $email]);
        return $st->fetch() ?: null;
    }

    public function updatePassword(int $id, string $hash): void
    {
        $st = Database::connection()->prepare('UPDATE usuarios SET senha_hash = :hash WHERE id = :id');
        $st->execute(['hash' => $hash, 'id' => $id]);
    }
}
