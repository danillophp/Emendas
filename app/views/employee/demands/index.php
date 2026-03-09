<h3>Minhas Demandas</h3>
<div class="table-responsive">
<table class="table table-striped table-modern align-middle data-table">
<tr>
  <th>Emenda</th>
  <th>Nome do político</th>
  <th>Data da emenda</th>
  <th>Tipo da emenda</th>
  <th>Observação</th>
  <th>Prazo final</th>
  <th>Tempo restante</th>
  <th>Status</th>
  <th>Ação</th>
</tr>
<?php foreach ($demands as $demand):
  $remainingSeconds = strtotime($demand['prazo_entrega']) - time();
  $remainingHours = (int) floor($remainingSeconds / 3600);
  $timeRemaining = $remainingSeconds > 0 ? $remainingHours . 'h' : 'Expirado';
?>
<tr>
  <td><?= e($demand['emenda']) ?></td>
  <td><?= e($demand['nome_politico']) ?></td>
  <td><?= e(date('d/m/Y', strtotime($demand['data_emenda']))) ?></td>
  <td><?= e($demand['tipo_emenda']) ?></td>
  <td><?= e($demand['observacao'] ?? '-') ?></td>
  <td><?= e(date('d/m/Y H:i', strtotime($demand['prazo_entrega']))) ?></td>
  <td><span class="badge <?= $remainingSeconds > 0 ? 'bg-info text-dark' : 'bg-danger' ?>"><?= e($timeRemaining) ?></span></td>
  <td><span class="badge <?= $demand['status'] === 'concluida' ? 'bg-success' : 'bg-warning text-dark' ?>"><?= e($demand['status']) ?></span></td>
  <td>
    <button class="btn btn-sm btn-outline-secondary mb-1" data-bs-toggle="modal" data-bs-target="#detalhe<?= (int)$demand['id'] ?>"><i class="bi bi-eye"></i> Detalhes</button>
    <?php if ($demand['status'] !== 'concluida'): ?>
      <form method="post" action="<?= url('employee/demands/complete') ?>" class="d-grid gap-1">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="id" value="<?= (int)$demand['id'] ?>">
        <input class="form-control form-control-sm" name="completion_note" placeholder="Observação de conclusão (opcional)">
        <button class="btn btn-success btn-sm"><i class="bi bi-check2-circle"></i> Concluir</button>
      </form>
    <?php else: ?>
      <small class="text-muted">Concluída em <?= e(date('d/m/Y H:i', strtotime($demand['data_conclusao'] ?? 'now'))) ?></small><br>
      <small><?= e($demand['observacao_conclusao'] ?? '') ?></small>
    <?php endif; ?>
  </td>
</tr>
  <div class="modal fade" id="detalhe<?= (int)$demand['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable"><div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Detalhes da demanda</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <p><strong>Emenda:</strong> <?= e($demand['emenda']) ?></p>
        <p><strong>Político:</strong> <?= e($demand['nome_politico']) ?></p>
        <p><strong>Tipo:</strong> <?= e($demand['tipo_emenda']) ?></p>
        <p><strong>Observação:</strong><br><?= nl2br(e($demand['observacao'] ?? '-')) ?></p>
      </div>
    </div></div>
  </div>
<?php endforeach; ?>
</table>
</div>
