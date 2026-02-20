# Cata Treco

Sistema web completo para gestão municipal de coleta de resíduos volumosos.

## Stack
- PHP 8+
- MySQL
- MVC simples (controllers/models/views/services)
- Frontend com HTML/CSS/JS (Fetch API)
- Google Maps (mapa principal)
- Leaflet + OpenStreetMap (confirmação geográfica)

## Fluxo
Cidadão → Solicitação (`PENDENTE`) → Admin (aprova/recusa/reagenda/atribui) → Funcionário (finaliza) → Notificação WhatsApp.

## Estrutura de pastas
- `app/Controllers`
- `app/Models`
- `app/Views`
- `app/Services`
- `app/Core`
- `assets/css`
- `assets/js`
- `config`
- `sql`
- `uploads`

## Banco de dados
Importe `sql/catatreco.sql` no phpMyAdmin.

Tabelas principais:
- `usuarios`
- `pontos_mapa`
- `solicitacoes`
- `logs`

## Credenciais iniciais
- Admin: `admin@prefeitura.gov.br` / `Admin@123`
- Funcionário: `funcionario@prefeitura.gov.br` / `Func@123`

## Configuração HostGator
1. Suba o projeto para `public_html/catatreco`.
2. Ajuste `config/db.php` com seu banco.
3. Ajuste `config/app.php`:
   - `APP_URL`
   - `GOOGLE_MAPS_API_KEY`
   - credenciais WhatsApp Cloud API (opcional)
4. Garanta escrita em `uploads/`.
5. Acesse `https://SEU_DOMINIO/catatreco/public/index.php`.

## WhatsApp
Arquivo: `app/Services/WhatsAppService.php`.

- Com API ativa envia pela Cloud API.
- Sem API ativa gera fallback `wa.me`.

## Rotas
- `?r=citizen/home`
- `?r=auth/login`
- `?r=admin/dashboard`
- `?r=employee/dashboard`
