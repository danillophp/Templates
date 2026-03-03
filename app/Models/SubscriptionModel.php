<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class SubscriptionModel
{
    public function activeWithPlan(int $tenantId): ?array
    {
        $stmt = Database::connection()->prepare("SELECT a.*, p.nome as plano_nome, p.limite_solicitacoes_mes, p.limite_funcionarios FROM assinaturas a JOIN planos p ON p.id = a.plano_id WHERE a.tenant_id = :tenant_id AND a.status = 'ATIVA' LIMIT 1");
        $stmt->execute(['tenant_id' => $tenantId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function requestsInMonth(int $tenantId): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM solicitacoes WHERE tenant_id = :tenant_id AND DATE_FORMAT(criado_em, "%Y-%m") = DATE_FORMAT(NOW(), "%Y-%m")');
        $stmt->execute(['tenant_id' => $tenantId]);
        return (int)$stmt->fetchColumn();
    }
}
