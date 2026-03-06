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

        $stats = [
            'clientes' => (int) $db->query('SELECT COUNT(*) FROM clientes')->fetchColumn(),
            'categorias' => (int) $db->query('SELECT COUNT(*) FROM categorias_servicos')->fetchColumn(),
            'servicos' => (int) $db->query('SELECT COUNT(*) FROM servicos')->fetchColumn(),
            'agendamentos_hoje' => (int) $db->query("SELECT COUNT(*) FROM agendamentos WHERE DATE(data_hora_inicio) = CURDATE()")->fetchColumn(),
        ];

        $this->view('dashboard/index', [
            'title' => 'Dashboard',
            'user' => Auth::user(),
            'stats' => $stats,
        ]);
    }
}
