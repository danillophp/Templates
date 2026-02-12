# Mapa Político — Plugin WordPress (OpenStreetMap + Leaflet)

Este projeto entrega a versão **WordPress (prioritária)** com:

- cadastro unificado (político + localização) em **uma única tela**;
- mapa integrado ao formulário de cadastro;
- página pública com mapa e pesquisa avançada (nome, partido, cidade, CEP);
- stack 100% gratuita (Leaflet + OpenStreetMap + Nominatim).

## Estrutura de pastas atualizada

```txt
wordpress-plugin/
  mapa-politico/
    mapa-politico.php
    uninstall.php
    includes/
      class-mapa-politico-db.php        # schema MySQL via $wpdb/dbDelta
      class-mapa-politico-admin.php     # tela única de cadastro + backend save/edit/delete
      class-mapa-politico-public.php    # shortcode + endpoint AJAX
    assets/
      css/mapa-politico.css             # layout (filtros, mapa, lista e modal)
      js/mapa-politico-public.js        # Leaflet + filtros avançados (sem reload)
```

## Banco de dados e URL base

### WordPress (produção recomendada)
- Usa `$wpdb` (config do próprio WordPress), sem hardcode de conexão.
- Tabelas criadas automaticamente na ativação.

### Fallback standalone (opcional)
Defaults editáveis:
- Database: `mapa_politico`
- Usuário: `mapa_politico`
- Senha: `Php@3903*`
- URL base padrão: `https://www.andredopremium.com.br/mapapolitico`

Arquivos de referência:
- `.env.example`
- `config/database.php`
- `config/app.php`

## Cadastro unificado (obrigatório)

A tela **Mapa Político > Cadastro Unificado** contém no mesmo formulário:
- nome do político, cargo, partido
- cidade (obrigatória), estado, CEP
- latitude, longitude
- mapa interativo Leaflet

Fluxo:
1. Digite cidade/CEP e clique em **Centralizar no mapa** (Nominatim).
2. Clique no mapa para ajustar o ponto exato.
3. Salve tudo em uma única ação.

## Código do formulário de cadastro (referência)

Arquivo: `includes/class-mapa-politico-admin.php`

```php
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
  <?php wp_nonce_field('mapa_politico_save_entry'); ?>
  <input type="hidden" name="action" value="mapa_politico_save_entry">
  <input required name="full_name">
  <input required name="position">
  <input required name="party">
  <input required name="city">
  <input name="state">
  <input name="postal_code">
  <input required step="0.000001" name="latitude">
  <input required step="0.000001" name="longitude">
  <div id="mapa-politico-admin-map"></div>
</form>
```

## Código JavaScript do mapa e pesquisa (referência)

Arquivo: `assets/js/mapa-politico-public.js`

```js
const map = L.map('mapa-politico-map', { center: [-14.235, -51.9253], zoom: 4 });
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '&copy; OpenStreetMap contributors',
}).addTo(map);

// filtros sem reload
const filtered = allEntries.filter((entry) => {
  return normalize(entry.full_name).includes(name)
    && normalize(entry.party).includes(party)
    && normalize(entry.location.city).includes(city)
    && normalize(entry.location.postal_code).includes(cep);
});
```

## Ajustes backend para salvar e filtrar

### Salvamento único
- A action `mapa_politico_save_entry` salva dados geográficos e políticos em uma única ação.
- Em edição, atualiza político + localização relacionados.

### Filtro no front-end
- Endpoint AJAX `mapa_politico_data` retorna registros completos (político + localização).
- A filtragem acontece em JavaScript sem recarregar página.
- Clique em resultado centra o mapa e abre o modal com informações do cadastro.

## Shortcode público

```txt
[mapa_politico]
```

## Instalação rápida

1. Compacte `wordpress-plugin/mapa-politico` em `.zip`.
2. WordPress > Plugins > Adicionar novo > Enviar plugin.
3. Ative o plugin.
4. Cadastre registros em **Mapa Político > Cadastro Unificado**.
5. Publique uma página com `[mapa_politico]`.
