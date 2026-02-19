<?php

declare(strict_types=1);

const APP_NAME = 'Cata Treco';
const BASE_PATH = '/catatreco/public';
const UPLOAD_DIR = __DIR__ . '/../uploads';
const MAX_UPLOAD_SIZE = 5 * 1024 * 1024;

function base_url(string $path = ''): string
{
    return rtrim(BASE_PATH, '/') . '/' . ltrim($path, '/');
}
