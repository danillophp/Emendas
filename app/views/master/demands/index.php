<?php
$statusLabels = ['pendente' => 'Pendente', 'cadastrado' => 'Cadastrado'];
$processLabels = ['prestacao_de_conta' => 'Prestação de conta', 'cadastro_de_emenda' => 'Cadastro de emenda'];
$amendmentLabels = ['parlamentar' => 'Parlamentar', 'estadual' => 'Estadual', 'municipal' => 'Municipal'];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Gestão de Demandas</h3>
  <span class="text-muted">Fluxo atualizado com anexos e notificação automática</span>
</div>

<div class="card card-ui mb-3"><div class="card-body">
<form method="get" action="<?= url('master/demands') ?>" class="row g-2">
  <div class="col-md-3"><select class="form-select" name="status"><option value="">Todos os status</option><?php foreach ($statusLabels as $k=>$l): ?><option value="<?= $k ?>" <?= ($filters['status']??'')===$k?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><select class="form-select" name="funcionario_id"><option value="">Todos os funcionários</option><?php foreach ($employees as $employee): ?><option value="<?= (int)$employee['id'] ?>" <?= (string)($filters['funcionario_id']??'')===(string)$employee['id']?'selected':'' ?>><?= e($employee['nome_completo']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><input type="date" class="form-control" name="prazo_de" value="<?= e($filters['prazo_de'] ?? '') ?>"></div>
  <div class="col-md-2"><input type="date" class="form-control" name="prazo_ate" value="<?= e($filters['prazo_ate'] ?? '') ?>"></div>
  <div class="col-md-2 d-grid"><button class="btn btn-dark">Filtrar</button></div>
</form>
</div></div>

<div class="card card-ui mb-4"><div class="card-body">
<h5 class="mb-3">Cadastrar demanda</h5>
<form method="post" action="<?= url('master/demands/create') ?>" class="row g-2" enctype="multipart/form-data">
  <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
  <div class="col-md-4"><input class="form-control" name="emenda" placeholder="Nome da emenda" required></div>
  <div class="col-md-4"><input class="form-control" name="nome_politico" placeholder="Nome do político" required></div>
  <div class="col-md-4"><select class="form-select" name="tipo_processo" required><option value="">Tipo de processo</option><?php foreach ($processLabels as $k=>$l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><select class="form-select" name="tipo_emenda" required><option value="">Tipo de emenda</option><?php foreach ($amendmentLabels as $k=>$l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><input type="datetime-local" class="form-control" name="data_prazo_resposta" required></div>
  <div class="col-md-3"><input type="date" class="form-control" name="data_cadastro_emenda" required></div>
  <div class="col-md-3"><select class="form-select" name="status" required><?php foreach ($statusLabels as $k=>$l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></div>
  <div class="col-md-8"><select class="form-select" name="funcionario_id" required><option value="">Selecione o funcionário</option><?php foreach ($employees as $employee): ?><option value="<?= (int)$employee['id'] ?>"><?= e($employee['nome_completo']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-4"><input type="file" class="form-control" name="anexo_emenda" accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"></div>
  <div class="col-12"><textarea class="form-control" name="observacao" rows="2" placeholder="Observações"></textarea></div>
  <div class="col-12 d-grid"><button class="btn btn-primary">Cadastrar demanda</button></div>
</form>
</div></div>

<div class="table-responsive">
<table class="table table-hover table-modern align-middle data-table">
<thead><tr><th>Demanda</th><th>Responsável</th><th>Prazo</th><th>Status</th><th>Anexo</th><th>Ações</th></tr></thead>
<tbody>
<?php foreach ($demands as $demand): ?>
<tr>
  <td><strong><?= e($demand['emenda']) ?></strong><br><small class="text-muted"><?= e($processLabels[$demand['tipo_processo']] ?? $demand['tipo_processo']) ?> • <?= e($amendmentLabels[$demand['tipo_emenda']] ?? $demand['tipo_emenda']) ?></small></td>
  <td><?= e($demand['funcionario_nome']) ?></td>
  <td><?= e(date('d/m/Y H:i', strtotime($demand['data_prazo_resposta']))) ?></td>
  <td><span class="badge bg-secondary"><?= e($statusLabels[$demand['status']] ?? $demand['status']) ?></span></td>
  <td><?php if (!empty($demand['anexo_emenda'])): ?><a class="btn btn-sm btn-outline-dark" target="_blank" href="<?= url('anexos/demandas/download?demand_id=' . (int)$demand['id']) ?>">Visualizar</a><?php else: ?><span class="text-muted">-</span><?php endif; ?></td>
  <td>
    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editar<?= (int)$demand['id'] ?>">Editar</button>
    <form method="post" action="<?= url('master/demands/delete') ?>" class="d-inline" onsubmit="return confirm('Deseja remover esta demanda?');"><input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden" name="id" value="<?= (int)$demand['id'] ?>"><button class="btn btn-sm btn-danger">Excluir</button></form>
  </td>
</tr>
<div class="modal fade" id="editar<?= (int)$demand['id'] ?>" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Editar demanda</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<form method="post" action="<?= url('master/demands/update') ?>" enctype="multipart/form-data"><div class="modal-body row g-2">
<input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden" name="id" value="<?= (int)$demand['id'] ?>">
<div class="col-md-6"><input class="form-control" name="emenda" value="<?= e($demand['emenda']) ?>" required></div>
<div class="col-md-6"><input class="form-control" name="nome_politico" value="<?= e($demand['nome_politico']) ?>" required></div>
<div class="col-md-4"><select class="form-select" name="tipo_processo" required><?php foreach ($processLabels as $k=>$l): ?><option value="<?= $k ?>" <?= $demand['tipo_processo']===$k?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><select class="form-select" name="tipo_emenda" required><?php foreach ($amendmentLabels as $k=>$l): ?><option value="<?= $k ?>" <?= $demand['tipo_emenda']===$k?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><select class="form-select" name="status" required><?php foreach ($statusLabels as $k=>$l): ?><option value="<?= $k ?>" <?= $demand['status']===$k?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><input class="form-control" type="datetime-local" name="data_prazo_resposta" value="<?= e(date('Y-m-d\TH:i', strtotime($demand['data_prazo_resposta']))) ?>" required></div>
<div class="col-md-4"><input class="form-control" type="date" name="data_cadastro_emenda" value="<?= e($demand['data_cadastro_emenda']) ?>" required></div>
<div class="col-md-4"><input type="file" class="form-control" name="anexo_emenda" accept=".pdf,.docx"></div>
<div class="col-md-12"><select class="form-select" name="funcionario_id" required><?php foreach ($employees as $employee): ?><option value="<?= (int)$employee['id'] ?>" <?= (int)$employee['id']===(int)$demand['funcionario_id']?'selected':'' ?>><?= e($employee['nome_completo']) ?></option><?php endforeach; ?></select></div>
<?php if (!empty($demand['anexo_emenda'])): ?><div class="col-12"><a target="_blank" href="<?= url('anexos/demandas/download?demand_id=' . (int)$demand['id']) ?>">Anexo atual</a></div><?php endif; ?>
<div class="col-12"><textarea class="form-control" name="observacao" rows="2"><?= e($demand['observacao'] ?? '') ?></textarea></div>
</div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-success">Salvar</button></div></form>
</div></div></div>
<?php endforeach; ?>
</tbody></table></div>
