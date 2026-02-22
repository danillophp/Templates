<?php
namespace App\Services;

use App\Models\LogModel;

class AuditService
{
    public function log(string $acao, string $entidade, int $entidadeId, ?array $antes = null, ?array $depois = null): void
    {
        (new LogModel())->create([
            'usuario_id' => $_SESSION['auth_user']['id'] ?? null,
            'acao' => $acao,
            'entidade' => $entidade,
            'entidade_id' => $entidadeId,
            'dados_antes' => $antes ? json_encode($antes, JSON_UNESCAPED_UNICODE) : null,
            'dados_depois' => $depois ? json_encode($depois, JSON_UNESCAPED_UNICODE) : null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'cli',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? php_sapi_name(),
        ]);
    }
}
