<?php

return [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'port' => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_DATABASE') ?: 'santo821_treco',
    'username' => getenv('DB_USERNAME') ?: 'santo821_catatreco',
    'password' => getenv('DB_PASSWORD') ?: 'php@3903',
    'charset' => 'utf8mb4',
];
