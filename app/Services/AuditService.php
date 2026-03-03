<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Security;

final class AuditService
{
    public static function log(string $entity, ?int $entityId, string $action, ?int $actorUserId, ?array $before = null, ?array $after = null): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO audit_log (actor_user_id, action, entity, entity_id, before_json, after_json, ip, user_agent, request_id, created_at)
            VALUES (:actor_user_id, :action, :entity, :entity_id, :before_json, :after_json, :ip, :user_agent, :request_id, NOW())'
        );

        $stmt->execute([
            ':actor_user_id' => $actorUserId,
            ':action' => $action,
            ':entity' => $entity,
            ':entity_id' => $entityId,
            ':before_json' => $before ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
            ':after_json' => $after ? json_encode($after, JSON_UNESCAPED_UNICODE) : null,
            ':ip' => Security::ip(),
            ':user_agent' => Security::userAgent(),
            ':request_id' => $_SERVER['HTTP_X_REQUEST_ID'] ?? null,
        ]);
    }
}
