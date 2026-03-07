# Revisão técnica profunda — produção em `/agenda`

## 1) Causa provável dos erros em produção

Após auditoria focada em hospedagem real (`studiobrunanayara.com.br` + sistema em `/agenda`), os pontos que mais quebram o sistema eram:

1. **Configuração de URL/base path inconsistente** (domínio/base path e geração de links/redirects).
2. **Reescrita Apache sensível ao subdiretório** (`.htaccess` sem estratégia clara para `/agenda`).
3. **Roteador com match apenas estático** (sem normalização robusta e sem rotas com parâmetro em path).
4. **Fluxo de sessão/autenticação suscetível a conflito de cookie com aplicação da raiz** (path global `/`).

## 2) Arquivos corrigidos nesta revisão

- `.htaccess`
- `app/config/config.php`
- `app/config/database.php`
- `app/helpers/helpers.php`
- `app/core/Router.php`
- `app/bootstrap.php`

## 3) Versão final do `.htaccess` do sistema (`/agenda/.htaccess`)

```apache
Options -Indexes
RewriteEngine On
RewriteBase /agenda/

# Bloqueia acesso a arquivos ocultos e sensíveis
RedirectMatch 403 /\..*$
<FilesMatch "^(composer\.(json|lock)|\.env|\.gitignore|phpunit\.xml)$">
    Require all denied
</FilesMatch>

# Headers básicos de segurança
<IfModule mod_headers.c>
    Header always set X-Frame-Options "DENY"
    Header always set X-Content-Type-Options "nosniff"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Mantém acesso a arquivos e diretórios reais (assets/uploads/favicon etc)
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]

# Todas as demais requisições vão para o front controller
RewriteRule ^ index.php [QSA,L]
```

## 4) Configuração central corrigida

`app/config/config.php` agora centraliza:
- `APP_NAME`
- `APP_ENV`
- `APP_DEBUG`
- `APP_URL`
- `APP_BASE_PATH`
- `TIMEZONE`
- bloco `database` com `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_CHARSET`

`app/config/database.php` passou a ler do config central (com `getenv()` + fallback HostGator).

## 5) Helpers globais corrigidos

Implementados/revisados:
- `base_url()`
- `asset_url()`
- `redirect_to()`
- `public_url()`

Exemplos reais:
- `base_url('login')` → `http://studiobrunanayara.com.br/agenda/login`
- `asset_url('assets/css/app.css')` → `http://studiobrunanayara.com.br/agenda/assets/css/app.css`
- `redirect_to('dashboard')` → redireciona para `http://studiobrunanayara.com.br/agenda/dashboard`

## 6) Router corrigido para subdiretório

- Remove o prefixo `APP_BASE_PATH` da URL antes de resolver rotas internas.
- Normaliza múltiplas barras e remove query string via `parse_url`.
- Mantém suporte GET/POST e fallback 404.
- Trata `HEAD` como `GET`.
- Adiciona suporte a parâmetros de rota (`{id}`), preenchendo `$_GET` para compatibilidade com controllers atuais.

Exemplo suportado:
- `/agenda/clientes/editar/5` → rota interna `/clientes/editar/{id}` com `$_GET['id']=5`.

## 7) Login/logout/sessão/redirecionamentos

- Sessão iniciada no bootstrap, com timeout por inatividade.
- `session_regenerate_id(true)` no login.
- Cookie de sessão com `path` baseado em `APP_BASE_PATH` (`/agenda`) para evitar conflito com site da raiz.
- Redirecionamentos padronizados via helper central (`redirect_to`/`base_url`) para evitar `header('Location: /login')` incorreto.

## 8) Revisão de links, formulários e assets

- Fluxos principais já usam helpers (`base_url`/`asset_url`) em views/layouts.
- Actions de formulário e links internos permanecem apontando para `/agenda/*` por geração centralizada.
- Upload/logo continua em caminho público sob `/agenda/uploads/logo/*`.

## 9) Checklist final de validação em produção

- [ ] `http://studiobrunanayara.com.br/agenda`
- [ ] `http://studiobrunanayara.com.br/agenda/login`
- [ ] `http://studiobrunanayara.com.br/agenda/dashboard`
- [ ] CSS e JS carregando sem 404
- [ ] login funcionando
- [ ] logout funcionando
- [ ] sessão persistindo corretamente
- [ ] rota privada redirecionando para `/agenda/login`
- [ ] upload de logo funcionando
- [ ] assets e uploads em `/agenda/assets/*` e `/agenda/uploads/*`
- [ ] site principal da raiz (`/`) sem interferência

## 10) Observação prática de teste local

No servidor embutido do PHP com `-t public`, acessos a `/agenda/assets/*` podem não refletir o comportamento de Apache em subpasta. A validação definitiva deve ser feita no Apache/HostGator conforme checklist acima.


## Regra recomendada no .htaccess da raiz (WordPress)

Para evitar interferência do WordPress no sistema em subpasta, inclua antes das regras do WP:

```apache
RewriteRule ^agenda/ - [L]
```
