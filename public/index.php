<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

try {
    (new App\Core\Router())->dispatch();
} catch (Throwable $e) {
    http_response_code(500);
    echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Cata Treco</title><style>body{font-family:Arial,sans-serif;background:#f4f6f8;padding:40px}.box{max-width:720px;margin:auto;background:#fff;padding:24px;border-radius:12px;box-shadow:0 4px 16px rgba(0,0,0,.08)}</style></head><body><div class="box"><h2>Serviço temporariamente indisponível</h2><p>Não foi possível carregar a página neste momento. Tente novamente em instantes.</p><p><a href="?r=citizen/home">Voltar para página inicial</a></p></div></body></html>';
}
