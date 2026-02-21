<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Middlewares\AuthMiddleware;
use App\Models\NotificationModel;

class ApiController extends Controller
{
    public function poll(): void
    {
        AuthMiddleware::handle();
        header('Content-Type: application/json');
        $lastId = (int)($_GET['last_id'] ?? 0);
        echo json_encode((new NotificationModel())->latestAfter($lastId), JSON_UNESCAPED_UNICODE);
    }
}
