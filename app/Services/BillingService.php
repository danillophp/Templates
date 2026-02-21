<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class BillingService
{
    public function runDaily(): array
    {
        $pdo = Database::connection();

        $inadimplentes = $pdo->exec("UPDATE assinaturas SET status='INADIMPLENTE' WHERE status='ATIVA' AND vencimento < CURDATE()");
        $notificados = $pdo->exec("INSERT INTO notificacoes (tenant_id, canal, destino, status, resposta, criado_em)
            SELECT tenant_id, 'sistema', 'admin', 'INADIMPLENTE', 'Assinatura vencida', NOW()
            FROM assinaturas WHERE status='INADIMPLENTE' AND DATE(vencimento) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");

        return [
            'inadimplentes_atualizados' => $inadimplentes,
            'notificacoes_geradas' => $notificados,
            'reset_contadores' => 'não aplicável (contagem mensal por query dinâmica)',
        ];
    }
}
