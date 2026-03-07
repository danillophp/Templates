<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Client extends Model
{
    public function all(?string $phoneSearch = null): array
    {
        $sql = 'SELECT id, nome, telefone, whatsapp, email, data_nascimento, created_at
                FROM clientes';

        $params = [];
        if ($phoneSearch !== null && $phoneSearch !== '') {
            $sql .= ' WHERE telefone LIKE :telefone OR whatsapp LIKE :whatsapp';
            $params[':telefone'] = '%' . $phoneSearch . '%';
            $params[':whatsapp'] = '%' . $phoneSearch . '%';
        }

        $sql .= ' ORDER BY nome ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM clientes WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): void
    {
        $sql = 'INSERT INTO clientes (nome, telefone, whatsapp, email, data_nascimento, observacoes)
                VALUES (:nome, :telefone, :whatsapp, :email, :data_nascimento, :observacoes)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nome' => $data['nome'],
            ':telefone' => $data['telefone'],
            ':whatsapp' => $data['whatsapp'] ?: null,
            ':email' => $data['email'] ?: null,
            ':data_nascimento' => $data['data_nascimento'] ?: null,
            ':observacoes' => $data['observacoes'] ?: null,
        ]);
    }

    public function update(int $id, array $data): void
    {
        $sql = 'UPDATE clientes
                SET nome = :nome,
                    telefone = :telefone,
                    whatsapp = :whatsapp,
                    email = :email,
                    data_nascimento = :data_nascimento,
                    observacoes = :observacoes
                WHERE id = :id';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':nome' => $data['nome'],
            ':telefone' => $data['telefone'],
            ':whatsapp' => $data['whatsapp'] ?: null,
            ':email' => $data['email'] ?: null,
            ':data_nascimento' => $data['data_nascimento'] ?: null,
            ':observacoes' => $data['observacoes'] ?: null,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM clientes WHERE id = :id');
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Histórico resumido de agendamentos do cliente.
     */

    public function findOrCreateByPhone(array $data): int
    {
        $stmt = $this->db->prepare('SELECT id FROM clientes WHERE telefone = :telefone LIMIT 1');
        $stmt->execute([':telefone' => $data['telefone']]);
        $found = $stmt->fetch();

        if ($found) {
            return (int) $found['id'];
        }

        $this->create($data + ['data_nascimento' => null]);
        return (int) $this->db->lastInsertId();
    }

    public function history(int $clientId): array
    {
        $sql = 'SELECT
                    a.id,
                    a.data_agendamento,
                    a.hora_inicio,
                    a.hora_fim,
                    a.status,
                    a.valor_total,
                    a.valor_entrada,
                    a.valor_restante,
                    s.nome AS servico_nome,
                    c.nome AS categoria_nome
                FROM agendamentos a
                INNER JOIN servicos s ON s.id = a.servico_id
                INNER JOIN categorias_servicos c ON c.id = s.categoria_id
                WHERE a.cliente_id = :cliente_id
                ORDER BY a.data_agendamento DESC, a.hora_inicio DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cliente_id' => $clientId]);

        return $stmt->fetchAll();
    }
}
