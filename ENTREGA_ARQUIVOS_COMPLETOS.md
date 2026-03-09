# Entrega em Etapas com Arquivos Completos
Este documento entrega cada etapa com **nome do arquivo**, **caminho**, **conteúdo completo** e **função resumida**.

## Etapa 1 — Estrutura de pastas

### Arquivo: `ESTRUTURA_MVC_HOSTGATOR.md`
- **Caminho:** `ESTRUTURA_MVC_HOSTGATOR.md`
- **Função:** Documenta a arquitetura MVC recomendada e organização profissional do projeto.
- **Conteúdo completo:**
```md
# Estrutura Profissional de Pastas — Sistema PHP MVC de Gestão de Emendas

## 1) Árvore de diretórios recomendada (deploy em `/emendas`)

``\`text
/emendas
├── app/
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── MasterController.php
│   │   ├── FuncionarioController.php
│   │   ├── DemandasController.php
│   │   └── NotificacoesController.php
│   ├── models/
│   │   ├── User.php
│   │   ├── Demanda.php
│   │   ├── Notificacao.php
│   │   ├── LogSistema.php
│   │   └── RedefinicaoSenha.php
│   ├── views/
│   │   ├── auth/
│   │   │   ├── login.php
│   │   │   └── alterar-senha.php
│   │   ├── master/
│   │   │   ├── dashboard.php
│   │   │   ├── funcionarios-index.php
│   │   │   ├── demandas-index.php
│   │   │   └── notificacoes-index.php
│   │   ├── funcionario/
│   │   │   ├── dashboard.php
│   │   │   ├── demandas-index.php
│   │   │   └── notificacoes-index.php
│   │   ├── layouts/
│   │   │   ├── header.php
│   │   │   ├── footer.php
│   │   │   └── app.php
│   │   └── components/
│   │       ├── alerts.php
│   │       ├── navbar.php
│   │       ├── sidebar.php
│   │       └── pagination.php
│   ├── middleware/
│   │   ├── AuthMiddleware.php
│   │   ├── CsrfMiddleware.php
│   │   └── RoleMiddleware.php
│   ├── helpers/
│   │   ├── auth.php
│   │   ├── csrf.php
│   │   ├── response.php
│   │   └── validation.php
│   └── core/
│       ├── App.php
│       ├── Router.php
│       ├── Controller.php
│       ├── Database.php
│       ├── Model.php
│       └── View.php
├── config/
│   ├── app.php
│   ├── database.php
│   ├── session.php
│   ├── mail.php
│   └── paths.php
├── database/
│   ├── schema.sql
│   ├── seeds.sql
│   └── migrations/
│       ├── 001_create_usuarios.sql
│       ├── 002_create_demandas.sql
│       └── 003_create_notificacoes_logs.sql
├── routes/
│   ├── web.php
│   ├── auth.php
│   ├── master.php
│   └── funcionario.php
├── public/
│   ├── index.php
│   ├── .htaccess
│   └── assets/
│       ├── css/
│       │   ├── app.css
│       │   ├── auth.css
│       │   └── dashboard.css
│       ├── js/
│       │   ├── app.js
│       │   ├── auth.js
│       │   └── demandas.js
│       └── img/
│           ├── logo.png
│           └── icons/
├── storage/
│   ├── cache/
│   ├── sessions/
│   └── uploads/
├── logs/
│   ├── app.log
│   ├── error.log
│   └── audit.log
├── vendor/
├── .env
├── .env.example
├── composer.json
└── README.md
``\`

---

## 2) Finalidade de cada diretório

### `app/`
Camada principal da aplicação (MVC + regras de domínio).

- **`app/controllers/`**: recebe requisições HTTP, coordena fluxo, chama models/services e retorna views/respostas.
- **`app/models/`**: acesso a dados, regras de persistência e regras de negócio ligadas às entidades.
- **`app/views/`**: interface renderizada (templates PHP).
  - **`auth/`**: telas de autenticação e troca de senha.
  - **`master/`**: telas do perfil administrador (gestão completa).
  - **`funcionario/`**: telas do perfil operacional (execução/atualização de demandas).
  - **`layouts/`**: estrutura-base reutilizável (header/footer/layout mestre).
  - **`components/`**: blocos visuais reutilizáveis (alertas, navegação, paginação).
- **`app/middleware/`**: filtros de segurança (auth, CSRF, autorização por perfil).
- **`app/helpers/`**: funções utilitárias transversais (validação, resposta, formatação).
- **`app/core/`**: infraestrutura interna do framework MVC (router, base controller/model, renderização, bootstrap).

### `config/`
Configurações centralizadas por contexto (app, banco, sessão, paths). Facilita troca de ambiente local/produção.

### `database/`
Artefatos SQL versionáveis: schema completo, seeds e migrações incrementais.

### `routes/`
Definição de rotas separada por domínio/contexto (auth, master, funcionário), melhorando organização e manutenção.

### `public/`
Única pasta pública da aplicação. Em HostGator, o ideal é apontar o domínio/subdomínio para `public/`.

- **`public/index.php`**: front controller.
- **`public/.htaccess`**: reescrita de URL e segurança básica.
- **`public/assets/`**: arquivos estáticos (css/js/img).

### `storage/`
Dados de runtime (cache, sessão, uploads). Evita poluir diretórios de código.

### `logs/`
Logs de aplicação, erros e auditoria operacional para suporte, troubleshooting e compliance.

---

## 3) Arquivos principais sugeridos por responsabilidade

### Entrada e bootstrap
- `public/index.php`
- `app/core/App.php`
- `app/core/Router.php`

### Configuração
- `config/app.php`
- `config/database.php`
- `config/session.php`
- `.env`

### Segurança
- `app/middleware/AuthMiddleware.php`
- `app/middleware/CsrfMiddleware.php`
- `app/middleware/RoleMiddleware.php`
- `app/helpers/csrf.php`

### Domínio de negócio
- `app/models/User.php`
- `app/models/Demanda.php`
- `app/models/Notificacao.php`
- `app/models/LogSistema.php`

### Camada web
- `app/controllers/AuthController.php`
- `app/controllers/MasterController.php`
- `app/controllers/FuncionarioController.php`
- `routes/web.php`, `routes/master.php`, `routes/funcionario.php`

### Banco de dados
- `database/schema.sql`
- `database/seeds.sql`
- `database/migrations/*.sql`

---

## 4) Sequência ideal de implementação (ordem recomendada)

1. **Fundação do projeto**
   - Estrutura de pastas, `composer.json`, autoload PSR-4 e `.env.example`.

2. **Core MVC**
   - `App`, `Router`, `Controller`, `View`, `Database`.

3. **Configuração de ambiente**
   - Arquivos em `config/` + leitura de variáveis de ambiente.

4. **Banco de dados**
   - `schema.sql` completo + seeds iniciais (master) + migrações versionadas.

5. **Autenticação e segurança**
   - Login/logout, hash de senha, controle de sessão, CSRF e middleware por perfil.

6. **Módulo Master**
   - CRUD de usuários/funcionários, gestão de demandas e painel administrativo.

7. **Módulo Funcionário**
   - Minhas demandas, atualização de status, conclusão e notificações.

8. **Auditoria e observabilidade**
   - Logs de sistema, trilha de auditoria, tratamento de erros e rotação de logs.

9. **Front-end e componentes reutilizáveis**
   - Layout base, componentes, assets organizados por contexto.

10. **Hardening para HostGator**
    - Ajustes de `public/.htaccess`, permissões em `storage/` e `logs/`, base path `/emendas` e validação final de deploy.

---

## 5) Adaptação específica para HostGator (hospedagem compartilhada)

- Priorizar **`public/` como raiz web** (quando o painel permitir apontamento do domínio/subdomínio).
- Caso o domínio aponte para `public_html`, publicar aplicação em `public_html/emendas` e garantir rewrite para `public/index.php`.
- Evitar dependências que exijam privilégios de root/daemon.
- Persistir sessão em arquivo (`storage/sessions`) quando necessário, com permissões seguras.
- Manter logs em `logs/` fora de caminhos públicos.
- Centralizar credenciais no `.env` e nunca versionar dados sensíveis reais.

> Resultado: arquitetura limpa, escalável e pronta para crescimento de módulos (ex.: API, relatórios, integrações com transparência pública, fila de notificações e BI).
```

## Etapa 2 — Script SQL completo

### Arquivo: `schema.sql`
- **Caminho:** `database/schema.sql`
- **Função:** Cria banco/tabelas/índices/FKs/triggers e usuário master inicial para produção.
- **Conteúdo completo:**
```sql
-- =========================================================
-- Sistema de Gestão de Emendas Governamentais
-- Banco: santo821_emenda
-- Compatível com MySQL 5.7+/8.0 e phpMyAdmin (HostGator)
-- =========================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET SQL_MODE = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------
-- Criação do banco
-- ---------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `santo821_emenda`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `santo821_emenda`;

-- ---------------------------------------------------------
-- Criação do usuário de banco (quando permitido pelo host)
-- Em hospedagem compartilhada pode já existir via painel.
-- ---------------------------------------------------------
CREATE USER IF NOT EXISTS 'santo821_emenda'@'localhost' IDENTIFIED BY 'php@3903.';
GRANT ALL PRIVILEGES ON `santo821_emenda`.* TO 'santo821_emenda'@'localhost';
FLUSH PRIVILEGES;

-- ---------------------------------------------------------
-- Reimportação segura (ordem por dependência)
-- ---------------------------------------------------------
DROP TRIGGER IF EXISTS `trg_demandas_ai_log`;
DROP TRIGGER IF EXISTS `trg_demandas_au_log`;
DROP TRIGGER IF EXISTS `trg_demandas_ad_log`;

DROP TABLE IF EXISTS `redefinicao_senha`;
DROP TABLE IF EXISTS `logs_sistema`;
DROP TABLE IF EXISTS `notificacoes`;
DROP TABLE IF EXISTS `demandas`;
DROP TABLE IF EXISTS `usuarios`;

-- ---------------------------------------------------------
-- 1) Tabela: usuarios
-- ---------------------------------------------------------
CREATE TABLE `usuarios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome_completo` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `endereco` VARCHAR(255) NOT NULL,
  `whatsapp` VARCHAR(20) NOT NULL,
  `numero_decreto` VARCHAR(50) NOT NULL,
  `data_nascimento` DATE NOT NULL,
  `usuario` VARCHAR(60) NOT NULL,
  `senha_hash` VARCHAR(255) NOT NULL,
  `perfil` ENUM('master','funcionario') NOT NULL,
  `primeiro_login` TINYINT(1) NOT NULL DEFAULT 1,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `ultimo_login` DATETIME NULL,
  `session_token` VARCHAR(128) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuarios_email` (`email`),
  UNIQUE KEY `uq_usuarios_usuario` (`usuario`),
  UNIQUE KEY `uq_usuarios_numero_decreto` (`numero_decreto`),
  KEY `idx_usuarios_perfil_ativo` (`perfil`, `ativo`),
  KEY `idx_usuarios_ultimo_login` (`ultimo_login`),
  KEY `idx_usuarios_session_token` (`session_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 2) Tabela: demandas
-- ---------------------------------------------------------
CREATE TABLE `demandas` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `emenda` VARCHAR(180) NOT NULL,
  `nome_politico` VARCHAR(150) NOT NULL,
  `data_emenda` DATE NOT NULL,
  `tipo_emenda` VARCHAR(100) NOT NULL,
  `observacao` TEXT NULL,
  `prazo_entrega` DATETIME NOT NULL,
  `funcionario_id` INT UNSIGNED NOT NULL,
  `status` ENUM('pendente','em_andamento','concluida','atrasada') NOT NULL DEFAULT 'pendente',
  `criado_por` INT UNSIGNED NOT NULL,
  `data_conclusao` DATETIME NULL,
  `observacao_conclusao` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_demandas_status` (`status`),
  KEY `idx_demandas_prazo_entrega` (`prazo_entrega`),
  KEY `idx_demandas_funcionario_id` (`funcionario_id`),
  KEY `idx_demandas_criado_por` (`criado_por`),
  KEY `idx_demandas_status_prazo` (`status`, `prazo_entrega`),
  CONSTRAINT `fk_demandas_funcionario`
    FOREIGN KEY (`funcionario_id`) REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_demandas_criado_por`
    FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 3) Tabela: notificacoes
-- ---------------------------------------------------------
CREATE TABLE `notificacoes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `titulo` VARCHAR(120) NOT NULL,
  `mensagem` VARCHAR(255) NOT NULL,
  `tipo` VARCHAR(50) NOT NULL,
  `referencia_id` BIGINT UNSIGNED NULL,
  `referencia_tabela` VARCHAR(80) NULL,
  `lida` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notificacoes_usuario_lida` (`usuario_id`, `lida`),
  KEY `idx_notificacoes_tipo` (`tipo`),
  KEY `idx_notificacoes_referencia` (`referencia_tabela`, `referencia_id`),
  KEY `idx_notificacoes_created_at` (`created_at`),
  KEY `idx_notificacoes_automacao` (`usuario_id`, `tipo`, `referencia_id`, `created_at`),
  CONSTRAINT `fk_notificacoes_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 4) Tabela: logs_sistema
-- ---------------------------------------------------------
CREATE TABLE `logs_sistema` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NULL,
  `acao` VARCHAR(100) NOT NULL,
  `entidade` VARCHAR(80) NOT NULL,
  `entidade_id` BIGINT UNSIGNED NULL,
  `descricao` TEXT NULL,
  `ip` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_logs_usuario_id` (`usuario_id`),
  KEY `idx_logs_entidade` (`entidade`, `entidade_id`),
  KEY `idx_logs_created_at` (`created_at`),
  CONSTRAINT `fk_logs_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 5) Tabela: redefinicao_senha
-- ---------------------------------------------------------
CREATE TABLE `redefinicao_senha` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(128) NOT NULL,
  `expira_em` DATETIME NOT NULL,
  `usado` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_redefinicao_token` (`token`),
  KEY `idx_redefinicao_usuario_id` (`usuario_id`),
  KEY `idx_redefinicao_expira_em` (`expira_em`),
  CONSTRAINT `fk_redefinicao_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Auditoria por trigger (demanda)
-- Uso opcional na aplicação: SET @app_user_id = <id_usuario_logado>;
-- ---------------------------------------------------------
DELIMITER $$

CREATE TRIGGER `trg_demandas_ai_log`
AFTER INSERT ON `demandas`
FOR EACH ROW
BEGIN
  INSERT INTO `logs_sistema` (`usuario_id`, `acao`, `entidade`, `entidade_id`, `descricao`)
  VALUES (
    COALESCE(@app_user_id, NEW.criado_por),
    'INSERT',
    'demandas',
    NEW.id,
    CONCAT('Demanda criada com status ', NEW.status)
  );
END$$

CREATE TRIGGER `trg_demandas_au_log`
AFTER UPDATE ON `demandas`
FOR EACH ROW
BEGIN
  INSERT INTO `logs_sistema` (`usuario_id`, `acao`, `entidade`, `entidade_id`, `descricao`)
  VALUES (
    COALESCE(@app_user_id, NEW.criado_por),
    'UPDATE',
    'demandas',
    NEW.id,
    CONCAT('Demanda atualizada. Status de ', OLD.status, ' para ', NEW.status)
  );
END$$

CREATE TRIGGER `trg_demandas_ad_log`
AFTER DELETE ON `demandas`
FOR EACH ROW
BEGIN
  INSERT INTO `logs_sistema` (`usuario_id`, `acao`, `entidade`, `entidade_id`, `descricao`)
  VALUES (
    COALESCE(@app_user_id, OLD.criado_por),
    'DELETE',
    'demandas',
    OLD.id,
    'Demanda removida'
  );
END$$

DELIMITER ;

-- ---------------------------------------------------------
-- Usuário MASTER inicial
-- senha em texto para cadastro inicial: Master@123
-- hash bcrypt correspondente abaixo
-- ---------------------------------------------------------
INSERT INTO `usuarios` (
  `nome_completo`,
  `email`,
  `endereco`,
  `whatsapp`,
  `numero_decreto`,
  `data_nascimento`,
  `usuario`,
  `senha_hash`,
  `perfil`,
  `primeiro_login`,
  `ativo`,
  `ultimo_login`
) VALUES (
  'Administrador Master',
  'master@emendas.local',
  'Endereço administrativo',
  '5500000000000',
  'MASTER-001',
  '1990-01-01',
  'master',
  '$2y$10$2oUApvafnV5fGecK4fX3Z.2UNx9lKk2fuRu8etIRsEhM99f2m2vHW',
  'master',
  0,
  1,
  NOW()
)
ON DUPLICATE KEY UPDATE
  `nome_completo` = VALUES(`nome_completo`),
  `usuario` = VALUES(`usuario`),
  `ativo` = VALUES(`ativo`),
  `primeiro_login` = VALUES(`primeiro_login`);

SET FOREIGN_KEY_CHECKS = 1;
```

## Etapa 3 — Configuração do banco

### Arquivo: `config.php`
- **Caminho:** `app/config/config.php`
- **Função:** Centraliza constantes de ambiente, URL base e credenciais do banco.
- **Conteúdo completo:**
```php
<?php

declare(strict_types=1);

/**
 * Configuração central da aplicação para ambiente HostGator.
 */
