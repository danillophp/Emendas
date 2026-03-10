<?php
$statusLabels = ['pendente' => 'Pendente', 'cadastrado' => 'Cadastrado'];
$processLabels = ['prestacao_de_conta' => 'Prestação de conta', 'cadastro_de_emenda' => 'Cadastro de emenda'];
$amendmentLabels = ['parlamentar' => 'Parlamentar', 'estadual' => 'Estadual', 'municipal' => 'Municipal'];
?>

<h3 class="mb-3">Minhas Demandas</h3>
<div class="table-responsive">
  <table class="table table-striped table-modern align-middle data-table" id="employeeDemandsTable">
    <thead><tr><th>Emenda</th><th>Tipo</th><th>Prazo</th><th>Status</th><th>Anexo</th><th>Ação</th></tr></thead>
    <tbody id="employeeDemandsBody">
      <?php foreach ($demands as $demand): ?>
      <tr>
        <td><strong><?= e($demand['emenda']) ?></strong><br><small class="text-muted"><?= e($demand['nome_politico']) ?></small></td>
        <td><?= e($processLabels[$demand['tipo_processo']] ?? $demand['tipo_processo']) ?> / <?= e($amendmentLabels[$demand['tipo_emenda']] ?? $demand['tipo_emenda']) ?></td>
        <td><?= e(date('d/m/Y H:i', strtotime($demand['data_prazo_resposta']))) ?></td>
        <td><span class="badge bg-secondary"><?= e($statusLabels[$demand['status']] ?? $demand['status']) ?></span></td>
        <td><?php if (!empty($demand['anexo_emenda'])): ?><a target="_blank" href="<?= url('anexos/demandas/download?demand_id=' . (int)$demand['id']) ?>">Abrir</a><?php else: ?>-<?php endif; ?></td>
        <td>
          <form method="post" action="<?= url('funcionario/demandas/status') ?>" class="d-flex gap-2">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="id" value="<?= (int)$demand['id'] ?>">
            <select class="form-select form-select-sm" name="status" required>
              <?php foreach ($statusLabels as $key => $label): ?>
                <option value="<?= $key ?>" <?= $demand['status'] === $key ? 'selected' : '' ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-primary">Atualizar</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
