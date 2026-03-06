# Estúdio Bruna Nayara - Base MVC PHP 8

Estrutura inicial de sistema web em **PHP 8** com arquitetura **MVC**, autenticação com sessão e proteção básica de segurança, compatível com hospedagem compartilhada (ex.: HostGator) e MySQL.

## 1) Estrutura de pastas

```text
/public
  index.php
/app
  /controllers
    AuthController.php
    DashboardController.php
  /models
    User.php
  /views
    /layouts
      app.php
      auth.php
    /auth
      login.php
    /dashboard
      index.php
  /core
    Auth.php
    Controller.php
    Database.php
    Model.php
    Router.php
  /config
    config.php
    database.php
  /helpers
    helpers.php
  bootstrap.php
/storage
  /logs
    .gitkeep
/sql
  users.sql
```

## 2) Requisitos

- PHP 8.0+
- Extensões `pdo` e `pdo_mysql`
- MySQL 5.7+ / 8+

## 3) Instalação local

1. Clone/copiei o projeto para o servidor.
2. Ajuste as configurações em `app/config/database.php` (ou variáveis de ambiente `DB_HOST`, `DB_DATABASE`, etc.).
3. Crie o banco e rode o script SQL inicial:
   - arquivo: `sql/users.sql`
4. Aponte o DocumentRoot para a pasta `/public`.
5. Inicie o servidor local:

```bash
php -S localhost:8000 -t public
```

6. Acesse `http://localhost:8000/login`.

### Credenciais iniciais

- E-mail: `admin@estudio.com`
- Senha: `admin123`

## 4) Segurança aplicada

- **SQL Injection**: uso de PDO com prepared statements.
- **XSS**: escape centralizado com helper `e()`.
- **CSRF**: token por sessão com validação nos formulários POST.
- **Sessão segura**: `httponly`, `samesite=Lax`, regeneração de ID no login.

## 5) Publicação na HostGator (resumo)

- Suba os arquivos via Gerenciador de Arquivos/FTP.
- Configure o domínio/subdomínio para apontar para `/public`.
- Configure as variáveis de ambiente (quando disponível) ou edite `app/config/database.php`.
- Importe `sql/users.sql` no phpMyAdmin.

Projeto pronto para expansão com novos controllers, models e módulos administrativos.
