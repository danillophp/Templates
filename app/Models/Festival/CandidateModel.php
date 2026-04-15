<?php

declare(strict_types=1);

namespace App\Models\Festival;

use App\Core\FestivalDatabase;
use PDO;

final class CandidateModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = FestivalDatabase::connection();
    }

    public function listPublic(): array
    {
        $sql = 'SELECT id, numero, nome, cidade, idade, descricao, foto, votos_total
                FROM candidatas
                WHERE status = :status AND exibir_publicamente = 1
                ORDER BY numero ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['status' => 'ATIVA']);
        return $stmt->fetchAll();
    }

    public function ranking(int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));
        $stmt = $this->db->query(
            sprintf('SELECT id, numero, nome, foto, votos_total FROM candidatas WHERE status = "ATIVA" ORDER BY votos_total DESC, numero ASC LIMIT %d', $limit)
        );
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM candidatas WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function incrementVoteTotal(int $candidateId): void
    {
        $stmt = $this->db->prepare('UPDATE candidatas SET votos_total = votos_total + 1, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $candidateId]);
    }

    public function listAdmin(): array
    {
        $stmt = $this->db->query('SELECT id, numero, nome, cidade, status, exibir_publicamente, votos_total, updated_at FROM candidatas ORDER BY numero ASC');
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO candidatas (numero, nome, slug, idade, cidade, descricao, foto, status, exibir_publicamente, votos_total, created_at, updated_at)
                VALUES (:numero, :nome, :slug, :idade, :cidade, :descricao, :foto, :status, :exibir_publicamente, 0, NOW(), NOW())';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $sql = 'UPDATE candidatas
                SET numero = :numero, nome = :nome, slug = :slug, idade = :idade, cidade = :cidade,
                    descricao = :descricao, foto = :foto, status = :status, exibir_publicamente = :exibir_publicamente,
                    updated_at = NOW()
                WHERE id = :id';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
    }
}
