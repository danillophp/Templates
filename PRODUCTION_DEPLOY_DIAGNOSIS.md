# Diagnóstico técnico — funcionamento em `/agenda`

## Causa raiz identificada
Durante a revisão, os principais pontos que costumam quebrar em produção para subdiretório `/agenda` foram:

1. **`RewriteBase` rígido** no `.htaccess` público, que falha quando a estrutura final de publicação muda (ex.: `/agenda/public` vs `/agenda`).
2. **Cookie de sessão no path raiz (`/`)** podendo conflitar com aplicações coexistentes no domínio raiz.
3. **Inconsistência de domínio padrão (`www` vs sem `www`)** em `APP_URL`.
4. **Falhas de banco em produção** quando variáveis de ambiente não estão definidas e fallback não está alinhado com HostGator.

## Correções aplicadas
- `.htaccess` público ajustado para rewrite sem `RewriteBase` rígido, preservando arquivos/diretórios reais.
- Sessão com `path` baseado em `APP_BASE_PATH` (usa `/agenda` quando configurado).
- `APP_URL` padrão ajustado para `http://studiobrunanayara.com.br`.
- `database.php` centralizado com `getenv()` + fallback HostGator esperado.

## Checklist objetivo de debug em produção
1. Verificar se o conteúdo de `public/` está realmente dentro de `/agenda`.
2. Confirmar `APP_BASE_PATH=/agenda`.
3. Confirmar import do schema e credenciais MySQL válidas.
4. Garantir permissões de escrita em `storage/logs` e `public/uploads/logo`.
5. Testar:
   - `/agenda`
   - `/agenda/login`
   - `/agenda/dashboard`
6. Verificar no DevTools se CSS/JS retornam 200 (sem 404 em `/agenda/assets/...`).
7. Verificar redirects de login/logout sempre com prefixo `/agenda`.

## Publicação recomendada (HostGator)
- Site institucional continua na raiz `/`.
- Sistema fica em `/agenda` com front controller e `.htaccess` próprios dessa pasta.
- Não copiar `app/`, `database/`, `storage/` para área pública quando não necessário; manter fora de webroot sempre que possível.
