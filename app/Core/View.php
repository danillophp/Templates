<?php

namespace App\Core;

class View
{
    public static function render(string $view, array $data = [], string $layout = 'layouts/main'): void
    {
        $appName = Config::get('app.app_name');
        $baseUrl = Config::get('app.base_url');
        extract($data, EXTR_SKIP);

        ob_start();
        require __DIR__ . '/../Views/' . $view . '.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/' . $layout . '.php';
    }
}
