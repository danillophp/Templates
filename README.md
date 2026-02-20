# Cata Treco SaaS (Multi-Tenant)

Sistema SaaS multi-tenant para gestão municipal de coleta de resíduos volumosos.

## Arquitetura
- **PHP 8+**, **MySQL**, MVC simples e legível.
- Multi-tenant em banco único por `tenant_id`.
- Isolamento por subdomínio (`prefeitura1.catatreco.com`) ou `?tenant=slug` para ambiente local.
- Perfis: `super_admin`, `admin`, `funcionario`, cidadão.

## Estrutura de pastas
- `app/Core`
- `app/Controllers`
- `app/Models`
- `app/Services`
- `app/Middlewares`
- `app/Helpers`
- `public`
- `resources`
- `config`
- `storage`
- `database`
- `scripts`

## Banco de dados
Importe `sql/catatreco.sql` (ou `database/schema.sql`) no phpMyAdmin.

### Tabelas
- `super_admin`
- `tenants`
- `planos`
- `assinaturas`
- `usuarios`
- `pontos_mapa`
- `solicitacoes`
- `logs`
- `notificacoes`
- `configuracoes`
- `pagamentos`

## Login inicial
- Super Admin: `owner@catatreco.com` / `Admin@123`
- Admin prefeitura demo: `admin@prefdemo.gov.br` / `Admin@123`
- Funcionário demo: `funcionario@prefdemo.gov.br` / `Func@123`

## Funcionalidades entregues
- Cadastro e resolução de tenant por subdomínio.
- Super Admin com métricas globais e criação de prefeitura.
- Admin da prefeitura com:
  - gestão de pontos;
  - gestão de solicitações;
  - gráfico de solicitações por mês (Chart.js);
  - exportação CSV.
- Funcionário com painel de coletas e finalização.
- Cidadão com mapa, formulário AJAX, protocolo `CAT-ANO-ID` e consulta por telefone+protocolo.
- WhatsApp Cloud API por prefeitura com retry/fallback `wa.me` e log de envio.
- Segurança: CSRF, prepared statements, validação server-side, upload MIME, rate limit de login.

## API interna
Ver `resources/API.md`.

## Cron jobs
Executar diariamente:
```bash
php scripts/cron.php
```

## HostGator (compartilhada)
1. Subir projeto para `public_html/catatreco`.
2. Importar SQL no phpMyAdmin.
3. Ajustar `config/db.php`.
4. Ajustar `config/app.php` (`APP_URL`, `APP_BASE_PATH`, `GOOGLE_MAPS_API_KEY`, `APP_DEFAULT_TENANT`).
5. Garantir permissão de escrita em `uploads/` e `storage/`.
6. Apontar subdomínios para a pasta `public/`.

## Migração futura (VPS/Cloud)
- Código já separado por camadas.
- Serviços desacoplados (tenant, whatsapp, billing).
- API interna pronta para app mobile.

## Resiliência de tenant (acesso público)
- Se não houver subdomínio válido, o sistema tenta `APP_DEFAULT_TENANT`.
- Se o tenant padrão não existir, usa a primeira prefeitura ativa.
- Se ainda não houver tenant ativo, a página pública mostra aviso amigável e seletor de prefeitura (sem erro técnico).

## Ajuste de hospedagem compartilhada (/catatreco)
- Entrada principal: `index.php` na raiz do projeto (`/catatreco/index.php`).
- `APP_BASE_PATH` deve ser `/catatreco` (sem `/public`).
- `.htaccess` da raiz faz rewrite para `index.php` e força HTTPS.
- `config/db.php` deve usar usuário do cPanel: `santo821_catatreco`.
- A URL `https://www.prefsade.com.br/catatreco` deve abrir direto o formulário público.
