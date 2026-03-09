<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Demand;
use App\Models\Notification;
use App\Models\SystemLog;
use App\Models\User;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly Demand $demands = new Demand(),
        private readonly Notification $notifications = new Notification(),
        private readonly User $users = new User(),
        private readonly SystemLog $logs = new SystemLog()
    ) {
    }

    public function dashboard(): void
    {
        $this->requireAuth('funcionario');
        $employeeId = (int)$_SESSION['user']['id'];

        // Mantém status atrasado sincronizado em toda navegação do funcionário.
        $this->demands->refreshOverdueStatuses();

        $this->view('funcionario/dashboard/index', [
            'summary' => $this->demands->employeeSummary($employeeId),
        ]);
    }

    public function demands(): void
    {
        $this->requireAuth('funcionario');
        $this->demands->refreshOverdueStatuses();

        // Regra crítica: funcionário só recebe demandas do próprio usuário.
        $employeeId = (int)$_SESSION['user']['id'];
        $list = $this->demands->byEmployee($employeeId);

        $this->view('funcionario/demandas/index', [
            'demands' => $list,
        ]);
    }

    public function completeDemand(): void
    {
        $this->requireAuth('funcionario');

        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            flash('error', 'Falha de segurança na requisição.');
            redirect('funcionario/demandas');
        }

        $employeeId = (int)$_SESSION['user']['id'];
        $demandId = (int)($_POST['id'] ?? 0);
        $note = trim((string)($_POST['completion_note'] ?? ''));

        if ($demandId <= 0) {
            flash('error', 'Demanda inválida para conclusão.');
            redirect('funcionario/demandas');
        }

        $ownedDemand = $this->demands->findOwnedById($demandId, $employeeId);
        if (!$ownedDemand) {
            flash('error', 'Você não tem permissão para concluir esta demanda.');
            redirect('funcionario/demandas');
        }

        $done = $this->demands->markCompleted($demandId, $employeeId, $note);
        if (!$done) {
            flash('error', 'Não foi possível concluir esta demanda.');
            redirect('funcionario/demandas');
        }

        // Notifica automaticamente o Master sobre conclusão.
        $masterId = $this->users->findActiveMasterId();
        if ($masterId !== null) {
            $this->notifications->create([
                'usuario_id' => $masterId,
                'titulo' => 'Demanda concluída',
                'mensagem' => $_SESSION['user']['name'] . ' concluiu a demanda: ' . $ownedDemand['emenda'],
                'tipo' => 'conclusao_demanda',
                'referencia_id' => $demandId,
            ]);
        }

        // Auditoria da conclusão para rastreabilidade operacional.
        $this->logs->create([
            'usuario_id' => $employeeId,
            'acao' => 'concluir',
            'entidade' => 'demandas',
            'entidade_id' => $demandId,
            'descricao' => 'Funcionário concluiu demanda ' . $ownedDemand['emenda'],
            'ip' => client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);

        flash('success', 'Demanda concluída com sucesso.');
        redirect('funcionario/demandas');
    }

    public function readNotification(): void
    {
        $this->requireAuth('funcionario');

        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            flash('error', 'Falha de segurança na requisição.');
            redirect('funcionario/notificacoes');
        }

        $notificationId = (int)($_POST['id'] ?? 0);
        $this->notifications->markRead($notificationId, (int)$_SESSION['user']['id']);
        flash('success', 'Notificação marcada como lida.');
        redirect('funcionario/notificacoes');
    }

    public function notifications(): void
    {
        $this->requireAuth('funcionario');
        $userId = (int)$_SESSION['user']['id'];
        $list = $this->notifications->forUser($userId);

        $this->view('funcionario/notificacoes/index', ['notifications' => $list]);
    }
}
