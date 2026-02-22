<?php
return [
    'APP_ENV' => getenv('APP_ENV') ?: 'development',
    'APP_URL' => getenv('APP_URL') ?: 'https://prefsade.com.br/catatreco',
    'APP_BASE_PATH' => getenv('APP_BASE_PATH') ?: '/catatreco',
    'TIMEZONE' => getenv('TIMEZONE') ?: 'America/Sao_Paulo',
    'APP_NAME' => 'Cata Treco',
    'MAIL_FROM' => getenv('MAIL_FROM') ?: 'no-reply@prefsade.com.br',
    'MAIL_FROM_NAME' => 'Cata Treco',
];
