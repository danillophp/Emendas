<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Gestão de Funcionários</h3>
  <span class="text-muted">Cadastro, atualização, senha e status de acesso</span>
</div>

<div class="card card-ui mb-3">
  <div class="card-body">
    <form method="get" action="<?= url('master/employees') ?>" class="row g-2">
      <div class="col-md-10">
        <input class="form-control" name="search" value="<?= e($search) ?>" placeholder="Pesquisar por nome, e-mail ou decreto">
      </div>
      <div class="col-md-2 d-grid">
        <button class="btn btn-dark">Buscar</button>
      </div>
    </form>
  </div>
</div>

<div class="card card-ui mb-4">
  <div class="card-body">
    <h5 class="mb-3">Cadastrar funcionário</h5>
    <form method="post" action="<?= url('master/employees/create') ?>" class="row g-2">
      <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
      <div class="col-md-6"><input class="form-control" name="nome_completo" placeholder="Nome completo" required></div>
      <div class="col-md-6"><input class="form-control" type="email" name="email" placeholder="E-mail" required></div>
      <div class="col-md-6"><input class="form-control" name="endereco" placeholder="Endereço" required></div>
      <div class="col-md-3"><input class="form-control" name="whatsapp" placeholder="WhatsApp" required></div>
      <div class="col-md-3"><input class="form-control" name="numero_decreto" placeholder="Número do decreto" required></div>
      <div class="col-md-3"><input class="form-control" type="date" name="data_nascimento" required></div>
      <div class="col-md-3 d-grid"><button class="btn btn-primary"><i class="bi bi-person-plus"></i> Cadastrar</button></div>
    </form>
    <small class="text-muted d-block mt-2">Usuário inicial = número do decreto | Senha inicial = data de nascimento (DDMMAAAA) | Primeiro login exige troca de senha.</small>
  </div>
</div>

<div class="table-responsive">
  <table class="table table-striped table-modern align-middle data-table">
    <thead>
      <tr>
        <th>Funcionário</th>
        <th>Contato</th>
        <th>Decreto</th>
        <th>Status</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($employees as $employee): ?>
        <tr>
          <td>
            <strong><?= e($employee['nome_completo']) ?></strong><br>
            <small class="text-muted">Nascimento: <?= e(date('d/m/Y', strtotime($employee['data_nascimento']))) ?></small>
          </td>
          <td>
            <?= e($employee['email']) ?><br>
            <small class="text-muted"><?= e($employee['whatsapp']) ?></small>
          </td>
          <td><?= e($employee['numero_decreto']) ?></td>
          <td>
            <span class="badge <?= (int)$employee['ativo'] === 1 ? 'bg-success' : 'bg-secondary' ?>">
              <?= (int)$employee['ativo'] === 1 ? 'Ativo' : 'Inativo' ?>
            </span>
          </td>
          <td>
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEdit<?= (int)$employee['id'] ?>">
              <i class="bi bi-pencil-square"></i> Editar
            </button>
            <button class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#modalPass<?= (int)$employee['id'] ?>">
              <i class="bi bi-shield-lock"></i> Senha
            </button>

            <form method="post" action="<?= url('master/employees/toggle') ?>" class="d-inline">
              <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="id" value="<?= (int)$employee['id'] ?>">
              <input type="hidden" name="active" value="<?= (int)$employee['ativo'] === 1 ? 0 : 1 ?>">
              <button class="btn btn-sm <?= (int)$employee['ativo'] === 1 ? 'btn-warning' : 'btn-success' ?>">
                <?= (int)$employee['ativo'] === 1 ? 'Desativar' : 'Ativar' ?>
              </button>
            </form>
          </td>
        </tr>

        <div class="modal fade" id="modalEdit<?= (int)$employee['id'] ?>" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Editar funcionário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <form method="post" action="<?= url('master/employees/update') ?>">
                <div class="modal-body row g-2">
                  <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                  <input type="hidden" name="id" value="<?= (int)$employee['id'] ?>">
                  <div class="col-md-6"><input class="form-control" name="nome_completo" value="<?= e($employee['nome_completo']) ?>" required></div>
                  <div class="col-md-6"><input class="form-control" type="email" name="email" value="<?= e($employee['email']) ?>" required></div>
                  <div class="col-md-6"><input class="form-control" name="endereco" value="<?= e($employee['endereco']) ?>" required></div>
                  <div class="col-md-3"><input class="form-control" name="whatsapp" value="<?= e($employee['whatsapp']) ?>" required></div>
                  <div class="col-md-3"><input class="form-control" name="numero_decreto" value="<?= e($employee['numero_decreto']) ?>" required></div>
                  <div class="col-md-3"><input class="form-control" type="date" name="data_nascimento" value="<?= e($employee['data_nascimento']) ?>" required></div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                  <button class="btn btn-primary"><i class="bi bi-save"></i> Salvar</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="modal fade" id="modalPass<?= (int)$employee['id'] ?>" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Alterar senha de funcionário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <form method="post" action="<?= url('master/employees/password') ?>">
                <div class="modal-body">
                  <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                  <input type="hidden" name="id" value="<?= (int)$employee['id'] ?>">
                  <label class="form-label">Nova senha</label>
                  <input class="form-control" name="new_password" type="password" minlength="8" required>
                  <small class="text-muted">Ao alterar, o funcionário será forçado a trocar a senha no próximo login.</small>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                  <button class="btn btn-dark"><i class="bi bi-key"></i> Atualizar senha</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<nav>
  <ul class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <li class="page-item <?= $i === $page ? 'active' : '' ?>">
        <a class="page-link" href="<?= url('master/employees?search=' . urlencode($search) . '&page=' . $i) ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
