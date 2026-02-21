<?php
namespace App\Services;

use App\Models\LogModel;

class AuditService
{
    public function log(string $action, string $entity, ?int $entityId, $before = null, $after = null): void
    {
        $user = $_SESSION['admin_user']['id'] ?? null;
        (new LogModel())->create([
            'usuario_id' => $user,
            'acao' => $action,
            'entidade' => $entity,
            'entidade_id' => $entityId,
            'dados_antes' => $before ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
            'dados_depois' => $after ? json_encode($after, JSON_UNESCAPED_UNICODE) : null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'cli',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? php_sapi_name(),
        ]);
    }
}