const APP_NAME = 'Sistema de Gestão de Emendas Governamentais';
const APP_ENV = 'production'; // production | local
const APP_DEBUG = false;

// Base de publicação em hospedagem compartilhada.
const APP_BASE_PATH = '/emendas';
const APP_URL = 'https://www.prefsade.com.br/emendas';
const BASE_URL = APP_URL; // alias solicitado para compatibilidade.

// Credenciais de produção (cPanel / phpMyAdmin).
const DB_HOST = 'localhost';
const DB_NAME = 'santo821_emenda';
const DB_USER = 'santo821_emenda';
const DB_PASS = 'php@3903.';
const DB_CHARSET = 'utf8mb4';

// Produção
const TIMEZONE = 'America/Sao_Paulo';
const LOG_PATH = BASE_PATH . '/storage/logs/app.log';

const MASTER_DEFAULT_EMAIL = 'master@emendas.local';
const MASTER_DEFAULT_PASSWORD = 'Master@123';

date_default_timezone_set(TIMEZONE);
```

### Arquivo: `Database.php`
- **Caminho:** `app/Core/Database.php`
- **Função:** Implementa conexão PDO segura com tratamento de erro e charset utf8mb4.
- **Conteúdo completo:**
```php
<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);

            try {
                self::$connection = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                ]);
            } catch (PDOException $exception) {
                app_log('error', 'Erro de conexão com banco', [
                    'error' => $exception->getMessage(),
                    'host' => DB_HOST,
                    'database' => DB_NAME,
                ]);

                http_response_code(500);

                if (APP_DEBUG) {
                    exit('Erro de conexão com banco de dados: ' . $exception->getMessage());
                }

                exit('Não foi possível conectar ao banco de dados no momento. Tente novamente em instantes.');
            }
        }

        return self::$connection;
    }
}
```

## Etapa 4 — Autenticação

### Arquivo: `AuthController.php`
- **Caminho:** `app/Controllers/AuthController.php`
- **Função:** Orquestra login, logout e troca obrigatória de senha.
- **Conteúdo completo:**
```php
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
```

### Arquivo: `AuthMiddleware.php`
- **Caminho:** `app/Middleware/AuthMiddleware.php`
- **Função:** Garante sessão válida, autenticação e regra de primeiro login.
- **Conteúdo completo:**
```php
<?php

