<?php
namespace App\Controllers;

use App\Models\NotificationModel;

class ApiController
{
    public function poll(): void
    {
        header('Content-Type: application/json');
        $lastId = (int)($_GET['last_id'] ?? 0);
        echo json_encode(['items' => (new NotificationModel())->since($lastId)]);
    }
}
