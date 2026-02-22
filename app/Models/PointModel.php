<?php
namespace App\Models;

use App\Core\Database;

class PointModel
{
    public function allActive(): array
    {
        return Database::connection()->query('SELECT * FROM pontos_coleta WHERE ativo = 1 ORDER BY nome')->fetchAll();
    }
}
