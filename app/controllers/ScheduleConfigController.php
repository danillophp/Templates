<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Logger;
use App\Models\ScheduleConfig;

class ScheduleConfigController extends Controller
{
    private function model(): ScheduleConfig
    {
        $dbConfig = require __DIR__ . '/../config/database.php';
        return new ScheduleConfig(Database::getConnection($dbConfig));
    }

    public function edit(): void
    {
        $config = $this->model()->get();

        if (!$config) {
            $config = [
                'id' => null,
                'hora_inicio' => '08:00',
                'hora_fim' => '18:00',
                'intervalo_minutos' => 30,
                'dias_funcionamento' => '1,2,3,4,5,6',
                'tempo_validade_pre_reserva' => 60,
            ];
        }

        $this->view('schedule/config', [
            'title' => 'Configuração da Agenda',
            'user' => Auth::user(),
            'config' => $config,
        ]);
    }

    public function save(): void
    {
        if (!verify_csrf_token($_POST['_token'] ?? null)) {
            http_response_code(419);
            exit('Token CSRF inválido.');
        }

        $data = [
            'hora_inicio' => input('hora_inicio'),
            'hora_fim' => input('hora_fim'),
            'intervalo_minutos' => (int) input('intervalo_minutos', '30'),
            'dias_funcionamento' => input('dias_funcionamento', '1,2,3,4,5,6'),
            'tempo_validade_pre_reserva' => (int) input('tempo_validade_pre_reserva', '60'),
        ];

        if ($data['hora_inicio'] >= $data['hora_fim']) {
            flash('error', 'A hora de início deve ser menor que a hora de fim.');
            redirect('/agenda/configuracoes');
        }

        if ($data['intervalo_minutos'] < 5) {
            flash('error', 'O intervalo mínimo entre atendimentos é 5 minutos.');
            redirect('/agenda/configuracoes');
        }

        if ($data['tempo_validade_pre_reserva'] < 1) {
            $data['tempo_validade_pre_reserva'] = 60;
        }

        $current = $this->model()->get();
        if ($current) {
            $this->model()->update((int) $current['id'], $data);
            Logger::info('Configuração da agenda atualizada', ['id' => (int) $current['id']]);
        } else {
            $this->model()->create($data);
            Logger::info('Configuração da agenda criada');
        }

        flash('success', 'Configuração da agenda salva com sucesso.');
        redirect('/agenda/configuracoes');
    }
}
