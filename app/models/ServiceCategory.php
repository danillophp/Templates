<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ServiceCategory extends Model
{
    public function all(): array
    {
        return $this->db->query('SELECT id, nome, descricao, ativo FROM categorias_servicos ORDER BY nome')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM categorias_servicos WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
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
        $stmt = $this->db->prepare('UPDATE categorias_servicos SET nome=:nome, descricao=:descricao, ativo=:ativo WHERE id=:id');
        $stmt->execute([
            ':id' => $id,
            ':nome' => $data['nome'],
            ':descricao' => $data['descricao'] ?: null,
            ':ativo' => $data['ativo'],
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM categorias_servicos WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
