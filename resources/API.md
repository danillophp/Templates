# API Interna - Cata Treco SaaS

Base local: `/?r=`

## Solicitações
### GET `/api/solicitacoes`
Lista solicitações do tenant autenticado.

### POST `/api/solicitacoes`
Cria solicitação (mesmo payload do formulário cidadão).

### PATCH `/api/solicitacoes`
Atualiza status de solicitação.
Campos: `_csrf`, `id`, `status`.

## Dashboard
### GET `/api/dashboard`
Retorna dados analíticos para o painel admin.

## Cidadão
### GET `/api/citizen/points`
Pontos ativos da prefeitura.

### POST `/api/citizen/create`
Criar solicitação.

### GET `/api/citizen/track&protocol=...&phone=...`
Consulta status por protocolo + telefone.

## Admin
### GET `/api/admin/requests`
Lista solicitações com filtros opcionais `status` e `date`.

### POST `/api/admin/update`
Aprova, recusa, reagenda e atribui funcionário.

### POST `/api/admin/point/create`
Cria novo ponto do mapa.
