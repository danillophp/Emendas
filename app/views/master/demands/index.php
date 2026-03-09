<?php
$statusLabels = [
    'pendente' => 'Pendente',
    'em_andamento' => 'Em andamento',
    'concluida' => 'Concluída',
    'atrasada' => 'Atrasada',
];

$statusClasses = [
    'pendente' => 'badge-pendente',
    'em_andamento' => 'badge-andamento',
    'concluida' => 'badge-concluida',
    'atrasada' => 'badge-atrasada',
];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Gestão de Demandas</h3>
  <span class="text-muted">Cadastro, filtros, atualização de status e atribuição</span>
</div>

<div class="card card-ui mb-3">
  <div class="card-body">
    <form method="get" action="<?= url('master/demands') ?>" class="row g-2">
      <div class="col-md-3">
        <select class="form-select" name="status">
          <option value="">Todos os status</option>
          <?php foreach ($statusLabels as $key => $label): ?>
            <option value="<?= $key ?>" <?= ($filters['status'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <select class="form-select" name="funcionario_id">
          <option value="">Todos os funcionários</option>
          <?php foreach ($employees as $employee): ?>
            <option value="<?= (int)$employee['id'] ?>" <?= (string)($filters['funcionario_id'] ?? '') === (string)$employee['id'] ? 'selected' : '' ?>>
              <?= e($employee['nome_completo']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2"><input type="date" class="form-control" name="prazo_de" value="<?= e($filters['prazo_de'] ?? '') ?>"></div>
      <div class="col-md-2"><input type="date" class="form-control" name="prazo_ate" value="<?= e($filters['prazo_ate'] ?? '') ?>"></div>
      <div class="col-md-2 d-grid"><button class="btn btn-dark">Filtrar</button></div>
    </form>
  </div>
</div>

<div class="card card-ui mb-4">
  <div class="card-body">
    <h5 class="mb-3">Cadastrar demanda</h5>
    <form method="post" action="<?= url('master/demands/create') ?>" class="row g-2">
      <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
      <div class="col-md-4"><input class="form-control" name="emenda" placeholder="Nome da emenda" required></div>
      <div class="col-md-4"><input class="form-control" name="nome_politico" placeholder="Nome do político" required></div>
      <div class="col-md-2"><input type="date" class="form-control" name="data_emenda" required></div>
      <div class="col-md-2"><input class="form-control" name="tipo_emenda" placeholder="Tipo de emenda" required></div>
      <div class="col-md-4"><input type="datetime-local" class="form-control" name="prazo_entrega" required></div>
      <div class="col-md-4">
        <select class="form-select" name="funcionario_id" required>
          <option value="">Selecione o funcionário</option>
          <?php foreach ($employees as $employee): ?>
            <option value="<?= (int)$employee['id'] ?>"><?= e($employee['nome_completo']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <select class="form-select" name="status" required>
          <?php foreach ($statusLabels as $key => $label): ?>
            <option value="<?= $key ?>"><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12"><textarea class="form-control" name="observacao" rows="2" placeholder="Observações"></textarea></div>
      <div class="col-12 d-grid"><button class="btn btn-primary"><i class="bi bi-plus-circle"></i> Cadastrar demanda</button></div>
    </form>
  </div>
</div>

<div class="table-responsive">
  <table class="table table-hover table-modern align-middle data-table">
    <thead>
      <tr>
        <th>Demanda</th>
        <th>Responsável</th>
        <th>Prazo</th>
        <th>Status</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($demands as $demand): ?>
        <tr>
          <td>
            <strong><?= e($demand['emenda']) ?></strong><br>
            <small class="text-muted"><?= e($demand['nome_politico']) ?> • <?= e($demand['tipo_emenda']) ?></small>
          </td>
          <td><?= e($demand['funcionario_nome']) ?></td>
          <td><?= e(date('d/m/Y H:i', strtotime($demand['prazo_entrega']))) ?></td>
          <td><span class="badge badge-status <?= $statusClasses[$demand['status']] ?? 'bg-secondary' ?>"><?= e($statusLabels[$demand['status']] ?? $demand['status']) ?></span></td>
          <td>
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#detalhe<?= (int)$demand['id'] ?>">
              <i class="bi bi-eye"></i> Detalhes
            </button>
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editar<?= (int)$demand['id'] ?>">
              <i class="bi bi-pencil-square"></i> Editar
            </button>
            <form method="post" action="<?= url('master/demands/delete') ?>" class="d-inline" onsubmit="return confirm('Deseja remover esta demanda?');">
              <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="id" value="<?= (int)$demand['id'] ?>">
              <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>

        <div class="modal fade" id="detalhe<?= (int)$demand['id'] ?>" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
              <div class="modal-header"><h5 class="modal-title">Detalhes da demanda</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
              <div class="modal-body">
                <p><strong>Emenda:</strong> <?= e($demand['emenda']) ?></p>
                <p><strong>Político:</strong> <?= e($demand['nome_politico']) ?></p>
                <p><strong>Tipo:</strong> <?= e($demand['tipo_emenda']) ?></p>
                <p><strong>Data da emenda:</strong> <?= e(date('d/m/Y', strtotime($demand['data_emenda']))) ?></p>
                <p><strong>Responsável:</strong> <?= e($demand['funcionario_nome']) ?></p>
                <p><strong>Prazo:</strong> <?= e(date('d/m/Y H:i', strtotime($demand['prazo_entrega']))) ?></p>
                <p><strong>Status:</strong> <?= e($statusLabels[$demand['status']] ?? $demand['status']) ?></p>
                <p><strong>Observação:</strong><br><?= nl2br(e($demand['observacao'] ?? '-')) ?></p>
              </div>
            </div>
          </div>
        </div>

        <div class="modal fade" id="editar<?= (int)$demand['id'] ?>" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header"><h5 class="modal-title">Editar demanda</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
              <form method="post" action="<?= url('master/demands/update') ?>">
                <div class="modal-body row g-2">
                  <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                  <input type="hidden" name="id" value="<?= (int)$demand['id'] ?>">
                  <div class="col-md-6"><input class="form-control" name="emenda" value="<?= e($demand['emenda']) ?>" required></div>
                  <div class="col-md-6"><input class="form-control" name="nome_politico" value="<?= e($demand['nome_politico']) ?>" required></div>
                  <div class="col-md-3"><input class="form-control" type="date" name="data_emenda" value="<?= e($demand['data_emenda']) ?>" required></div>
                  <div class="col-md-5"><input class="form-control" name="tipo_emenda" value="<?= e($demand['tipo_emenda']) ?>" required></div>
                  <div class="col-md-4"><input class="form-control" type="datetime-local" name="prazo_entrega" value="<?= e(date('Y-m-d\TH:i', strtotime($demand['prazo_entrega']))) ?>" required></div>
                  <div class="col-md-6">
                    <select class="form-select" name="funcionario_id" required>
                      <?php foreach ($employees as $employee): ?>
                        <option value="<?= (int)$employee['id'] ?>" <?= (int)$employee['id'] === (int)$demand['funcionario_id'] ? 'selected' : '' ?>>
                          <?= e($employee['nome_completo']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <select class="form-select" name="status" required>
                      <?php foreach ($statusLabels as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $demand['status'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-12"><textarea class="form-control" name="observacao" rows="3"><?= e($demand['observacao'] ?? '') ?></textarea></div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                  <button class="btn btn-success"><i class="bi bi-save"></i> Salvar alterações</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<nav>
  <ul class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <li class="page-item <?= $i === $page ? 'active' : '' ?>">
        <a class="page-link" href="<?= url('master/demands?status=' . urlencode($filters['status'] ?? '') . '&funcionario_id=' . urlencode($filters['funcionario_id'] ?? '') . '&prazo_de=' . urlencode($filters['prazo_de'] ?? '') . '&prazo_ate=' . urlencode($filters['prazo_ate'] ?? '') . '&page=' . $i) ?>">
          <?= $i ?>
        </a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
