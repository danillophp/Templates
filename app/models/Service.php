<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Service extends Model
{
    public function all(): array
    {
        $sql = 'SELECT
                    s.id,
                    s.categoria_id,
                    s.nome,
                    s.descricao,
                    s.duracao_minutos,
                    s.valor,
                    s.percentual_entrada,
                    s.ativo,
                    s.created_at,
                    s.updated_at,
                    c.nome AS categoria_nome
                FROM servicos s
                INNER JOIN categorias_servicos c ON c.id = s.categoria_id
                ORDER BY s.nome ASC';

        return $this->db->query($sql)->fetchAll();
    }


    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, categoria_id, nome, duracao_minutos, ativo FROM servicos WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM servicos WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function existsByName(string $nome, int $categoriaId, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT id FROM servicos WHERE nome = :nome AND categoria_id = :categoria_id';
        $params = [
            ':nome' => $nome,
            ':categoria_id' => $categoriaId,
        ];

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
        $sql = 'INSERT INTO servicos (
                    categoria_id,
                    nome,
                    descricao,
                    duracao_minutos,
                    valor,
                    percentual_entrada,
                    ativo
                ) VALUES (
                    :categoria_id,
                    :nome,
                    :descricao,
                    :duracao_minutos,
                    :valor,
                    :percentual_entrada,
                    :ativo
                )';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':categoria_id' => $data['categoria_id'],
            ':nome' => $data['nome'],
            ':descricao' => $data['descricao'] ?: null,
            ':duracao_minutos' => $data['duracao_minutos'],
            ':valor' => $data['valor'],
            ':percentual_entrada' => $data['percentual_entrada'],
            ':ativo' => $data['ativo'],
        ]);
    }

    public function update(int $id, array $data): void
    {
        $sql = 'UPDATE servicos
                SET categoria_id = :categoria_id,
                    nome = :nome,
                    descricao = :descricao,
                    duracao_minutos = :duracao_minutos,
                    valor = :valor,
                    percentual_entrada = :percentual_entrada,
                    ativo = :ativo
                WHERE id = :id';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':categoria_id' => $data['categoria_id'],
            ':nome' => $data['nome'],
            ':descricao' => $data['descricao'] ?: null,
            ':duracao_minutos' => $data['duracao_minutos'],
            ':valor' => $data['valor'],
            ':percentual_entrada' => $data['percentual_entrada'],
            ':ativo' => $data['ativo'],
        ]);
    }

    /**
     * Exclusão lógica: mantém registro e torna inativo.
     */
    public function softDelete(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE servicos SET ativo = 0 WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function toggleStatus(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE servicos SET ativo = IF(ativo = 1, 0, 1) WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
