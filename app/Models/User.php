<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    public function findByUsername(string $username): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE username = :username AND is_active = 1 LIMIT 1');
        $stmt->execute([':username' => $username]);
        return $stmt->fetch() ?: null;
    }
}
