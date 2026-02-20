<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class LogModel
{
    public function register(?int $solicitacaoId, ?int $usuarioId, string $acao): void
    {
        $stmt = Database::connection()->prepare('INSERT INTO logs (solicitacao_id, usuario_id, acao, criado_em) VALUES (:solicitacao_id,:usuario_id,:acao,NOW())');
        $stmt->execute([
            'solicitacao_id' => $solicitacaoId,
            'usuario_id' => $usuarioId,
            'acao' => $acao,
        ]);
    }
}
