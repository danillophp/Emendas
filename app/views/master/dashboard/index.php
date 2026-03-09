<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Dashboard Master</h3>
  <div class="d-flex gap-2">
    <a href="<?= url('master/employees') ?>" class="btn btn-success btn-icon"><i class="bi bi-people"></i> Funcionários</a>
    <a href="<?= url('master/demands') ?>" class="btn btn-primary btn-icon"><i class="bi bi-list-task"></i> Demandas</a>
  </div>
</div>
<div class="row g-3 mb-4">
  <div class="col-md-6 col-xl"><div class="card card-ui kpi p-3"><div class="d-flex justify-content-between"><div><small>Total</small><h4><?= $stats['total'] ?></h4></div><div class="icon icon-preto"><i class="bi bi-collection"></i></div></div></div></div>
  <div class="col-md-6 col-xl"><div class="card card-ui kpi p-3"><div class="d-flex justify-content-between"><div><small>Pendentes</small><h4><?= $stats['pendente'] ?></h4></div><div class="icon icon-azul"><i class="bi bi-hourglass-split"></i></div></div></div></div>
  <div class="col-md-6 col-xl"><div class="card card-ui kpi p-3"><div class="d-flex justify-content-between"><div><small>Em andamento</small><h4><?= $stats['em_andamento'] ?></h4></div><div class="icon icon-azul"><i class="bi bi-activity"></i></div></div></div></div>
  <div class="col-md-6 col-xl"><div class="card card-ui kpi p-3"><div class="d-flex justify-content-between"><div><small>Concluídas</small><h4><?= $stats['concluida'] ?></h4></div><div class="icon icon-verde"><i class="bi bi-check-circle"></i></div></div></div></div>
  <div class="col-md-6 col-xl"><div class="card card-ui kpi p-3"><div class="d-flex justify-content-between"><div><small>Atrasadas</small><h4><?= $stats['atrasada'] ?></h4></div><div class="icon icon-preto"><i class="bi bi-exclamation-triangle"></i></div></div></div></div>
</div>
<div class="row g-3">
  <div class="col-lg-8"><div class="card card-ui"><div class="card-body"><h6>Calendário</h6><div id="calendar" data-events='<?= e(json_encode(array_map(fn($d)=>["title"=>$d["emenda"],"start"=>$d["prazo_entrega"],"color"=>$d["status"]==="concluida"?"#16a34a":"#1f6feb"], $demands))) ?>'></div></div></div></div>
  <div class="col-lg-4">
    <div class="card card-ui mb-3"><div class="card-body"><h6>Notificações recentes</h6><ul class="list-group list-group-flush"><?php foreach($notifications as $n): ?><li class="list-group-item px-0"><strong><?= e($n['titulo']) ?></strong><br><small><?= e($n['mensagem']) ?></small></li><?php endforeach; ?></ul></div></div>
    <div class="card card-ui"><div class="card-body"><h6>Próximas do prazo</h6><ul class="list-group list-group-flush"><?php foreach($upcoming as $u): ?><li class="list-group-item px-0 d-flex justify-content-between"><span><?= e($u['emenda']) ?></span><small><?= e(date('d/m H:i', strtotime($u['prazo_entrega']))) ?></small></li><?php endforeach; ?></ul></div></div>
  </div>
</div>