namespace App\Middleware;

use App\Models\User;

class AuthMiddleware
{
    public static function ensureAuthenticated(): void
    {
        if (empty($_SESSION['user'])) {
            flash('error', 'Faça login para continuar.');
            redirect('login');
        }

        self::ensureSessionIsValid();
    }

    /**
     * Regra obrigatória:
     * Funcionário em primeiro login precisa alterar a senha
     * antes de acessar qualquer painel.
     */
    public static function ensurePasswordChanged(): void
    {
        $isFirstLogin = !empty($_SESSION['user']['first_login']);
        $isEmployee = ($_SESSION['user']['role'] ?? '') === 'funcionario';

        if (!$isEmployee || !$isFirstLogin) {
            return;
        }

        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        $basePath = rtrim(APP_BASE_PATH, '/');
        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }
        $path = '/' . ltrim($path, '/');

        $allowedDuringFirstLogin = ['/change-password', '/logout'];
        if (in_array($path, $allowedDuringFirstLogin, true)) {
            return;
        }

        flash('error', 'No primeiro acesso, altere sua senha para continuar.');
        redirect('change-password');
    }

    /**
     * Valida se o token da sessão atual ainda é o token ativo do usuário no banco.
     * Evita sessão simultânea inválida.
     */
    private static function ensureSessionIsValid(): void
    {
        $sessionUserId = (int)($_SESSION['user']['id'] ?? 0);
        $sessionToken = $_SESSION['user']['session_token'] ?? null;

        if ($sessionUserId <= 0 || empty($sessionToken)) {
            self::forceLogout('Sessão inválida. Faça login novamente.');
        }

        $model = new User();
        $dbToken = $model->getSessionToken($sessionUserId);

        if (empty($dbToken) || !hash_equals((string)$dbToken, (string)$sessionToken)) {
            self::forceLogout('Sua sessão foi encerrada por um novo login.');
        }
    }

    private static function forceLogout(string $message): void
    {
        $_SESSION = [];
        session_destroy();
        session_start();
        flash('error', $message);
        redirect('login');
    }
}
```

## Etapa 5 — Painel Master

### Arquivo: `MasterController.php`
- **Caminho:** `app/Controllers/MasterController.php`
- **Função:** Implementa gestão administrativa (funcionários, demandas, notificações, validações).
- **Conteúdo completo:**
```php
<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Demand;
use App\Models\Notification;
use App\Models\SystemLog;
use App\Models\User;
use App\Services\NotificationDeadlineService;

