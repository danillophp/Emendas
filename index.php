<?php

declare(strict_types=1);

// Endurecimento de sessão para produção.
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}

session_start();

define('BASE_PATH', __DIR__);
define('APP_PATH', BASE_PATH . '/app');

autoloadRegister();

require APP_PATH . '/helpers/functions.php';
require APP_PATH . '/config/config.php';

// Tratamento amigável de erros em produção.
if (APP_ENV === 'production') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

set_exception_handler(function (Throwable $exception): void {
    app_log('error', 'Exceção não tratada', [
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
    ]);

    http_response_code(500);
    if (APP_DEBUG) {
        echo '<h3>Erro interno</h3><pre>' . e($exception->getMessage()) . '</pre>';
        return;
    }

    echo 'Ocorreu um erro interno. Tente novamente em instantes.';
});

set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    app_log('error', 'Erro PHP', compact('severity', 'message', 'file', 'line'));
    return false;
});

use App\Core\Router;

$router = new Router();
require BASE_PATH . '/routes/web.php';
$router->dispatch($_SERVER['REQUEST_URI'] ?? '/', $_SERVER['REQUEST_METHOD'] ?? 'GET');

function autoloadRegister(): void
{
    spl_autoload_register(function (string $class): void {
        $prefix = 'App\\';
        if (strpos($class, $prefix) !== 0) {
            return;
        }

        $relativeClass = substr($class, strlen($prefix));
        $file = APP_PATH . '/' . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require $file;
        }
    });
}
