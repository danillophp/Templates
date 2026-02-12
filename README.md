# Plugin WordPress: Mapa Político (OpenStreetMap + Leaflet)

Este projeto está preparado com **prioridade para WordPress (Opção A)**, removendo totalmente Google Maps e usando stack gratuita:

- **Leaflet** (renderização)
- **OpenStreetMap** (tiles)
- **Nominatim** (geocodificação opcional no admin)

---

## 1) Estrutura de pastas (principal)

```txt
wordpress-plugin/
  mapa-politico/
    mapa-politico.php                         # Arquivo principal do plugin (hooks/boot)
    uninstall.php                             # Remove tabelas customizadas na desinstalação
    includes/
      class-mapa-politico-db.php              # Criação/upgrade das tabelas (dbDelta)
      class-mapa-politico-admin.php           # Menus admin + CRUD + geocodificação Nominatim
      class-mapa-politico-public.php          # Shortcode + enqueue + endpoint AJAX
    assets/
      css/mapa-politico.css                   # Estilos do mapa/modal
      js/mapa-politico-public.js              # Leaflet: mapa, marcadores e eventos
```

> Também existe um fallback standalone no repositório (`app/`, `public/`, `config/`) para ambientes sem WordPress.

---

## 2) Principais arquivos e responsabilidades

- `wordpress-plugin/mapa-politico/mapa-politico.php`
  - Registra constantes do plugin
  - Carrega classes
  - Ativa criação de tabelas no `register_activation_hook`
- `wordpress-plugin/mapa-politico/includes/class-mapa-politico-public.php`
  - Registra Leaflet CSS/JS
  - Disponibiliza shortcode `[mapa_politico]`
  - Fornece dados via `admin-ajax.php`
- `wordpress-plugin/mapa-politico/assets/js/mapa-politico-public.js`
  - Inicializa mapa com `L.map`
  - Aplica tiles OSM (`https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png`)
  - Cria marcadores (`L.marker`) e clique para abrir modal
- `wordpress-plugin/mapa-politico/includes/class-mapa-politico-admin.php`
  - CRUD de localizações e políticos
  - Botão de geocodificação gratuita via Nominatim
- `wordpress-plugin/mapa-politico/includes/class-mapa-politico-db.php`
  - Cria tabelas:
    - `{prefix}mapa_politico_locations`
    - `{prefix}mapa_politico_politicians`

---

## 3) Código do mapa (Leaflet) — referência de integração

No plugin, o carregamento é feito por enqueue (boas práticas WordPress), equivalente a:

```html
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
```

Trecho essencial de inicialização (já implementado em `mapa-politico-public.js`):

```js
const map = L.map('mapa-politico-map', {
  center: [12, 0],
  zoom: 2,
  minZoom: 2,
  worldCopyJump: true,
});

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '&copy; OpenStreetMap contributors',
  maxZoom: 19,
}).addTo(map);

L.marker([lat, lng]).addTo(map).on('click', () => {
  // abre modal com dados políticos
});
```

---

## 4) Shortcode público

Use em qualquer página/post WordPress:

```txt
[mapa_politico]
```

---

## 5) Instalação (Opção A — WordPress, prioridade)

1. Compacte a pasta `wordpress-plugin/mapa-politico` em `.zip`.
2. WP Admin → **Plugins > Adicionar novo > Enviar plugin**.
3. Instale e ative.
4. Cadastre dados em:
   - **Mapa Político > Localizações**
   - **Mapa Político > Políticos**
5. Crie uma página e adicione `[mapa_politico]`.

### Base URL dinâmica
No plugin WordPress, URLs são dinâmicas via funções nativas (`admin_url`, `plugin_dir_url`, etc.), evitando hardcode.

---

## 6) Banco de dados

### Plugin WordPress (produção recomendada)
- Usa **`$wpdb`** (padrão WordPress), sem arquivo externo de conexão.
- Tabelas são criadas automaticamente na ativação.

### Fallback standalone (opcional)
Foram definidos defaults editáveis conforme solicitado:

- Database: `mapa_politico`
- Usuário: `mapa_politico`
- Senha: `Php@3903*`
- URL base padrão: `https://www.andredopremium.com.br/mapapolitico`

Arquivos:
- `config/database.php`
- `config/app.php`
- `.env.example`

> Recomenda-se configurar variáveis de ambiente reais no servidor em produção.

---

## 7) Segurança e boas práticas aplicadas

- `manage_options` no admin.
- Nonces em formulários e AJAX.
- Sanitização/escape com APIs nativas WordPress.
- Upload via `media_handle_upload`.
- Sem dependência de API paga para mapas.

---

## 8) Resultado

✅ Sistema funcional com mapa gratuito (OpenStreetMap + Leaflet)  
✅ Sem Google Maps/API key paga  
✅ Instalação simples via plugin ZIP  
✅ Adequado para uso institucional
