# Desenho do cache (MySQL-first)

## 1) Arquitetura de cache
- **Store primário**: MySQL (`cache_store` + `cache_tags`).
- **Fallback opcional**: arquivo (`storage/cache/*.json`) para leitura quando houver falha/expiração no banco.
- **TTL obrigatório** por entrada.
- **Invalidação por tag** para evitar inconsistências em múltiplas páginas/rotas.
- **Prevenção de thundering herd** com `GET_LOCK()` por chave durante `remember()`.
- **Validação HTTP** com `ETag` e `Last-Modified` em rotas públicas para reduzir payload.

## 2) DDL do cache
```sql
CREATE TABLE cache_store (
  cache_key VARCHAR(191) PRIMARY KEY,
  cache_value LONGTEXT NOT NULL,
  created_at DATETIME NOT NULL,
  expires_at DATETIME NOT NULL,
  tags VARCHAR(500) NULL,
  checksum CHAR(64) NOT NULL,
  hits BIGINT UNSIGNED NOT NULL DEFAULT 0,
  last_hit_at DATETIME NULL,
  last_modified_at DATETIME NOT NULL,
  INDEX idx_cache_expires (expires_at),
  INDEX idx_cache_lastmod (last_modified_at)
) ENGINE=InnoDB;

CREATE TABLE cache_tags (
  tag_name VARCHAR(100) NOT NULL,
  cache_key VARCHAR(191) NOT NULL,
  PRIMARY KEY (tag_name, cache_key),
  CONSTRAINT fk_cache_tags_key FOREIGN KEY (cache_key) REFERENCES cache_store(cache_key) ON DELETE CASCADE,
  INDEX idx_cache_tag_key (cache_key)
) ENGINE=InnoDB;
```

## 3) API da biblioteca (`CacheService`)
- `get(key)`
- `set(key, value, ttl, tags)`
- `remember(key, ttl, tags, callback)`
- `invalidateKey(key)`
- `invalidateTag(tag)`
- `purgeExpired()`

## 4) Estratégia aplicada por rota
- `home`: tag `home`, `news`.
- listagem de notícias: tag `news` + chave por paginação.
- listagem de documentos: tag `docs` + chave por filtro/ano.
- mapa público (GeoJSON): tag `map_public`, `schools` + chave por parâmetros (`type`,`region`,`min_slots`).

## 5) Invalidação automática implementada
- Publicar notícia: invalida `news` e `home`.
- Publicar documento: invalida `docs` e `transparencia`.
- Cadastrar/atualizar escola: invalida `map_public`, `schools`, `school_{id}`.
- Movimentar estoque/cardápio: invalida `menu` e `map_public`.

## 6) Cabeçalhos HTTP
- `ETag` e `Last-Modified` aplicados nas rotas públicas de maior leitura.
- Resposta `304 Not Modified` quando `If-None-Match`/`If-Modified-Since` compatível.

## 7) Plano de testes manuais
1. Requisitar a home 2x e confirmar ganho de tempo (segunda chamada servida do cache).
2. Criar notícia no admin e verificar atualização imediata na home/listagem (invalidação por tag).
3. Atualizar escola e confirmar mudança no GeoJSON do mapa.
4. Testar `If-None-Match` com mesmo ETag e receber `304`.
5. Forçar expiração (TTL curto), chamar `purgeExpired()` e validar remoção em `cache_store`.
