# Mapa Político — Plugin WordPress (OpenStreetMap + Leaflet)

## Visão geral
Este plugin agora possui:

- cadastro manual unificado (político + localização)
- mapa público com filtros, rota interna e navegação externa
- **módulo de cadastro/atualização automática por IA (auditável) para Goiás**

> ⚠️ Conformidade: não há scraping ilegal implementado. O fluxo automático usa fontes públicas e oficiais (IBGE + Nominatim) e deixa dados sensíveis/duvidosos como pendentes de validação humana.

---

## Estrutura de pastas atualizada

```txt
wordpress-plugin/
  mapa-politico/
    mapa-politico.php
    uninstall.php
    includes/
      class-mapa-politico-db.php         # schema/migração
      class-mapa-politico-ai.php         # rotina automática Goiás (cron + importação)
      class-mapa-politico-admin.php      # painel admin + intervenção humana
      class-mapa-politico-public.php     # endpoint/mapa público
    assets/
      css/mapa-politico.css
      js/mapa-politico-public.js
```

---

## Banco de dados (atualizado)

### `wp_mapa_politico_locations`
Campos principais adicionados para automação:
- `ibge_code`
- `institution_type` (`prefeitura`/`camara`)
- `source_url`
- `last_synced_at`

### `wp_mapa_politico_politicians`
Campos principais adicionados para governança/auditoria:
- `source_url`
- `source_name`
- `data_status` (`completo`, `incompleto`, `aguardando_validacao`, `rejeitado`)
- `validation_notes`
- `is_auto`
- `municipality_code`
- `last_synced_at`

---

## Fluxo da IA (Goiás)

Arquivo principal: `includes/class-mapa-politico-ai.php`

### Fontes utilizadas
1. **IBGE** (lista oficial de municípios de GO):
   - `https://servicodados.ibge.gov.br/api/v1/localidades/estados/52/municipios`
2. **Nominatim / OpenStreetMap** para geocodificação institucional:
   - prefeitura municipal
   - câmara municipal

### O que a rotina faz
Para cada município de Goiás:
- cria/atualiza localizações institucionais (prefeitura/câmara)
- cria/atualiza registros políticos por cargo:
  - Prefeito
  - Vice-prefeito
  - Vereador
- quando faltam dados oficiais detalhados (nome/partido/telefone):
  - salva como `Pendente de validação`
  - marca status `aguardando_validacao`

### Periodicidade
- cron semanal (`mapa_politico_ai_sync_event`)
- também pode rodar manualmente no admin

---

## Pontos de intervenção do administrador

Menu: **Mapa Político > Atualização IA Goiás**

Funcionalidades:
- executar sincronização manual imediata
- ver lista de registros automáticos
- visualizar status, fonte e última atualização
- aprovar/rejeitar registros automáticos
- editar manualmente via **Cadastro Unificado**

Isso garante trilha auditável e controle humano final.

---

## Mapa público

Shortcode:

```txt
[mapa_politico]
```

Mostra:
- marcadores por políticos/localizações
- filtros por nome, partido, cidade, CEP
- rota interna (Leaflet Routing + OSRM)
- navegação externa (Google/Waze)
- botão de ligação quando telefone disponível

---

## Limitações técnicas (transparentes)

1. **Nomes/partidos automáticos**
   - para evitar scraping ilegal, o módulo automático usa fontes oficiais estruturadas.
   - quando não há endpoint oficial estruturado para nomes/cargos locais detalhados, o sistema cria pendências para validação humana.

2. **Qualidade de geocodificação**
   - Nominatim pode retornar coordenada aproximada em alguns municípios.
   - o administrador pode corrigir no cadastro manual.

3. **Escalabilidade multiestado**
   - código está preparado para expansão, mas atualmente focado em Goiás (UF 52).

---

## Segurança / LGPD

- somente dados institucionais públicos
- registro de fonte (`source_url`, `source_name`)
- status de validação explícito
- possibilidade de correção manual pelo administrador

---

## Instalação rápida

1. Compacte `wordpress-plugin/mapa-politico` em `.zip`.
2. WordPress > Plugins > Adicionar novo > Enviar plugin.
3. Ative o plugin.
4. Acesse:
   - **Cadastro Unificado** (manual)
   - **Atualização IA Goiás** (automático)
5. Publique uma página com `[mapa_politico]`.
