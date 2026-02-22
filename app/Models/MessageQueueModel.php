<?php
namespace App\Models;

use App\Core\Database;

class MessageQueueModel
{
    public function enqueue(array $d): void
    {
        $sql = 'INSERT INTO mensagens_fila (solicitacao_id,canal,destino,template,payload_json,status) VALUES (:solicitacao_id,:canal,:destino,:template,:payload_json,"pendente")';
        Database::connection()->prepare($sql)->execute($d);
    }

    public function pending(int $limit = 20): array
    {
        $st = Database::connection()->prepare('SELECT * FROM mensagens_fila WHERE status IN ("pendente","erro") AND tentativas < 3 ORDER BY id ASC LIMIT :lim');
        $st->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public function markSending(int $id): void { Database::connection()->prepare('UPDATE mensagens_fila SET status="enviando", tentativas=tentativas+1, atualizado_em=NOW() WHERE id=:id')->execute(['id'=>$id]); }
    public function markSent(int $id): void { Database::connection()->prepare('UPDATE mensagens_fila SET status="enviado", atualizado_em=NOW() WHERE id=:id')->execute(['id'=>$id]); }
    public function markError(int $id, string $err): void { Database::connection()->prepare('UPDATE mensagens_fila SET status="erro", erro_mensagem=:e, atualizado_em=NOW() WHERE id=:id')->execute(['id'=>$id,'e'=>mb_substr($err,0,1000)]); }
}
