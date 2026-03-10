<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Demand;
use App\Models\Notification;
use App\Services\NotificationDeadlineService;
use App\Models\SystemLog;
use App\Models\User;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly Demand $demands = new Demand(),
        private readonly Notification $notifications = new Notification(),
        private readonly User $users = new User(),
        private readonly SystemLog $logs = new SystemLog(),
        private readonly NotificationDeadlineService $deadlineService = new NotificationDeadlineService()
    ) {
    }

    public function dashboard(): void
    {
        $this->requireAuth('funcionario');
        $employeeId = (int)$_SESSION['user']['id'];
        $this->view('funcionario/dashboard/index', ['summary' => $this->demands->employeeSummary($employeeId)]);
    }

    public function demands(): void
    {
        $this->requireAuth('funcionario');
        $employeeId = (int)$_SESSION['user']['id'];
        $this->view('funcionario/demandas/index', ['demands' => $this->demands->byEmployee($employeeId)]);
    }

    public function completeDemand(): void
    {
        $this->updateStatus();
    }

    public function updateStatus(): void
    {
        $this->requireAuth('funcionario');
        if (!verify_csrf($_POST['_csrf'] ?? null)) { flash('error', 'Falha de segurança na requisição.'); redirect('funcionario/demandas'); }

        $employeeId = (int)$_SESSION['user']['id'];
        $demandId = (int)($_POST['id'] ?? 0);
        $status = trim((string)($_POST['status'] ?? ''));

        if ($demandId <= 0 || !in_array($status, Demand::STATUS_ALLOWED, true)) {
            flash('error', 'Dados inválidos para atualização de status.');
            redirect('funcionario/demandas');
        }

        $ownedDemand = $this->demands->findOwnedById($demandId, $employeeId);
        if (!$ownedDemand) { flash('error', 'Você não tem permissão para alterar esta demanda.'); redirect('funcionario/demandas'); }

        if (!$this->demands->updateStatusByEmployee($demandId, $employeeId, $status)) {
            flash('error', 'Não foi possível atualizar o status.');
            redirect('funcionario/demandas');
        }

        $masterId = $this->users->findActiveMasterId();
        if ($masterId !== null) {
            $this->deadlineService->notifyDemandEventToMaster(
                $masterId,
                ['id' => $demandId],
                'Status atualizado pelo funcionário',
                sprintf('%s atualizou a demanda "%s" para o status %s.', (string)($_SESSION['user']['name'] ?? 'Funcionário'), $ownedDemand['emenda'] ?? '', $status),
                'status_funcionario'
            );
        }

        $this->logs->create([
            'usuario_id' => $employeeId,
            'acao' => 'update_status',
            'entidade' => 'demandas',
            'entidade_id' => $demandId,
            'descricao' => 'Funcionário alterou status para ' . $status,
            'ip' => client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);

        flash('success', 'Status atualizado com sucesso.');
        redirect('funcionario/demandas');
    }

    public function polling(): void
    {
        $this->requireAuth('funcionario');
        header('Content-Type: application/json; charset=utf-8');
        $userId = (int)$_SESSION['user']['id'];
        $afterId = max(0, (int)($_GET['after_id'] ?? 0));
        $items = $this->notifications->latestForUser($userId, $afterId, 20);
        echo json_encode(['ok'=>true,'unread_count'=>$this->notifications->unreadCount($userId),'items'=>$items,'latest_id'=>$this->notifications->latestIdForUser($userId)]);
        exit;
    }

    public function readNotification(): void
    {
        $this->requireAuth('funcionario');
        if (!verify_csrf($_POST['_csrf'] ?? null)) { flash('error', 'Falha de segurança na requisição.'); redirect('funcionario/notificacoes'); }
        $notificationId = (int)($_POST['id'] ?? 0);
        $this->notifications->markRead($notificationId, (int)$_SESSION['user']['id']);
        flash('success', 'Notificação marcada como lida.');
        redirect('funcionario/notificacoes');
    }

    public function notifications(): void
    {
        $this->requireAuth('funcionario');
        $userId = (int)$_SESSION['user']['id'];
        $this->view('funcionario/notificacoes/index', ['notifications' => $this->notifications->forUser($userId)]);
    }
}
