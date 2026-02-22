<?php
namespace App\Models;

use App\Core\Database;

class NotificationModel
{
    public function createNewSchedule(int $solicitacaoId, array $payload): void
    {
        $sql = 'INSERT INTO notificacoes_admin (tipo,solicitacao_id,payload_json) VALUES ("novo_agendamento",:sid,:payload)';
        Database::connection()->prepare($sql)->execute(['sid' => $solicitacaoId, 'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE)]);
    }

    public function since(int $lastId): array
    {
        $st = Database::connection()->prepare('SELECT * FROM notificacoes_admin WHERE id > :id ORDER BY id ASC');
        $st->execute(['id' => $lastId]);
        return $st->fetchAll();
    }
}
