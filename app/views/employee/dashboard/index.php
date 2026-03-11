<section class="page-hero mb-3">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h3 class="mb-1">Dashboard do Funcionário</h3>
      <p class="text-muted mb-0">Acompanhe suas demandas e mantenha o andamento sempre atualizado.</p>
    </div>
    <a href="<?= url('funcionario/demandas') ?>" class="btn btn-primary btn-icon"><i class="bi bi-list-check"></i> Minhas Demandas</a>
  </div>
</section>

<div class="row g-3">
  <div class="col-md-3">
    <div class="card card-ui kpi p-3"><div class="d-flex justify-content-between"><div><small>Total recebidas</small><h4 id="employeeSummaryTotal"><?= (int)$summary['total'] ?></h4></div><div class="icon icon-preto"><i class="bi bi-collection"></i></div></div></div>
  </div>
  <div class="col-md-3">
    <div class="card card-ui kpi p-3"><div class="d-flex justify-content-between"><div><small>Pendentes</small><h4 id="employeeSummaryPendente"><?= (int)$summary['pendente'] ?></h4></div><div class="icon icon-azul"><i class="bi bi-hourglass-split"></i></div></div></div>
  </div>
  <div class="col-md-3">
    <div class="card card-ui kpi p-3"><div class="d-flex justify-content-between"><div><small>Cadastradas</small><h4 id="employeeSummaryCadastrada"><?= (int)$summary['cadastrada'] ?></h4></div><div class="icon icon-azul"><i class="bi bi-clipboard-check"></i></div></div></div>
  </div>
  <div class="col-md-3">
    <div class="card card-ui kpi p-3"><div class="d-flex justify-content-between"><div><small>Concluídas</small><h4 id="employeeSummaryConcluido"><?= (int)($summary['concluído'] ?? 0) ?></h4></div><div class="icon icon-verde"><i class="bi bi-check-circle"></i></div></div></div>
  </div>
</div>
