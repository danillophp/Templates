<?php

namespace App\Models;

class PickupRequest extends Model
{
    public const STATUSES = ['PENDENTE', 'APROVADA', 'EM_ANDAMENTO', 'FINALIZADA', 'RECUSADA'];

    public function create(array $data): int
    {
        $sql = 'INSERT INTO pickup_requests (
            citizen_name,address,cep,whatsapp,photo_path,scheduled_at,latitude,longitude,status,consent_lgpd,ip_address,created_at,updated_at
        ) VALUES (
            :citizen_name,:address,:cep,:whatsapp,:photo_path,:scheduled_at,:latitude,:longitude,"PENDENTE",:consent_lgpd,:ip_address,NOW(),NOW()
        )';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return (int) $this->db->lastInsertId();
    }

    public function dashboardCounts(array $filters): array
    {
        $where = $this->filterSql($filters, $params);
        $sql = "SELECT status, COUNT(*) total FROM pickup_requests {$where} GROUP BY status";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $out = ['PENDENTE' => 0, 'APROVADA' => 0, 'EM_ANDAMENTO' => 0, 'FINALIZADA' => 0];
        foreach ($rows as $r) {
            $out[$r['status']] = (int) $r['total'];
        }
        return $out;
    }

    public function list(array $filters = []): array
    {
        $where = $this->filterSql($filters, $params);
        $sql = "SELECT r.*, u.name AS employee_name FROM pickup_requests r LEFT JOIN users u ON u.id=r.assigned_user_id {$where} ORDER BY r.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function listAssigned(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM pickup_requests WHERE assigned_user_id=:u AND status IN ("EM_ANDAMENTO","APROVADA") ORDER BY scheduled_at ASC');
        $stmt->execute(['u' => $userId]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM pickup_requests WHERE id=:id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function updateAdmin(int $id, array $data): void
    {
        $sql = 'UPDATE pickup_requests SET status=:status, scheduled_at=:scheduled_at, assigned_user_id=:assigned_user_id, admin_notes=:admin_notes, updated_at=NOW() WHERE id=:id';
        $data['id'] = $id;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
    }

    public function markStarted(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE pickup_requests SET status="EM_ANDAMENTO", started_at=NOW(), updated_at=NOW() WHERE id=:id');
        $stmt->execute(['id' => $id]);
    }

    public function markFinished(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE pickup_requests SET status="FINALIZADA", finished_at=NOW(), updated_at=NOW() WHERE id=:id');
        $stmt->execute(['id' => $id]);
    }

    public function addStatusHistory(int $id, string $status, int $actorId, string $note = ''): void
    {
        $stmt = $this->db->prepare('INSERT INTO request_status_history (request_id,status,actor_user_id,note,created_at) VALUES (:r,:s,:a,:n,NOW())');
        $stmt->execute(['r' => $id, 's' => $status, 'a' => $actorId ?: null, 'n' => $note]);
    }

    private function filterSql(array $filters, ?array &$params): string
    {
        $clauses = [];
        $params = [];
        if (!empty($filters['status'])) {
            $clauses[] = 'r.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['date_from'])) {
            $clauses[] = 'DATE(r.created_at) >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $clauses[] = 'DATE(r.created_at) <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }
        if (!empty($filters['locality'])) {
            $clauses[] = 'r.address LIKE :locality';
            $params['locality'] = '%' . $filters['locality'] . '%';
        }

        return $clauses ? 'WHERE ' . implode(' AND ', $clauses) : '';
    }
}
