<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->routes['GET'][$path] = compact('handler', 'middlewares');
    }

    public function post(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->routes['POST'][$path] = compact('handler', 'middlewares');
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $basePath = normalize_base_path((string) app_config('app.base_path', ''));

        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath)) ?: '/';
        }

        $route = $this->routes[$method][$path] ?? null;

        if (!$route) {
            http_response_code(404);
            exit('Página não encontrada.');
        }

        foreach ($route['middlewares'] as $middleware) {
            $middleware();
        }

        $handler = $route['handler'];
        if (is_array($handler) && count($handler) === 2) {
            [$class, $action] = $handler;
            (new $class())->{$action}();
            return;
        }

        $handler();
    }
}