class MasterController extends Controller
{
    private const DEMAND_STATUS = ['pendente', 'em_andamento', 'concluida', 'atrasada'];

    public function __construct(
        private readonly User $users = new User(),
        private readonly Demand $demands = new Demand(),
        private readonly Notification $notifications = new Notification(),
        private readonly SystemLog $logs = new SystemLog(),
        private readonly NotificationDeadlineService $deadlineService = new NotificationDeadlineService()
    ) {
    }

    public function dashboard(): void
    {
        $this->requireAuth('master');
        $masterId = (int)$_SESSION['user']['id'];
        $this->deadlineService->runAutomationForMaster($masterId);

        $this->view('master/dashboard/index', [
            'stats' => $this->demands->stats(),
            'demands' => $this->demands->paginatedFiltered([], 300, 0),
            'notifications' => $this->notifications->forUser($masterId, 8),
            'upcoming' => $this->demands->upcomingDeadlines(8),
        ]);
    }

    public function employees(): void
    {
        $this->requireAuth('master');

        $search = trim((string)($_GET['search'] ?? ''));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 8;
        $offset = ($page - 1) * $perPage;

        $total = $this->users->countEmployees($search !== '' ? $search : null);
        $employees = $this->users->paginatedEmployees($perPage, $offset, $search !== '' ? $search : null);

        $this->view('master/employees/index', [
            'employees' => $employees,
            'search' => $search,
            'page' => $page,
            'totalPages' => max(1, (int)ceil($total / $perPage)),
        ]);
    }

    public function createEmployee(): void
    {
        $this->requireAuth('master');
        $this->assertCsrfOrRedirect('master/employees');

        $payload = $this->validateEmployeePayload();
        if (!$payload['ok']) {
            flash('error', implode(' ', $payload['errors']));
            redirect('master/employees');
        }

        $data = $payload['data'];
        if ($this->users->emailExists($data['email'])) {
            flash('error', 'E-mail já cadastrado.');
            redirect('master/employees');
        }
        if ($this->users->decreeExists($data['numero_decreto'])) {
            flash('error', 'Número de decreto já cadastrado.');
            redirect('master/employees');
        }

        $this->users->createEmployee([
            'nome_completo' => $data['nome_completo'],
            'email' => $data['email'],
            'endereco' => $data['endereco'],
            'whatsapp' => $data['whatsapp'],
            'numero_decreto' => $data['numero_decreto'],
            'data_nascimento' => $data['data_nascimento'],
            // Regra de credencial inicial.
            'usuario' => $data['numero_decreto'],
            'senha_hash' => password_hash(normalize_birth_password($data['data_nascimento']), PASSWORD_DEFAULT),
        ]);

        $this->logAction('create', 'usuarios', null, 'Cadastro de funcionário realizado pelo Master');
        flash('success', 'Funcionário cadastrado com sucesso.');
        redirect('master/employees');
    }

    public function updateEmployee(): void
    {
        $this->requireAuth('master');
        $this->assertCsrfOrRedirect('master/employees');

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0 || !$this->users->existsEmployeeById($id)) {
            flash('error', 'Funcionário inválido.');
            redirect('master/employees');
        }

        $payload = $this->validateEmployeePayload();
        if (!$payload['ok']) {
            flash('error', implode(' ', $payload['errors']));
            redirect('master/employees');
        }

        $data = $payload['data'];
        if ($this->users->emailExists($data['email'], $id) || $this->users->decreeExists($data['numero_decreto'], $id)) {
            flash('error', 'E-mail ou decreto já em uso.');
            redirect('master/employees');
        }

