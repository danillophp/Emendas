<?php

namespace App\Middleware;

/**
 * Middleware dedicado para autorização por perfil.
 *
 * Mantém as regras de negócio de autorização isoladas da autenticação,
 * facilitando manutenção e expansão para novos perfis.
 */
class RoleMiddleware
{
    public static function ensure(string $requiredRole): void
    {
        AuthMiddleware::ensureAuthenticated();

        $currentRole = $_SESSION['user']['role'] ?? '';
        if ($currentRole === $requiredRole) {
            return;
        }

        flash('error', 'Acesso negado para este perfil.');

        // Redireciona para o painel correto do usuário autenticado.
        if ($currentRole === 'master') {
            redirect('master/dashboard');
        }

        redirect('funcionario/dashboard');
    }
}
