<?php
namespace App\Models;

use App\Core\Database;

class LogModel
{
    public function create(array $d): void
    {
        $sql = 'INSERT INTO logs_auditoria (usuario_id,acao,entidade,entidade_id,dados_antes,dados_depois,ip,user_agent) VALUES (:usuario_id,:acao,:entidade,:entidade_id,:dados_antes,:dados_depois,:ip,:user_agent)';
        Database::connection()->prepare($sql)->execute($d);
    }
}
