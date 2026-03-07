<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => getenv('APP_NAME') ?: 'Studio Bruna Nayara',
        'env' => getenv('APP_ENV') ?: 'production',
        'debug' => filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOL),
        'url' => rtrim(getenv('APP_URL') ?: 'http://studiobrunanayara.com.br/agenda', '/'),
        'base_path' => getenv('APP_BASE_PATH') ?: '/agenda',
        'timezone' => getenv('TIMEZONE') ?: 'America/Sao_Paulo',
    ],
    'database' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'database' => getenv('DB_DATABASE') ?: 'santo821_studiobrunanayara',
        'username' => getenv('DB_USERNAME') ?: 'santo821_studiobrunanayara',
        'password' => getenv('DB_PASSWORD') ?: 'php@3903.',
        'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
    ],
    'session' => [
        'name' => 'studio_bruna_nayara_session',
        'lifetime' => 7200,
    ],
];
