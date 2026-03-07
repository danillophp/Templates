# Studio Bruna Nayara — Sistema de Agendamento (PHP 8 + MySQL)

Sistema MVC profissional preparado para produção em **subdiretório `/agenda`**.

## URL de produção

- `http://studiobrunanayara.com.br/agenda`

## Estrutura

```text
/index.php
/.htaccess
/assets
  /css
  /js
  /img
/uploads
  /logo
/app
  /config
  /controllers
  /core
  /helpers
  /models
  /services
  /views
/storage
  /logs
  /uploads
  /cache
/database
  schema.sql
  seeds.sql
```

## Configuração central

### App (`app/config/config.php`)
- `APP_URL`
- `APP_BASE_PATH` (fallback `/agenda`)
- timezone e sessão segura

### Banco (`app/config/database.php`)
Usa `getenv()` com fallback HostGator:
- database: `santo821_studiobrunanayara`
- username: `santo821_studiobrunanayara`
- password: `php@3903.`

## Helpers de subpasta

- `base_url()`
- `asset_url()`
- `redirect_to()`

Todos os links, formulários e redirects principais foram ajustados para respeitar `/agenda`.

## Branding e dados oficiais

- Logo gerenciável por upload em `uploads/logo/` (sem logo fixa em código)
- Dados institucionais em `configuracoes_studio`
- Rodapé com endereço, telefone, redes e localização
- Convite amigável de avaliação Google (ativável no painel)

## Módulos entregues

- Login + logout com sessão segura e CSRF
- Dashboard administrativo
- CRUD clientes
- CRUD categorias
- CRUD serviços
- Agenda/configuração de horários
- Agendamento público e pré-reserva
- Pagamento de entrada de 20%
- Configurações institucionais do Studio
- Convite de avaliação Google (público + confirmação)

## Deploy HostGator (passo a passo)

1. Suba o projeto para o servidor.
2. Publique os arquivos diretamente na pasta `/agenda` (front controller em `/agenda/index.php`).
3. Garanta `mod_rewrite` ativo e `.htaccess` da pasta pública.
4. Importe `database/schema.sql`.
5. Configure variáveis de ambiente (ou use fallback):
   - `APP_URL=http://studiobrunanayara.com.br`
   - `APP_BASE_PATH=/agenda`
   - `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
6. Dê permissão de escrita em `storage/logs`, `storage/uploads`, `storage/cache`.
7. Teste:
   - `/agenda/login`
   - `/agenda/dashboard`
   - `/agenda/agendamento`

## Checklist técnico de validação

- [ ] Login/logout funciona em `/agenda`
- [ ] Todos os assets carregam sem 404 em `/agenda/assets/...`
- [ ] CRUDs redirecionam para URLs com base `/agenda`
- [ ] Agendamento público cria pré-reserva
- [ ] Pagamento confirma entrada e agendamento
- [ ] Rodapé exibe dados oficiais
- [ ] Convite Google aparece quando ativo
- [ ] Logs são gerados em `storage/logs/app.log`
- [ ] Sem referências a sistemas antigos


## Como trocar a logo pelo painel

1. Acesse **Configurações > Studio** no painel administrativo.
2. No campo **Upload da logo**, envie uma imagem PNG, JPG/JPEG ou WEBP (máx. 5MB).
3. Clique em **Salvar configurações**.
4. A logo será salva em `uploads/logo/` com nome único e passará a aparecer automaticamente no login, topo do painel, página pública e rodapé.
5. Se nenhuma logo estiver enviada, o sistema exibe apenas o nome do studio como fallback (sem quebrar layout).

## Segurança de upload

- Validação de extensão e MIME.
- Tamanho máximo de 5MB.
- Renomeação automática para evitar sobrescrita.
- Upload em diretório isolado.
- `.htaccess` em `uploads/logo/` bloqueando execução de scripts e listagem de diretório.


## Diagnóstico de produção em /agenda

Consulte `PRODUCTION_DEPLOY_DIAGNOSIS.md` para causa raiz, correções aplicadas e checklist de validação em produção.