        $this->users->updateEmployee($id, $data);
        $this->logAction('update', 'usuarios', $id, 'Dados do funcionário atualizados pelo Master');
        flash('success', 'Funcionário atualizado com sucesso.');
        redirect('master/employees');
    }

    public function changeEmployeePassword(): void
    {
        $this->requireAuth('master');
        $this->assertCsrfOrRedirect('master/employees');

        $id = (int)($_POST['id'] ?? 0);
        $newPassword = (string)($_POST['new_password'] ?? '');

        if ($id <= 0 || !$this->users->existsEmployeeById($id)) {
            flash('error', 'Funcionário inválido.');
            redirect('master/employees');
        }

        if (strlen($newPassword) < 8) {
            flash('error', 'Informe uma nova senha com no mínimo 8 caracteres.');
            redirect('master/employees');
        }

        $this->users->updatePassword($id, password_hash($newPassword, PASSWORD_DEFAULT), true);
        $this->logAction('update_password', 'usuarios', $id, 'Master alterou senha de funcionário e reativou primeiro login');
        flash('success', 'Senha do funcionário alterada com sucesso.');
        redirect('master/employees');
    }

    public function toggleEmployee(): void
    {
        $this->requireAuth('master');
        $this->assertCsrfOrRedirect('master/employees');

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0 || !$this->users->existsEmployeeById($id)) {
            flash('error', 'Funcionário inválido.');
            redirect('master/employees');
        }

        $active = (int)($_POST['active'] ?? 0) === 1;
        $this->users->toggleEmployeeStatus($id, $active);

        $this->logAction('toggle', 'usuarios', $id, $active ? 'Funcionário ativado' : 'Funcionário desativado');
        flash('success', $active ? 'Funcionário ativado.' : 'Funcionário desativado.');
        redirect('master/employees');
    }

    public function deleteEmployee(): void
    {
        $this->requireAuth('master');
        $this->assertCsrfOrRedirect('master/employees');

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0 || !$this->users->existsEmployeeById($id)) {
            flash('error', 'Funcionário inválido.');
            redirect('master/employees');
        }

        // Exclusão lógica para preservar histórico.
        $this->users->toggleEmployeeStatus($id, false);
        $this->logAction('soft_delete', 'usuarios', $id, 'Funcionário desativado por ação de exclusão lógica');
        flash('success', 'Funcionário desativado com sucesso.');
        redirect('master/employees');
    }

    public function demands(): void
    {
        $this->requireAuth('master');
        $this->deadlineService->runAutomationForMaster((int)$_SESSION['user']['id']);

        $filters = [
            'status' => trim((string)($_GET['status'] ?? '')),
            'funcionario_id' => trim((string)($_GET['funcionario_id'] ?? '')),
            'prazo_de' => trim((string)($_GET['prazo_de'] ?? '')),
            'prazo_ate' => trim((string)($_GET['prazo_ate'] ?? '')),
        ];

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        $total = $this->demands->countFiltered($filters);

        $this->view('master/demands/index', [
            'demands' => $this->demands->paginatedFiltered($filters, $perPage, $offset),
            'employees' => $this->users->allActiveEmployees(),
            'filters' => $filters,
            'page' => $page,
            'totalPages' => max(1, (int)ceil($total / $perPage)),
        ]);
    }

    public function createDemand(): void
    {
        $this->requireAuth('master');
        $this->assertCsrfOrRedirect('master/demands');

        $payload = $this->validateDemandPayload();
        if (!$payload['ok']) {
            flash('error', implode(' ', $payload['errors']));
            redirect('master/demands');
        }

        $data = $payload['data'];
        $this->demands->create($data + ['criado_por' => (int)$_SESSION['user']['id']]);

        $this->logAction('create', 'demandas', null, 'Demanda cadastrada pelo Master');
        flash('success', 'Demanda cadastrada com sucesso.');
        redirect('master/demands');
    }

    public function updateDemand(): void
    {
        $this->requireAuth('master');
        $this->assertCsrfOrRedirect('master/demands');

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            flash('error', 'Demanda inválida.');
            redirect('master/demands');
        }

        $payload = $this->validateDemandPayload();
        if (!$payload['ok']) {
            flash('error', implode(' ', $payload['errors']));
            redirect('master/demands');
        }

        $this->demands->update($id, $payload['data']);

        $this->logAction('update', 'demandas', $id, 'Demanda atualizada pelo Master');
        flash('success', 'Demanda atualizada com sucesso.');
        redirect('master/demands');
    }

    public function deleteDemand(): void
    {
        $this->requireAuth('master');
        $this->assertCsrfOrRedirect('master/demands');

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            flash('error', 'Demanda inválida.');
            redirect('master/demands');
        }

        $this->demands->delete($id);
        $this->logAction('delete', 'demandas', $id, 'Demanda removida pelo Master');
        flash('success', 'Demanda removida.');
        redirect('master/demands');
    }

    public function readNotification(): void
    {
        $this->requireAuth('master');
        $this->assertCsrfOrRedirect('master/notifications');

        $notificationId = (int)($_POST['id'] ?? 0);
        $this->notifications->markRead($notificationId, (int)$_SESSION['user']['id']);
        flash('success', 'Notificação marcada como lida.');
        redirect('master/notifications');
    }

    public function notifications(): void
    {
        $this->requireAuth('master');
        $masterId = (int)$_SESSION['user']['id'];
        $this->deadlineService->runAutomationForMaster($masterId);

        $this->view('master/notifications/index', [
            'notifications' => $this->notifications->forUser($masterId, 50),
        ]);
    }

    /**
     * Validação centralizada de payload de funcionário.
     *
     * @return array{ok:bool,data:array<string,string>,errors:array<int,string>}
     */
    private function validateEmployeePayload(): array
    {
        $data = [
            'nome_completo' => trim((string)($_POST['nome_completo'] ?? '')),
            'email' => trim((string)($_POST['email'] ?? '')),
            'endereco' => trim((string)($_POST['endereco'] ?? '')),
            'whatsapp' => trim((string)($_POST['whatsapp'] ?? '')),
            'numero_decreto' => trim((string)($_POST['numero_decreto'] ?? '')),
            'data_nascimento' => trim((string)($_POST['data_nascimento'] ?? '')),
        ];

        $errors = validate_required($data, ['nome_completo', 'email', 'endereco', 'whatsapp', 'numero_decreto', 'data_nascimento']);

        if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'E-mail inválido.';
        }

        if ($data['data_nascimento'] !== '' && strtotime($data['data_nascimento']) === false) {
            $errors[] = 'Data de nascimento inválida.';
        }

        return ['ok' => $errors === [], 'data' => $data, 'errors' => $errors];
    }

    /**
     * Validação centralizada de payload de demanda.
     *
     * @return array{ok:bool,data:array<string,mixed>,errors:array<int,string>}
     */
    private function validateDemandPayload(): array
    {
        $data = [
            'emenda' => trim((string)($_POST['emenda'] ?? '')),
            'nome_politico' => trim((string)($_POST['nome_politico'] ?? '')),
            'data_emenda' => trim((string)($_POST['data_emenda'] ?? '')),
            'tipo_emenda' => trim((string)($_POST['tipo_emenda'] ?? '')),
            'observacao' => trim((string)($_POST['observacao'] ?? '')),
            'prazo_entrega' => trim((string)($_POST['prazo_entrega'] ?? '')),
            'funcionario_id' => (int)($_POST['funcionario_id'] ?? 0),
            'status' => trim((string)($_POST['status'] ?? 'pendente')),
        ];

        $errors = validate_required($data, ['emenda', 'nome_politico', 'data_emenda', 'tipo_emenda', 'prazo_entrega']);

        if ($data['funcionario_id'] <= 0 || !$this->users->existsEmployeeById($data['funcionario_id'])) {
            $errors[] = 'Selecione um funcionário válido para a demanda.';
        }

        if (!in_array($data['status'], self::DEMAND_STATUS, true)) {
            $errors[] = 'Status da demanda inválido.';
        }

        if (strtotime($data['data_emenda']) === false) {
            $errors[] = 'Data da emenda inválida.';
        }

        $prazoTimestamp = strtotime($data['prazo_entrega']);
        if ($prazoTimestamp === false) {
            $errors[] = 'Prazo de entrega inválido.';
        } else {
            $data['prazo_entrega'] = date('Y-m-d H:i:s', $prazoTimestamp);
        }

        return ['ok' => $errors === [], 'data' => $data, 'errors' => $errors];
    }

    private function assertCsrfOrRedirect(string $redirectPath): void
    {
        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            flash('error', 'Falha de segurança na requisição.');
            redirect($redirectPath);
        }
    }

    /**
     * Log básico de ações administrativas para auditoria.
     */
    private function logAction(string $acao, string $entidade, ?int $entidadeId, string $descricao): void
    {
        $this->logs->create([
            'usuario_id' => (int)($_SESSION['user']['id'] ?? 0) ?: null,
            'acao' => $acao,
            'entidade' => $entidade,
            'entidade_id' => $entidadeId,
            'descricao' => $descricao,
            'ip' => client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }
}
```

## Etapa 6 — Painel Funcionário

### Arquivo: `EmployeeController.php`
- **Caminho:** `app/Controllers/EmployeeController.php`
- **Função:** Implementa dashboard do funcionário, demandas próprias e conclusão com observação.
- **Conteúdo completo:**
```php
<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Demand;
use App\Models\Notification;
use App\Services\NotificationDeadlineService;
use App\Models\SystemLog;
use App\Models\User;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly Demand $demands = new Demand(),
        private readonly Notification $notifications = new Notification(),
        private readonly User $users = new User(),
        private readonly SystemLog $logs = new SystemLog(),
        private readonly NotificationDeadlineService $deadlineService = new NotificationDeadlineService()
    ) {
    }

    public function dashboard(): void
    {
        $this->requireAuth('funcionario');
        $employeeId = (int)$_SESSION['user']['id'];

        // Mantém status atrasado sincronizado em toda navegação do funcionário.
        $this->demands->refreshOverdueStatuses();

        $this->view('funcionario/dashboard/index', [
            'summary' => $this->demands->employeeSummary($employeeId),
        ]);
    }

    public function demands(): void
    {
        $this->requireAuth('funcionario');
        $this->demands->refreshOverdueStatuses();

        // Regra crítica: funcionário só recebe demandas do próprio usuário.
        $employeeId = (int)$_SESSION['user']['id'];
        $list = $this->demands->byEmployee($employeeId);

        $this->view('funcionario/demandas/index', [
            'demands' => $list,
        ]);
    }

    public function completeDemand(): void
    {
        $this->requireAuth('funcionario');

        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            flash('error', 'Falha de segurança na requisição.');
            redirect('funcionario/demandas');
        }

        $employeeId = (int)$_SESSION['user']['id'];
        $demandId = (int)($_POST['id'] ?? 0);
        $note = trim((string)($_POST['completion_note'] ?? ''));

        if ($demandId <= 0) {
            flash('error', 'Demanda inválida para conclusão.');
            redirect('funcionario/demandas');
        }

        $ownedDemand = $this->demands->findOwnedById($demandId, $employeeId);
        if (!$ownedDemand) {
            flash('error', 'Você não tem permissão para concluir esta demanda.');
            redirect('funcionario/demandas');
        }

        $done = $this->demands->markCompleted($demandId, $employeeId, $note);
        if (!$done) {
            flash('error', 'Não foi possível concluir esta demanda.');
            redirect('funcionario/demandas');
        }

        // Notifica automaticamente o Master sobre conclusão com referência da demanda.
        $masterId = $this->users->findActiveMasterId();
        if ($masterId !== null) {
            $this->deadlineService->notifyDemandCompletedForMaster(
                $masterId,
                $ownedDemand,
                (string)($_SESSION['user']['name'] ?? 'Funcionário')
            );
        }

        // Auditoria da conclusão para rastreabilidade operacional.
        $this->logs->create([
            'usuario_id' => $employeeId,
            'acao' => 'concluir',
            'entidade' => 'demandas',
            'entidade_id' => $demandId,
            'descricao' => 'Funcionário concluiu demanda ' . $ownedDemand['emenda'],
            'ip' => client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);

        flash('success', 'Demanda concluída com sucesso.');
        redirect('funcionario/demandas');
    }

    public function readNotification(): void
    {
        $this->requireAuth('funcionario');

        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            flash('error', 'Falha de segurança na requisição.');
            redirect('funcionario/notificacoes');
        }

        $notificationId = (int)($_POST['id'] ?? 0);
        $this->notifications->markRead($notificationId, (int)$_SESSION['user']['id']);
        flash('success', 'Notificação marcada como lida.');
        redirect('funcionario/notificacoes');
    }

    public function notifications(): void
    {
        $this->requireAuth('funcionario');
        $userId = (int)$_SESSION['user']['id'];
        $list = $this->notifications->forUser($userId);

        $this->view('funcionario/notificacoes/index', ['notifications' => $list]);
    }
}
```

## Etapa 7 — Notificações e prazos

### Arquivo: `NotificationDeadlineService.php`
- **Caminho:** `app/Services/NotificationDeadlineService.php`
- **Função:** Centraliza automações de prazo e envio de notificações ao Master.
- **Conteúdo completo:**
```php
<?php

