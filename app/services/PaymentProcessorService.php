<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use App\Models\Appointment;
use App\Models\Payment;
use PDO;

class PaymentProcessorService
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function startPayment(array $appointment, string $method): void
    {
        $paymentModel = new Payment($this->db);
        $appointmentModel = new Appointment($this->db);

        $appointmentModel->transitionStatus((int) $appointment['id'], 'aguardando_pagamento', 'Aguardando pagamento iniciado.');

        if ($method === 'dinheiro') {
            $paymentModel->createGatewayPayment([
                'agendamento_id' => (int) $appointment['id'],
                'metodo_pagamento' => 'dinheiro',
                'valor' => (float) $appointment['valor_entrada'],
                'status' => 'pendente',
                'gateway' => 'manual',
                'referencia_externa' => 'MANUAL-' . $appointment['id'] . '-' . time(),
                'transaction_id' => null,
                'payload' => ['manual' => true],
                'pago_em' => null,
            ]);
            return;
        }

        $charge = $method === 'pix'
            ? (new PixPaymentService())->createCharge($appointment)
            : (new CardPaymentService())->createCharge($appointment);

        $paymentModel->createGatewayPayment([
            'agendamento_id' => (int) $appointment['id'],
            'metodo_pagamento' => $method,
            'valor' => (float) $appointment['valor_entrada'],
            'status' => $charge['status'],
            'gateway' => $charge['gateway'],
            'referencia_externa' => $charge['referencia_externa'],
            'transaction_id' => null,
            'payload' => $charge,
            'pago_em' => null,
        ]);
    }

    public function handleGatewayCallback(string $reference, string $status, string $transactionId, array $payload = []): void
    {
        $paymentModel = new Payment($this->db);
        $appointmentModel = new Appointment($this->db);

        $normalized = match ($status) {
            'approved', 'pago', 'paid', 'confirmado' => 'pago',
            'cancelled', 'cancelado' => 'cancelado',
            'expired', 'expirado' => 'expirado',
            default => 'aguardando_confirmacao',
        };

        $payment = $paymentModel->findByReference($reference);
        if (!$payment) {
            Logger::warning('Webhook pagamento sem referência conhecida', ['reference' => $reference]);
            return;
        }

        $paymentModel->updateGatewayStatus((int) $payment['id'], $normalized, $transactionId, $payload, $normalized === 'pago');

        $appointmentId = (int) $payment['agendamento_id'];
        if ($normalized === 'pago') {
            $appointmentModel->transitionStatus($appointmentId, 'confirmado', 'Pagamento confirmado automaticamente.');
            return;
        }

        if ($normalized === 'cancelado' || $normalized === 'expirado') {
            $appointmentModel->transitionStatus($appointmentId, 'aguardando_pagamento', 'Pagamento não concluído (' . $normalized . ').');
        }
    }
}
