<?php

declare(strict_types=1);

/**
 * Configurações de banco para MySQL (HostGator compatível).
 * Sugestão: configure via variáveis de ambiente no painel de hospedagem.
 */
return [
    'driver' => 'mysql',
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_DATABASE') ?: 'estudio_bruna_nayara',
    'username' => getenv('DB_USERNAME') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];
