<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Appointment;
use App\Models\Payment;
use App\Services\PaymentProcessorService;

class PaymentController extends Controller
{
    private function db(): \PDO
    {
        $dbConfig = require __DIR__ . '/../config/database.php';
        return Database::getConnection($dbConfig);
    }

    public function process(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            http_response_code(419);
            exit('Token CSRF inválido.');
        }

        $appointmentId = (int) input('agendamento_id', '0');
        $method = input('metodo_pagamento');

        if ($appointmentId <= 0 || !in_array($method, ['pix', 'cartao', 'dinheiro'], true)) {
            flash('error', 'Dados de pagamento inválidos.');
            redirect('/agendamento');
        }

        $db = $this->db();
        $appointment = (new Appointment($db))->findDetailed($appointmentId);
        if (!$appointment) {
            flash('error', 'Agendamento não encontrado.');
            redirect('/agendamento');
        }

        $processor = new PaymentProcessorService($db);
        $processor->startPayment($appointment, $method);

        flash('success', 'Pagamento iniciado. Acompanhe o status abaixo.');
        redirect('/agendamento/pagamento?id=' . $appointmentId);
    }

    public function status(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $appointmentId = (int) input('agendamento_id', '0');
        if ($appointmentId <= 0) {
            echo json_encode(['ok' => false]);
            return;
        }

        $row = (new Payment($this->db()))->findLastByAppointment($appointmentId);
        echo json_encode(['ok' => true, 'payment' => $row], JSON_UNESCAPED_UNICODE);
    }
}
