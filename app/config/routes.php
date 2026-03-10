<?php

use App\Controllers\AttachmentController;
use App\Controllers\AuthController;
use App\Controllers\EmployeeController;
use App\Controllers\FuncionarioController;
use App\Controllers\MasterController;
use App\Controllers\NotificationController;

$router->get('/', [AuthController::class, 'showLogin']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/change-password', [AuthController::class, 'showChangePassword']);
$router->post('/change-password', [AuthController::class, 'changePassword']);
$router->post('/logout', [AuthController::class, 'logout']);

// Área Master
$router->get('/master/dashboard', [MasterController::class, 'dashboard']);
$router->get('/master/employees', [MasterController::class, 'employees']);
$router->post('/master/employees/create', [MasterController::class, 'createEmployee']);
$router->post('/master/employees/update', [MasterController::class, 'updateEmployee']);
$router->post('/master/employees/password', [MasterController::class, 'changeEmployeePassword']);
$router->post('/master/employees/toggle', [MasterController::class, 'toggleEmployee']);
$router->post('/master/employees/delete', [MasterController::class, 'deleteEmployee']);
$router->get('/master/demands', [MasterController::class, 'demands']);
$router->post('/master/demands/create', [MasterController::class, 'createDemand']);
$router->post('/master/demands/update', [MasterController::class, 'updateDemand']);
$router->post('/master/demands/delete', [MasterController::class, 'deleteDemand']);
$router->get('/master/notifications', [MasterController::class, 'notifications']);
$router->post('/master/notifications/read', [MasterController::class, 'readNotification']);

// Área Funcionário (nomenclatura oficial)
$router->get('/funcionario/dashboard', [FuncionarioController::class, 'dashboard']);
$router->get('/funcionario/demandas', [FuncionarioController::class, 'demands']);
$router->post('/funcionario/demandas/concluir', [FuncionarioController::class, 'completeDemand']);
$router->post('/funcionario/demandas/status', [FuncionarioController::class, 'updateStatus']);
$router->get('/funcionario/notificacoes', [FuncionarioController::class, 'notifications']);
$router->post('/funcionario/notificacoes/lida', [FuncionarioController::class, 'readNotification']);

// Compatibilidade retroativa com rotas antigas /employee
$router->get('/employee/dashboard', [EmployeeController::class, 'dashboard']);
$router->get('/employee/demands', [EmployeeController::class, 'demands']);
$router->post('/employee/demands/complete', [EmployeeController::class, 'completeDemand']);
$router->post('/employee/demands/status', [EmployeeController::class, 'updateStatus']);
$router->get('/employee/notifications', [EmployeeController::class, 'notifications']);
$router->post('/employee/notifications/read', [EmployeeController::class, 'readNotification']);


// API de notificações (AJAX/polling) protegida por sessão
$router->get('/api/notifications/poll', [NotificationController::class, 'poll']);
$router->post('/api/notifications/read', [NotificationController::class, 'markReadAjax']);

// Download seguro de anexos
$router->get('/anexos/demandas/download', [AttachmentController::class, 'downloadDemandAttachment']);
