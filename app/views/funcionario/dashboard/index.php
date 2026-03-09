<div class="d-flex justify-content-between mb-3">
  <h3 class="mb-0">Dashboard do Funcionário</h3>
  <a href="<?= url('funcionario/demandas') ?>" class="btn btn-primary btn-icon"><i class="bi bi-list-check"></i> Minhas Demandas</a>
</div>
<div class="row g-3">
  <div class="col-md-6 col-xl-3"><div class="card card-ui p-3"><small>Total recebidas</small><h4><?= (int)$summary['total'] ?></h4></div></div>
  <div class="col-md-6 col-xl-3"><div class="card card-ui p-3"><small>Pendentes</small><h4><?= (int)$summary['pendente'] ?></h4></div></div>
  <div class="col-md-6 col-xl-3"><div class="card card-ui p-3"><small>Concluídas</small><h4><?= (int)$summary['concluida'] ?></h4></div></div>
  <div class="col-md-6 col-xl-3"><div class="card card-ui p-3"><small>Próximas do prazo</small><h4><?= (int)$summary['proximas_prazo'] ?></h4></div></div>
</div>
<?php if ((int)$summary['proximas_prazo'] > 0): ?>
<div class="alert alert-warning mt-3"><i class="bi bi-exclamation-triangle"></i> Você possui demandas com prazo em até 24h.</div>
<?php endif; ?>
