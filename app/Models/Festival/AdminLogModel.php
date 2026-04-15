<?php

declare(strict_types=1);

namespace App\Models\Festival;

use App\Core\FestivalDatabase;
use PDO;

final class AdminLogModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = FestivalDatabase::connection();
    }

    public function add(int $adminId, string $action, string $description, string $ip): void
    {
        $stmt = $this->db->prepare('INSERT INTO logs_admin (admin_id, acao, descricao, ip, created_at) VALUES (:admin_id, :acao, :descricao, :ip, NOW())');
        $stmt->execute([
            'admin_id' => $adminId,
            'acao' => $action,
            'descricao' => $description,
            'ip' => $ip,
        ]);
    }
}
