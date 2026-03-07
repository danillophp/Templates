<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Classe base de Model para centralizar a conexão PDO.
 */
abstract class Model
{
    protected PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
}
