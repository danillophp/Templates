# Mapa Político — Plugin WordPress (OpenStreetMap + Leaflet)

Revisão completa com foco em correção funcional de ponta a ponta:
- salvamento confiável no banco;
- atualização correta dos dados no mapa;
- cadastro unificado (político + localização) em uma única tela;
- mapa inicial centralizado em Goiás.

## Estrutura de pastas atualizada

```txt
wordpress-plugin/
  mapa-politico/
    mapa-politico.php
    uninstall.php
    includes/
      class-mapa-politico-db.php
      class-mapa-politico-admin.php
      class-mapa-politico-public.php
    assets/
      css/mapa-politico.css
      js/mapa-politico-public.js
```

## Erros críticos corrigidos

1. **Falhas silenciosas no salvamento**
   - Foi implementada transação (`START TRANSACTION`, `COMMIT`, `ROLLBACK`) no salvamento.
   - Agora erros de `INSERT/UPDATE` geram log com `error_log` e feedback para o usuário.

2. **Inconsistência entre schema e código**
   - O plugin agora executa migração de schema por versão (`mapa_politico_schema_version`) no `plugins_loaded`, evitando quebra em instalações já existentes.

3. **Falta de feedback UX no admin**
   - Mensagens visuais de sucesso/erro/exclusão adicionadas no painel.

4. **Mapa e listagem pública sem robustez de erro**
   - Endpoint AJAX retorna `wp_send_json_error` em falha de consulta e log de erro.

## Cadastro unificado (admin)

Na tela **Mapa Político > Cadastro Unificado**, o formulário contém:
- Nome do político
- Cargo
- Partido
- Cidade (obrigatória)
- Estado
- CEP
- Latitude
- Longitude
- Mapa interativo Leaflet

### Comportamento do mapa no formulário
- O mapa abre em Goiás (`-15.8270`, `-49.8362`, zoom `7`).
- Ao informar cidade/estado/CEP e clicar em **Centralizar no mapa**, consulta Nominatim.
- Ao clicar no mapa, latitude/longitude são preenchidas automaticamente.

## Front-end público

Shortcode:

```txt
[mapa_politico]
```

Inclui:
- mapa com OpenStreetMap + Leaflet;
- pesquisa avançada em tempo real por:
  - nome do político
  - partido
  - cidade
  - CEP
- filtro sincronizado de:
  - marcadores no mapa
  - lista de resultados
- clique em resultado:
  - centraliza no ponto
  - abre modal com dados do cadastro

## Código principal (referências)

- Formulário e backend unificado:
  - `includes/class-mapa-politico-admin.php`
- Mapa + busca avançada (JS):
  - `assets/js/mapa-politico-public.js`
- Payload e endpoint de dados:
  - `includes/class-mapa-politico-public.php`
- Migração/estrutura do banco:
  - `includes/class-mapa-politico-db.php`

## Instalação rápida

1. Compacte `wordpress-plugin/mapa-politico` em `.zip`.
2. WordPress > Plugins > Adicionar novo > Enviar plugin.
3. Ative o plugin.
4. Cadastre registros em **Mapa Político > Cadastro Unificado**.
5. Publique uma página com `[mapa_politico]`.

## Sugestões futuras

- Adicionar testes automatizados E2E com WordPress de desenvolvimento.
- Inserir paginação na listagem pública quando houver muitos registros.
- Criar endpoint REST dedicado com cache para alta escala.


## Rota / Navegação (novo)

- Botão **📍 Traçar rota** disponível no popup do marcador, no modal e na lista de resultados.
- Usa **Geolocation API** para origem (posição do usuário).
- Usa **Leaflet Routing Machine** + **OSRM público** para calcular rota gratuita.
- Exibe origem (ícone verde), destino (ícone padrão do político), linha da rota e ajuste automático de viewport.
- Botão **Limpar rota** para remover navegação atual.
- Tratamento amigável de erros:
  - permissão negada
  - localização indisponível
  - timeout
  - falha de roteamento


## Responsividade + Navegação externa + ligação (final)

### Responsividade
- Layout público ajustado com CSS Grid/Flex e breakpoints para mobile, tablet e desktop.
- Botões e áreas clicáveis maiores em telas pequenas.
- Mapa com altura adaptável por viewport para melhor usabilidade touch.

### Como chegar (Google Maps / Waze)
- Botão **📍 Como chegar** disponível no popup, modal e resultados.
- O sistema obtém a posição atual via Geolocation API.
- Em mobile, tenta abrir Waze primeiro e usa Google Maps como fallback.
- Em desktop, abre Google Maps em nova aba.

Links oficiais usados:
- Google Maps: `https://www.google.com/maps/dir/?api=1`
- Waze: `https://waze.com/ul`

### Ligação direta
- Botão **📞 Ligar** disponível nos resultados, popup e modal quando há telefone válido.
- Link no formato `tel:+55...` (normalizado).
- Em desktop, quando não há telefone válido, o número é exibido como texto.
