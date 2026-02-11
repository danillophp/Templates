<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, array $action): void
    {
        $this->add('GET', $path, $action);
    }

    public function post(string $path, array $action): void
    {
        $this->add('POST', $path, $action);
    }

    private function add(string $method, string $path, array $action): void
    {
        $this->routes[$method][rtrim($path, '/') ?: '/'] = $action;
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $path = $request->path();

        $action = $this->routes[$method][$path] ?? null;

        if (!$action) {
            http_response_code(404);
            echo 'Página não encontrada';
            return;
        }

        [$controller, $handler] = $action;
        $instance = new $controller();
        $instance->$handler($request);
    }
}
