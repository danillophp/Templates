<?php
require __DIR__ . '/../index.php';
$service = new \App\Services\MessageQueueService();
$result = $service->processPending();
echo json_encode($result, JSON_UNESCAPED_UNICODE) . PHP_EOL;
