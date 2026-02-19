<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class RequestModel
{
    public function create(array $data): int
    {
        $sql = 'INSERT INTO requests (full_name,address,cep,district,whatsapp,photo_path,pickup_datetime,status,latitude,longitude,consent_given,request_ip,created_at,updated_at)
                VALUES (:full_name,:address,:cep,:district,:whatsapp,:photo,:pickup,"PENDENTE",:lat,:lng,1,:ip,NOW(),NOW())';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($data);
        return (int) Database::connection()->lastInsertId();
    }

    public function summary(): array
    {
        $pdo = Database::connection();
        $statuses = ['PENDENTE', 'APROVADO', 'EM_ANDAMENTO', 'FINALIZADO'];
        $result = [];
        foreach ($statuses as $status) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM requests WHERE status = ?');
            $stmt->execute([$status]);
            $result[$status] = (int) $stmt->fetchColumn();
        }
        return $result;
    }

    public function list(array $filters = []): array
    {
        $sql = 'SELECT r.*, u.full_name as assigned_name FROM requests r LEFT JOIN users u ON u.id = r.assigned_user_id WHERE 1=1';
        $params = [];
        if (!empty($filters['status'])) {
            $sql .= ' AND r.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['date'])) {
            $sql .= ' AND DATE(r.pickup_datetime) = :date';
            $params['date'] = $filters['date'];
        }
        if (!empty($filters['district'])) {
            $sql .= ' AND r.district LIKE :district';
            $params['district'] = '%' . $filters['district'] . '%';
        }
        $sql .= ' ORDER BY r.created_at DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM requests WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateStatus(int $id, string $status, ?string $pickup = null, ?int $employeeId = null): void
    {
        $sql = 'UPDATE requests SET status = :status, updated_at = NOW()';
        $params = ['status' => $status, 'id' => $id];
        if ($pickup !== null) {
            $sql .= ', pickup_datetime = :pickup';
            $params['pickup'] = $pickup;
        }
        if ($employeeId !== null) {
            $sql .= ', assigned_user_id = :employee';
            $params['employee'] = $employeeId;
        }
        if ($status === 'FINALIZADO') {
            $sql .= ', finalized_at = NOW()';
        }
        $sql .= ' WHERE id = :id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
    }

    public function byEmployee(int $userId): array
    {
        $stmt = Database::connection()->prepare("SELECT * FROM requests WHERE assigned_user_id = ? AND status IN ('EM_ANDAMENTO','APROVADO') ORDER BY pickup_datetime ASC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
