<?php
require __DIR__ . '/../app/bootstrap.php';

use App\Services\MessageQueueService;
use App\Core\ErrorHandler;

ErrorHandler::register();
(new MessageQueueService())->process();
echo 'Fila processada em ' . date('Y-m-d H:i:s') . PHP_EOL;
