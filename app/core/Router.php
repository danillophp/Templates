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
        $this->routes['GET'][] = ['path' => $this->normalizeRoutePath($path), 'handler' => $handler, 'middlewares' => $middlewares];
    }

    public function post(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->routes['POST'][] = ['path' => $this->normalizeRoutePath($path), 'handler' => $handler, 'middlewares' => $middlewares];
    }

    public function dispatch(string $method, string $uri): void
    {
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        if (!isset($this->routes[$method])) {
            http_response_code(405);
            exit('Método não permitido.');
        }

        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = preg_replace('#/+#', '/', $path) ?: '/';

        $basePath = normalize_base_path((string) app_config('app.base_path', ''));
        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath)) ?: '/';
        }

        $path = $this->normalizeRoutePath($path);

        $route = $this->findRoute($method, $path);

        if ($route === null) {
            http_response_code(404);
            exit('Página não encontrada.');
        }

        foreach ($route['middlewares'] as $middleware) {
            $middleware();
        }

        foreach ($route['params'] as $key => $value) {
            if (!isset($_GET[$key])) {
                $_GET[$key] = $value;
            }
        }

        $handler = $route['handler'];
        if (is_array($handler) && count($handler) === 2) {
            [$class, $action] = $handler;
            (new $class())->{$action}();
            return;
        }

        $handler();
    }

    private function findRoute(string $method, string $requestPath): ?array
    {
        foreach ($this->routes[$method] as $route) {
            $params = [];
            if ($this->matchRoute($route['path'], $requestPath, $params)) {
                return [
                    'handler' => $route['handler'],
                    'middlewares' => $route['middlewares'],
                    'params' => $params,
                ];
            }
        }

        return null;
    }

    private function matchRoute(string $routePath, string $requestPath, array &$params): bool
    {
        if ($routePath === $requestPath) {
            return true;
        }

        $routeSegments = explode('/', trim($routePath, '/'));
        $requestSegments = explode('/', trim($requestPath, '/'));

        if (count($routeSegments) !== count($requestSegments)) {
            return false;
        }

        foreach ($routeSegments as $index => $routeSegment) {
            $requestSegment = $requestSegments[$index] ?? '';

            if (preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/', $routeSegment, $matches)) {
                $params[$matches[1]] = urldecode($requestSegment);
                continue;
            }

            if ($routeSegment !== $requestSegment) {
                return false;
            }
        }

        return true;
    }

    private function normalizeRoutePath(string $path): string
    {
        $path = '/' . trim($path, '/');
        $path = preg_replace('#/+#', '/', $path) ?: '/';

        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
