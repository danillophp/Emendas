# Sistema de Gestão de Emendas Governamentais

Sistema web completo em **PHP 8+ / MySQL** para gestão de emendas governamentais, com arquitetura MVC organizada por camadas, dois perfis de acesso (Master e Funcionário), segurança básica de produção e compatibilidade com hospedagem compartilhada **HostGator**.

## Stack
- PHP 8+
- MySQL
- PDO (prepared statements)
- Bootstrap 5
- Bootstrap Icons
- FullCalendar
- DataTables
- HTML5 / CSS3 / JavaScript

## Estrutura de pastas (produção)
```text
/emendas
  /app
    /Controllers
    /Models
    /views
    /helpers
    /Middleware
    /Core
    /config
  /config                # ponte para configuração central
  /database
  /public
    /assets
      /css
      /js
      /img
  /routes
  /storage
  /logs
  /assets
  /sql
  /index.php
  /.htaccess
```


## Arquitetura recomendada (versão profissional e escalável)
- Consulte `ESTRUTURA_MVC_HOSTGATOR.md` para a proposta completa com:
  - árvore de diretórios ideal para `/emendas`;
  - finalidade de cada pasta;
  - arquivos principais sugeridos;
  - sequência ideal de implementação;
  - orientação específica para hospedagem compartilhada HostGator.

## Perfis e regras de acesso
### Master
- Acesso total ao painel administrativo.
- CRUD de funcionários.
- Alteração de senha de funcionário.
- Ativação/desativação de funcionário.
- CRUD de demandas e atribuição.
- Dashboard geral com calendário e indicadores.
- Notificações de conclusão e alertas de prazo.

### Funcionário
- Acesso apenas ao próprio painel.
- Visualiza apenas demandas atribuídas.
- Acompanha prazo e tempo restante.
- Conclui demanda com observação de conclusão.

## Regras de funcionário
Campos obrigatórios:
- nome completo
- email
- endereço
- WhatsApp
- número de decreto
- data de nascimento

Regras de credencial inicial:
- usuário padrão = número do decreto
- senha padrão = data de nascimento sem separadores
- senha em hash (`password_hash`)
- validação com `password_verify`
- troca obrigatória no primeiro login
- unicidade de e-mail, decreto e usuário

## Regras de demanda
Status suportados:
- `pendente`
- `em_andamento`
- `concluida`
- `atrasada`

Fluxo:
1. Master cria e atribui a demanda.
2. Funcionário visualiza no painel.
3. Funcionário conclui com observação.
4. Sistema grava data/hora de conclusão.
5. Sistema notifica o Master.
6. Sistema alerta Master quando faltar <=24h.
7. Sistema marca automaticamente `atrasada` quando expirar prazo sem conclusão.


## Módulo de notificações e controle de prazo
- Conclusão de demanda pelo funcionário grava `data_conclusao` e `observacao_conclusao` e envia notificação para o Master com referência da demanda.
- Automação de prazo roda em pontos estratégicos dos painéis (Master e Funcionário):
  - marca demandas vencidas como `atrasada`;
  - gera alerta de 24h para o Master em demandas não concluídas;
  - evita duplicação excessiva com janela de verificação por tipo/referência.
- Área de notificações lista itens mais recentes, exibe contador de não lidas e permite marcar como lida.
- Implementação centralizada em serviço reutilizável: `app/Services/NotificationDeadlineService.php`.

## Segurança implementada
- Sessão segura (strict mode, httponly, samesite, secure em HTTPS).
- CSRF token em formulários críticos.
- Prepared statements com PDO.
- Escape de saída com `e()` (XSS).
- Middleware de autenticação e autorização por perfil.
- Tokens de sessão por usuário para invalidar sessão concorrente.
- Logs básicos de autenticação e ações de negócio.
- Tratamento amigável de erros em produção.

## Configuração central
Arquivo principal:
- `app/config/config.php`

Ponte externa de compatibilidade:
- `config/config.php`

Parâmetros de produção já definidos:
- URL base: `https://www.prefsade.com.br/emendas`
- Base path: `/emendas`
- Banco: `santo821_emenda`
- Usuário: `santo821_emenda`
- Senha: `php@3903.`

## Banco de dados
Scripts de importação:
- `database/schema.sql` (principal)
- `sql/schema.sql` (espelho)

Tabelas:
- `usuarios`
- `demandas`
- `notificacoes`
- `logs_sistema`

## Instalação rápida (HostGator)
1. Suba o projeto para `public_html/emendas`.
2. Importe `database/schema.sql` no phpMyAdmin.
3. Garanta permissão de escrita em `storage/logs/`.
4. Verifique `.htaccess` com `RewriteBase /emendas/`.
5. Acesse `https://www.prefsade.com.br/emendas`.


## Entrega consolidada por etapas
- Consulte `ENTREGA_ETAPAS.md` para o mapa completo da solução em 10 etapas (arquitetura, banco, autenticação, painéis, notificações/prazos, layout, auditoria e deploy).

## Checklist de deploy
Consulte `DEPLOY_HOSTGATOR.md`.
