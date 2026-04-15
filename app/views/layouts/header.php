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
<?php
  $role = $_SESSION['user']['role'] ?? '';
  $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/';
  $basePath = rtrim(APP_BASE_PATH, '/');
  if ($basePath !== '' && str_starts_with($requestPath, $basePath)) {
      $requestPath = substr($requestPath, strlen($basePath));
  }
  $requestPath = '/' . ltrim($requestPath, '/');

  $navItems = $role === 'master'
    ? [
        ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'path' => '/master/dashboard'],
        ['label' => 'Funcionários', 'icon' => 'bi-people', 'path' => '/master/employees'],
        ['label' => 'Demandas', 'icon' => 'bi-list-task', 'path' => '/master/demands'],
        ['label' => 'Notificações', 'icon' => 'bi-bell', 'path' => '/master/notifications'],
        ['label' => 'Sessões', 'icon' => 'bi-shield-lock', 'path' => '/master/sessions'],
      ]
    : [
        ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'path' => '/funcionario/dashboard'],
        ['label' => 'Minhas Demandas', 'icon' => 'bi-list-check', 'path' => '/funcionario/demandas'],
        ['label' => 'Notificações', 'icon' => 'bi-bell', 'path' => '/funcionario/notificacoes'],
      ];
?>
<div class="app-shell" id="appShell" data-polling-url="<?= url('api/notifications/poll') ?>" data-sync-url="<?= url($role === 'master' ? 'api/master/sync' : 'api/funcionario/sync') ?>" data-notification-read-url="<?= url('api/notifications/read') ?>" data-notification-base-url="<?= url($role === 'master' ? 'master/notifications' : 'funcionario/notificacoes') ?>" data-notification-latest-id="<?= (int)($headerNotifications[0]['id'] ?? 0) ?>" data-employee-status-url="<?= url('funcionario/demandas/status') ?>" data-master-demands-url="<?= url('master/demands') ?>" data-employee-demands-url="<?= url('funcionario/demandas') ?>" data-attachment-download-url="<?= url('anexos/demandas/download') ?>" data-csrf-token="<?= csrf_token() ?>">
  <aside class="sidebar" id="sidebarNav" aria-label="Navegação principal">
    <a class="brand" href="<?= url($role === 'master' ? 'master/dashboard' : 'funcionario/dashboard') ?>">
      <span class="brand-mark"><i class="bi bi-building"></i></span>
      <span>
        <strong>Emendas Gov</strong>
        <small class="d-block text-white-50">Gestão Institucional</small>
      </span>
    </a>

    <div class="sidebar-section-title">Menu</div>
    <nav class="nav flex-column gap-1">
      <?php foreach ($navItems as $item): ?>
        <?php $active = str_starts_with($requestPath, $item['path']); ?>
        <a class="nav-link <?= $active ? 'active' : '' ?>" href="<?= url(ltrim($item['path'], '/')) ?>">
          <i class="bi <?= e($item['icon']) ?>"></i> <?= e($item['label']) ?>
        </a>
      <?php endforeach; ?>
    </nav>
  </aside>

  <div class="main-wrap">
    <header class="topbar d-flex justify-content-between align-items-center gap-3">
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-outline-secondary btn-sm d-lg-none" id="sidebarToggle" type="button" aria-label="Abrir menu">
          <i class="bi bi-list"></i>
        </button>
        <div class="topbar-title-wrap">
          <small class="topbar-kicker">Painel institucional</small>
          <strong class="topbar-user-name"><?= e($_SESSION['user']['name']) ?></strong>
        </div>
      </div>

      <div class="d-flex align-items-center gap-2">
        <span class="role-pill"><i class="bi bi-person-badge"></i> <?= e(strtoupper($role)) ?></span>

        <div class="dropdown">
          <button class="btn btn-outline-primary btn-sm position-relative" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Abrir notificações">
            <i class="bi bi-bell"></i>
            <?php if ((int)$headerUnreadCount > 0): ?>
              <span id="headerUnreadBadge" class="badge rounded-pill bg-warning text-dark position-absolute top-0 start-100 translate-middle"><?= (int)$headerUnreadCount ?></span>
            <?php endif; ?>
          </button>
          <div class="dropdown-menu dropdown-menu-end p-0 shadow notification-menu">
            <div class="p-3 border-bottom">
              <strong>Notificações</strong>
            </div>
            <div class="notification-list" id="headerNotificationList">
              <?php if (!empty($headerNotifications)): ?>
                <?php foreach ($headerNotifications as $headerNotification): ?>
                  <a class="dropdown-item py-2" href="<?= url($role === 'master' ? 'master/notifications' : 'funcionario/notificacoes') ?>">
                    <div class="small fw-semibold"><?= e($headerNotification['titulo']) ?></div>
                    <div class="small text-muted"><?= e($headerNotification['mensagem']) ?></div>
                  </a>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="px-3 py-3 text-muted small">Sem notificações recentes.</div>
              <?php endif; ?>
            </div>
            <a class="dropdown-item text-center py-2 border-top" href="<?= url($role === 'master' ? 'master/notifications' : 'funcionario/notificacoes') ?>">Ver todas</a>
          </div>
        </div>

        <form method="post" action="<?= url('logout') ?>" class="mb-0">
          <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
          <button class="btn btn-dark btn-sm"><i class="bi bi-box-arrow-right"></i> Sair</button>
        </form>
      </div>
    </header>

    <main class="container-fluid p-3 p-lg-4 main-content-shell">
<?php else: ?>
<main class="container py-4">
<?php endif; ?>
<?php if ($error = flash('error')): ?><div class="alert alert-danger shadow-sm border-0"><?= e($error) ?></div><?php endif; ?>
<?php if ($success = flash('success')): ?><div class="alert alert-success shadow-sm border-0"><?= e($success) ?></div><?php endif; ?>

<div id="toastArea" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:1080"></div>
