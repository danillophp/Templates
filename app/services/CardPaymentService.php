<?php

declare(strict_types=1);

namespace App\Services;

class CardPaymentService
{
    public function createCharge(array $appointment): array
    {
        $reference = 'CARD-' . $appointment['id'] . '-' . bin2hex(random_bytes(4));

        return [
            'gateway' => 'card_stub',
            'referencia_externa' => $reference,
            'status' => 'aguardando_confirmacao',
            'valor' => (float) $appointment['valor_entrada'],
            'checkout_url' => base_url('/agendamento/pagamento?id=' . (int) $appointment['id']),
            'mensagem' => 'Pagamento por cartão iniciado. Aguardando confirmação da adquirente.',
        ];
    }
}
