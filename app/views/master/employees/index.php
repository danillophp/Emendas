<div class="d-flex justify-content-between align-items-center mb-3"><h3 class="mb-0">Gestão de Funcionários</h3><span class="text-muted">Cadastro, edição e controle de acesso</span></div>

<div class="card mb-3"><div class="card-body">
    <form method="get" action="<?= url('master/employees') ?>" class="row g-2">
        <div class="col-md-10"><input class="form-control" name="search" value="<?= e($search) ?>" placeholder="Buscar por nome, e-mail ou decreto"></div>
        <div class="col-md-2"><button class="btn btn-dark w-100">Buscar</button></div>
    </form>
</div></div>

<div class="card mb-4"><div class="card-body">
    <h5>Novo funcionário</h5>
    <form method="post" action="<?= url('master/employees/create') ?>" class="row g-2">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
        <div class="col-md-4"><input class="form-control" name="nome_completo" placeholder="Nome completo" required></div>
        <div class="col-md-4"><input class="form-control" type="email" name="email" placeholder="E-mail" required></div>
        <div class="col-md-4"><input class="form-control" name="endereco" placeholder="Endereço" required></div>
        <div class="col-md-3"><input class="form-control" name="whatsapp" placeholder="WhatsApp" required></div>
        <div class="col-md-3"><input class="form-control" name="numero_decreto" placeholder="Número decreto" required></div>
        <div class="col-md-3"><input class="form-control" type="date" name="data_nascimento" required></div>
        <div class="col-md-3"><button class="btn btn-primary w-100"><i class="bi bi-person-plus"></i> Cadastrar</button></div>
    </form>
</div></div>

<div class="table-responsive">
<table class="table table-striped table-modern align-middle data-table">
    <tr><th>Nome</th><th>Email</th><th>Decreto</th><th>Status</th><th>Ações</th></tr>
    <?php foreach ($employees as $employee): ?>
    <tr>
        <td><?= e($employee['nome_completo']) ?></td>
        <td><?= e($employee['email']) ?></td>
        <td><?= e($employee['numero_decreto']) ?></td>
        <td><span class="badge <?= (int)$employee['ativo'] === 1 ? 'bg-success' : 'bg-secondary' ?>"><?= (int)$employee['ativo'] === 1 ? 'Ativo' : 'Inativo' ?></span></td>
        <td>
            <form method="post" action="<?= url('master/employees/update') ?>" class="row g-1 mb-1">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden" name="id" value="<?= (int)$employee['id'] ?>">
                <div class="col"><input class="form-control form-control-sm" name="nome_completo" value="<?= e($employee['nome_completo']) ?>" required></div>
                <div class="col"><input class="form-control form-control-sm" name="email" value="<?= e($employee['email']) ?>" required></div>
                <div class="col"><input class="form-control form-control-sm" name="endereco" value="<?= e($employee['endereco']) ?>" required></div>
                <div class="col"><input class="form-control form-control-sm" name="whatsapp" value="<?= e($employee['whatsapp']) ?>" required></div>
                <div class="col"><input class="form-control form-control-sm" name="numero_decreto" value="<?= e($employee['numero_decreto']) ?>" required></div>
                <div class="col"><input class="form-control form-control-sm" type="date" name="data_nascimento" value="<?= e($employee['data_nascimento']) ?>" required></div>
                <div class="col"><input class="form-control form-control-sm" name="new_password" placeholder="Nova senha"></div>
                <div class="col-auto"><button class="btn btn-success btn-sm"><i class="bi bi-save"></i> Salvar</button></div>
            </form>

            <form method="post" action="<?= url('master/employees/toggle') ?>" class="d-inline">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden" name="id" value="<?= (int)$employee['id'] ?>">
                <input type="hidden" name="active" value="<?= (int)$employee['ativo'] === 1 ? 0 : 1 ?>">
                <button class="btn btn-sm <?= (int)$employee['ativo'] === 1 ? 'btn-warning' : 'btn-info' ?>"><?= (int)$employee['ativo'] === 1 ? '<i class="bi bi-person-dash"></i> Desativar' : '<i class="bi bi-person-check"></i> Ativar' ?></button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
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
