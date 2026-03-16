<?php
$statusLabels = ['pendente' => 'Pendente', 'cadastrada' => 'Cadastrada', 'concluído' => 'Concluído'];
$statusClass = ['pendente' => 'badge-pendente', 'cadastrada' => 'badge-cadastrada', 'concluído' => 'badge-concluido'];
$processLabels = ['prestacao_de_conta' => 'Prestação de conta', 'cadastro_de_emenda' => 'Cadastro de emenda'];
$amendmentLabels = ['federal' => 'Federal', 'estadual' => 'Estadual', 'municipal' => 'Municipal'];
?>

<section class="page-hero mb-3">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h3 class="mb-1">Gestão de Demandas</h3>
      <p class="text-muted mb-0">Controle institucional completo de cadastro, prazos e acompanhamento.</p>
    </div>
    <span class="role-pill"><i class="bi bi-shield-check"></i> Governança ativa</span>
  </div>
</section>

<div class="card card-ui mb-3 surface-card"><div class="card-body">
<form method="get" action="<?= url('master/demands') ?>" class="row g-2">
  <div class="col-md-3"><select class="form-select" name="status"><option value="">Todos os status</option><?php foreach ($statusLabels as $k=>$l): ?><option value="<?= $k ?>" <?= ($filters['status']??'')===$k?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><select class="form-select" name="funcionario_id"><option value="">Todos os funcionários</option><?php foreach ($employees as $employee): ?><option value="<?= (int)$employee['id'] ?>" <?= (string)($filters['funcionario_id']??'')===(string)$employee['id']?'selected':'' ?>><?= e($employee['nome_completo']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><input type="date" class="form-control" name="prazo_de" value="<?= e($filters['prazo_de'] ?? '') ?>"></div>
  <div class="col-md-2"><input type="date" class="form-control" name="prazo_ate" value="<?= e($filters['prazo_ate'] ?? '') ?>"></div>
  <div class="col-md-2 d-grid"><button class="btn btn-dark">Filtrar</button></div>
</form>
</div></div>

<div class="card card-ui mb-4 surface-card"><div class="card-body">
<h5 class="mb-3 section-title">Cadastrar demanda</h5>
<form method="post" action="<?= url('master/demands/create') ?>" class="row g-3" enctype="multipart/form-data">
  <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
  <div class="col-md-4"><input class="form-control" name="emenda" placeholder="Nome da emenda" required></div>
  <div class="col-md-4"><input class="form-control" name="nome_politico" placeholder="Nome do político (opcional)"></div>
  <div class="col-md-4"><input class="form-control" name="numero_processo_sei" placeholder="Número do processo SEI (opcional)"></div>
  <div class="col-md-4"><select class="form-select" name="tipo_processo" required><option value="">Tipo de processo</option><?php foreach ($processLabels as $k=>$l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><select class="form-select" name="tipo_emenda" required><option value="">Tipo de emenda</option><?php foreach ($amendmentLabels as $k=>$l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></div>
  <div class="col-md-4">
    <label class="form-label fw-semibold mb-1">Data limite para o funcionário resolver a demanda</label>
    <input type="datetime-local" class="form-control" name="data_prazo_resposta" required>
  </div>
  <div class="col-md-4">
    <label class="form-label fw-semibold mb-1">Data final para o administrador cadastrar a demanda</label>
    <input type="date" class="form-control" name="data_cadastro_emenda" required>
  </div>
  <div class="col-md-3"><select class="form-select" name="status" required><?php foreach ($statusLabels as $k=>$l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></div>
  <div class="col-md-9"><select class="form-select" name="funcionario_id" required><option value="">Selecione o funcionário</option><?php foreach ($employees as $employee): ?><option value="<?= (int)$employee['id'] ?>"><?= e($employee['nome_completo']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-4"><input type="file" class="form-control" name="anexo_emenda" accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"></div>
  <div class="col-12"><textarea class="form-control" name="observacao" rows="2" placeholder="Observações da demanda (Master)"></textarea></div>
  <div class="col-12 d-grid"><button class="btn btn-primary">Cadastrar demanda</button></div>
</form>
</div></div>

<div class="table-responsive">
<table class="table table-hover table-modern align-middle data-table table-premium">
<thead><tr><th>Demanda</th><th>Responsável</th><th>Prazos</th><th>Status</th><th>Anexo</th><th>Ações</th></tr></thead>
<tbody id="masterDemandsBody">
<?php foreach ($demands as $demand): ?>
<tr>
  <td><strong><?= e($demand['emenda']) ?></strong><br><small class="text-muted"><?= e($processLabels[$demand['tipo_processo']] ?? $demand['tipo_processo']) ?> • <?= e($amendmentLabels[$demand['tipo_emenda']] ?? (($demand['tipo_emenda'] ?? '') === 'parlamentar' ? 'Federal' : $demand['tipo_emenda'])) ?></small><br><small class="text-muted">SEI: <?= e(trim((string)($demand['numero_processo_sei'] ?? '')) !== '' ? $demand['numero_processo_sei'] : 'Não informado') ?></small></td>
  <td><?= e($demand['funcionario_nome']) ?></td>
  <td>
    <div><small class="text-muted d-block">Prazo funcionário:</small><strong><?= e(date('d/m/Y H:i', strtotime($demand['data_prazo_resposta']))) ?></strong></div>
    <div><small class="text-muted d-block">Prazo administrativo:</small><strong><?= e(date('d/m/Y', strtotime($demand['data_cadastro_emenda']))) ?></strong></div>
  </td>
  <td><span class="badge badge-status <?= e($statusClass[$demand['status']] ?? 'bg-secondary') ?>"><?= e($statusLabels[$demand['status']] ?? $demand['status']) ?></span></td>
  <td><?php if (!empty($demand['anexo_emenda'])): ?><a class="btn btn-sm btn-outline-dark" target="_blank" href="<?= url('anexos/demandas/download?demand_id=' . (int)$demand['id']) ?>">Visualizar</a><?php else: ?><span class="text-muted">-</span><?php endif; ?></td>
  <td>
    <button class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#detalhes<?= (int)$demand['id'] ?>">Abrir demanda</button>
    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editar<?= (int)$demand['id'] ?>">Editar</button>
    <form method="post" action="<?= url('master/demands/delete') ?>" class="d-inline" onsubmit="return confirm('Deseja remover esta demanda?');"><input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden" name="id" value="<?= (int)$demand['id'] ?>"><button class="btn btn-sm btn-danger">Excluir</button></form>
  </td>
</tr>


<div class="modal fade" id="detalhes<?= (int)$demand['id'] ?>" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detalhes da demanda #<?= (int)$demand['id'] ?></h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3 mb-3">
          <div class="col-md-4"><small class="text-muted d-block">Emenda</small><strong><?= e($demand['emenda']) ?></strong></div>
          <div class="col-md-4"><small class="text-muted d-block">Nome do político</small><strong><?= e(trim((string)($demand['nome_politico'] ?? '')) !== '' ? $demand['nome_politico'] : 'Não informado') ?></strong></div>
          <div class="col-md-4"><small class="text-muted d-block">Número do processo SEI</small><strong><?= e(trim((string)($demand['numero_processo_sei'] ?? '')) !== '' ? $demand['numero_processo_sei'] : 'Não informado') ?></strong></div>
          <div class="col-md-4"><small class="text-muted d-block">Tipo de processo</small><strong><?= e($processLabels[$demand['tipo_processo']] ?? $demand['tipo_processo']) ?></strong></div>
          <div class="col-md-4"><small class="text-muted d-block">Tipo de emenda</small><strong><?= e($amendmentLabels[$demand['tipo_emenda']] ?? (($demand['tipo_emenda'] ?? '') === 'parlamentar' ? 'Federal' : $demand['tipo_emenda'])) ?></strong></div>
          <div class="col-md-4"><small class="text-muted d-block">Status atual</small><span class="badge badge-status <?= e($statusClass[$demand['status']] ?? 'bg-secondary') ?>"><?= e($statusLabels[$demand['status']] ?? $demand['status']) ?></span></div>
          <div class="col-md-4"><small class="text-muted d-block">Data limite do funcionário</small><strong><?= e(date('d/m/Y H:i', strtotime($demand['data_prazo_resposta']))) ?></strong></div>
          <div class="col-md-4"><small class="text-muted d-block">Data final administrativa</small><strong><?= e(date('d/m/Y', strtotime($demand['data_cadastro_emenda']))) ?></strong></div>
          <div class="col-md-4"><small class="text-muted d-block">Última atualização</small><strong><?= e(date('d/m/Y H:i', strtotime($demand['data_ultima_atualizacao'] ?? $demand['updated_at'] ?? $demand['created_at']))) ?></strong></div>
          <div class="col-md-6"><small class="text-muted d-block">Funcionário responsável</small><strong><?= e($demand['funcionario_nome']) ?></strong></div>
          <div class="col-md-6"><small class="text-muted d-block">Anexo</small><?php if (!empty($demand['anexo_emenda'])): ?><a target="_blank" href="<?= url('anexos/demandas/download?demand_id=' . (int)$demand['id']) ?>">Visualizar anexo</a><?php else: ?><span class="text-muted">Sem anexo</span><?php endif; ?></div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Observações registradas</label>
          <div class="border rounded p-2 bg-light small"><?= nl2br(e(trim((string)($demand['observacao'] ?? 'Sem observações do Master.')))) ?></div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Observações do funcionário</label>
          <div class="border rounded p-2 bg-light small"><?= nl2br(e(trim((string)($demand['observacao_funcionario'] ?? 'Sem observações do funcionário.')))) ?></div>
        </div>

        <div>
          <label class="form-label fw-semibold">Histórico de atualizações do funcionário</label>
          <?php $historyRows = $historyMap[(int)$demand['id']] ?? []; ?>
          <?php if (!empty($historyRows)): ?>
            <div class="list-group list-group-flush border rounded">
              <?php foreach ($historyRows as $history): ?>
                <div class="list-group-item">
                  <div class="d-flex justify-content-between flex-wrap gap-2">
                    <strong><?= e($history['usuario_nome_atual'] ?? $history['usuario_nome'] ?? 'Funcionário') ?></strong>
                    <small class="text-muted"><?= e(date('d/m/Y H:i', strtotime($history['created_at']))) ?></small>
                  </div>
                  <div class="small mt-1">Status: <span class="text-muted"><?= e($history['status_anterior'] ?? '-') ?></span> → <strong><?= e($history['status_novo'] ?? '-') ?></strong></div>
                  <div class="small mt-1">Observação: <?= e(trim((string)($history['observacao'] ?? '')) !== '' ? $history['observacao'] : 'Sem observação nesta atualização.') ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="alert alert-light border mb-0">Ainda não há histórico de alterações feito por funcionário para esta demanda.</div>
          <?php endif; ?>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="editar<?= (int)$demand['id'] ?>" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Editar demanda</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="post" action="<?= url('master/demands/update') ?>" enctype="multipart/form-data"><div class="modal-body row g-3">
      <input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden" name="id" value="<?= (int)$demand['id'] ?>">
      <div class="col-md-6"><label class="form-label">Nome da emenda</label><input class="form-control" name="emenda" value="<?= e($demand['emenda']) ?>" required></div>
      <div class="col-md-6"><label class="form-label">Nome do político <small class="text-muted">(opcional)</small></label><input class="form-control" name="nome_politico" value="<?= e($demand['nome_politico']) ?>"></div>
      <div class="col-md-6"><label class="form-label">Número do processo SEI <small class="text-muted">(opcional)</small></label><input class="form-control" name="numero_processo_sei" value="<?= e($demand['numero_processo_sei'] ?? '') ?>"></div>
      <div class="col-md-4"><label class="form-label">Tipo de processo</label><select class="form-select" name="tipo_processo" required><?php foreach ($processLabels as $k=>$l): ?><option value="<?= $k ?>" <?= $demand['tipo_processo']===$k?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="form-label">Tipo de emenda</label><select class="form-select" name="tipo_emenda" required><?php foreach ($amendmentLabels as $k=>$l): ?><option value="<?= $k ?>" <?= $demand['tipo_emenda']===$k?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status" required><?php foreach ($statusLabels as $k=>$l): ?><option value="<?= $k ?>" <?= $demand['status']===$k?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Data limite para o funcionário resolver a demanda</label><input type="datetime-local" class="form-control" name="data_prazo_resposta" value="<?= e(date('Y-m-d\TH:i', strtotime($demand['data_prazo_resposta']))) ?>" required></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Data final para o administrador cadastrar a demanda</label><input type="date" class="form-control" name="data_cadastro_emenda" value="<?= e(date('Y-m-d', strtotime($demand['data_cadastro_emenda']))) ?>" required></div>
      <div class="col-md-8"><label class="form-label">Funcionário responsável</label><select class="form-select" name="funcionario_id" required><?php foreach ($employees as $employee): ?><option value="<?= (int)$employee['id'] ?>" <?= (int)$demand['funcionario_id']===(int)$employee['id']?'selected':'' ?>><?= e($employee['nome_completo']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="form-label">Substituir anexo</label><input type="file" class="form-control" name="anexo_emenda" accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"></div>
      <div class="col-12"><label class="form-label">Observações da demanda (Master)</label><textarea class="form-control" name="observacao" rows="3"><?= e($demand['observacao'] ?? '') ?></textarea></div>
      <div class="col-12"><label class="form-label">Observações do funcionário</label><textarea class="form-control" rows="4" disabled><?= e($demand['observacao_funcionario'] ?? 'Sem observações do funcionário.') ?></textarea></div>
    </div><div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary">Salvar alterações</button></div></form>
  </div></div>
</div>
<?php endforeach; ?>
</tbody></table></div>

<?php if (($totalPages ?? 1) > 1): ?>
<nav class="mt-3"><ul class="pagination justify-content-end mb-0">
<?php for ($p=1; $p<=$totalPages; $p++): ?>
<li class="page-item <?= (int)$p === (int)$page ? 'active' : '' ?>"><a class="page-link" href="<?= url('master/demands?' . http_build_query(['status'=>$filters['status'] ?? '','funcionario_id'=>$filters['funcionario_id'] ?? '','prazo_de'=>$filters['prazo_de'] ?? '','prazo_ate'=>$filters['prazo_ate'] ?? '', 'page'=>$p])) ?>"><?= $p ?></a></li>
<?php endfor; ?>
</ul></nav>
<?php endif; ?>
