<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Demand;
use App\Models\Notification;
use App\Services\NotificationDeadlineService;

class PanelSyncController extends Controller
{
    public function __construct(
        private readonly Demand $demands = new Demand(),
        private readonly Notification $notifications = new Notification(),
        private readonly NotificationDeadlineService $deadlineService = new NotificationDeadlineService()
    ) {
    }

    public function master(): void
    {
        $this->requireAuth('master');

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $this->deadlineService->runAutomationForMaster($userId);

        header('Content-Type: application/json; charset=utf-8');
        $notifications = $this->withLinks($this->notifications->forUser($userId, 8), 'master');

        echo json_encode([
            'ok' => true,
            'role' => 'master',
            'timestamp' => date('c'),
            'unread_count' => $this->notifications->unreadCount($userId),
            'latest_notification_id' => $this->notifications->latestIdForUser($userId),
            'notifications' => $notifications,
            'demands' => $this->demands->paginatedFiltered([], 25, 0),
            'stats' => $this->demands->stats(),
            'upcoming' => $this->demands->upcomingDeadlines(8),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function employee(): void
    {
        $this->requireAuth('funcionario');

        $userId = (int)($_SESSION['user']['id'] ?? 0);

        header('Content-Type: application/json; charset=utf-8');
        $notifications = $this->withLinks($this->notifications->forUser($userId, 8), 'funcionario');

        echo json_encode([
            'ok' => true,
            'role' => 'funcionario',
            'timestamp' => date('c'),
            'unread_count' => $this->notifications->unreadCount($userId),
            'latest_notification_id' => $this->notifications->latestIdForUser($userId),
            'notifications' => $notifications,
            'demands' => $this->demands->byEmployee($userId),
            'summary' => $this->demands->employeeSummary($userId),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function withLinks(array $items, string $role): array
    {
        return array_map(function (array $item) use ($role): array {
            $table = (string)($item['referencia_tabela'] ?? '');
            if ($table === 'demandas') {
                $item['link'] = url($role === 'master' ? 'master/demands' : 'funcionario/demandas');
            } else {
                $item['link'] = url($role === 'master' ? 'master/notifications' : 'funcionario/notificacoes');
            }

            return $item;
        }, $items);
    }
}
