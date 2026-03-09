<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Demand;
use App\Models\Notification;
use App\Models\SystemLog;
use App\Models\User;
use App\Services\NotificationDeadlineService;

class MasterController extends Controller
{
    public function __construct(
        private readonly User $users = new User(),
        private readonly Demand $demands = new Demand(),
        private readonly Notification $notifications = new Notification(),
        private readonly SystemLog $logs = new SystemLog(),
        private readonly NotificationDeadlineService $deadlineService = new NotificationDeadlineService()
    ) {
    }

    public function dashboard(): void
    {
        $this->requireAuth('master');
        $masterId = (int)$_SESSION['user']['id'];
        $this->deadlineService->runAutomationForMaster($masterId);

        $this->view('master/dashboard/index', [
            'stats' => $this->demands->stats(),
            'demands' => $this->demands->paginatedFiltered([], 300, 0),
            'notifications' => $this->notifications->forUser($masterId, 8),
            'upcoming' => $this->demands->upcomingDeadlines(8),
        ]);
    }

    public function employees(): void
    {
        $this->requireAuth('master');

        $search = trim($_GET['search'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 8;
        $offset = ($page - 1) * $perPage;

        $total = $this->users->countEmployees($search ?: null);
        $employees = $this->users->paginatedEmployees($perPage, $offset, $search ?: null);

        $this->view('master/employees/index', [
            'employees' => $employees,
            'search' => $search,
            'page' => $page,
            'totalPages' => max(1, (int)ceil($total / $perPage)),
        ]);
    }

    public function createEmployee(): void
    {
        $this->requireAuth('master');
        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            flash('error', 'Falha de segurança na requisição.');
            redirect('master/employees');
        }

        $data = [
            'nome_completo' => trim($_POST['nome_completo'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'endereco' => trim($_POST['endereco'] ?? ''),
            'whatsapp' => trim($_POST['whatsapp'] ?? ''),
            'numero_decreto' => trim($_POST['numero_decreto'] ?? ''),
            'data_nascimento' => trim($_POST['data_nascimento'] ?? ''),
        ];

        $errors = validate_required($data, ['nome_completo', 'email', 'endereco', 'whatsapp', 'numero_decreto', 'data_nascimento']);
        if ($this->users->emailExists($data['email'])) {
            $errors[] = 'E-mail já cadastrado.';
        }
        if ($this->users->decreeExists($data['numero_decreto'])) {
            $errors[] = 'Número de decreto já cadastrado.';
        }

        if ($errors) {
            flash('error', implode(' ', $errors));
            redirect('master/employees');
        }

        // Regra do negócio: usuário = decreto | senha inicial = data de nascimento (sem separadores).
        $this->users->createEmployee([
            'nome_completo' => $data['nome_completo'],
            'email' => $data['email'],
            'endereco' => $data['endereco'],
            'whatsapp' => $data['whatsapp'],
            'numero_decreto' => $data['numero_decreto'],
            'data_nascimento' => $data['data_nascimento'],
            'usuario' => $data['numero_decreto'],
            'senha_hash' => password_hash(normalize_birth_password($data['data_nascimento']), PASSWORD_DEFAULT),
        ]);

        $this->logAction('create', 'usuarios', null, 'Cadastro de funcionário realizado pelo Master');
        flash('success', 'Funcionário cadastrado com sucesso.');
        redirect('master/employees');
    }

    public function updateEmployee(): void
    {
        $this->requireAuth('master');
        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            flash('error', 'Falha de segurança na requisição.');
            redirect('master/employees');
        }

        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'nome_completo' => trim($_POST['nome_completo'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'endereco' => trim($_POST['endereco'] ?? ''),
            'whatsapp' => trim($_POST['whatsapp'] ?? ''),
            'numero_decreto' => trim($_POST['numero_decreto'] ?? ''),
            'data_nascimento' => trim($_POST['data_nascimento'] ?? ''),
        ];

        if ($this->users->emailExists($data['email'], $id) || $this->users->decreeExists($data['numero_decreto'], $id)) {
            flash('error', 'E-mail ou decreto já em uso.');
            redirect('master/employees');
        }

        $this->users->updateEmployee($id, $data);
        $this->logAction('update', 'usuarios', $id, 'Dados do funcionário atualizados pelo Master');
        flash('success', 'Funcionário atualizado com sucesso.');
        redirect('master/employees');
    }

    public function changeEmployeePassword(): void
    {
        $this->requireAuth('master');
        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            flash('error', 'Falha de segurança na requisição.');
            redirect('master/employees');
        }

        $id = (int)($_POST['id'] ?? 0);
        $newPassword = (string)($_POST['new_password'] ?? '');

        if ($id <= 0 || strlen($newPassword) < 8) {
            flash('error', 'Informe uma nova senha com no mínimo 8 caracteres.');
            redirect('master/employees');
        }

        $this->users->updatePassword($id, password_hash($newPassword, PASSWORD_DEFAULT), true);
        $this->logAction('update_password', 'usuarios', $id, 'Master alterou senha de funcionário e reativou primeiro login');
        flash('success', 'Senha do funcionário alterada com sucesso.');
        redirect('master/employees');
    }

    public function toggleEmployee(): void
    {
        $this->requireAuth('master');
        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            flash('error', 'Falha de segurança na requisição.');
            redirect('master/employees');
        }

        $id = (int)($_POST['id'] ?? 0);
        $active = (int)($_POST['active'] ?? 0) === 1;
        $this->users->toggleEmployeeStatus($id, $active);
        $this->logAction('toggle', 'usuarios', $id, $active ? 'Funcionário ativado' : 'Funcionário desativado');
        flash('success', $active ? 'Funcionário ativado.' : 'Funcionário desativado.');
        redirect('master/employees');
    }

    public function deleteEmployee(): void
    {
        $this->requireAuth('master');
        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            flash('error', 'Falha de segurança na requisição.');
            redirect('master/employees');
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            flash('error', 'Funcionário inválido.');
            redirect('master/employees');
        }

        // Segurança operacional: não remove fisicamente para preservar histórico.
        $this->users->toggleEmployeeStatus($id, false);
        $this->logAction('soft_delete', 'usuarios', $id, 'Funcionário desativado por ação de exclusão lógica');
        flash('success', 'Funcionário desativado com sucesso.');
        redirect('master/employees');
    }

