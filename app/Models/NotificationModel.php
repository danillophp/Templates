<?php
namespace App\Models;

use App\Core\Database;

class NotificationModel
{
    public function create(int $requestId, array $payload): bool
    {
        $st = Database::connection()->prepare('INSERT INTO notificacoes_admin(tipo,solicitacao_id,payload_json,criado_em) VALUES("novo_agendamento",:sid,:p,NOW())');
        return $st->execute(['sid'=>$requestId,'p'=>json_encode($payload, JSON_UNESCAPED_UNICODE)]);
    }

    public function latestAfter(int $id): array
    {
        $st = Database::connection()->prepare('SELECT * FROM notificacoes_admin WHERE id > :id ORDER BY id ASC');
        $st->execute(['id'=>$id]);
        return $st->fetchAll();
    }
}
