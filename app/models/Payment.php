<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Payment extends Model
{
    public function createEntryPayment(array $data): int
    {
        $sql = 'INSERT INTO pagamentos (agendamento_id, tipo, valor, metodo_pagamento, status, pago_em)
                VALUES (:agendamento_id, :tipo, :valor, :metodo_pagamento, :status, :pago_em)';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':agendamento_id' => $data['agendamento_id'],
            ':tipo' => 'entrada',
            ':valor' => $data['valor'],
            ':metodo_pagamento' => $data['metodo_pagamento'],
            ':status' => $data['status'],
            ':pago_em' => $data['pago_em'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function hasPaidEntry(int $appointmentId): bool
    {
        $sql = 'SELECT id
                FROM pagamentos
                WHERE agendamento_id = :agendamento_id
                  AND tipo = "entrada"
                  AND status = "pago"
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':agendamento_id' => $appointmentId]);

        return (bool) $stmt->fetch();
    }
}
