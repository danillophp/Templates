# Cata Treco — Versão Profissional (HostGator /catatreco)

Sistema institucional para prefeitura com arquitetura SaaS simplificada, seguro e compatível com hospedagem compartilhada.

## URL e pasta de instalação
- URL final: `https://prefsade.com.br/catatreco`
- Pasta: `public_html/catatreco`

## Estrutura principal
- `index.php` (front controller na raiz)
- `.htaccess` (rewrite + HTTPS + proteção de listagem)
- `config/app.php`
- `config/database.php` e `config/db.php` (compatibilidade)
- `app/Core/ErrorHandler.php`
- `resources/views/errors/500.php`
- `storage/logs/app.log`

## Configuração rápida
### 1) Banco de dados
Edite `config/database.php`:
- host
- database
- username
- password

### 2) App em subpasta
`config/app.php` já está pronto para:
- `APP_URL = https://prefsade.com.br/catatreco`
- `APP_BASE_PATH = /catatreco`
- `APP_DEFAULT_TENANT = 1`

### 3) Importe o SQL
Importe `sql/catatreco.sql` via phpMyAdmin.

### 4) Permissões
- `uploads/` com escrita (775)
- `storage/logs/` com escrita (775)

## Segurança implementada
- PDO + prepared statements
- CSRF
- Validação server-side e sanitização
- Upload seguro por MIME real
- Rate limit de login
- Senha com bcrypt (`password_hash`/`password_verify`)
- Session fixation protection (`session_regenerate_id` no login)
- Headers de segurança:
  - `X-Frame-Options`
  - `X-Content-Type-Options`
  - `Content-Security-Policy`
- Tratamento centralizado de erro 500 com log em arquivo e tela amigável em produção

## Multi-tenant simplificado (sem subdomínio obrigatório)
Fallback do tenant:
1. `?tenant=slug` ou `?tenant=id`
2. subdomínio (quando existir)
3. `APP_DEFAULT_TENANT`
4. primeira prefeitura ativa

## Mapa gratuito
- Leaflet.js
- OpenStreetMap
- Nominatim

Sem API paga, sem cartão de crédito.

## Fluxo público
Ao abrir `https://prefsade.com.br/catatreco`:
- formulário cidadão abre imediatamente
- mapa funciona sem login
- erros técnicos não são exibidos ao cidadão

## Checklist definitivo de produção
- [ ] HTTPS ativo e redirecionando 100%
- [ ] `APP_ENV=production` e `APP_DEBUG=false`
- [ ] Banco conectado e testado
- [ ] Tenant padrão existente (`id=1`) ativo
- [ ] Permissões em `uploads/` e `storage/logs/`
- [ ] `Options -Indexes` ativo no `.htaccess`
- [ ] Teste de envio de formulário
- [ ] Teste de upload (jpg/png/webp)
- [ ] Teste de login e logout
- [ ] Teste de bloqueio por tentativas de login
- [ ] Teste de sessão (regeneração no login)
- [ ] Teste de erro forçado (checar tela amigável + `storage/logs/app.log`)
- [ ] Backup diário do banco (via painel HostGator)

## Testes recomendados antes de ir ao ar
1. Abrir home pública
2. Enviar solicitação completa
3. Consultar protocolo
4. Aprovar no admin
5. Finalizar no funcionário
6. Validar logs de auditoria


## URLs úteis (subpasta /catatreco)
- Público: `https://prefsade.com.br/catatreco/`
- Admin: `https://prefsade.com.br/catatreco/admin`
- Login: `https://prefsade.com.br/catatreco/login`
- Funcionário: `https://prefsade.com.br/catatreco/funcionario`
