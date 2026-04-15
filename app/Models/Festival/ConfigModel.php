<?php

declare(strict_types=1);

namespace App\Models\Festival;

use App\Core\FestivalDatabase;
use PDO;

final class ConfigModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = FestivalDatabase::connection();
    }

    public function all(): array
    {
        $rows = $this->db->query('SELECT chave, valor FROM configuracoes')->fetchAll();
        $config = [];
        foreach ($rows as $row) {
            $config[$row['chave']] = $row['valor'];
        }

        return $config;
    }

    public function set(string $key, string $value): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO configuracoes (chave, valor, updated_at)
             VALUES (:chave, :valor, NOW())
             ON DUPLICATE KEY UPDATE valor = VALUES(valor), updated_at = NOW()'
        );

        $stmt->execute(['chave' => $key, 'valor' => $value]);
    }
}
