<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    public function index(): void
    {
        $dbConfig = require __DIR__ . '/../config/database.php';
        $db = Database::getConnection($dbConfig);

        $dashboard = (new DashboardService($db))->build();

        $this->view('dashboard/index', [
            'title' => 'Dashboard',
            'user' => Auth::user(),
            ...$dashboard,
        ]);
    }
}
