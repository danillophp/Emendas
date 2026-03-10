<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\SystemLog;
use App\Models\User;

class AuthController extends Controller
{
    private User $users;
    private SystemLog $logs;

    public function __construct()
    {
        $this->users = new User();
        $this->logs = new SystemLog();
    }

    public function showLogin(): void
    {
        if (!empty($_SESSION['user'])) {
            redirect($_SESSION['user']['role'] === 'master' ? 'master/dashboard' : 'funcionario/dashboard');
        }

        $this->view('auth/login');
    }

    public function login(): void
    {
        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            flash('error', 'Sua sessão expirou. Atualize a página e tente novamente.');
            redirect('login');
        }

        $username = trim((string)($_POST['usuario'] ?? ''));
        $password = (string)($_POST['senha'] ?? '');

        if ($username === '' || $password === '') {
            flash('error', 'Informe usuário e senha para entrar no sistema.');
            redirect('login');
        }

        $user = $this->users->findByUsername($username);

        if (!$user || !password_verify($password, $user['senha_hash'])) {
            flash('error', 'Usuário ou senha inválidos.');
            redirect('login');
        }

        if (!(bool)$user['ativo']) {
            flash('error', 'Seu usuário está inativo. Contate o administrador Master.');
            redirect('login');
        }

        // Invalida sessão anterior e cria novo identificador para sessão ativa.
        $sessionToken = bin2hex(random_bytes(32));
        $this->users->updateSessionToken((int)$user['id'], $sessionToken);

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'name' => $user['nome_completo'],
            'role' => $user['perfil'],
            'first_login' => (bool)$user['primeiro_login'],
            'session_token' => $sessionToken,
        ];

        $this->logs->create([
            'usuario_id' => (int)$user['id'],
            'acao' => 'login',
            'entidade' => 'sessao',
            'entidade_id' => (int)$user['id'],
            'descricao' => 'Login realizado com sucesso',
            'ip' => client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);

        if ((bool)$user['primeiro_login'] && $user['perfil'] === 'funcionario') {
            flash('success', 'Primeiro acesso detectado. Defina sua nova senha para continuar.');
            redirect('change-password');
        }

        redirect($user['perfil'] === 'master' ? 'master/dashboard' : 'funcionario/dashboard');
    }

    public function showChangePassword(): void
    {
        $this->requireAuth();

        $role = $_SESSION['user']['role'] ?? '';
        if ($role !== 'funcionario' && $role !== 'master') {
            flash('error', 'Perfil inválido para alteração de senha.');
            redirect('login');
        }

        $this->view('auth/change-password');
    }

    public function changePassword(): void
    {
        $this->requireAuth();

        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            flash('error', 'Sua sessão expirou. Atualize a página e tente novamente.');
            redirect('change-password');
        }

        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['password_confirm'] ?? '');

        if (strlen($password) < 8) {
            flash('error', 'A nova senha deve ter no mínimo 8 caracteres.');
            redirect('change-password');
        }

        if ($password !== $confirm) {
            flash('error', 'A confirmação de senha não confere.');
            redirect('change-password');
        }

        $this->users->updatePassword((int)$_SESSION['user']['id'], password_hash($password, PASSWORD_DEFAULT), false);
        $_SESSION['user']['first_login'] = false;

        flash('success', 'Senha alterada com sucesso.');
        redirect($_SESSION['user']['role'] === 'master' ? 'master/dashboard' : 'funcionario/dashboard');
    }

    public function logout(): void
    {
        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            flash('error', 'Requisição inválida de logout.');
            redirect('login');
        }

        if (!empty($_SESSION['user']['id'])) {
            $userId = (int)$_SESSION['user']['id'];
            $this->users->updateSessionToken($userId, null);

            // Auditoria de encerramento de sessão.
            $this->logs->create([
                'usuario_id' => $userId,
                'acao' => 'logout',
                'entidade' => 'sessao',
                'entidade_id' => $userId,
                'descricao' => 'Logout realizado',
                'ip' => client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);
        }

        $_SESSION = [];
        session_destroy();
        session_start();

        flash('success', 'Você saiu com segurança.');
        redirect('login');
    }
}
