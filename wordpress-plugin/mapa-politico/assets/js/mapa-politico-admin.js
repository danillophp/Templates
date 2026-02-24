(function () {
  const cfg = window.MapaPoliticoAdminConfig || {};

  const initCustomFields = function () {
    const wrap = document.getElementById('mp-custom-fields-wrap');
    const addBtn = document.getElementById('mp-add-custom-field');
    if (!wrap || !addBtn) return;

    const buildFieldRow = function () {
      const row = document.createElement('div');
      row.className = 'mp-custom-field-row';
      row.style.display = 'grid';
      row.style.gridTemplateColumns = '2fr 1fr 2fr 1fr 1fr auto';
      row.style.gap = '8px';
      row.style.marginBottom = '8px';
      row.style.alignItems = 'center';

      row.innerHTML = `
        <input type="text" name="custom_label[]" placeholder="Nome do campo">
        <select name="custom_type[]">
          <option value="text">Texto</option>
          <option value="textarea">Área de texto</option>
          <option value="email">E-mail</option>
          <option value="url">URL</option>
          <option value="number">Número</option>
        </select>
        <input type="text" name="custom_value[]" placeholder="Valor">
        <select name="custom_show_map[]"><option value="1">Mapa: Sim</option><option value="0">Mapa: Não</option></select>
        <select name="custom_show_profile[]"><option value="1">Perfil: Sim</option><option value="0">Perfil: Não</option></select>
        <button type="button" class="button-link-delete mp-remove-custom-field">Remover</button>
      `;
      return row;
    };

    addBtn.addEventListener('click', function () {
      wrap.appendChild(buildFieldRow());
    });

    wrap.addEventListener('click', function (event) {
      const removeBtn = event.target.closest('.mp-remove-custom-field');
      if (!removeBtn) return;
      const row = removeBtn.closest('.mp-custom-field-row');
      if (row) row.remove();
    });
  };

  const mapEl = document.getElementById('mp-admin-map');
  if (!mapEl || typeof L === 'undefined') {
    initCustomFields();
    return;
  }

  const fields = {
    city: document.getElementById('city'),
    state: document.getElementById('state'),
    lat: document.getElementById('latitude'),
    lng: document.getElementById('longitude'),
    latDisplay: document.getElementById('latitude_display'),
    lngDisplay: document.getElementById('longitude_display'),
  };

  const feedback = document.getElementById('mp-geo-feedback');
  const findBtn = document.getElementById('mp-find-location');
  const aiBtn = document.getElementById('mp-search-ai');

  const defaultLat = Number(cfg.defaultLat || -15.827);
  const defaultLng = Number(cfg.defaultLng || -49.8362);
  const initialLat = Number(fields.lat?.value || defaultLat);
  const initialLng = Number(fields.lng?.value || defaultLng);

  const map = L.map(mapEl).setView([initialLat, initialLng], Number(cfg.defaultZoom || 7));
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19,
  }).addTo(map);

  const marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(map);

  const setCoordFields = function (lat, lng) {
    if (fields.lat) fields.lat.value = String(lat);
    if (fields.lng) fields.lng.value = String(lng);
    if (fields.latDisplay) fields.latDisplay.value = String(lat);
    if (fields.lngDisplay) fields.lngDisplay.value = String(lng);
  };

  const setFeedback = function (msg, isError) {
    if (!feedback) return;
    feedback.textContent = msg || '';
    feedback.style.color = isError ? '#b32d2e' : '#2271b1';
  };

  marker.on('dragend', function () {
    const pos = marker.getLatLng();
    setCoordFields(pos.lat.toFixed(7), pos.lng.toFixed(7));
    setFeedback('Marcador ajustado manualmente.', false);
  });

  map.on('click', function (event) {
    const lat = event.latlng.lat;
    const lng = event.latlng.lng;
    marker.setLatLng([lat, lng]);
    setCoordFields(lat.toFixed(7), lng.toFixed(7));
    setFeedback('Posição definida manualmente no mapa.', false);
  });

  setCoordFields(initialLat.toFixed(7), initialLng.toFixed(7));

  const hasCityState = function () {
    const city = String(fields.city?.value || '').trim();
    const state = String(fields.state?.value || '').trim();
    return city.length >= 2 && state.length >= 2;
  };

  let geocodeTimeout = null;

  const requestGeocode = async function () {
    if (!hasCityState()) {
      setFeedback('Informe cidade e estado para geocodificar.', true);
      return;
    }

    try {
      if (findBtn) findBtn.disabled = true;
      setFeedback('Buscando localização do município...', false);

      const body = new URLSearchParams({
        action: 'mapa_politico_geocode_address',
        nonce: cfg.nonce || '',
        city: String(fields.city?.value || '').trim(),
        state: String(fields.state?.value || '').trim(),
      });

      const response = await fetch(cfg.ajaxUrl || '', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: body.toString(),
      });

      const json = await response.json();
      if (!json?.success) {
        throw new Error(json?.data?.message || 'Não foi possível geocodificar.');
      }

      const lat = Number(json.data.lat);
      const lng = Number(json.data.lng);
      if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        throw new Error('Coordenadas inválidas retornadas pelo serviço.');
      }

      marker.setLatLng([lat, lng]);
      map.setView([lat, lng], 13);
      setCoordFields(lat.toFixed(7), lng.toFixed(7));
      setFeedback('Localização atualizada. Você pode ajustar manualmente o marcador.', false);
    } catch (error) {
      setFeedback(error.message || 'Falha na geocodificação. Ajuste manualmente no mapa.', true);
    } finally {
      if (findBtn) findBtn.disabled = false;
    }
  };

  const triggerAutoGeocode = function () {
    if (geocodeTimeout) window.clearTimeout(geocodeTimeout);
    geocodeTimeout = window.setTimeout(function () {
      requestGeocode();
    }, 600);
  };

  if (findBtn) {
    findBtn.addEventListener('click', requestGeocode);
  }

  ['city', 'state'].forEach(function (id) {
    const field = document.getElementById(id);
    if (!field) return;
    field.addEventListener('change', triggerAutoGeocode);
    field.addEventListener('blur', triggerAutoGeocode);
  });

  aiBtn?.addEventListener('click', async function () {
    const name = document.getElementById('full_name')?.value || '';
    const position = document.getElementById('position')?.value || '';
    const city = document.getElementById('city')?.value || '';

    try {
      aiBtn.disabled = true;
      const body = new URLSearchParams({
        action: 'mapa_politico_ai_enrich_text',
        nonce: cfg.nonce || '',
        name,
        position,
        city,
      });

      const response = await fetch(cfg.ajaxUrl || '', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: body.toString(),
      });

      const json = await response.json();
      if (!json?.success) throw new Error(json?.data?.message || 'Falha na IA');

      const bio = document.getElementById('biography');
      const hist = document.getElementById('career_history');
      if (bio) bio.value = json.data.biography || '';
      if (hist) hist.value = json.data.history || '';
    } catch (error) {
      alert(error.message || 'Erro na IA');
    } finally {
      aiBtn.disabled = false;
    }
  });

  initCustomFields();
})();
