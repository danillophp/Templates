<?php
namespace App\Models;

use App\Core\Database;

class PointModel
{
    public function allActive(): array
    {
        return Database::connection()->query('SELECT * FROM pontos_coleta WHERE ativo=1 ORDER BY nome')->fetchAll();
    }

    public function all(): array
    {
        return Database::connection()->query('SELECT * FROM pontos_coleta ORDER BY id DESC')->fetchAll();
    }

    public function create(array $data): bool
    {
        $st = Database::connection()->prepare('INSERT INTO pontos_coleta(nome,descricao,latitude,longitude,ativo,criado_em) VALUES(:nome,:descricao,:lat,:lng,:ativo,NOW())');
        return $st->execute([
            'nome'=>$data['nome'],'descricao'=>$data['descricao'],'lat'=>$data['latitude'],'lng'=>$data['longitude'],'ativo'=>$data['ativo'] ?? 1
        ]);
    }
}
