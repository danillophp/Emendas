<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function __construct(
        private readonly Notification $notifications = new Notification()
    ) {
    }

    public function poll(): void
    {
        $this->requireAuth();

        header('Content-Type: application/json; charset=utf-8');

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        if ($userId <= 0) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'message' => 'Sessão inválida.']);
            exit;
        }

        $afterId = max(0, (int)($_GET['after_id'] ?? 0));
        $role = (string)($_SESSION['user']['role'] ?? '');

        $items = $this->notifications->latestForUser($userId, $afterId, 20);
        $items = array_map(function (array $item) use ($role): array {
            $item['link'] = $this->resolveNotificationLink($item, $role);
            return $item;
        }, $items);

        echo json_encode([
            'ok' => true,
            'unread_count' => $this->notifications->unreadCount($userId),
            'latest_id' => $this->notifications->latestIdForUser($userId),
            'items' => $items,
        ]);
        exit;
    }

    public function markReadAjax(): void
    {
        $this->requireAuth();

        header('Content-Type: application/json; charset=utf-8');

        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'Token CSRF inválido.']);
            exit;
        }

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $notificationId = (int)($_POST['id'] ?? 0);

        if ($userId <= 0 || $notificationId <= 0) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Parâmetros inválidos.']);
            exit;
        }

        $ok = $this->notifications->markRead($notificationId, $userId);

        echo json_encode([
            'ok' => $ok,
            'unread_count' => $this->notifications->unreadCount($userId),
        ]);
        exit;
    }

    private function resolveNotificationLink(array $item, string $role): string
    {
        $table = (string)($item['referencia_tabela'] ?? '');

        if ($table === 'demandas') {
            return url($role === 'master' ? 'master/demands' : 'funcionario/demandas');
        }

        return url($role === 'master' ? 'master/notifications' : 'funcionario/notificacoes');
    }
}
