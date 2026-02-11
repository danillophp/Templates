# Mapa Político Mundial (PHP + MySQL + Google Maps)

Sistema institucional com mapa mundial interativo e painel administrativo para cadastro de localizações e figuras políticas.

## Arquitetura

Estrutura em MVC simplificado:

- `public/`: ponto de entrada (`index.php`), assets e uploads.
- `app/Controllers`: fluxo HTTP e regras de entrada.
- `app/Models`: acesso a dados via PDO e prepared statements.
- `app/Views`: templates da área pública e área admin.
- `config/`: configurações da aplicação e banco.
- `database/schema.sql`: criação das tabelas normalizadas.

## Funcionalidades implementadas

### Área pública
- Mapa Google Maps com visão global.
- Marcadores dinâmicos carregados via `/api/map-data`.
- Modal com detalhes de políticos por localidade: nome, cargo, partido, idade, biografia, carreira, contato, assessores e contexto local/regional.

### Área administrativa
- Login com sessão e `password_hash/password_verify`.
- Proteção CSRF em todos os formulários de escrita.
- CRUD de localizações (endereço, CEP, latitude/longitude, município/região).
- CRUD de dados políticos (campos completos, vínculo com localidade, upload de foto com validação de MIME e tamanho).

## Configuração local

1. Crie banco e tabelas:

```bash
mysql -u root -p < database/schema.sql
```

2. Ajuste configurações:

- `config/database.php`: host, porta, base, usuário e senha MySQL.
- `config/app.php`: `google_maps_api_key` e URL base.

3. Rode em ambiente local:

```bash
php -S localhost:8000 -t public
```

4. Acesse:
- Site público: `http://localhost:8000`
- Admin: `http://localhost:8000/admin/login`
- Usuário inicial: `admin@seudominio.com`
- Senha inicial: `admin123`

> Importante: troque a senha inicial em produção.

## Deploy na HostGator (Apache + PHP + MySQL)

1. No cPanel, crie banco MySQL e usuário com permissões.
2. Importe `database/schema.sql` via phpMyAdmin.
3. Faça upload dos arquivos para `public_html` mantendo estrutura.
   - Se possível, exponha somente a pasta `public` como web root.
4. Se usar `public_html` como raiz:
   - mova conteúdo de `public/` para `public_html/`.
   - ajuste os caminhos do `require` em `index.php` para apontar ao diretório pai correto.
5. Configure `GOOGLE_MAPS_API_KEY` no ambiente (ou em `config/app.php`).
6. Garanta `mod_rewrite` ativo e `.htaccess` aplicado.

## Boas práticas de segurança usadas

- Queries com `PDO::prepare` para evitar SQL injection.
- Escape de saída com `htmlspecialchars` nas views.
- CSRF token em formulários sensíveis.
- Sessão com `httponly` e `samesite=Lax`.
- Upload de imagem restrito por MIME, tamanho e extensão.

## Observações de produção

- Ativar HTTPS obrigatório.
- Implementar rotação de logs e monitoramento de erros.
- Adicionar paginação/filtros para grandes volumes.
- Configurar backup automático do banco de dados.
