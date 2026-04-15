<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Demand;
use App\Models\Notification;
use App\Models\SystemLog;
use App\Models\User;
use App\Services\NotificationDeadlineService;

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
        $this->updateDemandProgress();
    }

    public function updateStatus(): void
    {
        $this->updateDemandProgress();
    }

    public function updateDemandProgress(): void
    {
        $this->requireAuth('funcionario');
        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            flash('error', 'Falha de segurança na requisição.');
            redirect('funcionario/demandas');
        }

        $employeeId = (int)$_SESSION['user']['id'];
        $demandId = (int)($_POST['id'] ?? 0);
        $status = trim((string)($_POST['status'] ?? ''));
        $newObservation = trim((string)($_POST['observacao_funcionario'] ?? ''));

        if ($demandId <= 0 || !in_array($status, Demand::STATUS_ALLOWED, true)) {
            flash('error', 'Dados inválidos para atualização da demanda.');
            redirect('funcionario/demandas');
        }

        if ($newObservation !== '' && mb_strlen($newObservation) > 2500) {
            flash('error', 'A observação deve ter no máximo 2500 caracteres.');
            redirect('funcionario/demandas');
        }

        $ownedDemand = $this->demands->findOwnedById($demandId, $employeeId);
        if (!$ownedDemand) {
            flash('error', 'Você não tem permissão para alterar esta demanda.');
            redirect('funcionario/demandas');
        }

        $previousObservation = trim((string)($ownedDemand['observacao_funcionario'] ?? ''));
        $observationLog = $previousObservation;
        if ($newObservation !== '') {
            $entry = sprintf("[%s] %s: %s", date('d/m/Y H:i'), (string)($_SESSION['user']['name'] ?? 'Funcionário'), $newObservation);
            $observationLog = $previousObservation === '' ? $entry : ($previousObservation . "\n" . $entry);
        }

        if (!$this->demands->updateProgressByEmployee($demandId, $employeeId, $status, $observationLog)) {
            flash('error', 'Não foi possível atualizar a demanda.');
            redirect('funcionario/demandas');
        }

        $this->demands->registerEmployeeHistory(
            $demandId,
            $employeeId,
            (string)($_SESSION['user']['name'] ?? 'Funcionário'),
            (string)($ownedDemand['status'] ?? 'pendente'),
            $status,
            $newObservation !== '' ? $newObservation : null
        );

        $masterId = $this->users->findActiveMasterId();
        if ($masterId !== null) {
            $message = sprintf(
                '%s atualizou a demanda "%s" para "%s"%s',
                (string)($_SESSION['user']['name'] ?? 'Funcionário'),
                $ownedDemand['emenda'] ?? '',
                $status,
                $newObservation !== '' ? ' e registrou nova observação.' : '.'
            );

            $this->deadlineService->notifyDemandEventToMaster(
                $masterId,
                ['id' => $demandId],
                'Atualização de demanda pelo funcionário',
                $message,
                'status_funcionario'
            );
        }

        $this->logs->create([
            'usuario_id' => $employeeId,
            'acao' => 'update_demand_progress',
            'entidade' => 'demandas',
            'entidade_id' => $demandId,
            'descricao' => 'Funcionário atualizou status para ' . $status . ($newObservation !== '' ? ' com observação.' : '.'),
            'ip' => client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);

        if ($newObservation !== '') {
            $this->logs->create([
                'usuario_id' => $employeeId,
                'acao' => 'include_observation',
                'entidade' => 'demandas',
                'entidade_id' => $demandId,
                'descricao' => 'Funcionário incluiu observação de andamento na demanda.',
                'ip' => client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);
        }

        flash('success', 'Demanda atualizada com sucesso.');
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
        $this->view('funcionario/notificacoes/index', ['notifications' => $this->notifications->forUser($userId)]);
    }
}
