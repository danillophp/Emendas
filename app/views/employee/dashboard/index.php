<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Dashboard do Funcionário</h3>
  <a href="<?= url('funcionario/demandas') ?>" class="btn btn-primary btn-icon"><i class="bi bi-list-check"></i> Minhas Demandas</a>
</div>

<div class="row g-3">
  <div class="col-md-4">
    <div class="card card-ui kpi p-3"><div class="d-flex justify-content-between"><div><small>Total recebidas</small><h4 id="employeeSummaryTotal"><?= (int)$summary['total'] ?></h4></div><div class="icon icon-preto"><i class="bi bi-collection"></i></div></div></div>
  </div>
  <div class="col-md-4">
    <div class="card card-ui kpi p-3"><div class="d-flex justify-content-between"><div><small>Pendentes</small><h4 id="employeeSummaryPendente"><?= (int)$summary['pendente'] ?></h4></div><div class="icon icon-azul"><i class="bi bi-hourglass-split"></i></div></div></div>
  </div>
  <div class="col-md-4">
    <div class="card card-ui kpi p-3"><div class="d-flex justify-content-between"><div><small>Cadastradas</small><h4 id="employeeSummaryCadastrado"><?= (int)$summary['cadastrado'] ?></h4></div><div class="icon icon-verde"><i class="bi bi-check-circle"></i></div></div></div>
  </div>
</div>
