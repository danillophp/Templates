<?php

return [
    'app_name' => 'Mapa Político',
    'base_url' => getenv('APP_URL') ?: 'http://localhost',
    'timezone' => 'America/Sao_Paulo',
    'google_maps_api_key' => getenv('GOOGLE_MAPS_API_KEY') ?: 'SUA_CHAVE_AQUI',
    'session_name' => 'mapa_politico_session',
    'csrf_token_name' => '_csrf',
];
