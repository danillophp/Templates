<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Service extends Model
{
    public function all(): array
    {
        $sql = 'SELECT s.id, s.nome, s.valor_total, s.duracao_minutos, s.ativo, c.nome AS categoria_nome
                FROM servicos s
                INNER JOIN categorias_servicos c ON c.id = s.categoria_id
                ORDER BY s.id DESC';
        return $this->db->query($sql)->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM servicos WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): void
    {
        $sql = 'INSERT INTO servicos (categoria_id, nome, descricao, valor_total, duracao_minutos, ativo) VALUES (:categoria_id,:nome,:descricao,:valor_total,:duracao_minutos,:ativo)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':categoria_id' => $data['categoria_id'],
            ':nome' => $data['nome'],
            ':descricao' => $data['descricao'] ?: null,
            ':valor_total' => $data['valor_total'],
            ':duracao_minutos' => $data['duracao_minutos'],
            ':ativo' => $data['ativo'],
        ]);
    }

    public function update(int $id, array $data): void
    {
        $sql = 'UPDATE servicos SET categoria_id=:categoria_id, nome=:nome, descricao=:descricao, valor_total=:valor_total, duracao_minutos=:duracao_minutos, ativo=:ativo WHERE id=:id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':categoria_id' => $data['categoria_id'],
            ':nome' => $data['nome'],
            ':descricao' => $data['descricao'] ?: null,
            ':valor_total' => $data['valor_total'],
            ':duracao_minutos' => $data['duracao_minutos'],
            ':ativo' => $data['ativo'],
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM servicos WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
