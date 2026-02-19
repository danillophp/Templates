<?php

declare(strict_types=1);

// Configurações principais da aplicação.
const APP_NAME = 'CATA TRECO';
const APP_URL = 'https://www.prefsade.com.br/catatreco';
const APP_BASE_PATH = '/catatreco/public';
const APP_TIMEZONE = 'America/Sao_Paulo';

// Uploads.
const UPLOAD_PATH = __DIR__ . '/../uploads';
const MAX_UPLOAD_BYTES = 5 * 1024 * 1024;

// WhatsApp Cloud API (configurar em produção).
const WA_API_ENABLED = false;
const WA_TOKEN = '';
const WA_PHONE_NUMBER_ID = '';
const WA_API_VERSION = 'v20.0';

// Segurança da sessão.
const SESSION_NAME = 'catatreco_session';
