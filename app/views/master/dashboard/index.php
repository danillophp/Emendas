<?php
$calendarEvents = array_map(static function (array $demand): array {
    $color = match ($demand['status']) {
        'concluído' => '#16a34a',
        'cadastrada' => '#2563eb',
        default => '#f59e0b',
    };

    return [
        'title' => $demand['emenda'] . ' • ' . $demand['funcionario_nome'],
        'start' => $demand['data_prazo_resposta'],
        'color' => $color,
    ];
}, $demands);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Painel Master</h3>
  <div class="d-flex gap-2">
    <a href="<?= url('master/employees') ?>" class="btn btn-success btn-icon"><i class="bi bi-people"></i> Funcionários</a>
    <a href="<?= url('master/demands') ?>" class="btn btn-primary btn-icon"><i class="bi bi-list-task"></i> Demandas</a>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-6 col-xl"><div class="card card-ui kpi p-3"><div class="d-flex justify-content-between"><div><small>Total de demandas</small><h4 id="masterStatTotal"><?= (int)$stats['total'] ?></h4></div><div class="icon icon-preto"><i class="bi bi-collection"></i></div></div></div></div>
  <div class="col-md-6 col-xl"><div class="card card-ui kpi p-3"><div class="d-flex justify-content-between"><div><small>Pendentes</small><h4 id="masterStatPendente"><?= (int)$stats['pendente'] ?></h4></div><div class="icon icon-azul"><i class="bi bi-hourglass-split"></i></div></div></div></div>
  <div class="col-md-6 col-xl"><div class="card card-ui kpi p-3"><div class="d-flex justify-content-between"><div><small>Cadastradas</small><h4 id="masterStatCadastrada"><?= (int)$stats['cadastrada'] ?></h4></div><div class="icon icon-azul"><i class="bi bi-clipboard-check"></i></div></div></div></div>
  <div class="col-md-6 col-xl"><div class="card card-ui kpi p-3"><div class="d-flex justify-content-between"><div><small>Concluídas</small><h4 id="masterStatConcluido"><?= (int)($stats['concluído'] ?? 0) ?></h4></div><div class="icon icon-verde"><i class="bi bi-check-circle"></i></div></div></div></div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card card-ui h-100">
      <div class="card-body">
        <h6 class="mb-3">Calendário de prazos das demandas</h6>
        <div id="calendar" data-events='<?= e(json_encode($calendarEvents)) ?>'></div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card card-ui mb-3">
      <div class="card-body">
        <h6 class="mb-3">Notificações recentes</h6>
        <ul class="list-group list-group-flush" id="masterDashboardNotifications">
          <?php foreach ($notifications as $notification): ?>
            <li class="list-group-item px-0">
              <strong><?= e($notification['titulo']) ?></strong>
              <?php if ((int)$notification['lida'] === 0): ?><span class="badge bg-warning text-dark ms-1">nova</span><?php endif; ?>
              <br>
              <small class="text-muted"><?= e($notification['mensagem']) ?></small>
            </li>
          <?php endforeach; ?>
          <?php if (empty($notifications)): ?>
            <li class="list-group-item px-0 text-muted">Sem notificações recentes.</li>
          <?php endif; ?>
        </ul>
      </div>
    </div>

    <div class="card card-ui">
      <div class="card-body">
        <h6 class="mb-3">Demandas próximas do vencimento</h6>
        <ul class="list-group list-group-flush" id="masterUpcomingList">
          <?php foreach ($upcoming as $item): ?>
            <li class="list-group-item px-0 d-flex justify-content-between align-items-start">
              <div>
                <strong><?= e($item['emenda']) ?></strong><br>
                <small class="text-muted"><?= e($item['funcionario_nome']) ?></small>
              </div>
              <small class="badge bg-dark"><?= e(date('d/m H:i', strtotime($item['data_prazo_resposta']))) ?></small>
            </li>
          <?php endforeach; ?>
          <?php if (empty($upcoming)): ?>
            <li class="list-group-item px-0 text-muted">Nenhuma demanda com prazo crítico.</li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
</div>
