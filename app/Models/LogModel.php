<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class LogModel
{
    public function register(?int $requestId, ?int $userId, string $role, string $action, string $details): void
    {
        $stmt = Database::connection()->prepare('INSERT INTO logs (request_id, actor_user_id, actor_role, action, details, actor_ip, created_at) VALUES (:request_id,:user_id,:role,:action,:details,:ip,NOW())');
        $stmt->execute([
            'request_id' => $requestId,
            'user_id' => $userId,
            'role' => $role,
            'action' => $action,
            'details' => $details,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        ]);
    }
}
