# Plugin WordPress — Mapa Político Goiás

## Instalação
1. Compacte a pasta `mapa-politico-goias` em `.zip`.
2. No WordPress: **Plugins > Adicionar novo > Enviar plugin**.
3. Ative o plugin.

## Funcionalidades
- Fila de sincronização automática por município (1 item por execução).
- Busca manual inteligente por IA (texto livre) para cadastrar político específico.
- Coleta IA de prefeito e vice-prefeito com fontes institucionais.
- Logs da IA no painel WordPress.
- Exclusão individual, em lote e total.
- Shortcode: `[mapa_politico_goias]`.
- Mapa com OpenStreetMap + Leaflet.

## Menu Admin
- **Mapa Político Goiás > Sincronizar Prefeitos**
- **Mapa Político Goiás > Logs da IA**
- **Mapa Político Goiás > 🔍 Buscar Político por IA**
- **Mapa Político Goiás > Excluir Cadastros**

## Como sincronizar
### Automático por fila
- Enfileire um município ou todos na tela **Sincronizar Prefeitos**.
- Clique em **Processar próximo da fila** (ou aguarde o Cron).

### Busca manual inteligente
1. Abra **🔍 Buscar Político por IA**.
2. Digite texto livre (nome, cidade, cargo, biografia).
3. Clique em **Pesquisar e Cadastrar com IA**.
4. O plugin enfileira e processa via AJAX com status em tempo real.

## Cron + AJAX
- WP Cron (`mpg_process_queue_event`) processa 1 item por execução (fila automática e manual).
- AJAX para enfileirar/processar sem travar página.

## Segurança
- Sanitização de entradas (`sanitize_text_field`, `sanitize_textarea_field`, `wp_unslash`, etc.).
- Endpoints AJAX com `nonce`.
- Verificação de permissão `manage_options`.
- Queries sensíveis com `$wpdb->prepare`.

## Solução de erros
- Consulte **Logs da IA** para município, etapa, motivo e fontes.
- Itens com erro ficam com status `erro` para retentativa.
- Se a busca manual não encontrar cidade/nome, o cadastro é bloqueado e o motivo aparece nos logs.
