# Sistema de Gestão de Emendas Governamentais

Aplicação PHP 8+ / MySQL pronta para produção em HostGator, com arquitetura MVC simples, segurança básica e rotas amigáveis.

## Estrutura de pastas recomendada
- `app/`
  - `config/` (configuração central)
  - `Core/` (Router, Controller, Database)
  - `Middleware/`
  - `Controllers/`
  - `Models/`
  - `views/`
  - `helpers/`
- `assets/` (CSS/JS)
- `database/` (script SQL principal)
- `sql/` (script SQL espelho)
- `routes/`
- `storage/logs/`
- `index.php`
- `.htaccess`

## Configuração central (produção)
Arquivo: `app/config/config.php`

Já configurado para o contexto solicitado:
- URL base: `https://www.prefsade.com.br/emendas`
- base path: `/emendas`
- banco: `santo821_emenda`
- usuário: `santo821_emenda`
- senha: `php@3903.`

## Segurança implementada
- PDO com prepared statements (proteção contra SQL Injection)
- Escape de saída com `e()` (proteção contra XSS)
- CSRF token em formulários críticos
- Sessão endurecida (`httponly`, `samesite`, strict mode)
- Senhas com `password_hash` / `password_verify`
- Logs básicos em `storage/logs/app.log`
- `.htaccess` com bloqueio de diretórios sensíveis e security headers

## Arquivos-chave de produção
- `app/config/config.php`
- `app/Core/Database.php`
- `database/schema.sql`
- `sql/schema.sql`
- `.htaccess`
- `DEPLOY_HOSTGATOR.md`

## Instalação rápida (HostGator)
1. Subir projeto para `public_html/emendas`.
2. Importar `database/schema.sql` no phpMyAdmin.
3. Garantir permissão de escrita em `storage/logs/`.
4. Acessar `https://www.prefsade.com.br/emendas`.

## Checklist de deploy
Consulte: `DEPLOY_HOSTGATOR.md`.
