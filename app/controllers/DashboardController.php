<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;

class DashboardController extends Controller
{
    public function index(): void
    {
        $dbConfig = require __DIR__ . '/../config/database.php';
        $db = Database::getConnection($dbConfig);

        $summary = [
            'agendamentos_hoje' => (int) $db->query('SELECT COUNT(*) FROM agendamentos WHERE data_agendamento = CURDATE()')->fetchColumn(),
            'pagamentos_recebidos_hoje' => (float) $db->query('SELECT COALESCE(SUM(valor),0) FROM pagamentos WHERE status = "pago" AND DATE(pago_em) = CURDATE()')->fetchColumn(),
            'saldo_a_receber_hoje' => (float) $db->query('SELECT COALESCE(SUM(valor_restante),0) FROM agendamentos WHERE data_agendamento = CURDATE() AND status IN ("confirmado","realizado")')->fetchColumn(),
            'clientes_do_dia' => (int) $db->query('SELECT COUNT(DISTINCT cliente_id) FROM agendamentos WHERE data_agendamento = CURDATE()')->fetchColumn(),
        ];

        $todayAppointmentsStmt = $db->query('SELECT a.id, a.hora_inicio, a.hora_fim, a.status, a.valor_total, c.nome AS cliente_nome, s.nome AS servico_nome
                                             FROM agendamentos a
                                             INNER JOIN clientes c ON c.id = a.cliente_id
                                             INNER JOIN servicos s ON s.id = a.servico_id
                                             WHERE a.data_agendamento = CURDATE()
                                             ORDER BY a.hora_inicio ASC');
        $todayAppointments = $todayAppointmentsStmt->fetchAll();

        $appointmentsByDayStmt = $db->query('SELECT DATE_FORMAT(data_agendamento, "%Y-%m-%d") AS dia, COUNT(*) AS total
                                             FROM agendamentos
                                             WHERE data_agendamento >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                                             GROUP BY data_agendamento
                                             ORDER BY data_agendamento ASC');
        $appointmentsByDayRows = $appointmentsByDayStmt->fetchAll();

        $appointmentsMap = [];
        foreach ($appointmentsByDayRows as $row) {
            $appointmentsMap[$row['dia']] = (int) $row['total'];
        }

        $appointmentsByDay = [];
        for ($i = 6; $i >= 0; $i--) {
            $dateKey = date('Y-m-d', strtotime('-' . $i . ' days'));
            $appointmentsByDay[] = [
                'label' => date('d/m', strtotime($dateKey)),
                'total' => $appointmentsMap[$dateKey] ?? 0,
            ];
        }

        $monthlyRevenueStmt = $db->query('SELECT DATE_FORMAT(pago_em, "%Y-%m") AS ym, COALESCE(SUM(valor),0) AS total
                                          FROM pagamentos
                                          WHERE status = "pago" AND pago_em IS NOT NULL
                                          GROUP BY DATE_FORMAT(pago_em, "%Y-%m")
                                          ORDER BY ym DESC
                                          LIMIT 12');
        $monthlyRevenueRows = array_reverse($monthlyRevenueStmt->fetchAll());

        $revenueMap = [];
        foreach ($monthlyRevenueRows as $row) {
            $revenueMap[$row['ym']] = (float) $row['total'];
        }

        $monthlyRevenue = [];
        for ($i = 11; $i >= 0; $i--) {
            $monthKey = date('Y-m', strtotime(date('Y-m-01') . ' -' . $i . ' months'));
            $monthlyRevenue[] = [
                'label' => date('m/Y', strtotime($monthKey . '-01')),
                'total' => $revenueMap[$monthKey] ?? 0.0,
            ];
        }

        $this->view('dashboard/index', [
            'title' => 'Dashboard',
            'user' => Auth::user(),
            'summary' => $summary,
            'todayAppointments' => $todayAppointments,
            'appointmentsByDay' => $appointmentsByDay,
            'monthlyRevenue' => $monthlyRevenue,
        ]);
    }
}
