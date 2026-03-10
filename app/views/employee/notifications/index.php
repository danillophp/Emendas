<h3>Notificações</h3>
<ul class="list-group card-ui p-2">
<?php foreach ($notifications as $item): ?>
<li class="list-group-item d-flex justify-content-between align-items-start">
    <div>
        <span><strong><?= e($item['titulo']) ?>:</strong> <?= e($item['mensagem']) ?></span>
        <?php if ((int)$item['lida'] === 0): ?><span class="badge bg-warning text-dark ms-1">não lida</span><?php endif; ?>
    </div>
    <div class="text-end">
        <small class="d-block"><?= e($item['created_at']) ?></small>
        <?php if ((int)$item['lida'] === 0): ?>
        <form method="post" action="<?= url('employee/notifications/read') ?>">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-check2"></i> Lida</button>
        </form>
        <?php endif; ?>
    </div>
</li>
<?php endforeach; ?>
</ul>
