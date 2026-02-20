<?php

declare(strict_types=1);

/**
 * Configuração principal para instalação em /catatreco (HostGator).
 */
const APP_NAME = 'Cata Treco SaaS';
const APP_ENV = 'production'; // production | development
const APP_DEBUG = false;

const APP_URL = 'https://prefsade.com.br/catatreco';
const APP_BASE_PATH = '/catatreco';
const APP_TIMEZONE = 'America/Sao_Paulo';

// Simplificação multi-tenant: tenant padrão por ID (funciona sem subdomínio).
const APP_DEFAULT_TENANT = 1;

const APP_FORCE_HTTPS = false; // Evita loop de redirect em proxy compartilhado

const UPLOAD_PATH = __DIR__ . '/../uploads';
const MAX_UPLOAD_BYTES = 5 * 1024 * 1024;

// Mapa gratuito (Leaflet + OSM + Nominatim). Sem API paga.
const GOOGLE_MAPS_API_KEY = null;
const WA_API_VERSION = 'v20.0';

const SESSION_NAME = 'catatreco_session';
