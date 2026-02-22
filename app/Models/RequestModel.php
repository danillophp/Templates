<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Helpers\ProtocolHelper;

final class RequestModel
{
    public function create(array $data): int
    {
        $sql = 'INSERT INTO solicitacoes (tenant_id,nome,endereco,cep,bairro,telefone,email,foto,data_solicitada,latitude,longitude,status,criado_em,atualizado_em)
                VALUES (:tenant_id,:nome,:endereco,:cep,:bairro,:telefone,:email,:foto,:data_solicitada,:latitude,:longitude,"PENDENTE",NOW(),NOW())';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($data);

        $id = (int) Database::connection()->lastInsertId();
        $protocol = ProtocolHelper::fromId($id);
        Database::connection()->prepare('UPDATE solicitacoes SET protocolo = :protocolo WHERE id = :id')->execute(['protocolo' => $protocol, 'id' => $id]);

        (new AdminNotificationModel())->createNewRequest((int)$data['tenant_id'], $id, [
            'protocolo' => $protocol,
            'nome' => (string)$data['nome'],
            'endereco' => (string)$data['endereco'],
            'data_solicitada' => (string)$data['data_solicitada'],
        ]);

        return $id;
    }

    public function summary(int $tenantId): array
    {
        $statuses = ['PENDENTE', 'APROVADO', 'RECUSADO', 'ALTERADO', 'FINALIZADO'];
        $result = [];

        foreach ($statuses as $status) {
            $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM solicitacoes WHERE tenant_id = :tenant_id AND status = :status');
            $stmt->execute(['tenant_id' => $tenantId, 'status' => $status]);
            $result[$status] = (int)$stmt->fetchColumn();
        }

        return $result;
    }

    public function list(int $tenantId, array $filters = []): array
    {
        $sql = 'SELECT s.* FROM solicitacoes s WHERE s.tenant_id = :tenant_id';
        $params = ['tenant_id' => $tenantId];

        if (!empty($filters['status'])) {
            $sql .= ' AND s.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['date'])) {
            $sql .= ' AND DATE(s.data_solicitada) = :date';
            $params['date'] = $filters['date'];
        }

        $sql .= ' ORDER BY DATE(s.data_solicitada) ASC, s.criado_em ASC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id, int $tenantId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM solicitacoes WHERE id = :id AND tenant_id = :tenant_id LIMIT 1');
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByProtocolOrPhone(string $protocol, string $phone, int $tenantId): ?array
    {
        if ($protocol !== '' && $phone !== '') {
            $stmt = Database::connection()->prepare('SELECT id, protocolo, status, data_solicitada, criado_em FROM solicitacoes WHERE tenant_id = :tenant_id AND protocolo = :protocolo AND telefone = :telefone LIMIT 1');
            $stmt->execute(['tenant_id' => $tenantId, 'protocolo' => $protocol, 'telefone' => $phone]);
            $row = $stmt->fetch();
            return $row ?: null;
        }

        if ($protocol !== '') {
            $stmt = Database::connection()->prepare('SELECT id, protocolo, status, data_solicitada, criado_em FROM solicitacoes WHERE tenant_id = :tenant_id AND protocolo = :protocolo LIMIT 1');
            $stmt->execute(['tenant_id' => $tenantId, 'protocolo' => $protocol]);
            $row = $stmt->fetch();
            return $row ?: null;
        }

        $stmt = Database::connection()->prepare('SELECT id, protocolo, status, data_solicitada, criado_em FROM solicitacoes WHERE tenant_id = :tenant_id AND telefone = :telefone ORDER BY criado_em DESC LIMIT 1');
        $stmt->execute(['tenant_id' => $tenantId, 'telefone' => $phone]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateStatus(int $id, int $tenantId, string $status, ?string $date = null): void
    {
        $sql = 'UPDATE solicitacoes SET status = :status, atualizado_em = NOW()';
        $params = ['status' => $status, 'id' => $id, 'tenant_id' => $tenantId];

        if ($date !== null) {
            $sql .= ', data_solicitada = :data_solicitada';
            $params['data_solicitada'] = $date;
        }

        if ($status === 'FINALIZADO') {
            $sql .= ', finalizado_em = NOW()';
        }

        $sql .= ' WHERE id = :id AND tenant_id = :tenant_id';
        Database::connection()->prepare($sql)->execute($params);
    }

    public function delete(int $id, int $tenantId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM solicitacoes WHERE id = :id AND tenant_id = :tenant_id');
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
    }


    public function summaryByMonthDate(int $tenantId, string $yearMonth): array
    {
        $start = $yearMonth . '-01';
        $startDate = \DateTimeImmutable::createFromFormat('Y-m-d', $start);
        if (!$startDate) {
            return [];
        }

        $endDate = $startDate->modify('first day of next month');

        $stmt = Database::connection()->prepare('SELECT DATE(data_solicitada) as dia, COUNT(*) as total FROM solicitacoes WHERE tenant_id = :tenant_id AND data_solicitada >= :inicio AND data_solicitada < :fim GROUP BY DATE(data_solicitada)');
        $stmt->execute([
            'tenant_id' => $tenantId,
            'inicio' => $startDate->format('Y-m-d 00:00:00'),
            'fim' => $endDate->format('Y-m-d 00:00:00'),
        ]);

        $rows = $stmt->fetchAll();
        $result = [];
        foreach ($rows as $row) {
            $result[(string)$row['dia']] = (int)$row['total'];
        }

        return $result;
    }

    public function chartByMonth(int $tenantId): array
    {
        $stmt = Database::connection()->prepare('SELECT DATE_FORMAT(criado_em, "%Y-%m") as mes, COUNT(*) as total FROM solicitacoes WHERE tenant_id = :tenant_id GROUP BY DATE_FORMAT(criado_em, "%Y-%m") ORDER BY mes ASC');
        $stmt->execute(['tenant_id' => $tenantId]);
        return $stmt->fetchAll();
    }
}
