# CATA TRECO

Sistema web institucional para gestão municipal de coleta de resíduos volumosos, construído com **PHP 8+ (OO/MVC)**, **MySQL**, **Bootstrap 5**, **Leaflet** e **Fetch API**.

## Arquitetura

- Backend OO com MVC simples (`app/Controllers`, `app/Models`, `app/Core`)
- API REST interna em JSON via rotas `?r=api/...`
- Sessão PHP para autenticação e perfis (ADMIN e FUNCIONARIO)
- Logs e trilha de auditoria LGPD
- Front moderno, responsivo e dinâmico (AJAX)

## Banco de dados (HostGator)

- Banco: `santo821_treco`
- Usuário: `santo821_catatreco`
- Senha: `php@3903`

Arquivo de conexão: `config/db.php`.
Script SQL completo: `sql/catatreco.sql`.

## Funcionalidades

1. **Módulo do cidadão**
   - Formulário moderno com validações e envio AJAX.
   - Mapa Leaflet + OpenStreetMap.
   - Geocoding automático Nominatim por endereço/CEP.
   - Upload de foto + consentimento LGPD + IP + status inicial `PENDENTE`.

2. **Login e perfis**
   - Senha com bcrypt.
   - Acesso por perfil ADMINISTRADOR e FUNCIONARIO.

3. **Painel administrativo**
   - Cards de indicadores (Pendentes, Aprovadas, Em andamento, Finalizadas).
   - Filtros por data, status e localidade.
   - Ações: aprovar, recusar, alterar data/hora, atribuir funcionário.

4. **Painel do funcionário**
   - Coletas atribuídas com dados completos do cidadão.
   - Botões Ligar, WhatsApp, Como chegar, foto.
   - Mapa por coleta + ações iniciar/finalizar.

5. **WhatsApp automático**
   - Estrutura pronta para WhatsApp Cloud API com templates oficiais.
   - Fallback automático via `wa.me`.

6. **LGPD, segurança e auditoria**
   - Consentimento explícito.
   - Registro de IP e data/hora.
   - Log de ações administrativas e operacionais.
   - Estrutura preparada para anonimização futura.

## Instalação na HostGator

1. Envie os arquivos para `public_html/catatreco`.
2. Importe `sql/catatreco.sql` no phpMyAdmin.
3. Confirme credenciais em `config/db.php`.
4. Garanta permissão de escrita em `uploads/` (ex.: `775`).
5. Acesse: `https://www.prefsade.com.br/catatreco/public/index.php`.

## Credenciais iniciais

- Admin: `admin` / `Admin@123`
- Funcionário: `funcionario1` / `Func@123`

> Altere as senhas imediatamente em produção.

## Rotas principais

- `?r=citizen/home`
- `?r=auth/login`
- `?r=admin/dashboard`
- `?r=employee/dashboard`

## Produção

- Ative HTTPS e cookies de sessão seguros.
- Configure `WA_TOKEN`, `WA_PHONE_NUMBER_ID` e templates em `config/app.php`.
- Recomendado: backup diário, monitoramento e WAF.
