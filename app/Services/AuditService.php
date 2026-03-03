<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class AuditService
{
    public static function log(string $entityType, int $entityId, string $action, ?int $actorId, array $payload = []): void
    {
        $stmt = Database::connection()->prepare('INSERT INTO audit_logs (entity_type, entity_id, action, actor_user_id, payload_json, created_at) VALUES (:entity_type, :entity_id, :action, :actor_user_id, :payload_json, NOW())');
        $stmt->execute([
            ':entity_type' => $entityType,
            ':entity_id' => $entityId,
            ':action' => $action,
            ':actor_user_id' => $actorId,
            ':payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);
    }
}
