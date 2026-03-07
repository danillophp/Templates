<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'Studio Bruna Nayara',
        'url' => getenv('APP_URL') ?: 'http://www.studiobrunanayara.com.br',
        'base_path' => getenv('APP_BASE_PATH') ?: '/agenda',
        'debug' => filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOL),
        'timezone' => 'America/Sao_Paulo',
    ],
    'session' => [
        'name' => 'studio_bruna_nayara_session',
        'lifetime' => 7200,
    ],
];
