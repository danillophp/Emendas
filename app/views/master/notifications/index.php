<h3 class="mb-3">Notificações do Master</h3>
<div class="card"><div class="card-body">
<ul class="list-group list-group-flush">
<?php foreach ($notifications as $item): ?>
<li class="list-group-item d-flex justify-content-between align-items-start">
    <div>
        <strong><?= e($item['titulo']) ?></strong>
        <?php if ((int)$item['lida'] === 0): ?><span class="badge bg-warning text-dark ms-1">não lida</span><?php endif; ?>
        <br>
        <span><?= e($item['mensagem']) ?></span>
    </div>
    <div class="text-end">
        <small class="text-muted d-block mb-1"><?= e(date('d/m/Y H:i', strtotime($item['created_at']))) ?></small>
        <?php if ((int)$item['lida'] === 0): ?>
        <form method="post" action="<?= url('master/notifications/read') ?>">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
            <button class="btn btn-sm btn-outline-primary">Marcar como lida</button>
        </form>
        <?php endif; ?>
    </div>
</li>
<?php endforeach; ?>
</ul>
</div></div>
