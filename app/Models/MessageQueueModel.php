<?php
namespace App\Models;

use App\Core\Database;

class MessageQueueModel
{
    public function enqueue(array $data): bool
    {
        $sql = 'INSERT INTO mensagens_fila(solicitacao_id,canal,destino,template,payload_json,status,tentativas,criado_em,atualizado_em)
                VALUES(:sid,:canal,:dest,:tpl,:payload,"pendente",0,NOW(),NOW())';
        $st = Database::connection()->prepare($sql);
        return $st->execute([
            'sid'=>$data['solicitacao_id'],'canal'=>$data['canal'],'dest'=>$data['destino'],'tpl'=>$data['template'],'payload'=>json_encode($data['payload'], JSON_UNESCAPED_UNICODE)
        ]);
    }

    public function pending(int $limit = 20): array
    {
        $st = Database::connection()->prepare('SELECT * FROM mensagens_fila WHERE status IN ("pendente","erro") AND tentativas < 3 ORDER BY id ASC LIMIT :lim');
        $st->bindValue('lim', $limit, \PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public function markSending(int $id): void
    {
        Database::connection()->prepare('UPDATE mensagens_fila SET status="enviando", tentativas=tentativas+1, atualizado_em=NOW() WHERE id=:id')->execute(['id'=>$id]);
    }

    public function markSent(int $id): void
    {
        Database::connection()->prepare('UPDATE mensagens_fila SET status="enviado", erro_mensagem=NULL, atualizado_em=NOW() WHERE id=:id')->execute(['id'=>$id]);
    }

    public function markError(int $id, string $error): void
    {
        Database::connection()->prepare('UPDATE mensagens_fila SET status="erro", erro_mensagem=:e, atualizado_em=NOW() WHERE id=:id')->execute(['id'=>$id,'e'=>$error]);
    }
}
