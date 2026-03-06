<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Models\Appointment;
use App\Models\ScheduleConfig;
use App\Models\Service;
use App\Services\AvailabilityEngine;

class AvailabilityController
{
    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function availableSlots(): void
    {
        $date = input('data');
        $serviceId = (int) input('servico_id', '0');

        if (!$this->isValidDate($date) || $serviceId <= 0) {
            $this->json(['error' => 'Parâmetros inválidos. Envie data (Y-m-d) e servico_id.'], 422);
        }

        $dbConfig = require __DIR__ . '/../config/database.php';
        $db = Database::getConnection($dbConfig);

        $appointmentModel = new Appointment($db);
        $appointmentModel->expireOutdatedPreReservations();

        $service = (new Service($db))->findById($serviceId);
        if (!$service || (int) $service['ativo'] !== 1) {
            $this->json(['error' => 'Serviço não encontrado ou inativo.'], 404);
        }

        $config = (new ScheduleConfig($db))->get();
        if (!$config) {
            $this->json(['error' => 'Agenda não configurada.'], 409);
        }

        $weekday = (int) (new \DateTimeImmutable($date))->format('N'); // 1=seg ... 7=dom
        $workingDays = array_map('trim', explode(',', (string) $config['dias_funcionamento']));
        if (!in_array((string) $weekday, $workingDays, true)) {
            $this->json([]);
        }

        $busySlots = $appointmentModel->listBusyByDate($date);

        $engine = new AvailabilityEngine();
        $available = $engine->calculate(
            $date,
            (string) $config['hora_inicio'],
            (string) $config['hora_fim'],
            (int) $config['intervalo_minutos'],
            (int) $service['duracao_minutos'],
            $busySlots
        );

        $this->json($available);
    }

    private function isValidDate(string $date): bool
    {
        $d = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $d !== false && $d->format('Y-m-d') === $date;
    }
}
