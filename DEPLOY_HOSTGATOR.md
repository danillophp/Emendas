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
