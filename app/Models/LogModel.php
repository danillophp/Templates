<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class LogModel
{
    public function register(int $tenantId, ?int $solicitacaoId, ?int $usuarioId, string $acao, string $detalhes = ''): void
    {
        $stmt = Database::connection()->prepare('INSERT INTO logs (tenant_id, solicitacao_id, usuario_id, acao, detalhes, criado_em) VALUES (:tenant_id,:solicitacao_id,:usuario_id,:acao,:detalhes,NOW())');
        $stmt->execute([
            'tenant_id' => $tenantId,
            'solicitacao_id' => $solicitacaoId,
            'usuario_id' => $usuarioId,
            'acao' => $acao,
            'detalhes' => $detalhes,
        ]);
    }
}
