<h3 class="mb-3">Minhas Notificações</h3>

<div class="card card-ui">
  <div class="card-body">
    <ul class="list-group list-group-flush">
      <?php foreach ($notifications as $item): ?>
        <li class="list-group-item d-flex justify-content-between align-items-start">
          <div>
            <span><strong><?= e($item['titulo']) ?>:</strong> <?= e($item['mensagem']) ?></span>
            <?php if ((int)$item['lida'] === 0): ?><span class="badge bg-warning text-dark ms-1">não lida</span><?php endif; ?>
          </div>
          <div class="text-end">
            <small class="d-block text-muted"><?= e(date('d/m/Y H:i', strtotime($item['created_at']))) ?></small>
            <?php if ((int)$item['lida'] === 0): ?>
              <form method="post" action="<?= url('funcionario/notificacoes/lida') ?>">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-check2"></i> Marcar como lida</button>
              </form>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
      <?php if (empty($notifications)): ?>
        <li class="list-group-item text-muted">Você não possui notificações no momento.</li>
      <?php endif; ?>
    </ul>
  </div>
</div>
