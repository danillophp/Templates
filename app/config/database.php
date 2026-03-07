<?php

declare(strict_types=1);

return [
    'driver' => 'mysql',
    'host' => getenv('DB_HOST') ?: 'localhost',
    'port' => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_DATABASE') ?: 'santo821_studiobrunanayara',
    'username' => getenv('DB_USERNAME') ?: 'santo821_studiobrunanayara',
    'password' => getenv('DB_PASSWORD') ?: 'php@3903.',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];
