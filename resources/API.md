# API Interna - Cata Treco SaaS

Base de produção (HostGator): `https://dominio.com/catatreco`

A API aceita tanto rotas amigáveis quanto fallback com query string (`?r=`), mantendo compatibilidade em hospedagem compartilhada.

## Solicitações
### GET `/api/solicitacoes`
Lista solicitações do tenant autenticado.

### POST `/api/solicitacoes`
Cria solicitação (mesmo payload do formulário cidadão).

### PATCH `/api/solicitacoes/{id}`
Atualiza status de solicitação.
Campos esperados no body: `_csrf`, `status`.
Status aceitos: `PENDENTE`, `APROVADO`, `RECUSADO`, `FINALIZADO`.

## Dashboard
### GET `/api/dashboard`
Retorna dados analíticos para o painel admin.

## Cidadão
### GET `/api/citizen/points`
Pontos ativos da prefeitura.

### POST `/api/citizen/create`
Cria solicitação.

### GET `/api/citizen/track?protocol=...&phone=...`
Consulta status por protocolo + telefone.

## Admin
### GET `/api/admin/requests`
Lista solicitações com filtros opcionais `status` e `date`.

### POST `/api/admin/update`
Aprova, recusa, reagenda e atribui funcionário.

### POST `/api/admin/point/create`
Cria novo ponto do mapa.
