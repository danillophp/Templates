<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ServiceCategory extends Model
{
    public function all(): array
    {
        $sql = 'SELECT id, nome, descricao, ativo, created_at, updated_at
                FROM categorias_servicos
                ORDER BY nome ASC';

        return $this->db->query($sql)->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, nome, descricao, ativo, created_at, updated_at FROM categorias_servicos WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function existsByName(string $nome, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT id FROM categorias_servicos WHERE nome = :nome';
        $params = [':nome' => $nome];

        if ($ignoreId !== null) {
            $sql .= ' AND id <> :ignore_id';
            $params[':ignore_id'] = $ignoreId;
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetch();
    }

    public function create(array $data): void
    {
        $stmt = $this->db->prepare('INSERT INTO categorias_servicos (nome, descricao, ativo) VALUES (:nome,:descricao,:ativo)');
        $stmt->execute([
            ':nome' => $data['nome'],
            ':descricao' => $data['descricao'] ?: null,
            ':ativo' => $data['ativo'],
        ]);
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare('UPDATE categorias_servicos SET nome = :nome, descricao = :descricao, ativo = :ativo WHERE id = :id');
        $stmt->execute([
            ':id' => $id,
            ':nome' => $data['nome'],
            ':descricao' => $data['descricao'] ?: null,
            ':ativo' => $data['ativo'],
        ]);
    }

    /**
     * Exclusão lógica: mantém o registro e marca como inativo.
     */
    public function softDelete(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE categorias_servicos SET ativo = 0 WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function toggleStatus(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE categorias_servicos SET ativo = IF(ativo = 1, 0, 1) WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
