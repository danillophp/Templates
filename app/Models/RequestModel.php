<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Helpers\ProtocolHelper;

final class RequestModel
{
    public function create(array $data): int
    {
        $sql = 'INSERT INTO solicitacoes (tenant_id,nome,endereco,cep,bairro,telefone,foto,data_solicitada,latitude,longitude,status,criado_em,atualizado_em)
                VALUES (:tenant_id,:nome,:endereco,:cep,:bairro,:telefone,:foto,:data_solicitada,:latitude,:longitude,"PENDENTE",NOW(),NOW())';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($data);

        $id = (int) Database::connection()->lastInsertId();
        $protocol = ProtocolHelper::fromId($id);
        Database::connection()->prepare('UPDATE solicitacoes SET protocolo = :protocolo WHERE id = :id')->execute(['protocolo' => $protocol, 'id' => $id]);

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
        $sql = 'SELECT s.*, u.nome AS funcionario_nome
                FROM solicitacoes s
                LEFT JOIN usuarios u ON u.id = s.funcionario_id
                WHERE s.tenant_id = :tenant_id';
        $params = ['tenant_id' => $tenantId];

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

    public function find(int $id, int $tenantId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM solicitacoes WHERE id = :id AND tenant_id = :tenant_id LIMIT 1');
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByProtocol(string $protocol, string $phone, int $tenantId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT id, protocolo, status, data_solicitada, criado_em FROM solicitacoes WHERE tenant_id = :tenant_id AND protocolo = :protocolo AND telefone = :telefone LIMIT 1');
        $stmt->execute(['tenant_id' => $tenantId, 'protocolo' => $protocol, 'telefone' => $phone]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateStatus(int $id, int $tenantId, string $status, ?string $date = null, ?int $employeeId = null): void
    {
        $sql = 'UPDATE solicitacoes SET status = :status, atualizado_em = NOW()';
        $params = ['status' => $status, 'id' => $id, 'tenant_id' => $tenantId];

        if ($date !== null) {
            $sql .= ', data_solicitada = :data_solicitada';
            $params['data_solicitada'] = $date;
        }

        if ($employeeId !== null) {
            $sql .= ', funcionario_id = :funcionario_id';
            $params['funcionario_id'] = $employeeId;
        }

        if ($status === 'FINALIZADO') {
            $sql .= ', finalizado_em = NOW()';
        }

        $sql .= ' WHERE id = :id AND tenant_id = :tenant_id';
        Database::connection()->prepare($sql)->execute($params);
    }

    public function byEmployee(int $userId, int $tenantId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM solicitacoes WHERE tenant_id = :tenant_id AND funcionario_id = :funcionario_id AND status IN ("APROVADO", "PENDENTE") ORDER BY data_solicitada ASC');
        $stmt->execute(['tenant_id' => $tenantId, 'funcionario_id' => $userId]);
        return $stmt->fetchAll();
    }



    public function delete(int $id, int $tenantId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM solicitacoes WHERE id = :id AND tenant_id = :tenant_id');
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
    }

    public function chartByMonth(int $tenantId): array
    {
        $stmt = Database::connection()->prepare('SELECT DATE_FORMAT(criado_em, "%Y-%m") as mes, COUNT(*) as total FROM solicitacoes WHERE tenant_id = :tenant_id GROUP BY DATE_FORMAT(criado_em, "%Y-%m") ORDER BY mes ASC');
        $stmt->execute(['tenant_id' => $tenantId]);
        return $stmt->fetchAll();
    }
}
