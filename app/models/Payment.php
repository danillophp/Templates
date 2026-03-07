<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Payment extends Model
{
    public function createEntryPayment(array $data): int
    {
        return $this->createGatewayPayment([
            'agendamento_id' => $data['agendamento_id'],
            'metodo_pagamento' => $data['metodo_pagamento'],
            'valor' => $data['valor'],
            'status' => $data['status'],
            'gateway' => $data['gateway'] ?? null,
            'referencia_externa' => $data['referencia_externa'] ?? null,
            'transaction_id' => $data['transaction_id'] ?? null,
            'payload' => $data['payload'] ?? null,
            'pago_em' => $data['pago_em'] ?? null,
        ]);
    }

    public function createGatewayPayment(array $data): int
    {
        $sql = 'INSERT INTO pagamentos (agendamento_id, tipo, valor, metodo_pagamento, status, referencia_externa, transaction_id, gateway, payload, pago_em)
                VALUES (:agendamento_id, :tipo, :valor, :metodo_pagamento, :status, :referencia_externa, :transaction_id, :gateway, :payload, :pago_em)';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':agendamento_id' => $data['agendamento_id'],
            ':tipo' => 'entrada',
            ':valor' => $data['valor'],
            ':metodo_pagamento' => $data['metodo_pagamento'],
            ':status' => $data['status'],
            ':referencia_externa' => $data['referencia_externa'],
            ':transaction_id' => $data['transaction_id'],
            ':gateway' => $data['gateway'],
            ':payload' => isset($data['payload']) ? json_encode($data['payload'], JSON_UNESCAPED_UNICODE) : null,
            ':pago_em' => $data['pago_em'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function hasPaidEntry(int $appointmentId): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM pagamentos WHERE agendamento_id = :agendamento_id AND tipo = "entrada" AND status = "pago" LIMIT 1');
        $stmt->execute([':agendamento_id' => $appointmentId]);
        return (bool) $stmt->fetch();
    }

    public function findByReference(string $reference): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM pagamentos WHERE referencia_externa = :r LIMIT 1');
        $stmt->execute([':r' => $reference]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateGatewayStatus(int $id, string $status, ?string $transactionId, array $payload, bool $paidNow): void
    {
        $stmt = $this->db->prepare('UPDATE pagamentos
            SET status = :status,
                transaction_id = COALESCE(:transaction_id, transaction_id),
                payload = :payload,
                pago_em = CASE WHEN :paid_now = 1 THEN NOW() ELSE pago_em END,
                updated_at = NOW()
            WHERE id = :id');

        $stmt->execute([
            ':status' => $status,
            ':transaction_id' => $transactionId,
            ':payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            ':paid_now' => $paidNow ? 1 : 0,
            ':id' => $id,
        ]);
    }

    public function findLastByAppointment(int $appointmentId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM pagamentos WHERE agendamento_id = :id ORDER BY id DESC LIMIT 1');
        $stmt->execute([':id' => $appointmentId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
