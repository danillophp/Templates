<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Appointment extends Model
{
    public function listBusyByDate(string $date): array
    {
        $sql = 'SELECT hora_inicio, hora_fim, status
                FROM agendamentos
                WHERE data_agendamento = :data_agendamento
                  AND status IN ("pre_reservado", "aguardando_pagamento", "confirmado", "realizado")';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':data_agendamento' => $date]);

        return $stmt->fetchAll();
    }

    public function hasConflict(string $date, string $startTime, string $endTime): bool
    {
        $sql = 'SELECT id FROM agendamentos
                WHERE data_agendamento = :data
                  AND status IN ("pre_reservado", "aguardando_pagamento", "confirmado", "realizado")
                  AND (:start_time < hora_fim AND :end_time > hora_inicio)
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':data' => $date,
            ':start_time' => $startTime,
            ':end_time' => $endTime,
        ]);

        return (bool) $stmt->fetch();
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO agendamentos (
                    cliente_id,
                    servico_id,
                    data_agendamento,
                    hora_inicio,
                    hora_fim,
                    status,
                    valor_total,
                    valor_entrada,
                    valor_restante,
                    observacoes
                ) VALUES (
                    :cliente_id,
                    :servico_id,
                    :data_agendamento,
                    :hora_inicio,
                    :hora_fim,
                    :status,
                    :valor_total,
                    :valor_entrada,
                    :valor_restante,
                    :observacoes
                )';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cliente_id' => $data['cliente_id'],
            ':servico_id' => $data['servico_id'],
            ':data_agendamento' => $data['data_agendamento'],
            ':hora_inicio' => $data['hora_inicio'],
            ':hora_fim' => $data['hora_fim'],
            ':status' => $data['status'],
            ':valor_total' => $data['valor_total'],
            ':valor_entrada' => $data['valor_entrada'],
            ':valor_restante' => $data['valor_restante'],
            ':observacoes' => $data['observacoes'] ?: null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findDetailed(int $id): ?array
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
                    c.nome AS cliente_nome,
                    c.telefone,
                    c.whatsapp,
                    c.email,
                    s.nome AS servico_nome
                FROM agendamentos a
                INNER JOIN clientes c ON c.id = a.cliente_id
                INNER JOIN servicos s ON s.id = a.servico_id
                WHERE a.id = :id
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }
}
