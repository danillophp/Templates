# Educa SADE - Portal + E-EDUCA

Ecossistema web para a Secretaria Municipal de Educação de Santo Antônio do Descoberto (GO), compatível com **HostGator shared hosting**, **PHP 8.2+** e **MySQL 8**.

## Estrutura de pastas

- `public/`: front controller web (`index.php`) e assets públicos.
- `app/Core`: núcleo (Router, Controller, Auth, Csrf, Database).
- `app/Controllers`: controllers públicos, admin, auth e API.
- `app/Models`: acesso a dados com PDO + prepared statements.
- `app/Services`: serviços transversais (auditoria).
- `app/Views`: páginas portal, admin e autenticação.
- `config/`: configurações da aplicação e banco.
- `sql/`: script completo de banco (`educasade.sql`) + migrations.
- `storage/uploads`: uploads (com proteção via `.htaccess`).
- `storage/logs`: logs técnicos.
- `docs/`: planejamento e documentação técnica.

## Instalação (HostGator)
1. Suba os arquivos para `public_html/educa`.
2. Configure `config/db.php` com usuário/senha reais.
3. Importe `sql/educasade.sql` no phpMyAdmin.
4. Garanta permissão de escrita para `storage/uploads` e `storage/logs`.
5. Configure DocumentRoot para `/public` (ou mantenha `index.php` raiz redirecionando para `public/index.php`).
6. Acesse `/index.php?r=home`.

## Segurança aplicada
- PDO com prepared statements.
- CSRF em formulários sensíveis.
- Sessão com `HttpOnly` + `SameSite=Lax`.
- RBAC por perfis: super_admin, secretaria, gestor, diretor, estoque, conteudo.
- Auditoria em `audit_logs` para ações críticas.
- Dados públicos apenas agregados (LGPD).

## Checklist rápido
- [ ] Trocar senha do usuário seed (`superadmin`).
- [ ] Ativar HTTPS obrigatório no domínio.
- [ ] Restringir acesso a arquivos sensíveis por `.htaccess`.
- [ ] Validar MIMEs/extensões de upload em produção.
- [ ] Configurar rotina de backup diário do MySQL.
