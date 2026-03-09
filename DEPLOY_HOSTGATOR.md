# Deploy em HostGator - Sistema de Gestão de Emendas

## 1) Pré-requisitos
- PHP 8+
- MySQL disponível no cPanel
- mod_rewrite habilitado
- Acesso ao `public_html`

## 2) Upload da aplicação
1. Envie todo o conteúdo do projeto para:
   - `public_html/emendas`
2. Verifique se o `.htaccess` está presente na raiz da aplicação.

## 3) Banco de dados
1. No cPanel, confirme o banco e usuário:
   - Banco: `santo821_emenda`
   - Usuário: `santo821_emenda`
   - Senha: `php@3903.`
2. No phpMyAdmin, selecione o banco e importe:
   - `database/schema.sql` (ou `sql/schema.sql`)

## 4) Configuração da aplicação
Arquivo central: `app/config/config.php`
- `APP_URL`: `https://www.prefsade.com.br/emendas`
- `APP_BASE_PATH`: `/emendas`
- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` conforme cPanel
- `APP_ENV=production` e `APP_DEBUG=false`

## 5) Permissões
- Garanta escrita em:
  - `storage/logs/`
- Em geral: 755 para pastas e 644 para arquivos.

## 6) Checklist final
- [ ] Acesso ao login em `https://www.prefsade.com.br/emendas`
- [ ] Login do Master funcionando
- [ ] CRUD de funcionários funcionando
- [ ] CRUD de demandas funcionando
- [ ] Notificações funcionando
- [ ] Logs sendo gravados em `storage/logs/app.log`
- [ ] Rotas amigáveis funcionando (sem `index.php` na URL)
- [ ] Bloqueio de acesso às pastas sensíveis testado

## 7) Troubleshooting rápido
- Erro 500: revisar `storage/logs/app.log` e `error_log` da hospedagem.
- Página em branco: confirmar `APP_ENV`/`APP_DEBUG` e permissões de arquivo.
- URL quebrada: confirmar `RewriteBase /emendas/` no `.htaccess`.
