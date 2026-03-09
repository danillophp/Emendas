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

<h3 class="mb-3">Minhas Demandas</h3>

<div class="table-responsive">
  <table class="table table-striped table-modern align-middle data-table">
    <thead>
      <tr>
        <th>Emenda</th>
        <th>Político</th>
        <th>Data da emenda</th>
        <th>Tipo</th>
        <th>Observação</th>
        <th>Prazo de entrega</th>
        <th>Tempo restante</th>
        <th>Status</th>
        <th>Ação</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($demands as $demand): ?>
        <?php
        $remainingSeconds = strtotime($demand['prazo_entrega']) - time();
        $remainingHours = (int)floor($remainingSeconds / 3600);
        $timeRemaining = $remainingSeconds > 0 ? $remainingHours . 'h' : 'Expirado';
        $timeClass = $remainingSeconds > 0 ? ($remainingSeconds <= 86400 ? 'bg-warning text-dark' : 'bg-info text-dark') : 'bg-danger';
        ?>

        <tr>
          <td><strong><?= e($demand['emenda']) ?></strong></td>
          <td><?= e($demand['nome_politico']) ?></td>
          <td><?= e(date('d/m/Y', strtotime($demand['data_emenda']))) ?></td>
          <td><?= e($demand['tipo_emenda']) ?></td>
          <td><?= e($demand['observacao'] ?: '-') ?></td>
          <td><?= e(date('d/m/Y H:i', strtotime($demand['prazo_entrega']))) ?></td>
          <td><span class="badge <?= $timeClass ?>"><?= e($timeRemaining) ?></span></td>
          <td><span class="badge badge-status <?= $statusClasses[$demand['status']] ?? 'bg-secondary' ?>"><?= e($statusLabels[$demand['status']] ?? $demand['status']) ?></span></td>
          <td>
            <button class="btn btn-sm btn-outline-secondary mb-1" data-bs-toggle="modal" data-bs-target="#detalhe<?= (int)$demand['id'] ?>">
              <i class="bi bi-eye"></i> Detalhes
            </button>

            <?php if ($demand['status'] !== 'concluida'): ?>
              <!-- Funcionário só pode concluir sua própria demanda; sem edição estrutural -->
              <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#concluir<?= (int)$demand['id'] ?>">
                <i class="bi bi-check2-circle"></i> Concluir
              </button>
            <?php else: ?>
              <small class="text-muted d-block">Concluída em <?= e(date('d/m/Y H:i', strtotime($demand['data_conclusao'] ?? 'now'))) ?></small>
              <small><?= e($demand['observacao_conclusao'] ?? '-') ?></small>
            <?php endif; ?>
          </td>
        </tr>

        <div class="modal fade" id="detalhe<?= (int)$demand['id'] ?>" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Detalhes da demanda</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <p><strong>Emenda:</strong> <?= e($demand['emenda']) ?></p>
                <p><strong>Político:</strong> <?= e($demand['nome_politico']) ?></p>
                <p><strong>Tipo:</strong> <?= e($demand['tipo_emenda']) ?></p>
                <p><strong>Data:</strong> <?= e(date('d/m/Y', strtotime($demand['data_emenda']))) ?></p>
                <p><strong>Prazo:</strong> <?= e(date('d/m/Y H:i', strtotime($demand['prazo_entrega']))) ?></p>
                <p><strong>Status:</strong> <?= e($statusLabels[$demand['status']] ?? $demand['status']) ?></p>
                <p><strong>Observação:</strong><br><?= nl2br(e($demand['observacao'] ?: '-')) ?></p>
              </div>
            </div>
          </div>
        </div>

        <div class="modal fade" id="concluir<?= (int)$demand['id'] ?>" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Concluir demanda</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <form method="post" action="<?= url('funcionario/demandas/concluir') ?>">
                <div class="modal-body">
                  <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                  <input type="hidden" name="id" value="<?= (int)$demand['id'] ?>">
                  <p class="mb-2">Confirma a conclusão da demanda <strong><?= e($demand['emenda']) ?></strong>?</p>
                  <label class="form-label">Observação de conclusão</label>
                  <textarea class="form-control" name="completion_note" rows="3" placeholder="Descreva o resultado da execução"></textarea>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                  <button class="btn btn-success"><i class="bi bi-check2-circle"></i> Confirmar conclusão</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
