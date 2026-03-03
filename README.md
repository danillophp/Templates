# EDUCASADECOM + E-EDUCA

Plataforma web da Secretaria Municipal de Educação de Santo Antônio do Descoberto (GO), com portal público + módulo restrito de gestão, em **PHP 8.2** e **MySQL 8**, otimizada para **HostGator compartilhada**.

## Módulos
- Portal institucional público.
- Primeira Infância.
- Transparência + SIC/e-SIC (base estrutural).
- E-EDUCA (admin): escolas/creches, conteúdos e estoque.
- APIs públicas e administrativas.
- Cache interno em MySQL com TTL + tags + ETag/Last-Modified.

## Estrutura
- `app/Core`: roteamento, auth, csrf, db, headers.
- `app/Controllers`: público, admin, auth, api.
- `app/Models`: persistência via PDO prepared statements.
- `app/Services`: `CacheService` e `AuditService`.
- `app/Views`: frontend responsivo Bootstrap 5.
- `sql/educasade.sql`: schema completo incluindo cache.
- `docs/PLANEJAMENTO.md`: desenho de módulos e roadmap.
- `docs/CACHE.md`: desenho do cache, SQL, estratégia e testes.

## Instalação rápida (HostGator)
1. Upload para `public_html/educa`.
2. Ajuste `config/db.php`.
3. Importe `sql/educasade.sql`.
4. Permissões de escrita em `storage/uploads`, `storage/logs`, `storage/cache`.
5. Acesse `index.php?r=home`.

## Segurança
- CSRF em formulários críticos.
- Sessão segura (`HttpOnly`, `SameSite`).
- RBAC por perfis.
- Prepared statements.
- Auditoria (`audit_logs`).
- Upload protegido (`storage/uploads/.htaccess`).
- LGPD: público só com dados agregados.
