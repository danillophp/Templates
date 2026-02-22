# Cata Treco (PHP MVC)

## Deploy na HostGator (subpasta)
1. Faça upload dos arquivos para `public_html/catatreco`.
2. Crie banco MySQL e importe `database/schema.sql` e `database/seed.sql`.
3. Ajuste `config/database.php` com credenciais.
4. Ajuste `config/app.php` (`APP_URL` e `APP_BASE_PATH=/catatreco`).
5. Garanta permissão de escrita em `storage/logs`, `storage/reports` e `uploads`.
6. Configure cron (cPanel):
   - `*/5 * * * * /usr/local/bin/php /home/USUARIO/public_html/catatreco/cron/process_queue.php`

## Rotas principais
- `/` formulário com Leaflet + ViaCEP + Nominatim.
- `/protocolo` consulta por protocolo ou telefone.
- `/login` admin por usuário/e-mail + senha.
- `/admin/dashboard` gestão de agendamentos e polling.

## Segurança e arquitetura
- MVC simples em PHP 8+, PDO prepared statements, CSRF no admin, upload seguro básico.
- Error handler com log em `storage/logs/app.log` em produção.
- Fila de mensagens em `mensagens_fila` e processamento por cron.

## Observações
- Não há login por WhatsApp.
- Notificação em tempo real no painel via polling (10-15s).