namespace App\Services;

use App\Models\Demand;
use App\Models\Notification;

/**
 * Serviço responsável pelo módulo de notificações e controle de prazo.
 *
 * Centraliza regras reutilizáveis para:
 * - alerta de demandas com 24h ou menos;
 * - prevenção de notificações duplicadas excessivas;
 * - notificação de conclusão de demanda ao Master.
 */
class NotificationDeadlineService
{
    public function __construct(
        private readonly Demand $demands = new Demand(),
        private readonly Notification $notifications = new Notification()
    ) {
    }

    /**
     * Atualiza status atrasado e dispara alertas de 24h para o Master.
     *
     * @return array{overdue_updated:int, alerts_created:int}
     */
    public function runAutomationForMaster(int $masterId): array
    {
        $overdueUpdated = $this->demands->refreshOverdueStatuses();
        $alertsCreated = 0;

        foreach ($this->demands->dueIn24Hours() as $item) {
            $demandId = (int)$item['id'];

            // Evita repetição excessiva da mesma notificação em janelas curtas.
            if ($this->notifications->existsRecent($masterId, 'prazo_24h', $demandId, 24)) {
                continue;
            }

            $this->notifications->notify(
                $masterId,
                'Prazo próximo (<= 24h)',
                sprintf(
                    'Demanda #%d (%s) vence em até 24h. Responsável: %s.',
                    $demandId,
                    $item['emenda'],
                    $item['funcionario_nome']
                ),
                'prazo_24h',
                $demandId,
                'demandas'
            );
            $alertsCreated++;
        }

        return [
            'overdue_updated' => $overdueUpdated,
            'alerts_created' => $alertsCreated,
        ];
    }

    /**
     * Gera notificação de conclusão para o Master com referência da demanda.
     */
    public function notifyDemandCompletedForMaster(int $masterId, array $demand, string $employeeName): bool
    {
        $demandId = (int)($demand['id'] ?? 0);

        return $this->notifications->notify(
            $masterId,
            'Demanda concluída',
            sprintf('%s concluiu a demanda #%d (%s).', $employeeName, $demandId, $demand['emenda'] ?? 'sem título'),
            'conclusao_demanda',
            $demandId,
            'demandas'
        );
    }
}
```

### Arquivo: `Notification.php`
- **Caminho:** `app/Models/Notification.php`
- **Função:** CRUD de notificações, contagem de não lidas e deduplicação temporal.
- **Conteúdo completo:**
```php
<?php

namespace App\Models;

use App\Core\Database;

class Notification
{
    /**
     * Função reaproveitável para registrar notificações internas.
     */
    public function notify(
        int $usuarioId,
        string $titulo,
        string $mensagem,
        string $tipo,
        ?int $referenciaId = null,
        ?string $referenciaTabela = null
    ): bool {
        $sql = 'INSERT INTO notificacoes (usuario_id, titulo, mensagem, tipo, referencia_id, referencia_tabela)
                VALUES (:usuario_id, :titulo, :mensagem, :tipo, :referencia_id, :referencia_tabela)';

        return Database::connection()->prepare($sql)->execute([
            'usuario_id' => $usuarioId,
            'titulo' => $titulo,
            'mensagem' => $mensagem,
            'tipo' => $tipo,
            'referencia_id' => $referenciaId,
            'referencia_tabela' => $referenciaTabela,
        ]);
    }

    public function create(array $data): bool
    {
        return $this->notify(
            (int)$data['usuario_id'],
            (string)$data['titulo'],
            (string)$data['mensagem'],
            (string)$data['tipo'],
            isset($data['referencia_id']) ? (int)$data['referencia_id'] : null,
            $data['referencia_tabela'] ?? null
        );
    }

    /**
     * Verifica se já existe notificação recente para evitar duplicidade excessiva.
     */
    public function existsRecent(int $usuarioId, string $type, int $referenciaId, int $hoursWindow = 24): bool
    {
        $cutoff = date('Y-m-d H:i:s', time() - max(1, $hoursWindow) * 3600);

        $sql = 'SELECT id FROM notificacoes
                WHERE usuario_id = :usuario_id AND tipo = :tipo AND referencia_id = :referencia_id
                AND created_at >= :cutoff
                LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->bindValue(':usuario_id', $usuarioId, \PDO::PARAM_INT);
        $stmt->bindValue(':tipo', $type);
        $stmt->bindValue(':referencia_id', $referenciaId, \PDO::PARAM_INT);
        $stmt->bindValue(':cutoff', $cutoff);
        $stmt->execute();
        return (bool)$stmt->fetch();
    }

    public function forUser(int $userId, int $limit = 30): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM notificacoes WHERE usuario_id = :usuario_id ORDER BY created_at DESC LIMIT :limite');
        $stmt->bindValue(':usuario_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function unreadCount(int $userId): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) AS total FROM notificacoes WHERE usuario_id = :usuario_id AND lida = 0');
        $stmt->execute(['usuario_id' => $userId]);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    public function markAllRead(int $userId): bool
    {
        return Database::connection()->prepare('UPDATE notificacoes SET lida = 1 WHERE usuario_id = :usuario_id')->execute(['usuario_id' => $userId]);
    }

