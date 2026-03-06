<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Notification extends Model
{
    public function create(array $data): int
    {
        $sql = 'INSERT INTO notificacoes (
                    agendamento_id,
                    cliente_id,
                    tipo,
                    canal,
                    destinatario,
                    mensagem,
                    status,
                    enviado_em,
                    erro_detalhes
                ) VALUES (
                    :agendamento_id,
                    :cliente_id,
                    :tipo,
                    :canal,
                    :destinatario,
                    :mensagem,
                    :status,
                    :enviado_em,
                    :erro_detalhes
                )';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':agendamento_id' => $data['agendamento_id'] ?? null,
            ':cliente_id' => $data['cliente_id'] ?? null,
            ':tipo' => $data['tipo'],
            ':canal' => $data['canal'] ?? 'interno',
            ':destinatario' => $data['destinatario'] ?? null,
            ':mensagem' => $data['mensagem'],
            ':status' => $data['status'] ?? 'pendente',
            ':enviado_em' => $data['enviado_em'] ?? null,
            ':erro_detalhes' => $data['erro_detalhes'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }
}
