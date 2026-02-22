<?php
namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void { $this->add('GET', $path, $handler); }
    public function post(string $path, callable|array $handler): void { $this->add('POST', $path, $handler); }

    private function add(string $method, string $path, callable|array $handler): void
    {
        $this->routes[$method][rtrim($path, '/') ?: '/'] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $clean = parse_url($uri, PHP_URL_PATH) ?: '/';
        $base = rtrim($_ENV['APP_BASE_PATH'] ?? '/catatreco', '/');
        if ($base && str_starts_with($clean, $base)) {
            $clean = substr($clean, strlen($base)) ?: '/';
        }
        $path = rtrim($clean, '/') ?: '/';
        $handler = $this->routes[$method][$path] ?? null;
        if (!$handler) {
            http_response_code(404);
            require __DIR__ . '/../../resources/views/errors/404.php';
            return;
        }
        if (is_array($handler)) {
            [$class, $fn] = $handler;
            (new $class())->{$fn}();
            return;
        }
        $handler();
    }
}