    public function markRead(int $notificationId, int $userId): bool
    {
        $sql = 'UPDATE notificacoes SET lida = 1 WHERE id = :id AND usuario_id = :usuario_id';
        return Database::connection()->prepare($sql)->execute(['id' => $notificationId, 'usuario_id' => $userId]);
    }
}
```

## Etapa 8 — Layout final

### Arquivo: `header.php`
- **Caminho:** `app/views/layouts/header.php`
- **Função:** Renderiza shell institucional com sidebar, topbar e preview de notificações.
- **Conteúdo completo:**
```php
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
      ]
    : [
        ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'path' => '/funcionario/dashboard'],
        ['label' => 'Minhas Demandas', 'icon' => 'bi-list-check', 'path' => '/funcionario/demandas'],
        ['label' => 'Notificações', 'icon' => 'bi-bell', 'path' => '/funcionario/notificacoes'],
      ];
?>
<div class="app-shell" id="appShell">
  <aside class="sidebar" id="sidebarNav">
    <a class="brand" href="<?= url($role === 'master' ? 'master/dashboard' : 'funcionario/dashboard') ?>">
      <i class="bi bi-building"></i> Emendas Gov
    </a>

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
    <header class="topbar d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-outline-secondary btn-sm d-lg-none" id="sidebarToggle" type="button" aria-label="Abrir menu">
          <i class="bi bi-list"></i>
        </button>
        <div>
          <small class="text-muted d-block text-uppercase fw-semibold">Painel institucional</small>
          <strong><?= e($_SESSION['user']['name']) ?></strong>
        </div>
      </div>

      <div class="d-flex align-items-center gap-2">
        <div class="dropdown">
          <button class="btn btn-outline-primary btn-sm position-relative" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-bell"></i>
            <?php if ((int)$headerUnreadCount > 0): ?>
              <span class="badge rounded-pill bg-warning text-dark position-absolute top-0 start-100 translate-middle"><?= (int)$headerUnreadCount ?></span>
            <?php endif; ?>
          </button>
          <div class="dropdown-menu dropdown-menu-end p-0 shadow notification-menu">
            <div class="p-3 border-bottom">
              <strong>Notificações</strong>
            </div>
            <div class="notification-list">
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

    <main class="container-fluid p-3 p-lg-4">
<?php else: ?>
<main class="container py-4">
<?php endif; ?>
<?php if ($error = flash('error')): ?><div class="alert alert-danger shadow-sm border-0"><?= e($error) ?></div><?php endif; ?>
<?php if ($success = flash('success')): ?><div class="alert alert-success shadow-sm border-0"><?= e($success) ?></div><?php endif; ?>
```

### Arquivo: `app.css`
- **Caminho:** `assets/css/app.css`
- **Função:** Define design system responsivo e visual institucional.
- **Conteúdo completo:**
```md
:root {
  --azul: #0d3b66;
  --azul-2: #1f6feb;
  --verde: #1f9d55;
  --branco: #ffffff;
  --preto: #0f172a;
  --cinza: #eef2f7;
  --borda: #dbe3ee;
}

body {
  background: linear-gradient(180deg, #f8fbff 0%, var(--cinza) 100%);
  color: var(--preto);
  font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
}

/* ---------- Auth ---------- */
.auth-page {
  min-height: 100vh;
  background: linear-gradient(140deg, #0b1220 0%, var(--azul) 45%, var(--azul-2) 100%);
}
.auth-wrapper { max-width: 1020px; }
.auth-panel {
  border-radius: 1.25rem;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, .15);
}
.auth-hero {
  background: radial-gradient(circle at 20% 10%, rgba(255,255,255,.16), transparent 45%), #0f172a;
}
.auth-card,
.card-ui,
.card {
  border: 0;
  border-radius: 1rem;
  box-shadow: 0 10px 28px rgba(15, 23, 42, .08);
}

/* ---------- App Shell ---------- */
.app-shell {
  display: flex;
  min-height: 100vh;
}
.sidebar {
  width: 272px;
  background: linear-gradient(180deg, #0f172a 0%, #111827 100%);
  color: var(--branco);
  position: fixed;
  top: 0;
  bottom: 0;
  left: 0;
  z-index: 1040;
  padding: 1rem;
  border-right: 1px solid rgba(255, 255, 255, .06);
  transition: transform .2s ease;
}
.sidebar .brand {
  font-weight: 700;
  color: var(--branco);
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: .55rem;
  margin-bottom: 1rem;
  font-size: 1.02rem;
}
.sidebar .nav-link {
  color: #c7d2fe;
  border-radius: .65rem;
  padding: .58rem .75rem;
  display: flex;
  align-items: center;
  gap: .5rem;
}
.sidebar .nav-link:hover,
.sidebar .nav-link.active {
  background: rgba(255, 255, 255, .12);
  color: #fff;
}

.main-wrap { margin-left: 272px; flex: 1; }
.topbar {
  background: var(--branco);
  border-bottom: 1px solid var(--borda);
  padding: .75rem 1rem;
  position: sticky;
  top: 0;
  z-index: 1030;
}

.notification-menu { width: 340px; border: 0; border-radius: .85rem; }
.notification-list { max-height: 280px; overflow: auto; }

/* ---------- Components ---------- */
.kpi { background: var(--branco); }
.kpi .icon {
  width: 42px;
  height: 42px;
  border-radius: .7rem;
  display: grid;
  place-items: center;
  color: #fff;
}
.icon-azul { background: var(--azul-2); }
.icon-verde { background: var(--verde); }
.icon-preto { background: var(--preto); }

.table-modern {
  border-collapse: separate;
  border-spacing: 0;
}
.table-modern thead th {
  background: #f8fafc;
  color: #334155;
  border-bottom: 1px solid #e2e8f0;
}
.table-modern tbody tr:hover { background: #f8fbff; }

.form-control,
.form-select {
  border-color: #d5dde8;
  padding-top: .55rem;
  padding-bottom: .55rem;
}
.form-control:focus,
.form-select:focus {
  border-color: #88b7ff;
  box-shadow: 0 0 0 .2rem rgba(31, 111, 235, .15);
}

.badge-status { font-weight: 600; }
.badge-pendente { background: #f59e0b; color: #111827; }
.badge-andamento { background: #0ea5e9; }
.badge-concluida { background: #16a34a; }
.badge-atrasada { background: #dc2626; }

.btn-icon i { margin-right: .35rem; }
.modal-content { border: 0; border-radius: 1rem; }

/* ---------- DataTables tuning ---------- */
div.dataTables_wrapper div.dataTables_filter input {
  border-radius: .6rem;
  border: 1px solid #d5dde8;
}

/* ---------- Mobile sidebar ---------- */
@media (max-width: 991px) {
  .sidebar {
    transform: translateX(-100%);
    box-shadow: 0 0 0 9999px rgba(2, 6, 23, 0);
  }
  .app-shell.sidebar-open .sidebar {
    transform: translateX(0);
    box-shadow: 0 0 0 9999px rgba(2, 6, 23, .45);
  }
  .main-wrap { margin-left: 0; }
}
```

### Arquivo: `app.js`
- **Caminho:** `assets/js/app.js`
- **Função:** Inicializa sidebar responsiva, DataTables e FullCalendar.
- **Conteúdo completo:**
```md
document.addEventListener('DOMContentLoaded', () => {
  const appShell = document.getElementById('appShell');
  const sidebar = document.getElementById('sidebarNav');
  const sidebarToggle = document.getElementById('sidebarToggle');

  if (appShell && sidebar && sidebarToggle) {
    sidebarToggle.addEventListener('click', () => {
      appShell.classList.toggle('sidebar-open');
    });

    document.addEventListener('click', (event) => {
      if (window.innerWidth > 991) return;
      const clickedInsideSidebar = sidebar.contains(event.target);
      const clickedToggle = sidebarToggle.contains(event.target);
      if (!clickedInsideSidebar && !clickedToggle) {
        appShell.classList.remove('sidebar-open');
      }
    });
  }

  const calendarEl = document.getElementById('calendar');
  if (calendarEl && window.FullCalendar) {
    const events = JSON.parse(calendarEl.dataset.events || '[]');
    const calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      locale: 'pt-br',
      events,
      height: 'auto',
      dayMaxEvents: true,
      buttonText: { today: 'Hoje' },
    });
    calendar.render();
  }

  if (window.jQuery && window.jQuery.fn.DataTable) {
    $('.data-table').DataTable({
      pageLength: 10,
      order: [],
      responsive: true,
      language: {
        url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/pt-BR.json',
      },
    });
  }
});
```

## Etapa 9 — Revisão de erros

### Arquivo: `Router.php`
- **Caminho:** `app/Core/Router.php`
- **Função:** Normaliza rotas com APP_BASE_PATH e faz despacho seguro.
- **Conteúdo completo:**
```php
<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    private function addRoute(string $method, string $path, array $handler): void
    {
        $normalized = '/' . trim($path, '/');
        $this->routes[$method][$normalized === '/' ? '/' : $normalized] = $handler;
    }

    public function dispatch(string $uri, string $method): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        // Remove APP_BASE_PATH do início da rota para compatibilidade com /emendas.
        $basePath = rtrim(APP_BASE_PATH, '/');
        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }

        $path = '/' . trim($path, '/');
        $path = $path === '/' ? '/' : $path;

        $httpMethod = strtoupper($method);
        $handler = $this->routes[$httpMethod][$path] ?? null;

        if (!$handler) {
            http_response_code(404);
            echo 'Página não encontrada.';
            return;
        }

        [$controllerClass, $action] = $handler;
        $controller = new $controllerClass();
        $controller->$action();
    }
}
```

### Arquivo: `functions.php`
- **Caminho:** `app/helpers/functions.php`
- **Função:** Fornece utilitários de URL, CSRF, escape, flash e logging.
- **Conteúdo completo:**
```php
<?php

declare(strict_types=1);

/**
 * Escape de saída para prevenir XSS em templates.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Gera URL baseada no APP_BASE_PATH (ex.: /emendas).
 * Evita acoplamento em domínio fixo e funciona em diferentes ambientes.
 */
function url(string $path = ''): string
{
    $normalizedPath = trim($path);
    $normalizedPath = ltrim($normalizedPath, '/');

    $basePath = rtrim(APP_BASE_PATH, '/');
    if ($basePath === '') {
        return '/' . $normalizedPath;
    }

    return $basePath . ($normalizedPath !== '' ? '/' . $normalizedPath : '');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }

    $message = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);

    return $message;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function verify_csrf(?string $token): bool
{
    return $token !== null && isset($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $token);
}

function validate_required(array $data, array $fields): array
{
    $errors = [];
    foreach ($fields as $field) {
        if (trim((string)($data[$field] ?? '')) === '') {
            $errors[] = "O campo {$field} é obrigatório.";
        }
    }
    return $errors;
}

function normalize_birth_password(string $birthDate): string
{
    return preg_replace('/\D/', '', $birthDate) ?? '';
}

function client_ip(): ?string
{
    $keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];

    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', (string)$_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    return null;
}

