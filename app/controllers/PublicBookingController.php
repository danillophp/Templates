<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Logger;
use App\Models\Appointment;
use App\Models\Client;
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
        $serviceModel = new Service($db);
        $service = $serviceModel->findById($payload['servico_id']);

        if (!$service || (int) $service['categoria_id'] !== $payload['categoria_id']) {
            flash('error', 'Serviço inválido para a categoria selecionada.');
            redirect('/agendamento');
        }

        $start = new \DateTimeImmutable($payload['data'] . ' ' . $payload['hora'] . ':00');
        $end = $start->modify('+' . max(1, (int) $service['duracao_minutos']) . ' minutes');

        $appointmentModel = new Appointment($db);
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

        $valorTotal = (float) $service['valor'];
        $percentual = (float) ($service['percentual_entrada'] ?? 20);
        $valorEntrada = round($valorTotal * ($percentual / 100), 2);
        $valorRestante = round($valorTotal - $valorEntrada, 2);

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
            'observacoes' => $payload['observacoes'],
        ]);

        Logger::info('Pré-reserva pública criada', ['agendamento_id' => $appointmentId]);
        redirect('/agendamento/pagamento?id=' . $appointmentId);
    }

    public function payment(): void
    {
        $id = (int) input('id', '0');
        if ($id <= 0) {
            redirect('/agendamento');
        }

        $appointment = (new Appointment($this->db()))->findDetailed($id);
        if (!$appointment) {
            flash('error', 'Agendamento não encontrado.');
            redirect('/agendamento');
        }

        $this->view('public/payment', [
            'title' => 'Pagamento da Entrada',
            'appointment' => $appointment,
        ], 'layouts/public');
    }
}
