<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e(APP_NAME) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/main.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="<?= url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body>
<?php if (!empty($_SESSION['user'])): ?>
<div class="app-shell">
  <aside class="sidebar">
    <a class="brand" href="#"><i class="bi bi-building"></i> Emendas Gov</a>
    <?php $role = $_SESSION['user']['role'] ?? ''; ?>
    <nav class="nav flex-column gap-1">
      <?php if ($role === 'master'): ?>
        <a class="nav-link" href="<?= url('master/dashboard') ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a class="nav-link" href="<?= url('master/employees') ?>"><i class="bi bi-people"></i> Funcionários</a>
        <a class="nav-link" href="<?= url('master/demands') ?>"><i class="bi bi-list-task"></i> Demandas</a>
        <a class="nav-link" href="<?= url('master/notifications') ?>"><i class="bi bi-bell"></i> Notificações</a>
      <?php else: ?>
        <a class="nav-link" href="<?= url('funcionario/dashboard') ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a class="nav-link" href="<?= url('funcionario/demandas') ?>"><i class="bi bi-list-check"></i> Minhas Demandas</a>
        <a class="nav-link" href="<?= url('funcionario/notificacoes') ?>"><i class="bi bi-bell"></i> Notificações</a>
      <?php endif; ?>
    </nav>
  </aside>
  <div class="main-wrap">
    <header class="topbar d-flex justify-content-between align-items-center">
      <div>
        <strong><?= e($_SESSION['user']['name']) ?></strong>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a class="btn btn-outline-primary btn-sm" href="<?= url(($role === 'master') ? 'master/notifications' : 'funcionario/notificacoes') ?>">
          <i class="bi bi-bell"></i> <span class="badge bg-warning text-dark"><?= (int)$headerUnreadCount ?></span>
        </a>
        <form method="post" action="<?= url('logout') ?>" class="mb-0">
          <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
          <button class="btn btn-dark btn-sm"><i class="bi bi-box-arrow-right"></i> Sair</button>
        </form>
      </div>
    </header>
    <main class="container-fluid p-3 p-lg-4">
<?php else: ?>
<main class="container py-4">
<?php endif; ?>
<?php if ($error = flash('error')): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<?php if ($success = flash('success')): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
