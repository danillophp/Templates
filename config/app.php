<?php
return [
    'APP_ENV' => getenv('APP_ENV') ?: 'development',
    'APP_URL' => getenv('APP_URL') ?: 'https://prefsade.com.br/catatreco',
    'APP_BASE_PATH' => getenv('APP_BASE_PATH') ?: '/catatreco',
    'APP_NAME' => getenv('APP_NAME') ?: 'Cata Treco',
    'TIMEZONE' => getenv('TIMEZONE') ?: 'America/Sao_Paulo',
    'MAIL_FROM' => getenv('MAIL_FROM') ?: 'no-reply@prefsade.com.br',
    'MAIL_FROM_NAME' => getenv('MAIL_FROM_NAME') ?: 'Cata Treco',
    'WHATSAPP_API_URL' => getenv('WHATSAPP_API_URL') ?: '',
    'WHATSAPP_API_TOKEN' => getenv('WHATSAPP_API_TOKEN') ?: '',
];
