<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    public function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM usuarios WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function employees(): array
    {
        return Database::connection()->query("SELECT id, nome FROM usuarios WHERE tipo='funcionario' ORDER BY nome")->fetchAll();
    }
}
