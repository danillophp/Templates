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
}
