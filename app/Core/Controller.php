<?php

namespace App\Core;

use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Models\Notification;

class Controller
{
    protected function view(string $view, array $data = []): void
    {
        // Dados globais do cabeçalho (área de notificações no topo).
        $headerNotifications = [];
        $headerUnreadCount = 0;

        if (!empty($_SESSION['user']['id'])) {
            $notificationModel = new Notification();
            $userId = (int)$_SESSION['user']['id'];
            $headerUnreadCount = $notificationModel->unreadCount($userId);
            $headerNotifications = $notificationModel->forUser($userId, 5);
        }

        extract($data, EXTR_SKIP);
        $viewFile = APP_PATH . '/views/' . $view . '.php';
        require APP_PATH . '/views/layouts/header.php';
        require $viewFile;
        require APP_PATH . '/views/layouts/footer.php';
    }

    protected function requireAuth(?string $role = null): void
    {
        AuthMiddleware::ensureAuthenticated();
        AuthMiddleware::ensurePasswordChanged();

        if ($role !== null) {
            RoleMiddleware::ensure($role);
        }
    }
}
