<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Appointment;

class CalendarController extends Controller
{
    private function model(): Appointment
    {
        $dbConfig = require __DIR__ . '/../config/database.php';
        return new Appointment(Database::getConnection($dbConfig));
    }

    public function index(): void
    {
        $this->view('calendar/index', [
            'title' => 'Agenda em Calendário',
            'user' => Auth::user(),
        ]);
    }

    public function events(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $start = input('start');
        $end = input('end');
        $status = input('status');

        echo json_encode($this->model()->listForCalendar($start, $end, $status), JSON_UNESCAPED_UNICODE);
    }
}
