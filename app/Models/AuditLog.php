<?php

namespace App\Models;

class AuditLog extends Model
{
    public function log(int $userId, string $action, array $meta = []): void
    {
        $stmt = $this->db->prepare('INSERT INTO audit_logs (user_id, action, metadata_json, ip_address, created_at) VALUES (:user_id,:action,:metadata_json,:ip,NOW())');
        $stmt->execute([
            'user_id' => $userId ?: null,
            'action' => $action,
            'metadata_json' => json_encode($meta, JSON_UNESCAPED_UNICODE),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        ]);
    }

    public function latest(int $limit = 50): array
    {
        $stmt = $this->db->prepare('SELECT a.*, u.name AS user_name FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.id DESC LIMIT :lim');
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
