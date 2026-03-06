<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ScheduleConfig extends Model
{
    public function get(): ?array
    {
        $stmt = $this->db->query('SELECT id, hora_inicio, hora_fim, intervalo_minutos, dias_funcionamento, tempo_validade_pre_reserva, created_at, updated_at FROM configuracoes_agenda ORDER BY id ASC LIMIT 1');
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(array $data): void
    {
        $sql = 'INSERT INTO configuracoes_agenda (
                    hora_inicio,
                    hora_fim,
                    intervalo_minutos,
                    dias_funcionamento,
                    tempo_validade_pre_reserva
                ) VALUES (
                    :hora_inicio,
                    :hora_fim,
                    :intervalo_minutos,
                    :dias_funcionamento,
                    :tempo_validade_pre_reserva
                )';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':hora_inicio' => $data['hora_inicio'],
            ':hora_fim' => $data['hora_fim'],
            ':intervalo_minutos' => $data['intervalo_minutos'],
            ':dias_funcionamento' => $data['dias_funcionamento'],
            ':tempo_validade_pre_reserva' => $data['tempo_validade_pre_reserva'],
        ]);
    }

    public function update(int $id, array $data): void
    {
        $sql = 'UPDATE configuracoes_agenda
                SET hora_inicio = :hora_inicio,
                    hora_fim = :hora_fim,
                    intervalo_minutos = :intervalo_minutos,
                    dias_funcionamento = :dias_funcionamento,
                    tempo_validade_pre_reserva = :tempo_validade_pre_reserva
                WHERE id = :id';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':hora_inicio' => $data['hora_inicio'],
            ':hora_fim' => $data['hora_fim'],
            ':intervalo_minutos' => $data['intervalo_minutos'],
            ':dias_funcionamento' => $data['dias_funcionamento'],
            ':tempo_validade_pre_reserva' => $data['tempo_validade_pre_reserva'],
        ]);
    }
}
