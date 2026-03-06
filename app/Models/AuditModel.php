<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class AuditModel
{
    public function search(array $filters = []): array
    {
        $sql = 'SELECT a.*, u.username FROM audit_log a LEFT JOIN users u ON u.id = a.actor_user_id WHERE 1=1';
        $params = [];

        if (!empty($filters['entity'])) {
            $sql .= ' AND a.entity = :entity';
            $params[':entity'] = $filters['entity'];
        }

        if (!empty($filters['action'])) {
            $sql .= ' AND a.action = :action';
            $params[':action'] = $filters['action'];
        }

        $sql .= ' ORDER BY a.created_at DESC LIMIT 500';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}
