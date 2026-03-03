# EDUCASADECOM + E-EDUCA

Plataforma da Secretaria Municipal de Educação (Santo Antônio do Descoberto/GO) com portal público e backoffice administrativo, compatível com hospedagem compartilhada (HostGator), usando PHP 8.2 + MySQL 8.

## Fluxo de entrega solicitado
1. **Threat model** e controles de segurança: `docs/SECURITY_HARDENING.md`.
2. **SQL completo (DDL + seeds)**: `sql/educasade.sql`.
3. **Código de segurança**: autenticação com rate limit/2FA opcional, RBAC por permissões, auditoria e upload seguro.

## Principais recursos
- Portal público: home, secretaria, notícias, documentos, transparência, primeira infância, contato e mapa.
- Admin: escolas, notícias, documentos, estoque, auditoria (filtro e exportação CSV).
- API pública e administrativa.
- Cache interno em MySQL (TTL + tags) com ETag/Last-Modified.
- Auditoria detalhada (`audit_log`) com before/after, IP e user-agent.

## Segurança implementada
- `password_hash` / `password_verify`.
- Rate limit de login em MySQL + bloqueio progressivo.
- Sessão segura (`HttpOnly`, `SameSite=Strict`, regeneração de sessão).
- 2FA opcional via TOTP sem dependência externa.
- RBAC por `roles`, `permissions`, `role_permissions`, `user_roles`.
- CSRF, PDO prepared statements e escaping centralizado.
- Upload seguro (MIME real, extensão permitida, nome por hash e bloqueio de execução).
- Logs técnicos fora do webroot (`storage/logs/app.log`).

## Instalação (HostGator)
1. Publique o projeto e aponte o domínio para `public/` (ou use `.htaccess` da raiz conforme ambiente).
2. Configure `config/db.php` e (opcional) `.env` local.
3. Importe `sql/educasade.sql` no MySQL.
4. Ajuste permissões: `storage/uploads`, `storage/logs`, `storage/cache`.
5. Login inicial (seed): `superadmin`.

## Documentação
- `docs/PLANEJAMENTO.md`: arquitetura e roadmap funcional.
- `docs/CACHE.md`: desenho do cache MySQL.
- `docs/SECURITY_HARDENING.md`: ameaças, modelagem, índices e checklist OWASP/HostGator.
