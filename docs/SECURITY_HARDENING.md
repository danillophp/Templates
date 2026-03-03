# Threat Model, Segurança e Modelagem MySQL (EDUCASADECOM + E-EDUCA)

## 1) Consultas críticas (use cases) e índices derivados
- Login por usuário + validação de bloqueio: `users(username,is_active,locked_until)` + `login_attempts(username,ip_address,attempted_at)`.
- Painel mapa público por tipo/região/vagas: `schools(type,region,is_active)` + `school_stats(school_id,reference_date)`.
- Alertas de estoque por validade e quantidade: `inventory_batches(expiry_date,quantity_current)` + `inventory_movements(school_id,created_at)`.
- Lista pública de notícias/documentos por data/categoria: `news(status,published_at)` + `documents(category,year_ref,is_public)` + FULLTEXT para busca textual.
- Auditoria por entidade/período/ator: `audit_log(entity,entity_id,created_at)` e `audit_log(actor_user_id,created_at)`.

## 2) Threat model (ameaças prioritárias)
1. Credenciais comprometidas por brute force -> mitigado com rate limit MySQL + bloqueio progressivo.
2. Sequestro de sessão -> cookie HttpOnly/SameSite=Strict + `session_regenerate_id` no login.
3. CSRF em operações administrativas -> token CSRF obrigatório em formulários POST.
4. SQL Injection -> somente PDO com prepared statements.
5. XSS armazenado/refletido -> escaping centralizado (`Security::e`) nas views.
6. Upload malicioso -> validação MIME real, extensão permitida, nome com hash e bloqueio de execução em `/storage/uploads/.htaccess`.
7. Fraude/negação de ações -> auditoria com before/after, IP, user-agent e exportação CSV para evidência.
8. Exposição LGPD -> no módulo público apenas agregados (`school_stats`), sem dados pessoais de alunos.

## 3) Modelo conceitual e lógico (resumo)
- **Identidade e acesso:** `users` N:N `roles` via `user_roles`; `roles` N:N `permissions` via `role_permissions`.
- **Educação:** `schools` 1:N `school_photos`, `school_staff`, `school_stats`, `menus`, `calendars`.
- **Estoque:** `inventory_items` 1:N `inventory_batches`; `inventory_batches` 1:N `inventory_movements`; `alerts` para eventos operacionais.
- **Portal público:** `news`, `events`, `documents`, `document_tags`, `transparency_items`, `sic_requests`.
- **Integração:** `integrations` 1:N `integration_runs` 1:N `sync_logs`.
- **Governança:** `audit_log` para trilha completa; `cache_store`/`cache_tags` para performance com invalidação por tag.

## 4) Controles implementados
- Password hashing com `password_hash/password_verify`.
- 2FA opcional por TOTP (sem libs externas).
- RBAC por permissões com middleware obrigatório em todas as rotas `/admin/*` e `/api/admin/*`.
- Headers de segurança (CSP, X-Frame-Options, nosniff, Referrer-Policy).
- Logs técnicos em arquivo fora do webroot (`storage/logs/app.log`).

## 5) Hardening HostGator (checklist)
- Permissões: `public/` leitura; `storage/uploads` e `storage/logs` escrita restrita (750).
- Confirmar `.htaccess` raiz e `/public/.htaccess` ativos.
- Habilitar HTTPS e forçar redirecionamento para TLS.
- Manter `.env` fora do versionamento e com credenciais exclusivas por ambiente.
- Backup diário de banco e semanal de uploads.
- Revisão periódica de `audit_log` e tentativas em `login_attempts`.
