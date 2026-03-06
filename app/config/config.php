<?php

declare(strict_types=1);

/**
 * Configurações gerais da aplicação.
 * Em produção, ajuste APP_DEBUG para false.
 */
return [
    'app' => [
        'name' => 'Estúdio Bruna Nayara',
        'url' => getenv('APP_URL') ?: 'http://localhost:8000',
        'debug' => filter_var(getenv('APP_DEBUG') ?: true, FILTER_VALIDATE_BOOL),
        'timezone' => 'America/Sao_Paulo',
    ],
    'session' => [
        'name' => 'estudio_session',
        'lifetime' => 7200,
    ],
];
