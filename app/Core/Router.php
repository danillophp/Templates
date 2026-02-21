<?php
namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $uri, $action): void { $this->add('GET', $uri, $action); }
    public function post(string $uri, $action): void { $this->add('POST', $uri, $action); }

    private function add(string $method, string $uri, $action): void
    {
        $this->routes[$method][rtrim($uri, '/') ?: '/'] = $action;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = rtrim($uri, '/') ?: '/';
        $action = $this->routes[$method][$path] ?? null;
        if (!$action) {
            http_response_code(404);
            require __DIR__ . '/../../resources/views/errors/404.php';
            return;
        }
        if (is_callable($action)) {
            $action();
            return;
        }
        [$class, $methodAction] = $action;
        (new $class())->$methodAction();
    }
}
