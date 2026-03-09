<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    private function addRoute(string $method, string $path, array $handler): void
    {
        $normalized = '/' . trim($path, '/');
        $this->routes[$method][$normalized === '/' ? '/' : $normalized] = $handler;
    }

    public function dispatch(string $uri, string $method): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        // Remove APP_BASE_PATH do início da rota para compatibilidade com /emendas.
        $basePath = rtrim(APP_BASE_PATH, '/');
        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }

        $path = '/' . trim($path, '/');
        $path = $path === '/' ? '/' : $path;

        $httpMethod = strtoupper($method);
        $handler = $this->routes[$httpMethod][$path] ?? null;

        if (!$handler) {
            http_response_code(404);
            echo 'Página não encontrada.';
            return;
        }

        [$controllerClass, $action] = $handler;
        $controller = new $controllerClass();
        $controller->$action();
    }
}
