<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    public function findByEmail(string $email, ?int $tenantId): ?array
    {
        $sql = 'SELECT * FROM usuarios WHERE email = :email AND ativo = 1';
        $params = ['email' => $email];

        if ($tenantId !== null) {
            $sql .= ' AND tenant_id = :tenant_id';
            $params['tenant_id'] = $tenantId;
        } else {
            $sql .= " AND tipo = 'super_admin'";
        }

        $sql .= ' LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findByIdentifier(string $identifier, ?int $tenantId): ?array
    {
        $sql = 'SELECT * FROM usuarios WHERE (email = :identifier OR nome = :identifier) AND ativo = 1';
        $params = ['identifier' => $identifier];

        if ($tenantId !== null) {
            $sql .= ' AND tenant_id = :tenant_id';
            $params['tenant_id'] = $tenantId;
        } else {
            $sql .= " AND tipo = 'super_admin'";
        }

        $sql .= ' LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updatePassword(int $id, string $hash): void
    {
        $stmt = Database::connection()->prepare('UPDATE usuarios SET senha = :senha WHERE id = :id');
        $stmt->execute(['senha' => $hash, 'id' => $id]);
    }

    public function employees(int $tenantId): array
    {
        $stmt = Database::connection()->prepare("SELECT id, nome FROM usuarios WHERE tenant_id = :tenant_id AND tipo='funcionario' AND ativo = 1 ORDER BY nome");
        $stmt->execute(['tenant_id' => $tenantId]);
        return $stmt->fetchAll();
    }
}
