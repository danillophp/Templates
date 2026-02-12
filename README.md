# Plugin WordPress: Mapa Político Mundial (OpenStreetMap + Leaflet)

Este repositório está adaptado para uso como **plugin WordPress** sem dependência de APIs pagas de mapa.

## O que o plugin entrega

- Shortcode público `[mapa_politico]` para renderizar mapa mundial com **Leaflet + OpenStreetMap**.
- Marcadores dinâmicos por localidade.
- Modal com dados políticos completos:
  - nome, cargo, partido, idade
  - biografia e histórico político
  - foto
  - informações do município e região
  - telefone, e-mail e assessores
- Painel administrativo no WordPress:
  - **Mapa Político > Visão geral**
  - **Mapa Político > Localizações** (CRUD + geocodificação gratuita com Nominatim)
  - **Mapa Político > Políticos** (CRUD + upload de foto via biblioteca de mídia)

## Bibliotecas do mapa

No plugin, o equivalente aos includes abaixo é feito via `wp_register_style` e `wp_register_script`:

```html
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
```

## Estrutura do plugin

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

## Instalação no WordPress

1. Compacte a pasta `wordpress-plugin/mapa-politico` em `.zip`.
2. No WP Admin, vá em **Plugins > Adicionar novo > Enviar plugin**.
3. Envie o `.zip`, instale e ative.
4. Em **Mapa Político > Localizações**, cadastre os pontos e, se desejar, use o botão de geocodificação Nominatim.
5. Crie uma página e adicione o shortcode:

```txt
[mapa_politico]
```

## Segurança e boas práticas aplicadas

- Controle de acesso por `manage_options` nas telas administrativas.
- Nonces (`wp_nonce_field`, `check_admin_referer`, `check_ajax_referer`) em ações sensíveis.
- Sanitização com funções nativas do WordPress (`sanitize_text_field`, `sanitize_email`, etc.).
- Escape de saída (`esc_html`, `esc_attr`, `esc_textarea`, `esc_url`).
- Upload de mídia via APIs nativas do WordPress (`media_handle_upload`).

## Observações

- O plugin cria duas tabelas customizadas com prefixo do WordPress:
  - `{prefix}mapa_politico_locations`
  - `{prefix}mapa_politico_politicians`
- Na desinstalação (`uninstall.php`), as tabelas são removidas.
