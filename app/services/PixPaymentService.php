<?php

declare(strict_types=1);

namespace App\Services;

class PixPaymentService
{
    public function createCharge(array $appointment): array
    {
        $reference = 'PIX-' . $appointment['id'] . '-' . bin2hex(random_bytes(4));
        $amount = (float) $appointment['valor_entrada'];

        return [
            'gateway' => 'pix_stub',
            'referencia_externa' => $reference,
            'status' => 'aguardando_confirmacao',
            'valor' => $amount,
            'pix_key' => 'studio@pix.com.br',
            'qr_code_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode('PIX|' . $reference . '|' . number_format($amount, 2, '.', '')),
            'mensagem' => 'Pague via PIX para confirmar o agendamento automaticamente.',
        ];
    }
}
