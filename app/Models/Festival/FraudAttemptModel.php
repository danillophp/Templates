<?php

declare(strict_types=1);

namespace App\Models\Festival;

use App\Core\FestivalDatabase;
use PDO;

final class FraudAttemptModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = FestivalDatabase::connection();
    }

    public function create(array $data): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO tentativas_fraude (ip_hash, user_agent_hash, fingerprint, motivo, payload_json, created_at)
             VALUES (:ip_hash, :user_agent_hash, :fingerprint, :motivo, :payload_json, NOW())'
        );

        $stmt->execute($data);
    }

    public function latest(int $limit = 100): array
    {
        $stmt = $this->db->prepare('SELECT id, motivo, created_at, ip_hash, user_agent_hash FROM tentativas_fraude ORDER BY id DESC LIMIT :limit');
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
