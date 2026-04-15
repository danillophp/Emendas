<?php

namespace App\Middleware;

use App\Models\User;

class AuthMiddleware
{
    public static function ensureAuthenticated(): void
    {
        if (empty($_SESSION['user'])) {
            flash('error', 'Faça login para continuar.');
            redirect('login');
        }

        self::ensureSessionIsValid();
    }

    /**
     * Regra obrigatória:
     * Funcionário em primeiro login precisa alterar a senha
     * antes de acessar qualquer painel.
     */
    public static function ensurePasswordChanged(): void
    {
        $isFirstLogin = !empty($_SESSION['user']['first_login']);
        $isEmployee = ($_SESSION['user']['role'] ?? '') === 'funcionario';

        if (!$isEmployee || !$isFirstLogin) {
            return;
        }

        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        $basePath = rtrim(APP_BASE_PATH, '/');
        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }
        $path = '/' . ltrim($path, '/');

        $allowedDuringFirstLogin = ['/change-password', '/logout'];
        if (in_array($path, $allowedDuringFirstLogin, true)) {
            return;
        }

        flash('error', 'No primeiro acesso, altere sua senha para continuar.');
        redirect('change-password');
    }

    /**
     * Validação de sessão:
     * - Master: múltiplas sessões permitidas, cada sessão auditada em banco.
     * - Funcionário: sessão única por política (sessões antigas desativadas no login).
     */
    private static function ensureSessionIsValid(): void
    {
        $sessionUserId = (int)($_SESSION['user']['id'] ?? 0);
        $sessionId = (string)($_SESSION['user']['session_id'] ?? session_id());

        if ($sessionUserId <= 0 || $sessionId === '') {
            self::forceLogout('Sessão inválida. Faça login novamente.');
        }

        $model = new User();

        if ($model->supportsSessionAudit()) {
            if (!$model->isSessionActive($sessionUserId, $sessionId)) {
                self::forceLogout('Sua sessão foi encerrada ou não está mais ativa.');
            }

            $model->touchSession($sessionUserId, $sessionId);
            return;
        }

        // Fallback legado para ambiente sem tabela de sessão.
        $sessionToken = $_SESSION['user']['session_token'] ?? null;
        if (empty($sessionToken)) {
            self::forceLogout('Sessão inválida. Faça login novamente.');
        }

        $dbToken = $model->getSessionToken($sessionUserId);
        if (empty($dbToken) || !hash_equals((string)$dbToken, (string)$sessionToken)) {
            self::forceLogout('Sua sessão foi encerrada por um novo login.');
        }
    }

    private static function forceLogout(string $message): void
    {
        // Força saída apenas desta sessão HTTP; não encerra outras sessões do mesmo usuário.
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        flash('error', $message);
        redirect('login');
    }
}
