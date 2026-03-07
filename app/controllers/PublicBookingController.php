<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Logger;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Payment;
use App\Services\PaymentProcessorService;
use App\Models\ScheduleConfig;
use App\Models\Service;
use App\Models\ServiceCategory;

class PublicBookingController extends Controller
{
    private function db(): \PDO
    {
        $dbConfig = require __DIR__ . '/../config/database.php';
        return Database::getConnection($dbConfig);
    }

    public function index(): void
    {
        $db = $this->db();
        $appointmentModel = new Appointment($db);
        $appointmentModel->expireOutdatedPreReservations();

        $categories = (new ServiceCategory($db))->all();
        $services = array_values(array_filter((new Service($db))->all(), static fn(array $s): bool => (int) $s['ativo'] === 1));

        $this->view('public/booking', [
            'title' => 'Agendamento Online',
            'categories' => $categories,
            'services' => $services,
        ], 'layouts/public');
    }

    public function reserve(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            http_response_code(419);
            exit('Token CSRF inválido.');
        }

        $payload = [
            'categoria_id' => (int) input('categoria_id', '0'),
            'servico_id' => (int) input('servico_id', '0'),
            'data' => input('data'),
            'hora' => input('hora'),
            'nome' => input('nome'),
            'telefone' => input('telefone'),
            'whatsapp' => input('whatsapp'),
            'email' => input('email'),
            'observacoes' => input('observacoes'),
        ];

        foreach (['categoria_id', 'servico_id', 'data', 'hora', 'nome', 'telefone', 'whatsapp', 'email'] as $required) {
            if (empty((string) $payload[$required])) {
                flash('error', 'Preencha todos os campos obrigatórios do agendamento.');
                redirect('/agendamento');
            }
        }

        $db = $this->db();
        $appointmentModel = new Appointment($db);
        $appointmentModel->expireOutdatedPreReservations();

        $serviceModel = new Service($db);
        $service = $serviceModel->findById($payload['servico_id']);
        if (!$service || (int) $service['categoria_id'] !== $payload['categoria_id']) {
            flash('error', 'Serviço inválido para a categoria selecionada.');
            redirect('/agendamento');
        }

        $scheduleConfig = (new ScheduleConfig($db))->get();
        $validadeMinutos = max(1, (int) ($scheduleConfig['tempo_validade_pre_reserva'] ?? 60));

        $start = new \DateTimeImmutable($payload['data'] . ' ' . $payload['hora'] . ':00');
        $end = $start->modify('+' . max(1, (int) $service['duracao_minutos']) . ' minutes');

        if ($appointmentModel->hasConflict($payload['data'], $start->format('H:i:s'), $end->format('H:i:s'))) {
            flash('error', 'Este horário acabou de ser ocupado. Escolha outro horário disponível.');
            redirect('/agendamento');
        }

        $clientModel = new Client($db);
        $clientId = $clientModel->findOrCreateByPhone([
            'nome' => $payload['nome'],
            'telefone' => $payload['telefone'],
            'whatsapp' => $payload['whatsapp'],
            'email' => $payload['email'],
            'observacoes' => $payload['observacoes'],
        ]);

        // Regra: entrada fixa de 20%
        $valorTotal = (float) $service['valor'];
        $valorEntrada = round($valorTotal * 0.20, 2);
        $valorRestante = round($valorTotal - $valorEntrada, 2);
        $expiraEm = (new \DateTimeImmutable())->modify('+' . $validadeMinutos . ' minutes')->format('Y-m-d H:i:s');

        $appointmentId = $appointmentModel->create([
            'cliente_id' => $clientId,
            'servico_id' => (int) $service['id'],
            'data_agendamento' => $payload['data'],
            'hora_inicio' => $start->format('H:i:s'),
            'hora_fim' => $end->format('H:i:s'),
            'status' => 'pre_reservado',
            'valor_total' => $valorTotal,
            'valor_entrada' => $valorEntrada,
            'valor_restante' => $valorRestante,
            'pre_reserva_expira_em' => $expiraEm,
            'observacoes' => $payload['observacoes'],
        ]);

        Logger::info('Pré-reserva pública criada', ['agendamento_id' => $appointmentId, 'expira_em' => $expiraEm]);
        redirect('/agendamento/pagamento?id=' . $appointmentId);
    }

    public function payment(): void
    {
        $id = (int) input('id', '0');
        if ($id <= 0) {
            redirect('/agendamento');
        }

        $appointmentModel = new Appointment($this->db());
        $appointmentModel->expireOutdatedPreReservations();
        $appointment = $appointmentModel->findDetailed($id);

        if (!$appointment) {
            flash('error', 'Agendamento não encontrado.');
            redirect('/agendamento');
        }

        if ($appointment['status'] === 'cancelado') {
            flash('error', 'Esta pré-reserva expirou. Escolha um novo horário.');
            redirect('/agendamento');
        }

        $payment = (new Payment($this->db()))->findLastByAppointment($id);

        $this->view('public/payment', [
            'title' => 'Pagamento da Entrada',
            'appointment' => $appointment,
            'payment' => $payment,
        ], 'layouts/public');
    }

    public function confirmPayment(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            http_response_code(419);
            exit('Token CSRF inválido.');
        }

        $appointmentId = (int) input('agendamento_id', '0');
        $metodo = input('metodo_pagamento');

        if ($appointmentId <= 0 || $metodo === '') {
            flash('error', 'Dados de pagamento inválidos.');
            redirect('/agendamento');
        }

        $db = $this->db();
        $appointmentModel = new Appointment($db);
        $appointmentModel->expireOutdatedPreReservations();

        $appointment = $appointmentModel->findDetailed($appointmentId);
        if (!$appointment || $appointment['status'] === 'cancelado') {
            flash('error', 'Pré-reserva não encontrada ou expirada.');
            redirect('/agendamento');
        }

        (new PaymentProcessorService($db))->startPayment($appointment, $metodo);

        if ($metodo === 'dinheiro') {
            $appointmentModel->transitionStatus($appointmentId, 'confirmado', 'Pagamento manual confirmado no balcão.');
            Logger::info('Agendamento confirmado com pagamento manual', ['agendamento_id' => $appointmentId]);
            flash('success', 'Pagamento manual registrado. Agendamento confirmado.');
        } else {
            Logger::info('Pagamento iniciado', ['agendamento_id' => $appointmentId, 'metodo' => $metodo]);
            flash('success', 'Pagamento iniciado. Finalize e aguarde confirmação automática.');
        }

        redirect('/agendamento/pagamento?id=' . $appointmentId);
    }
}
