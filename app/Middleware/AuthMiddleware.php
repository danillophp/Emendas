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

    public static function ensureRole(string $role): void
    {
        self::ensureAuthenticated();

        $currentRole = $_SESSION['user']['role'] ?? '';
        if ($currentRole !== $role) {
            flash('error', 'Você não tem permissão para acessar esta área.');
            redirect($currentRole === 'master' ? 'master/dashboard' : 'employee/dashboard');
        }
    }

    public static function ensurePasswordChanged(): void
    {
        $isFirstLogin = !empty($_SESSION['user']['first_login']);
        $isEmployee = ($_SESSION['user']['role'] ?? '') === 'funcionario';
        $isChangePasswordRoute = str_contains($_SERVER['REQUEST_URI'] ?? '', '/change-password');

        if ($isEmployee && $isFirstLogin && !$isChangePasswordRoute) {
            flash('error', 'No primeiro acesso, você precisa alterar sua senha.');
            redirect('change-password');
        }
    }

    /**
     * Valida se o token da sessão atual ainda é o token ativo do usuário no banco.
     * Evita sessão simultânea inválida.
     */
    private static function ensureSessionIsValid(): void
    {
        $sessionUserId = (int)($_SESSION['user']['id'] ?? 0);
        $sessionToken = $_SESSION['user']['session_token'] ?? null;

        if ($sessionUserId <= 0 || empty($sessionToken)) {
            self::forceLogout('Sessão inválida. Faça login novamente.');
        }

        $model = new User();
        $dbToken = $model->getSessionToken($sessionUserId);

        if (empty($dbToken) || !hash_equals((string)$dbToken, (string)$sessionToken)) {
            self::forceLogout('Sua sessão foi encerrada por um novo login.');
        }
    }

    private static function forceLogout(string $message): void
    {
        $_SESSION = [];
        session_destroy();
        session_start();
        flash('error', $message);
        redirect('login');
    }
}
