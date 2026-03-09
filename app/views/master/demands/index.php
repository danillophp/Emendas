<div class="d-flex justify-content-between align-items-center mb-3"><h3 class="mb-0">Gestão de Demandas</h3><span class="text-muted">Filtros, prazos e atribuições</span></div>

<div class="card mb-3"><div class="card-body">
<form method="get" action="<?= url('master/demands') ?>" class="row g-2">
    <div class="col-md-3">
        <select class="form-select" name="status">
            <option value="">Todos os status</option>
            <?php foreach (['pendente'=>'Pendente','em_andamento'=>'Em andamento','concluida'=>'Concluída','atrasada'=>'Atrasada'] as $k=>$v): ?>
                <option value="<?= $k ?>" <?= ($filters['status'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3"><select class="form-select" name="funcionario_id"><option value="">Todos os funcionários</option><?php foreach ($employees as $emp): ?><option value="<?= (int)$emp['id'] ?>" <?= (string)($filters['funcionario_id'] ?? '') === (string)$emp['id'] ? 'selected' : '' ?>><?= e($emp['nome_completo']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><input type="date" class="form-control" name="prazo_de" value="<?= e($filters['prazo_de'] ?? '') ?>"></div>
    <div class="col-md-2"><input type="date" class="form-control" name="prazo_ate" value="<?= e($filters['prazo_ate'] ?? '') ?>"></div>
    <div class="col-md-2"><button class="btn btn-dark w-100">Filtrar</button></div>
</form>
</div></div>

<div class="card mb-4"><div class="card-body">
<h5>Cadastrar demanda</h5>
<form method="post" action="<?= url('master/demands/create') ?>" class="row g-2">
    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
    <div class="col-md-3"><input class="form-control" name="emenda" placeholder="Emenda" required></div>
    <div class="col-md-3"><input class="form-control" name="nome_politico" placeholder="Nome do político" required></div>
    <div class="col-md-2"><input type="date" class="form-control" name="data_emenda" required></div>
    <div class="col-md-2"><input class="form-control" name="tipo_emenda" placeholder="Tipo de emenda" required></div>
    <div class="col-md-2"><input type="datetime-local" class="form-control" name="prazo_entrega" required></div>
    <div class="col-md-4"><select class="form-select" name="funcionario_id" required><?php foreach($employees as $emp): ?><option value="<?= (int)$emp['id'] ?>"><?= e($emp['nome_completo']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-3"><select class="form-select" name="status"><option value="pendente">Pendente</option><option value="em_andamento">Em andamento</option><option value="concluida">Concluída</option><option value="atrasada">Atrasada</option></select></div>
    <div class="col-md-5"><textarea class="form-control" name="observacao" placeholder="Observação"></textarea></div>
    <div class="col-md-12"><button class="btn btn-primary"><i class="bi bi-plus-circle"></i> Cadastrar</button></div>
</form>
</div></div>

<div class="table-responsive">
<table class="table table-hover table-modern align-middle data-table">
    <tr><th>Emenda</th><th>Responsável</th><th>Prazo</th><th>Status</th><th>Ações</th></tr>
    <?php foreach ($demands as $d): ?>
    <tr>
        <td><?= e($d['emenda']) ?><br><small class="text-muted"><?= e($d['nome_politico']) ?></small></td>
        <td><?= e($d['funcionario_nome']) ?></td>
        <td><?= e(date('d/m/Y H:i', strtotime($d['prazo_entrega']))) ?></td>
        <td><span class="badge bg-light text-dark"><?= e($d['status']) ?></span></td>
        <td>
            <form method="post" action="<?= url('master/demands/update') ?>" class="d-flex gap-1 flex-wrap">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                <input class="form-control form-control-sm" name="emenda" value="<?= e($d['emenda']) ?>" required>
                <input class="form-control form-control-sm" name="nome_politico" value="<?= e($d['nome_politico']) ?>" required>
                <input class="form-control form-control-sm" type="date" name="data_emenda" value="<?= e($d['data_emenda']) ?>" required>
                <input class="form-control form-control-sm" name="tipo_emenda" value="<?= e($d['tipo_emenda']) ?>" required>
                <input class="form-control form-control-sm" type="datetime-local" name="prazo_entrega" value="<?= date('Y-m-d\TH:i', strtotime($d['prazo_entrega'])) ?>">
                <select class="form-select form-select-sm" name="funcionario_id"><?php foreach($employees as $emp): ?><option value="<?= (int)$emp['id'] ?>" <?= (int)$emp['id']===(int)$d['funcionario_id'] ? 'selected' : '' ?>><?= e($emp['nome_completo']) ?></option><?php endforeach; ?></select>
                <select class="form-select form-select-sm" name="status"><option value="pendente" <?= $d['status']==='pendente'?'selected':'' ?>>Pendente</option><option value="em_andamento" <?= $d['status']==='em_andamento'?'selected':'' ?>>Em andamento</option><option value="concluida" <?= $d['status']==='concluida'?'selected':'' ?>>Concluída</option><option value="atrasada" <?= $d['status']==='atrasada'?'selected':'' ?>>Atrasada</option></select>
                <input class="form-control form-control-sm" name="observacao" value="<?= e($d['observacao'] ?? '') ?>">
                <button class="btn btn-success btn-sm"><i class="bi bi-save"></i> Salvar</button>
            </form>
            <form method="post" action="<?= url('master/demands/delete') ?>" class="mt-1">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                <button class="btn btn-danger btn-sm"><i class="bi bi-trash"></i> Excluir</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</div>

<nav><ul class="pagination">
<?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
        <a class="page-link" href="<?= url('master/demands?status=' . urlencode($filters['status'] ?? '') . '&funcionario_id=' . urlencode($filters['funcionario_id'] ?? '') . '&prazo_de=' . urlencode($filters['prazo_de'] ?? '') . '&prazo_ate=' . urlencode($filters['prazo_ate'] ?? '') . '&page=' . $i) ?>"><?= $i ?></a>
    </li>
<?php endfor; ?>
</ul></nav>