/**
 * Log básico de aplicação para produção.
 */
function app_log(string $level, string $message, array $context = []): void
{
    $line = sprintf(
        "[%s] [%s] %s %s\n",
        date('Y-m-d H:i:s'),
        strtoupper($level),
        $message,
        $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''
    );

    @file_put_contents(LOG_PATH, $line, FILE_APPEND);
}
```

## Etapa 10 — Checklist HostGator

### Arquivo: `DEPLOY_HOSTGATOR.md`
- **Caminho:** `DEPLOY_HOSTGATOR.md`
- **Função:** Checklist operacional completo para deploy em hospedagem compartilhada.
- **Conteúdo completo:**
```md
# Deploy em Produção (HostGator) — Sistema de Gestão de Emendas

## 1) Dados de produção (preenchidos)
- **URL base:** `https://www.prefsade.com.br/emendas`
- **Pasta de publicação:** `public_html/emendas`
- **Banco:** `santo821_emenda`
- **Usuário:** `santo821_emenda`
- **Senha:** `php@3903.`

---

## 2) Arquivo de configuração pronto
Arquivo principal: `app/config/config.php`

Constantes obrigatórias já configuradas:
- `DB_HOST`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`
- `BASE_URL`

Também inclui:
- `APP_BASE_PATH = /emendas`
- `APP_ENV = production`
- `APP_DEBUG = false`
- `TIMEZONE = America/Sao_Paulo`
- `LOG_PATH = storage/logs/app.log`

---

## 3) Conexão PDO (produção)
A classe `app/Core/Database.php` está pronta para HostGator:
- `PDO::ERRMODE_EXCEPTION`
- `PDO::ATTR_EMULATE_PREPARES = false`
- `charset utf8mb4`
- init command com collation `utf8mb4_unicode_ci`
- tratamento de erro sem exposição sensível em produção
- log de falhas em arquivo

---

## 4) .htaccess (produção)
Arquivo `.htaccess` na raiz do projeto com:
- bloqueio de listagem de diretórios (`Options -Indexes`)
- bloqueio de acesso a pastas/arquivos sensíveis
- rewrite amigável para front controller
- força de HTTPS (quando o módulo está disponível)
- headers de segurança básicos

---

## 5) Checklist de instalação (passo a passo)

### Banco
- [ ] Entrar no **phpMyAdmin** da HostGator
- [ ] Selecionar banco `santo821_emenda`
- [ ] Importar `database/schema.sql` (ou `sql/schema.sql`)

### Arquivos
- [ ] Enviar projeto para `public_html/emendas`
- [ ] Confirmar presença do `.htaccess` na raiz de `/emendas`
- [ ] Confirmar `app/config/config.php` com dados corretos

### Permissões
- [ ] Pastas com `755` e arquivos com `644`
- [ ] Garantir escrita em `storage/logs` (e pasta de sessões/cache, se usada)

### Smoke tests de produção
- [ ] Acessar login: `https://www.prefsade.com.br/emendas/login`
- [ ] Testar login Master
- [ ] Testar redirecionamento por perfil (Master/Funcionário)
- [ ] Testar troca obrigatória de senha no primeiro login
- [ ] Testar permissões de acesso entre perfis
- [ ] Testar calendário (FullCalendar)
- [ ] Testar tabelas (DataTables)
- [ ] Testar notificações (criação, listagem, marcar como lida)
- [ ] Testar controle de prazo (24h e status atrasada)

---

## 6) Ajustes de produção recomendados
- Manter `APP_DEBUG=false` para ocultar erros sensíveis
- Registrar erros apenas em log (`storage/logs/app.log`)
- Confirmar timezone `America/Sao_Paulo`
- Validar permissões dos diretórios de runtime
- Não versionar `.env`/credenciais sensíveis em repositório público

---

## 7) Troubleshooting rápido
- **Erro 500:** conferir `storage/logs/app.log` e `error_log` da hospedagem
- **404 em rotas:** confirmar `RewriteBase /emendas/` no `.htaccess`
- **Tela em branco:** verificar permissões e `APP_ENV/APP_DEBUG`
- **Falha DB:** revisar credenciais em `app/config/config.php`
```
