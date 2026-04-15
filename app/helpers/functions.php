<?php

declare(strict_types=1);

/**
 * Escape de saída para prevenir XSS em templates.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Gera URL baseada no APP_BASE_PATH (ex.: /emendas).
 * Evita acoplamento em domínio fixo e funciona em diferentes ambientes.
 */
function url(string $path = ''): string
{
    $normalizedPath = trim($path);
    $normalizedPath = ltrim($normalizedPath, '/');

    $basePath = rtrim(APP_BASE_PATH, '/');
    if ($basePath === '') {
        return '/' . $normalizedPath;
    }

    return $basePath . ($normalizedPath !== '' ? '/' . $normalizedPath : '');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}


/**
 * Retorna o caminho absoluto dentro de /public para um arquivo relativo.
 */
function public_path(string $relativePath = ''): string
{
    $relativePath = ltrim(trim($relativePath), '/');
    $publicBase = rtrim(BASE_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'public';

    return $relativePath === ''
        ? $publicBase
        : $publicBase . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
}

/**
 * Resolve um asset dentro de /public com fallback para case-insensitive (Linux-safe).
 * Retorna o caminho relativo encontrado (preservando o case real) ou null.
 */
function resolve_public_asset(string $relativePath): ?string
{
    $relativePath = trim($relativePath);
    if ($relativePath === '') {
        return null;
    }

    $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
    $directory = trim(dirname($relativePath), '.');
    $filename = basename($relativePath);

    $roots = [];
    $documentRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $basePath = rtrim(APP_BASE_PATH, '/');

    if ($documentRoot !== '') {
        $roots[] = $documentRoot . ($basePath !== '' ? $basePath : '') . '/public';
        $roots[] = $documentRoot . ($basePath !== '' ? $basePath : '');
    }

    $roots[] = public_path();

    $roots = array_values(array_unique(array_filter($roots)));

    foreach ($roots as $root) {
        $root = rtrim(str_replace('\\', '/', $root), '/');
        $full = $root . '/' . $relativePath;

        if (is_file($full)) {
            return $relativePath;
        }

        $dirFull = $directory !== '' ? $root . '/' . $directory : $root;
        if (!is_dir($dirFull)) {
            continue;
        }

        $entries = @scandir($dirFull);
        if ($entries === false) {
            continue;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            if (strcasecmp($entry, $filename) === 0 && is_file($dirFull . '/' . $entry)) {
                return ($directory !== '' ? $directory . '/' : '') . $entry;
            }
        }
    }

    return null;
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }

    $message = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);

    return $message;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function verify_csrf(?string $token): bool
{
    return $token !== null && isset($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $token);
}

function rotate_csrf_token(): string
{
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['_csrf'];
}

function validate_required(array $data, array $fields): array
{
    $errors = [];
    foreach ($fields as $field) {
        if (trim((string)($data[$field] ?? '')) === '') {
            $errors[] = "O campo {$field} é obrigatório.";
        }
    }
    return $errors;
}

function normalize_birth_password(string $birthDate): string
{
    $birthDate = trim($birthDate);

    // Regra de negócio: senha inicial no formato DDMMAAAA (sem separadores).
    foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
        $date = \DateTime::createFromFormat($format, $birthDate);
        if ($date instanceof \DateTime) {
            return $date->format('dmY');
        }
    }

    // Fallback para entradas sem separador ou formatos inesperados.
    $digits = preg_replace('/\D/', '', $birthDate) ?? '';
    if (strlen($digits) === 8) {
        // Se vier em AAAAMMDD, converte para DDMMAAAA.
        if (preg_match('/^(19|20)\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])$/', $digits) === 1) {
            return substr($digits, 6, 2) . substr($digits, 4, 2) . substr($digits, 0, 4);
        }

        return $digits;
    }

    return $digits;
}

function client_ip(): ?string
{
    $keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];

    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', (string)$_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    return null;
}

/**
 * Log básico de aplicação para produção.
 */
function app_log(string $level, string $message, array $context = []): void
{
    $line = sprintf(
        "[%s] [%s] %s %s\n",
        date('Y-m-d H:i:s'),
        strtoupper($level),
        $message,
        $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''
    );

    @file_put_contents(LOG_PATH, $line, FILE_APPEND);
}
