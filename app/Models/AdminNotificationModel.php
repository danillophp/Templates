<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class AdminNotificationModel
{
    public function createNewRequest(int $tenantId, int $requestId, array $payload): void
    {
        $stmt = Database::connection()->prepare('INSERT INTO notificacoes_admin (tenant_id, tipo, solicitacao_id, payload_json, criado_em) VALUES (:tenant_id, :tipo, :solicitacao_id, :payload_json, NOW())');
        $stmt->execute([
            'tenant_id' => $tenantId,
            'tipo' => 'NOVO_AGENDAMENTO',
            'solicitacao_id' => $requestId,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function listSince(int $tenantId, int $lastId): array
    {
        $stmt = Database::connection()->prepare('SELECT id, tipo, solicitacao_id, payload_json, criado_em FROM notificacoes_admin WHERE tenant_id = :tenant_id AND id > :last_id ORDER BY id ASC LIMIT 50');
        $stmt->execute(['tenant_id' => $tenantId, 'last_id' => $lastId]);
        return $stmt->fetchAll();
    }
}
