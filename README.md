# Cata Treco (produção em subpasta `/catatreco`)

## Requisitos
- PHP 8+
- MySQL 5.7+/8
- Apache com `mod_rewrite`

## Estrutura
Sistema MVC em PHP puro com entrada em `index.php` na raiz `public_html/catatreco`.

## Deploy na HostGator
1. Envie os arquivos para `public_html/catatreco` via FTP.
2. Crie o banco MySQL no cPanel.
3. Importe `database/schema.sql` e depois `database/seed.sql` no phpMyAdmin.
4. Ajuste `config/database.php` com host, porta, nome do banco, usuário e senha.
5. Ajuste `config/app.php` mantendo:
   - `APP_URL=https://prefsade.com.br/catatreco`
   - `APP_BASE_PATH=/catatreco`
6. Permissões de escrita:
   - `uploads/`
   - `storage/logs/`
   - `storage/reports/`
7. Configure cron no cPanel (a cada minuto):
   - `php /home/SEU_USUARIO/public_html/catatreco/cron/process_queue.php`

## Rotas principais
- `GET /catatreco/` Home + formulário do cidadão
- `POST /catatreco/solicitar` Salva solicitação
- `GET /catatreco/comprovante?id=` Comprovante
- `GET /catatreco/protocolo` Consulta por protocolo/telefone
- `GET|POST /catatreco/login` Login admin
- `GET|POST /catatreco/forgot-password` Recuperação de senha
- `GET /catatreco/admin/dashboard` Painel
- `POST /catatreco/admin/request/update` Ações admin
- `GET|POST /catatreco/admin/points` Pontos de coleta
- `GET /catatreco/admin/reports` Relatórios CSV/PDF
- `GET /catatreco/api/poll` Polling de notificações
- `GET /catatreco/app/api/poll_novos_agendamentos.php` endpoint alternativo exigido

## Teste rápido pós deploy
1. Acesse `/catatreco/` e faça uma solicitação com CEP válido de Santo Antônio do Descoberto/GO.
2. Confira geração de protocolo e comprovante.
3. Acesse `/catatreco/login` com:
   - usuário: `admin`
   - senha: `admin123`
4. Em dashboard, confira agendamentos do dia e notificação por polling.
5. Faça ação de aprovar/recusar e execute cron para processar fila.
6. Teste exportação CSV e PDF.

## Observações
- Upload de foto é validado por MIME e tamanho.
- Sem login por WhatsApp.
- Quando API WhatsApp não estiver configurada, sistema usa fallback manual (`wa.me`).
