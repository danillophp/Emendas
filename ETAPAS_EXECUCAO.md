# Execução em Etapas com Validação de Compatibilidade

## Etapa 1 — Estrutura e dependências base
**Objetivo:** garantir estrutura mínima para runtime em produção HostGator.

### Ações aplicadas
- Validado MVC (`app/Core`, `Controllers`, `Models`, `Views`, `Middleware`, `Services`, `routes`, `database`, `public/assets`).
- Corrigido erro estrutural identificado: criação de `storage/logs/` para compatibilidade com `LOG_PATH`.

### Validação para próxima etapa
- `LOG_PATH` aponta para `storage/logs/app.log` e diretório existe.

---

## Etapa 2 — Banco e conexão
**Objetivo:** garantir conexão PDO e parâmetros de produção.

### Ações aplicadas
- Configuração de produção confirmada em `app/config/config.php`.
- Conexão PDO validada com `utf8mb4`, prepared statements nativos e tratamento de erro.

### Validação para próxima etapa
- Lint de `app/config/config.php` e `app/Core/Database.php` sem erros.

---

## Etapa 3 — Roteamento e base path
**Objetivo:** validar funcionamento em `/emendas`.

### Ações aplicadas
- Router com normalização de `APP_BASE_PATH`.
- Helper `url()` baseado em `APP_BASE_PATH` para evitar quebra por domínio fixo.

### Validação para próxima etapa
- Rotas registradas em `app/config/routes.php` compatíveis com paths renderizados nas views.

---

## Etapa 4 — Autenticação e autorização
**Objetivo:** manter controle de acesso seguro por perfil.

### Itens validados
- Sessão segura, CSRF, first-login password change e middleware por perfil.
- Logout com invalidação de sessão/token.

### Validação para próxima etapa
- Lint dos controllers/middlewares sem erros.

---

## Etapa 5 — Módulo Master
**Objetivo:** validar CRUD e governança de demandas/funcionários.

### Itens validados
- CRUD de funcionários e demandas.
- filtros e notificações.
- atualização de prazo validada no update.

### Validação para próxima etapa
- Lint em `MasterController` e views master sem erros.

---

## Etapa 6 — Módulo Funcionário
**Objetivo:** garantir isolamento das demandas por usuário.

### Itens validados
- funcionário só visualiza demandas próprias.
- conclusão com observação, data/hora e logs.

### Validação para próxima etapa
- Lint em `EmployeeController` e views funcionário sem erros.

---

## Etapa 7 — Notificações e prazos
**Objetivo:** automação robusta sem duplicidade excessiva.

### Itens validados
- serviço `NotificationDeadlineService` centralizado.
- alerta 24h com deduplicação por janela temporal.
- atualização de status `atrasada`.

### Validação para próxima etapa
- Lint em `NotificationDeadlineService`, `Notification`, `Demand` sem erros.

---

## Etapa 8 — Interface e assets
**Objetivo:** manter consistência visual responsiva.

### Itens validados
- shell com sidebar + topbar.
- DataTables e FullCalendar inicializando via `assets/js/app.js`.

### Validação para próxima etapa
- assets sincronizados em `assets/` e `public/assets/`.

---

## Etapa 9 — Segurança e hardening
**Objetivo:** reduzir superfície de ataque em shared hosting.

### Itens validados
- `.htaccess` com bloqueios e rewrite.
- escape `e()` aplicado nas views.
- prepared statements em models.

### Validação para próxima etapa
- Lint global do código PHP sem erros.

---

## Etapa 10 — Deploy HostGator
**Objetivo:** disponibilizar instalação reproduzível.

### Itens validados
- `DEPLOY_HOSTGATOR.md` atualizado com checklist de produção.
- parâmetros de produção preenchidos.

### Checklist de fechamento técnico
- Estrutura pronta
- Banco pronto
- Config pronta
- Módulos prontos
- Segurança/hardening prontos
- Deploy documentado
