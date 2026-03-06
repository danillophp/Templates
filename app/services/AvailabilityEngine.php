<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Motor de cálculo de horários disponíveis para agendamento.
 */
class AvailabilityEngine
{
    /**
     * @param string $date formato Y-m-d
     * @param string $startTime formato H:i:s
     * @param string $endTime formato H:i:s
     * @param int $intervalMinutes intervalo entre atendimentos
     * @param int $serviceDurationMinutes duração do serviço
     * @param array<int, array{hora_inicio:string,hora_fim:string}> $busySlots
     * @return array<int, string>
     */
    public function calculate(
        string $date,
        string $startTime,
        string $endTime,
        int $intervalMinutes,
        int $serviceDurationMinutes,
        array $busySlots
    ): array {
        $intervalMinutes = max(5, $intervalMinutes);
        $serviceDurationMinutes = max(1, $serviceDurationMinutes);

        $workStart = new \DateTimeImmutable($date . ' ' . $startTime);
        $workEnd = new \DateTimeImmutable($date . ' ' . $endTime);

        if ($workStart >= $workEnd) {
            return [];
        }

        $busy = [];
        foreach ($busySlots as $slot) {
            $busy[] = [
                new \DateTimeImmutable($date . ' ' . $slot['hora_inicio']),
                new \DateTimeImmutable($date . ' ' . $slot['hora_fim']),
            ];
        }

        $available = [];
        for ($cursor = $workStart; ; $cursor = $cursor->modify('+' . $intervalMinutes . ' minutes')) {
            $candidateEnd = $cursor->modify('+' . $serviceDurationMinutes . ' minutes');
            if ($candidateEnd > $workEnd) {
                break;
            }

            $hasConflict = false;
            foreach ($busy as [$busyStart, $busyEnd]) {
                // conflito se houver interseção temporal
                if ($cursor < $busyEnd && $candidateEnd > $busyStart) {
                    $hasConflict = true;
                    break;
                }
            }

            if (!$hasConflict) {
                $available[] = $cursor->format('H:i');
            }
        }

        return $available;
    }
}
