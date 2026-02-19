# CATA TRECO

Sistema web completo para gestão municipal de coleta de inservíveis, construído com **PHP 8+ (OO/MVC)**, **MySQL**, **Bootstrap 5**, **Leaflet** e **Fetch API**.

## Arquitetura

- Backend OO com MVC simples (`app/Controllers`, `app/Models`, `app/Core`)
- API REST interna em JSON via rotas `?r=api/...`
- Sessão PHP para autenticação
- Logs e trilha de auditoria LGPD
- Front moderno, responsivo e SPA-like (AJAX)

## Banco de dados

- Banco: `santo821_treco`
- Usuário: `catatreco`
- Senha: `php@3903`

Arquivo de conexão: `config/db.php`.
SQL completo: `sql/catatreco.sql`.

## Módulos implementados

1. **Cidadão**: formulário com geolocalização automática Nominatim, mapa Leaflet, upload de foto e envio AJAX.
2. **Login e perfis**: ADMINISTRADOR e FUNCIONARIO com bcrypt.
3. **Painel admin**: cards de status, filtros (data/status/bairro), ações de aprovar/recusar/reagendar/atribuir.
4. **Painel funcionário**: tarefas atribuídas, ligar, WhatsApp, rota, iniciar e finalizar coleta.
5. **WhatsApp automático**: estrutura para Cloud API + fallback wa.me.
6. **LGPD/Auditoria**: consentimento, IP, logs com usuário responsável e estrutura de anonimização.

## Instalação na HostGator

1. Faça upload do projeto para `public_html/catatreco`.
2. Importe `sql/catatreco.sql` no phpMyAdmin.
3. Verifique permissões de escrita em `uploads/` (`775`).
4. Acesse: `https://www.prefsade.com.br/catatreco/public/index.php`.

## Credenciais iniciais

- Admin: `admin` / `Admin@123`
- Funcionário: `funcionario1` / `Func@123`

## Rotas principais

- `?r=citizen/home`
- `?r=auth/login`
- `?r=admin/dashboard`
- `?r=employee/dashboard`

## Observações de produção

- Ative HTTPS e ajuste cookies de sessão com `secure`/`httponly`.
- Configure `WA_TOKEN` e `WA_PHONE_NUMBER_ID` em `config/app.php` para ativar WhatsApp Cloud API.
- Recomenda-se WAF, rate-limit e backup diário do MySQL.
