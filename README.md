# Cata Treco — Versão Profissional (HostGator /catatreco)

## URL e pasta
- URL: `https://prefsade.com.br/catatreco`
- Pasta: `public_html/catatreco`
- Front controller da raiz: `index.php` (inclui `public/index.php`, sem redirect)

## Configuração rápida
1. **Upload via FTP** de todo conteúdo para `public_html/catatreco`.
2. **Banco MySQL**: importar `sql/catatreco.sql` (ou `database/schema.sql` + `database/seed.sql`).
3. **Credenciais** em `config/database.php` e compatibilidade em `config/db.php`.
4. **Subpasta** em `config/app.php`:
   - `APP_URL=https://prefsade.com.br/catatreco`
   - `APP_BASE_PATH=/catatreco`
   - `APP_DEFAULT_TENANT=1`
5. **Permissões** (775): `uploads/`, `storage/logs/`, `storage/reports/`.
6. **Cron** (cPanel):
   - `*/5 * * * * /usr/local/bin/php /home/USUARIO/public_html/catatreco/cron/process_queue.php`

## Segurança implementada
- PDO + prepared statements.
- CSRF no admin.
- Sanitização e validação server-side.
- Upload seguro por MIME real (`jpeg/png/webp`).
- Rate limit de login (5 tentativas em 15 min por sessão/login).
- `password_hash`/`password_verify` + `session_regenerate_id(true)`.
- Headers: `X-Frame-Options`, `X-Content-Type-Options`, `Content-Security-Policy`.
- ErrorHandler centralizado com log em `storage/logs/app.log` e tela 500 amigável em produção.

## Fluxo principal
- Público `/`: formulário + mapa Leaflet/OSM, ViaCEP + Nominatim.
- Consulta `/protocolo` ou `/consultar` por protocolo/telefone.
- Login `/login` apenas usuário/e-mail + senha (sem WhatsApp).
- Admin `/admin` e `/admin/dashboard` com polling de novos agendamentos.
- Fila de e-mails em `mensagens_fila` + processamento via cron.

## Loop de redirect (ERR_TOO_MANY_REDIRECTS)
- `index.php` raiz inclui `public/index.php` diretamente.
- `.htaccess` reescreve para `public/index.php` sem forçar HTTPS.
- `public/.htaccess` sem redirecionamentos.

## Checklist de produção
- HTTPS ativo no domínio.
- `APP_ENV=production` e `APP_DEBUG=false`.
- Banco conectado.
- `tenant` padrão ativo (`APP_DEFAULT_TENANT=1`).
- Permissões em `uploads/` e `storage/logs/`.
- `Options -Indexes` ativo.
- Testes de formulário, upload, login/logout, rate limit e erro 500.
