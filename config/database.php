<?php

return [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_DATABASE') ?: 'mapa_politico',
    'username' => getenv('DB_USERNAME') ?: 'mapa_politico',
    'password' => getenv('DB_PASSWORD') ?: 'Php@3903*',
    'charset' => 'utf8mb4',
];
