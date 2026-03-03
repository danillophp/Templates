<?php

declare(strict_types=1);

const APP_NAME = 'Educa SADE';
const APP_TIMEZONE = 'America/Sao_Paulo';
const APP_BASE_PATH = '';
const SESSION_NAME = 'educa_sade';
const UPLOAD_PATH = __DIR__ . '/../storage/uploads';
const LOG_PATH = __DIR__ . '/../storage/logs/app.log';

const ROLES = [
    'super_admin',
    'secretaria',
    'gestor',
    'diretor',
    'estoque',
    'conteudo',
];
