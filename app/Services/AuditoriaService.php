<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class AuditoriaService
{
    public function registrar(?int $usuarioId, string $acao, string $entidade, int $entidadeId, ?array $antes, ?array $depois): void
    {
        $stmt = Database::connection()->prepare('INSERT INTO auditoria_logs (usuario_id, acao, entidade, entidade_id, dados_antes, dados_depois, ip, user_agent, criado_em)
        VALUES (:usuario_id, :acao, :entidade, :entidade_id, :dados_antes, :dados_depois, :ip, :user_agent, NOW())');

        $stmt->execute([
            'usuario_id' => $usuarioId,
            'acao' => $acao,
            'entidade' => $entidade,
            'entidade_id' => $entidadeId,
            'dados_antes' => $antes ? json_encode($antes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'dados_depois' => $depois ? json_encode($depois, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'user_agent' => mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);
    }
}
