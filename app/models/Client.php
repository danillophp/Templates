<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Client extends Model
{
    public function all(): array
    {
        return $this->db->query('SELECT id, nome, telefone, email, created_at FROM clientes ORDER BY id DESC')->fetchAll();
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
        $sql = 'INSERT INTO clientes (nome, telefone, email, data_nascimento, observacoes) VALUES (:nome,:telefone,:email,:data_nascimento,:observacoes)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nome' => $data['nome'],
            ':telefone' => $data['telefone'],
            ':email' => $data['email'],
            ':data_nascimento' => $data['data_nascimento'] ?: null,
            ':observacoes' => $data['observacoes'] ?: null,
        ]);
    }

    public function update(int $id, array $data): void
    {
        $sql = 'UPDATE clientes SET nome=:nome, telefone=:telefone, email=:email, data_nascimento=:data_nascimento, observacoes=:observacoes WHERE id=:id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':nome' => $data['nome'],
            ':telefone' => $data['telefone'],
            ':email' => $data['email'],
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
}
