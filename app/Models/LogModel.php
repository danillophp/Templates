<?php
namespace App\Models;

use App\Core\Database;

class LogModel
{
    public function create(array $data): bool
    {
        $st = Database::connection()->prepare('INSERT INTO logs_auditoria(usuario_id,acao,entidade,entidade_id,dados_antes,dados_depois,ip,user_agent,criado_em) VALUES(:u,:a,:e,:eid,:da,:dd,:ip,:ua,NOW())');
        return $st->execute([
            'u'=>$data['usuario_id'],'a'=>$data['acao'],'e'=>$data['entidade'],'eid'=>$data['entidade_id'],
            'da'=>$data['dados_antes'],'dd'=>$data['dados_depois'],'ip'=>$data['ip'],'ua'=>$data['user_agent']
        ]);
    }
}