    public function demands(): void
    {
        $this->requireAuth('master');
        $this->deadlineService->runAutomationForMaster((int)$_SESSION['user']['id']);

        $filters = [
            'status' => trim($_GET['status'] ?? ''),
            'funcionario_id' => trim($_GET['funcionario_id'] ?? ''),
            'prazo_de' => trim($_GET['prazo_de'] ?? ''),
            'prazo_ate' => trim($_GET['prazo_ate'] ?? ''),
        ];

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        $total = $this->demands->countFiltered($filters);

        $this->view('master/demands/index', [
            'demands' => $this->demands->paginatedFiltered($filters, $perPage, $offset),
            'employees' => $this->users->allActiveEmployees(),
            'filters' => $filters,
            'page' => $page,
            'totalPages' => max(1, (int)ceil($total / $perPage)),
        ]);
    }

    public function createDemand(): void
    {
        $this->requireAuth('master');
        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            flash('error', 'Falha de segurança na requisição.');
            redirect('master/demands');
        }

        $prazoEntrega = trim((string)($_POST['prazo_entrega'] ?? ''));
        if ($prazoEntrega === '') {
            flash('error', 'Informe o prazo de entrega da demanda.');
            redirect('master/demands');
        }

        $this->demands->create([
            'emenda' => trim($_POST['emenda'] ?? ''),
            'nome_politico' => trim($_POST['nome_politico'] ?? ''),
            'data_emenda' => $_POST['data_emenda'] ?? date('Y-m-d'),
            'tipo_emenda' => trim($_POST['tipo_emenda'] ?? ''),
            'observacao' => trim($_POST['observacao'] ?? ''),
            'prazo_entrega' => date('Y-m-d H:i:s', strtotime($prazoEntrega)),
            'funcionario_id' => (int)($_POST['funcionario_id'] ?? 0),
            'status' => $_POST['status'] ?? 'pendente',
            'criado_por' => (int)$_SESSION['user']['id'],
        ]);

        $this->logAction('create', 'demandas', null, 'Demanda cadastrada pelo Master');
        flash('success', 'Demanda cadastrada com sucesso.');
        redirect('master/demands');
    }

    public function updateDemand(): void
    {
        $this->requireAuth('master');
        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            flash('error', 'Falha de segurança na requisição.');
            redirect('master/demands');
        }

        $this->demands->update((int)$_POST['id'], [
            'emenda' => trim($_POST['emenda'] ?? ''),
            'nome_politico' => trim($_POST['nome_politico'] ?? ''),
            'data_emenda' => $_POST['data_emenda'] ?? date('Y-m-d'),
            'tipo_emenda' => trim($_POST['tipo_emenda'] ?? ''),
            'observacao' => trim($_POST['observacao'] ?? ''),
            'prazo_entrega' => date('Y-m-d H:i:s', strtotime((string)($_POST['prazo_entrega'] ?? date('Y-m-d H:i:s')))),
            'funcionario_id' => (int)($_POST['funcionario_id'] ?? 0),
            'status' => $_POST['status'] ?? 'pendente',
        ]);

        $this->logAction('update', 'demandas', (int)$_POST['id'], 'Demanda atualizada pelo Master');
        flash('success', 'Demanda atualizada com sucesso.');
        redirect('master/demands');
    }

    public function deleteDemand(): void
    {
        $this->requireAuth('master');
        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            flash('error', 'Falha de segurança na requisição.');
            redirect('master/demands');
        }
        $this->demands->delete((int)($_POST['id'] ?? 0));
        $this->logAction('delete', 'demandas', (int)($_POST['id'] ?? 0), 'Demanda removida pelo Master');
        flash('success', 'Demanda removida.');
        redirect('master/demands');
    }

    public function readNotification(): void
    {
        $this->requireAuth('master');
        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            flash('error', 'Falha de segurança na requisição.');
            redirect('master/notifications');
        }

        $notificationId = (int)($_POST['id'] ?? 0);
        $this->notifications->markRead($notificationId, (int)$_SESSION['user']['id']);
        flash('success', 'Notificação marcada como lida.');
        redirect('master/notifications');
    }

    public function notifications(): void
    {
        $this->requireAuth('master');
        $masterId = (int)$_SESSION['user']['id'];
        $this->deadlineService->runAutomationForMaster($masterId);
        $list = $this->notifications->forUser($masterId, 50);
        $this->view('master/notifications/index', ['notifications' => $list]);
    }

    /**
     * Log básico de ações administrativas para auditoria.
     */
    private function logAction(string $acao, string $entidade, ?int $entidadeId, string $descricao): void
    {
        $this->logs->create([
            'usuario_id' => (int)($_SESSION['user']['id'] ?? 0) ?: null,
            'acao' => $acao,
            'entidade' => $entidade,
            'entidade_id' => $entidadeId,
            'descricao' => $descricao,
            'ip' => client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }
}
