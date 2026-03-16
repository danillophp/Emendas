<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Demand;
use App\Models\Notification;
use App\Models\SystemLog;
use App\Models\User;
use App\Services\NotificationDeadlineService;
use App\Services\SecureAttachmentService;

class MasterController extends Controller
{
    public function __construct(
        private readonly User $users = new User(),
        private readonly Demand $demands = new Demand(),
        private readonly Notification $notifications = new Notification(),
        private readonly SystemLog $logs = new SystemLog(),
        private readonly NotificationDeadlineService $deadlineService = new NotificationDeadlineService(),
        private readonly SecureAttachmentService $attachmentService = new SecureAttachmentService()
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

    public function employees(): void { /* unchanged */
        $this->requireAuth('master');
        $search = trim((string)($_GET['search'] ?? ''));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 8;
        $offset = ($page - 1) * $perPage;
        $total = $this->users->countEmployees($search !== '' ? $search : null);
        $employees = $this->users->paginatedEmployees($perPage, $offset, $search !== '' ? $search : null);
        $this->view('master/employees/index', ['employees' => $employees,'search' => $search,'page' => $page,'totalPages' => max(1, (int)ceil($total / $perPage))]);
    }

    public function createEmployee(): void { /* unchanged */
        $this->requireAuth('master');$this->assertCsrfOrRedirect('master/employees');$payload = $this->validateEmployeePayload();if (!$payload['ok']) {flash('error', implode(' ', $payload['errors']));redirect('master/employees');}
        $data = $payload['data'];if ($this->users->emailExists($data['email'])) {flash('error', 'E-mail já cadastrado.');redirect('master/employees');}
        if ($this->users->decreeExists($data['numero_decreto'])) {flash('error', 'Número de decreto já cadastrado.');redirect('master/employees');}
        $this->users->createEmployee(['nome_completo'=>$data['nome_completo'],'email'=>$data['email'],'endereco'=>$data['endereco'],'whatsapp'=>$data['whatsapp'],'numero_decreto'=>$data['numero_decreto'],'data_nascimento'=>$data['data_nascimento'],'usuario'=>$data['numero_decreto'],'senha_hash'=>password_hash(normalize_birth_password($data['data_nascimento']), PASSWORD_DEFAULT)]);
        $this->logAction('create', 'usuarios', null, 'Cadastro de funcionário realizado pelo Master');flash('success', 'Funcionário cadastrado com sucesso.');redirect('master/employees');
    }
    public function updateEmployee(): void { $this->requireAuth('master');$this->assertCsrfOrRedirect('master/employees');$id=(int)($_POST['id']??0);if($id<=0||!$this->users->existsEmployeeById($id)){flash('error','Funcionário inválido.');redirect('master/employees');}$payload=$this->validateEmployeePayload();if(!$payload['ok']){flash('error',implode(' ',$payload['errors']));redirect('master/employees');}$data=$payload['data'];if($this->users->emailExists($data['email'],$id)||$this->users->decreeExists($data['numero_decreto'],$id)){flash('error','E-mail ou decreto já em uso.');redirect('master/employees');}$this->users->updateEmployee($id,$data);$this->logAction('update','usuarios',$id,'Dados do funcionário atualizados pelo Master');flash('success','Funcionário atualizado com sucesso.');redirect('master/employees'); }
    public function changeEmployeePassword(): void { $this->requireAuth('master');$this->assertCsrfOrRedirect('master/employees');$id=(int)($_POST['id']??0);$newPassword=(string)($_POST['new_password']??'');if($id<=0||!$this->users->existsEmployeeById($id)){flash('error','Funcionário inválido.');redirect('master/employees');}if(strlen($newPassword)<8){flash('error','Informe uma nova senha com no mínimo 8 caracteres.');redirect('master/employees');}$this->users->updatePassword($id,password_hash($newPassword,PASSWORD_DEFAULT),true);$this->logAction('update_password','usuarios',$id,'Master alterou senha de funcionário e reativou primeiro login');flash('success','Senha do funcionário alterada com sucesso.');redirect('master/employees'); }
    public function toggleEmployee(): void { $this->requireAuth('master');$this->assertCsrfOrRedirect('master/employees');$id=(int)($_POST['id']??0);if($id<=0||!$this->users->existsEmployeeById($id)){flash('error','Funcionário inválido.');redirect('master/employees');}$active=(int)($_POST['active']??0)===1;$this->users->toggleEmployeeStatus($id,$active);$this->logAction('toggle','usuarios',$id,$active?'Funcionário ativado':'Funcionário desativado');flash('success',$active?'Funcionário ativado.':'Funcionário desativado.');redirect('master/employees'); }
    public function deleteEmployee(): void { $this->requireAuth('master');$this->assertCsrfOrRedirect('master/employees');$id=(int)($_POST['id']??0);if($id<=0||!$this->users->existsEmployeeById($id)){flash('error','Funcionário inválido.');redirect('master/employees');}$this->users->toggleEmployeeStatus($id,false);$this->logAction('soft_delete','usuarios',$id,'Funcionário desativado por ação de exclusão lógica');flash('success','Funcionário desativado com sucesso.');redirect('master/employees'); }

    public function demands(): void
    {
        $this->requireAuth('master');
        $this->deadlineService->runAutomationForMaster((int)$_SESSION['user']['id']);
        $filters = ['status'=>trim((string)($_GET['status'] ?? '')),'funcionario_id'=>trim((string)($_GET['funcionario_id'] ?? '')),'prazo_de'=>trim((string)($_GET['prazo_de'] ?? '')),'prazo_ate'=>trim((string)($_GET['prazo_ate'] ?? ''))];
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        $total = $this->demands->countFiltered($filters);
        $demands = $this->demands->paginatedFiltered($filters, $perPage, $offset);
        $historyMap = $this->demands->historiesByDemandIds(array_map(static fn(array $d): int => (int)$d['id'], $demands));

        $this->view('master/demands/index', [
            'demands' => $demands,
            'historyMap' => $historyMap,
            'employees' => $this->users->allActiveEmployees(),
            'filters' => $filters,
            'page' => $page,
            'totalPages' => max(1, (int)ceil($total / $perPage)),
        ]);
    }

    public function createDemand(): void
    {
        $this->requireAuth('master');
        $this->assertCsrfOrRedirect('master/demands');

        $payload = $this->validateDemandPayload();
        if (!$payload['ok']) { flash('error', implode(' ', $payload['errors'])); redirect('master/demands'); }

        $dataToPersist = $payload['data'];
        $uploadOriginalName = $dataToPersist['_upload_original_name'] ?? null;
        unset($dataToPersist['_upload_original_name']);

        $demandId = $this->demands->create($dataToPersist + ['criado_por' => (int)$_SESSION['user']['id']]);
        $demand = $this->demands->findById($demandId) ?? ($payload['data'] + ['id' => $demandId]);

        $masterId = (int)$_SESSION['user']['id'];
        $this->deadlineService->notifyDemandEventToMaster($masterId, $demand, 'Nova demanda cadastrada', 'A demanda foi cadastrada com sucesso no sistema.', 'demanda_criada_master');
        $this->deadlineService->notifyDemandAssignedToEmployee((int)$payload['data']['funcionario_id'], $demand);

        $this->logAction('create', 'demandas', $demandId, 'Demanda cadastrada pelo Master');
        if (is_string($uploadOriginalName) && $uploadOriginalName !== '') {
            $this->logAction('upload_anexo', 'demandas', $demandId, 'Upload do anexo: ' . $uploadOriginalName);
        }
        flash('success', 'Demanda cadastrada com sucesso.');
        redirect('master/demands');
    }

    public function updateDemand(): void
    {
        $this->requireAuth('master');$this->assertCsrfOrRedirect('master/demands');
        $id = (int)($_POST['id'] ?? 0); if ($id <= 0) { flash('error', 'Demanda inválida.'); redirect('master/demands'); }
        $current = $this->demands->findById($id); if (!$current) { flash('error', 'Demanda não encontrada.'); redirect('master/demands'); }
        $payload = $this->validateDemandPayload($current['anexo_emenda'] ?? null);
        if (!$payload['ok']) { flash('error', implode(' ', $payload['errors'])); redirect('master/demands'); }
        $dataToPersist = $payload['data'];
        $uploadOriginalName = $dataToPersist['_upload_original_name'] ?? null;
        unset($dataToPersist['_upload_original_name']);

        $this->demands->update($id, $dataToPersist);
        $updated = $this->demands->findById($id) ?? ($payload['data'] + ['id' => $id]);
        $this->deadlineService->notifyDemandEventToMaster((int)$_SESSION['user']['id'], $updated, 'Demanda atualizada', 'Uma demanda foi atualizada no painel Master.', 'demanda_atualizada_master');
        $this->deadlineService->notifyDemandUpdatedToEmployee((int)$payload['data']['funcionario_id'], $updated);
        $this->logAction('update', 'demandas', $id, 'Demanda atualizada pelo Master');
        if (is_string($uploadOriginalName) && $uploadOriginalName !== '') {
            $this->logAction('upload_anexo', 'demandas', $id, 'Substituição de anexo: ' . $uploadOriginalName);
        }
        flash('success', 'Demanda atualizada com sucesso.');
        redirect('master/demands');
    }

    public function deleteDemand(): void { $this->requireAuth('master');$this->assertCsrfOrRedirect('master/demands');$id=(int)($_POST['id']??0);if($id<=0){flash('error','Demanda inválida.');redirect('master/demands');}$this->demands->delete($id);$this->logAction('delete','demandas',$id,'Demanda removida pelo Master');flash('success','Demanda removida.');redirect('master/demands'); }
    public function readNotification(): void { $this->requireAuth('master');$this->assertCsrfOrRedirect('master/notifications');$notificationId=(int)($_POST['id']??0);$this->notifications->markRead($notificationId,(int)$_SESSION['user']['id']);flash('success','Notificação marcada como lida.');redirect('master/notifications'); }
    public function notifications(): void { $this->requireAuth('master');$masterId=(int)$_SESSION['user']['id'];$this->deadlineService->runAutomationForMaster($masterId);$this->view('master/notifications/index',['notifications'=>$this->notifications->forUser($masterId,50)]); }


    public function sessions(): void
    {
        $this->requireAuth('master');

        $this->view('master/sessions/index', [
            'sessions' => $this->users->activeSessions(),
            'currentSessionId' => (string)($_SESSION['user']['session_id'] ?? session_id()),
        ]);
    }

    public function terminateSession(): void
    {
        $this->requireAuth('master');
        $this->assertCsrfOrRedirect('master/sessions');

        $sessionAuditId = (int)($_POST['session_audit_id'] ?? 0);
        if ($sessionAuditId <= 0) {
            flash('error', 'Sessão inválida para encerramento.');
            redirect('master/sessions');
        }

        $session = $this->users->findActiveSessionById($sessionAuditId);
        if (!$session || (int)($session['ativo'] ?? 0) !== 1) {
            flash('error', 'Sessão não encontrada ou já encerrada.');
            redirect('master/sessions');
        }

        $this->users->deactivateSessionByAuditId($sessionAuditId);

        $this->logAction(
            'terminate_session',
            'sessoes_usuario',
            $sessionAuditId,
            sprintf('Master encerrou sessão auditada #%d do usuário #%d', $sessionAuditId, (int)$session['usuario_id'])
        );

        flash('success', 'Sessão encerrada com sucesso.');
        redirect('master/sessions');
    }

    private function validateEmployeePayload(): array
    {
        $data=['nome_completo'=>trim((string)($_POST['nome_completo']??'')),'email'=>trim((string)($_POST['email']??'')),'endereco'=>trim((string)($_POST['endereco']??'')),'whatsapp'=>trim((string)($_POST['whatsapp']??'')),'numero_decreto'=>trim((string)($_POST['numero_decreto']??'')),'data_nascimento'=>trim((string)($_POST['data_nascimento']??''))];
        $errors=validate_required($data,['nome_completo','email','endereco','whatsapp','numero_decreto','data_nascimento']);
        if($data['email']!==''&&!filter_var($data['email'],FILTER_VALIDATE_EMAIL)){$errors[]='E-mail inválido.';}
        if($data['data_nascimento']!==''&&strtotime($data['data_nascimento'])===false){$errors[]='Data de nascimento inválida.';}
        return ['ok'=>$errors===[],'data'=>$data,'errors'=>$errors];
    }

    private function validateDemandPayload(?string $existingAttachment = null): array
    {
        $data = [
            'emenda' => trim((string)($_POST['emenda'] ?? '')),
            'nome_politico' => trim((string)($_POST['nome_politico'] ?? '')),
            'numero_processo_sei' => trim((string)($_POST['numero_processo_sei'] ?? '')),
            'tipo_processo' => trim((string)($_POST['tipo_processo'] ?? '')),
            'tipo_emenda' => trim((string)($_POST['tipo_emenda'] ?? '')),
            'data_prazo_resposta' => trim((string)($_POST['data_prazo_resposta'] ?? '')),
            'data_cadastro_emenda' => trim((string)($_POST['data_cadastro_emenda'] ?? '')),
            'observacao' => trim((string)($_POST['observacao'] ?? '')),
            'funcionario_id' => (int)($_POST['funcionario_id'] ?? 0),
            'status' => trim((string)($_POST['status'] ?? 'pendente')),
            'anexo_emenda' => $existingAttachment,
            'anexo_nome_original' => trim((string)($_POST['anexo_nome_original'] ?? '')),
        ];

        $errors = validate_required($data, ['emenda', 'tipo_processo', 'tipo_emenda', 'data_prazo_resposta', 'data_cadastro_emenda']);
        if (!in_array($data['tipo_processo'], Demand::PROCESS_TYPES, true)) { $errors[] = 'Tipo de processo inválido.'; }
        if (!in_array($data['tipo_emenda'], Demand::AMENDMENT_TYPES, true)) { $errors[] = 'Tipo de emenda inválido.'; }
        if (!in_array($data['status'], Demand::STATUS_ALLOWED, true)) { $errors[] = 'Status da demanda inválido.'; }

        if ($data['numero_processo_sei'] !== '' && mb_strlen($data['numero_processo_sei']) > 80) { $errors[] = 'Número do processo SEI deve ter no máximo 80 caracteres.'; }
        if ($data['funcionario_id'] <= 0 || !$this->users->existsEmployeeById($data['funcionario_id'])) { $errors[] = 'Selecione um funcionário válido para a demanda.'; }

        $prazoTimestamp = strtotime($data['data_prazo_resposta']);
        $cadastroTimestamp = strtotime($data['data_cadastro_emenda']);
        if ($prazoTimestamp === false) { $errors[] = 'Data prazo de resposta inválida.'; } else { $data['data_prazo_resposta'] = date('Y-m-d H:i:s', $prazoTimestamp); }
        if ($cadastroTimestamp === false) { $errors[] = 'Data de cadastro da emenda inválida.'; } else { $data['data_cadastro_emenda'] = date('Y-m-d', $cadastroTimestamp); }

        $upload = $this->attachmentService->store($_FILES['anexo_emenda'] ?? null);
        if (!$upload['ok']) {
            $errors = array_merge($errors, $upload['errors']);
        } elseif ($upload['path'] !== null) {
            $data['anexo_emenda'] = $upload['path'];
            $data['_upload_original_name'] = $upload['original_name'];
            $data['anexo_nome_original'] = (string)$upload['original_name'];
        }

        return ['ok' => $errors === [], 'data' => $data, 'errors' => $errors];
    }

    private function assertCsrfOrRedirect(string $redirectPath): void
    {
        if (!verify_csrf($_POST['_csrf'] ?? null)) { flash('error', 'Falha de segurança na requisição.'); redirect($redirectPath); }
    }

    private function logAction(string $acao, string $entidade, ?int $entidadeId, string $descricao): void
    {
        $this->logs->create(['usuario_id'=>(int)($_SESSION['user']['id'] ?? 0) ?: null,'acao'=>$acao,'entidade'=>$entidade,'entidade_id'=>$entidadeId,'descricao'=>$descricao,'ip'=>client_ip(),'user_agent'=>$_SERVER['HTTP_USER_AGENT'] ?? null]);
    }
}
