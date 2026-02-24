<?php

return [
    'app_name' => 'CATA TRECO',
    'base_url' => getenv('APP_URL') ?: 'https://www.prefsade.com.br/catatreco',
    'base_path' => getenv('APP_BASE_PATH') ?: '/catatreco/public',
    'timezone' => 'America/Sao_Paulo',
    'session_name' => 'catatreco_session',
    'csrf_token_name' => '_csrf',
    'whatsapp_token' => getenv('WHATSAPP_TOKEN') ?: '',
    'whatsapp_phone_number_id' => getenv('WHATSAPP_PHONE_NUMBER_ID') ?: '',
];
