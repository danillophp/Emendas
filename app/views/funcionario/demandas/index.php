<?php
$statusLabels = ['pendente' => 'Pendente', 'cadastrada' => 'Cadastrada', 'concluído' => 'Concluído'];
$statusClass = ['pendente' => 'badge-pendente', 'cadastrada' => 'badge-cadastrada', 'concluído' => 'badge-concluido'];
$processLabels = ['prestacao_de_conta' => 'Prestação de conta', 'cadastro_de_emenda' => 'Cadastro de emenda', 'outros' => 'Outros'];
$amendmentLabels = ['federal' => 'Federal', 'estadual' => 'Estadual', 'municipal' => 'Municipal', 'outros' => 'Outros'];
?>

<section class="page-hero mb-3">
  <div>
    <h3 class="mb-1">Minhas Demandas</h3>
    <p class="text-muted mb-0">Abra cada demanda para registrar observações e atualizar o status com segurança.</p>
  </div>
</section>
<div class="table-responsive">
  <table class="table table-striped table-modern align-middle data-table table-premium" id="employeeDemandsTable">
    <thead><tr><th>Emenda</th><th>Tipo</th><th>Prazo</th><th>Status</th><th>Anexo</th><th>Ação</th></tr></thead>
    <tbody id="employeeDemandsBody">
      <?php foreach ($demands as $demand): ?>
      <tr>
        <td><strong><?= e($demand['emenda']) ?></strong><br><small class="text-muted"><?= e(trim((string)($demand['nome_politico'] ?? '')) !== '' ? $demand['nome_politico'] : 'Não informado') ?></small></td>
        <td><?= e((($demand['tipo_processo'] ?? '') === 'outros' && trim((string)($demand['tipo_processo_outros'] ?? '')) !== '') ? $demand['tipo_processo_outros'] : ($processLabels[$demand['tipo_processo']] ?? $demand['tipo_processo'])) ?> / <?= e((($demand['tipo_emenda'] ?? '') === 'outros' && trim((string)($demand['tipo_emenda_outros'] ?? '')) !== '') ? $demand['tipo_emenda_outros'] : ($amendmentLabels[$demand['tipo_emenda']] ?? (($demand['tipo_emenda'] ?? '') === 'parlamentar' ? 'Federal' : $demand['tipo_emenda']))) ?></td>
        <td><?= e(date('d/m/Y H:i', strtotime($demand['data_prazo_resposta']))) ?></td>
        <td><span class="badge badge-status <?= e($statusClass[$demand['status']] ?? 'bg-secondary') ?>"><?= e($statusLabels[$demand['status']] ?? $demand['status']) ?></span></td>
        <td><?php if (!empty($demand['anexo_emenda'])): ?><a target="_blank" href="<?= url('anexos/demandas/download?demand_id=' . (int)$demand['id']) ?>">Abrir</a><?php else: ?>-<?php endif; ?></td>
        <td>
          <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#demanda<?= (int)$demand['id'] ?>">Abrir demanda</button>
        </td>
      </tr>

      <div class="modal fade" id="demanda<?= (int)$demand['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><?= e($demand['emenda']) ?></h5>
              <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="<?= url('funcionario/demandas/atualizar') ?>">
              <div class="modal-body">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="id" value="<?= (int)$demand['id'] ?>">

                <div class="row g-3 mb-3">
                  <div class="col-md-6"><small class="text-muted d-block">Nome do político</small><strong><?= e(trim((string)($demand['nome_politico'] ?? '')) !== '' ? $demand['nome_politico'] : 'Não informado') ?></strong></div>
                  <div class="col-md-6"><small class="text-muted d-block">Número do processo SEI</small><strong><?= e(trim((string)($demand['numero_processo_sei'] ?? '')) !== '' ? $demand['numero_processo_sei'] : 'Não informado') ?></strong></div>
                  <div class="col-md-6"><small class="text-muted d-block">Prazo da demanda</small><strong><?= e(date('d/m/Y H:i', strtotime($demand['data_prazo_resposta']))) ?></strong></div>
                  <div class="col-md-6"><small class="text-muted d-block">Tipo de demanda</small><strong><?= e((($demand['tipo_processo'] ?? '') === 'outros' && trim((string)($demand['tipo_processo_outros'] ?? '')) !== '') ? $demand['tipo_processo_outros'] : ($processLabels[$demand['tipo_processo']] ?? $demand['tipo_processo'])) ?></strong></div>
                  <div class="col-md-6"><small class="text-muted d-block">Tipo de emenda</small><strong><?= e((($demand['tipo_emenda'] ?? '') === 'outros' && trim((string)($demand['tipo_emenda_outros'] ?? '')) !== '') ? $demand['tipo_emenda_outros'] : ($amendmentLabels[$demand['tipo_emenda']] ?? (($demand['tipo_emenda'] ?? '') === 'parlamentar' ? 'Federal' : $demand['tipo_emenda']))) ?></strong></div>
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">Observações existentes</label>
                  <div class="border rounded p-2 bg-light small"><?= nl2br(e(trim((string)($demand['observacao'] ?? 'Sem observações do Master.')))) ?></div>
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">Histórico de observações do funcionário</label>
                  <div class="border rounded p-2 bg-light small"><?= nl2br(e(trim((string)($demand['observacao_funcionario'] ?? 'Sem atualizações registradas.')))) ?></div>
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold" for="observacao_funcionario_<?= (int)$demand['id'] ?>">Nova observação</label>
                  <textarea class="form-control" id="observacao_funcionario_<?= (int)$demand['id'] ?>" name="observacao_funcionario" rows="3" maxlength="2500" placeholder="Descreva o andamento da demanda"></textarea>
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold" for="status_<?= (int)$demand['id'] ?>">Status da demanda</label>
                  <select class="form-select" id="status_<?= (int)$demand['id'] ?>" name="status" required>
                    <?php foreach ($statusLabels as $key => $label): ?>
                      <option value="<?= $key ?>" <?= $demand['status'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                <button class="btn btn-primary">Salvar atualização</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
