<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    public function findByEmail(string $email): ?array
    {
        $sql = 'SELECT id, nome AS name, email, senha AS password FROM usuarios WHERE email = :email AND ativo = 1 LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->execute();

        $user = $stmt->fetch();
        return $user ?: null;
    }
}
