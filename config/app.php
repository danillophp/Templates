<?php

declare(strict_types=1);

/**
 * Configuração principal para instalação em /catatreco (HostGator).
 */
const APP_NAME = 'Cata Treco';
const APP_ENV = 'production'; // production | development
const APP_DEBUG = false;

const APP_URL = 'https://prefsade.com.br/catatreco';
const APP_BASE_PATH = '/catatreco';
const APP_TIMEZONE = 'America/Sao_Paulo';

// Simplificação multi-tenant: tenant padrão por ID (funciona sem subdomínio).
const APP_DEFAULT_TENANT = 1;

const APP_FORCE_HTTPS = false; // Evita loop de redirect em proxy compartilhado

const UPLOAD_PATH = __DIR__ . '/../uploads';
const STORAGE_PATH = __DIR__ . '/../storage';
const MAX_UPLOAD_BYTES = 5 * 1024 * 1024;

// Google Maps API (produção)
const GOOGLE_MAPS_API_KEY = ''; // definir chave válida com restrição de domínio
const WA_API_VERSION = 'v20.0';

const MAIL_FROM_ADDRESS = 'nao-responda@prefsade.com.br';
const MAIL_FROM_NAME = 'Prefeitura Municipal - Cata Treco';

const SESSION_NAME = 'catatreco_session';
