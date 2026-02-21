<?php
require_once __DIR__ . '/../bootstrap.php';

use App\Middlewares\AuthMiddleware;
use App\Models\NotificationModel;

AuthMiddleware::handle();
header('Content-Type: application/json');
$lastId = (int)($_GET['last_id'] ?? 0);
echo json_encode((new NotificationModel())->latestAfter($lastId), JSON_UNESCAPED_UNICODE);
