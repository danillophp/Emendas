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

        if (!$this->isAuthorizedPathForCurrentSession($path)) {
            return;
        }

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

    private function isAuthorizedPathForCurrentSession(string $path): bool
    {
        $sessionRole = (string)($_SESSION['user']['role'] ?? '');
        $isAuthenticated = !empty($_SESSION['user']['id']);

        $requiresMaster = str_starts_with($path, '/master') || str_starts_with($path, '/api/master');
        $requiresEmployee = str_starts_with($path, '/funcionario') || str_starts_with($path, '/employee') || str_starts_with($path, '/api/funcionario');

        if (!$requiresMaster && !$requiresEmployee) {
            return true;
        }

        if (!$isAuthenticated) {
            $this->denyAccess($path, 401, 'Faça login para continuar.');
            return false;
        }

        if ($requiresMaster && $sessionRole !== 'master') {
            $this->denyAccess($path, 403, 'Acesso negado para este perfil.');
            return false;
        }

        if ($requiresEmployee && $sessionRole !== 'funcionario') {
            $this->denyAccess($path, 403, 'Acesso negado para este perfil.');
            return false;
        }

        return true;
    }

    private function denyAccess(string $path, int $statusCode, string $message): void
    {
        if (function_exists('app_log')) {
            app_log('warning', 'Acesso bloqueado por regra de rota', [
                'path' => $path,
                'status' => $statusCode,
                'session_user_id' => $_SESSION['user']['id'] ?? null,
                'session_role' => $_SESSION['user']['role'] ?? null,
            ]);
        }

        if (str_starts_with($path, '/api/')) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (function_exists('flash')) {
            flash('error', $message);
        }

        if (function_exists('redirect')) {
            redirect('login');
        }

        http_response_code($statusCode);
        echo $message;
    }
}
