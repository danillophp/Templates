<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class RequestModel
{
    public function create(array $data): int
    {
        $sql = 'INSERT INTO solicitacoes (nome,endereco,cep,telefone,foto,data_solicitada,latitude,longitude,status,criado_em)
                VALUES (:nome,:endereco,:cep,:telefone,:foto,:data_solicitada,:latitude,:longitude,"PENDENTE",NOW())';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($data);
        return (int) Database::connection()->lastInsertId();
    }

    public function summary(): array
    {
        $pdo = Database::connection();
        $statuses = ['PENDENTE', 'APROVADO', 'RECUSADO', 'FINALIZADO'];
        $result = [];

        foreach ($statuses as $status) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM solicitacoes WHERE status = ?');
            $stmt->execute([$status]);
            $result[$status] = (int) $stmt->fetchColumn();
        }

        return $result;
    }

    public function list(array $filters = []): array
    {
        $sql = 'SELECT s.*, u.nome AS funcionario_nome
                FROM solicitacoes s
                LEFT JOIN usuarios u ON u.id = s.funcionario_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= ' AND s.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['date'])) {
            $sql .= ' AND DATE(s.data_solicitada) = :date';
            $params['date'] = $filters['date'];
        }

        $sql .= ' ORDER BY s.criado_em DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM solicitacoes WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function updateStatus(int $id, string $status, ?string $date = null, ?int $employeeId = null): void
    {
        $sql = 'UPDATE solicitacoes SET status = :status';
        $params = ['status' => $status, 'id' => $id];

        if ($date !== null) {
            $sql .= ', data_solicitada = :data_solicitada';
            $params['data_solicitada'] = $date;
        }

        if ($employeeId !== null) {
            $sql .= ', funcionario_id = :funcionario_id';
            $params['funcionario_id'] = $employeeId;
        }

        $sql .= ' WHERE id = :id';
        Database::connection()->prepare($sql)->execute($params);
    }

    public function byEmployee(int $userId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM solicitacoes WHERE funcionario_id = ? AND status IN ("APROVADO", "PENDENTE") ORDER BY data_solicitada ASC');
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }
}
