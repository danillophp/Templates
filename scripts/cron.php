<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$service = new App\Services\BillingService();
$result = $service->runDaily();

echo '[CRON CATA TRECO] ' . json_encode($result, JSON_UNESCAPED_UNICODE) . PHP_EOL;
