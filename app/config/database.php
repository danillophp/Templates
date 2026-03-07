<?php

declare(strict_types=1);

$config = require __DIR__ . '/config.php';
$db = $config['database'];

return [
    'driver' => 'mysql',
    'host' => $db['host'],
    'port' => getenv('DB_PORT') ?: '3306',
    'database' => $db['database'],
    'username' => $db['username'],
    'password' => $db['password'],
    'charset' => $db['charset'],
    'collation' => 'utf8mb4_unicode_ci',
];
