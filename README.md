# Studio Bruna Nayara - Sistema de Agendamento (PHP 8 MVC)

Sistema web profissional para **agendamento online** de serviços de cílios e limpeza de pele, com foco em arquitetura limpa, segurança e compatibilidade com **HostGator + MySQL**.

## 1) Arquitetura do projeto

- **Padrão MVC** (Controllers, Models, Views).
- **PDO** para acesso ao banco com prepared statements.
- **Camadas separadas**:
  - `core`: infraestrutura base (Router, Controller, Model, Database, Auth, Logger)
  - `controllers`: fluxo HTTP e orquestração
  - `models`: acesso a dados
  - `views`: interface administrativa
  - `helpers`: utilitários globais (CSRF, escape, flash, sanitização)
- **Segurança**: CSRF, escape XSS, sanitização de entrada, sessão segura.
- **Escalável** para integrações futuras com gateway de pagamento, WhatsApp e SMS.

## 2) Estrutura de pastas

```text
/public
  index.php
  .htaccess
/app
  /config
    config.php
    database.php
  /core
    Auth.php
    Controller.php
    Database.php
    Logger.php
    Model.php
    Router.php
  /helpers
    helpers.php
  /controllers
    AuthController.php
    DashboardController.php
    ClientController.php
    ServiceCategoryController.php
    ServiceController.php
  /models
    User.php
    Client.php
    ServiceCategory.php
    Service.php
  /views
    /layouts
      app.php
      auth.php
    /auth
      login.php
    /dashboard
      index.php
    /clients
      index.php
      form.php
    /categories
      index.php
      form.php
    /services
      index.php
      form.php
  bootstrap.php
/storage
  /logs
    .gitkeep
/sql
  schema.sql
  users.sql
```

## 3) Módulos entregues nesta etapa

- ✅ Autenticação (login/logout/sessão/proteção de rotas)
- ✅ Dashboard administrativo inicial
- ✅ CRUD de clientes
- ✅ CRUD de categorias de serviços
- ✅ CRUD de serviços
- ✅ Base pronta para:
  - agenda e bloqueio de horários
  - pré-reserva e confirmação por pagamento de entrada
  - notificações diárias com logs

## 4) Regras de negócio modeladas na base de dados

- Serviço deve ser escolhido antes do agendamento (`agendamentos.servico_id`).
- Estrutura para impedir conflito de horários com validação de janela de tempo (`data_hora_inicio` / `data_hora_fim`).
- Campos financeiros no agendamento:
  - `valor_total`
  - `valor_entrada` (20%)
  - `valor_restante`
- Status de agendamento e pagamento conforme especificação.
- Tabelas de notificações e logs para rotina diária e auditoria.

## 5) SQL inicial completo

Use o arquivo:

- `sql/schema.sql`

Ele contém as tabelas:

- `usuarios`
- `clientes`
- `categorias_servicos`
- `servicos`
- `configuracoes_agenda`
- `bloqueios_agenda`
- `agendamentos`
- `pagamentos`
- `notificacoes`
- `logs_sistema`

Também inclui seed inicial:

- usuário admin
- categorias padrão
- serviços padrão

## 6) Instalação local (desenvolvimento)

1. Configure o PHP 8 com extensões: `pdo`, `pdo_mysql`, `mbstring`.
2. Crie banco MySQL (ex: `studio_bruna_nayara`).
3. Ajuste credenciais em `app/config/database.php` ou variáveis de ambiente:
   - `DB_HOST`
   - `DB_PORT`
   - `DB_DATABASE`
   - `DB_USERNAME`
   - `DB_PASSWORD`
4. Importe `sql/schema.sql`.
5. Inicie servidor local:

```bash
php -S localhost:8000 -t public
```

6. Acesse: `http://localhost:8000/login`

### Credenciais padrão

- E-mail: `admin@studiobrunanayara.com`
- Senha: `admin123`

## 7) Publicação detalhada na HostGator

### 7.1 Preparar banco no cPanel

1. Abra **MySQL® Databases**.
2. Crie banco (`studio_bruna_nayara`).
3. Crie usuário MySQL.
4. Associe usuário ao banco com **ALL PRIVILEGES**.
5. No **phpMyAdmin**, selecione o banco e importe `sql/schema.sql`.

### 7.2 Publicar arquivos

1. Envie o projeto por FTP ou Gerenciador de Arquivos.
2. Estrutura recomendada:
   - aplicação fora de `public_html` (quando possível)
   - conteúdo de `/public` como raiz web
3. Se não puder alterar DocumentRoot:
   - copie conteúdo de `/public` para `public_html`
   - ajuste caminhos absolutos de include no `index.php` conforme estrutura final

### 7.3 Configurar Apache

- Garanta que `.htaccess` em `public` esteja ativo para redirecionar para `index.php`.
- No cPanel, use PHP 8+ em **MultiPHP Manager**.

### 7.4 Configurar credenciais e segurança

1. Edite `app/config/database.php` com dados reais da HostGator.
2. Em produção:
   - mantenha HTTPS ativo
   - `APP_DEBUG=false`
   - permissões seguras para `storage/logs` (ex.: 775)

### 7.5 Cron diário (base para notificações)

No **Cron Jobs** da HostGator, configure 1 execução diária (exemplo 07:00):

```bash
0 7 * * * /usr/local/bin/php /home/SEU_USUARIO/public_html/scripts/daily_notifications.php
```

> A rotina futura deve ler agendamentos do dia, notificar clientes e proprietária e registrar em `notificacoes` + `logs_sistema`.

## 8) Segurança implementada

- PDO com prepared statements (mitiga SQL Injection)
- Escape de saída com `e()` (mitiga XSS)
- CSRF token obrigatório em formulários POST
- Sessão com `httponly`, `samesite=Lax`, regeneração de ID no login
- Registro em log de eventos críticos de autenticação e CRUD

## 9) Próximos passos recomendados

1. Módulo de agenda com geração de slots disponíveis.
2. Validador de conflitos de horários no agendamento.
3. Fluxo de pré-reserva e pagamento de entrada (20%).
4. Integração de notificações (WhatsApp/SMS/e-mail).
5. Relatórios financeiros e exportação CSV/PDF.
