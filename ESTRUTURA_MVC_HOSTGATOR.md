# Estrutura Profissional de Pastas — Sistema PHP MVC de Gestão de Emendas

## 1) Árvore de diretórios recomendada (deploy em `/emendas`)

```text
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
```

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
