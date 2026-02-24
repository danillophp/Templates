# Mapa Político (WordPress) — Instalação

## Arquitetura atual

- Cadastro manual no wp-admin
- IA somente para biografia e histórico (ação manual por botão)
- Sem sincronização automática
- Sem filas/cron de IA
- Foto apenas por upload manual (Media Library), salvando `photo_id`

## Instalação

1. Compacte a pasta `wordpress-plugin/mapa-politico` e instale no WordPress.
2. Ative o plugin.
3. Acesse `Mapa Político > Cadastro Manual`.
4. Cadastre registros manualmente.
5. Use shortcode `[mapa_politico]` em uma página.

## IA

Configure opção `mapa_politico_openai_api_key` (ou constante `MAPA_POLITICO_OPENAI_API_KEY`).
A IA é usada apenas para gerar texto de:
- Biografia
- Histórico político


## Geolocalização manual no cadastro

- Preencha Rua/Quadra, Lote, Município e Estado.
- Clique em `📍 Find location on map` para buscar latitude/longitude via Nominatim (OpenStreetMap).
- Ajuste o marcador manualmente por arrastar/soltar antes de salvar.
