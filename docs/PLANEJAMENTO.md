# Planejamento inicial (antes do código)

## a) Diagrama de módulos (texto)
- **Portal Público**: páginas institucionais, notícias, documentos, transparência, primeira infância, contato e mapa público.
- **E-EDUCA Restrito (Admin)**: autenticação, RBAC, dashboard, cadastros (escolas, conteúdos), estoque alimentar e integrações.
- **API Interna**:
  - `/api/public/*` para portal e mapa.
  - `/api/admin/*` para painel e integrações.
- **Camada de Dados (MySQL)**: entidades institucionais, estoque, integrações e auditoria.
- **Segurança transversal**: CSRF, sessão segura, prepared statements, validação/sanitização, trilha de auditoria e segregação público/privado LGPD.

## b) Modelo de dados (tabelas + relacionamentos)
- `roles (1) -> users (N)`
- `users (1) -> news/documents/stock_movements/sync_logs/audit_logs (N)`
- `schools (1) -> school_photos (N)`
- `schools (1) -> stock_movements (N)`
- `stock_items (1) -> stock_movements (N)`
- `integrations (1 por provider)`
- `sic_requests`, `contact_messages` independentes para atendimento ao cidadão.

## c) Endpoints REST
### Públicos
- `GET /index.php?r=api/public/escolas`
- `GET /index.php?r=api/public/noticias`

### Restritos
- `GET /index.php?r=api/admin/estoque/alertas`

### Backoffice HTTP (forms)
- `POST /index.php?r=admin/escolas/salvar`
- `POST /index.php?r=admin/noticias/salvar`
- `POST /index.php?r=admin/documentos/salvar`
- `POST /index.php?r=admin/estoque/movimentar`

## d) Plano de implementação em etapas
1. **MVP Base**: schema MySQL, autenticação, RBAC, rotas, páginas públicas principais, API pública.
2. **Gestão Essencial**: CRUD escolas, notícias, documentos, contato, dashboard.
3. **Estoque**: itens, movimentações, alertas e endpoint de alertas.
4. **Transparência + SIC**: estruturas de páginas e tabelas para protocolos.
5. **Integrações simuladas**: credenciais, importação manual e logs de sincronização.
6. **Hardening**: checklist HostGator, upload seguro, logs de auditoria e revisão LGPD.
