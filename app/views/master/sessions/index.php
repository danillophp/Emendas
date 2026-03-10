<h3 class="mb-3">Auditoria de Sessões Ativas</h3>
<p class="text-muted">Visão administrativa para acompanhamento de acessos e encerramento manual de sessões.</p>

<div class="table-responsive">
  <table class="table table-striped table-modern align-middle data-table">
    <thead>
      <tr>
        <th>Usuário</th>
        <th>Perfil</th>
        <th>IP</th>
        <th>Navegador</th>
        <th>Login</th>
        <th>Última atividade</th>
        <th>Sessão</th>
        <th>Ação</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($sessions as $session): ?>
        <?php $isCurrent = (string)$session['session_id'] === (string)$currentSessionId; ?>
        <tr>
          <td>
            <strong><?= e($session['nome_completo']) ?></strong><br>
            <small class="text-muted"><?= e($session['usuario']) ?></small>
          </td>
          <td><?= e($session['perfil']) ?></td>
          <td><?= e($session['ip'] ?? '-') ?></td>
          <td><small><?= e($session['user_agent'] ?? '-') ?></small></td>
          <td><?= e(date('d/m/Y H:i:s', strtotime((string)$session['data_login']))) ?></td>
          <td><?= e(date('d/m/Y H:i:s', strtotime((string)$session['ultima_atividade']))) ?></td>
          <td>
            <code><?= e(substr((string)$session['session_id'], 0, 24)) ?>...</code>
            <?php if ($isCurrent): ?><span class="badge bg-primary ms-1">Atual</span><?php endif; ?>
          </td>
          <td>
            <?php if (!$isCurrent): ?>
              <form method="post" action="<?= url('master/sessions/terminate') ?>" onsubmit="return confirm('Confirma encerramento desta sessão?');">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="session_audit_id" value="<?= (int)$session['id'] ?>">
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-octagon"></i> Encerrar</button>
              </form>
            <?php else: ?>
              <span class="text-muted small">Sessão atual</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($sessions)): ?>
        <tr><td colspan="8" class="text-center text-muted">Nenhuma sessão ativa encontrada.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
