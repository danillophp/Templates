<?php

declare(strict_types=1);

const APP_NAME = 'Cata Treco SaaS';
const APP_ENV = 'production'; // production | development
const APP_DEBUG = false;

// Aplicação instalada em /public_html/catatreco
const APP_URL = 'https://www.prefsade.com.br/catatreco';
const APP_BASE_PATH = '/catatreco';
const APP_TIMEZONE = 'America/Sao_Paulo';
const APP_DEFAULT_TENANT = 'demo';

const APP_FORCE_HTTPS = true;

const UPLOAD_PATH = __DIR__ . '/../uploads';
const MAX_UPLOAD_BYTES = 5 * 1024 * 1024;

const WA_API_VERSION = 'v20.0';
const GOOGLE_MAPS_API_KEY = '';

const SESSION_NAME = 'catatreco_session';
