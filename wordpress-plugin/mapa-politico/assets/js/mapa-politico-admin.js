(function () {
  const cfg = window.MapaPoliticoAdminConfig || {};
  const mapEl = document.getElementById('mp-admin-map');
  if (!mapEl || typeof L === 'undefined') return;

  const fields = {
    street: document.getElementById('address_street'),
    lot: document.getElementById('address_lot'),
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

  const map = L.map(mapEl).setView([Number(cfg.defaultLat || -15.827), Number(cfg.defaultLng || -49.8362)], Number(cfg.defaultZoom || 7));
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19,
  }).addTo(map);

  let marker = L.marker([Number(cfg.defaultLat || -15.827), Number(cfg.defaultLng || -49.8362)], { draggable: true }).addTo(map);

  const setCoordFields = (lat, lng) => {
    fields.lat.value = String(lat);
    fields.lng.value = String(lng);
    if (fields.latDisplay) fields.latDisplay.value = String(lat);
    if (fields.lngDisplay) fields.lngDisplay.value = String(lng);
  };

  const setFeedback = (msg, isError) => {
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
    const { lat, lng } = event.latlng;
    marker.setLatLng([lat, lng]);
    setCoordFields(lat.toFixed(7), lng.toFixed(7));
    setFeedback('Posição definida manualmente no mapa.', false);
  });

  setCoordFields(Number(cfg.defaultLat || -15.827).toFixed(7), Number(cfg.defaultLng || -49.8362).toFixed(7));

  const normalizePostalCode = (value) => String(value || '').replace(/\D/g, '');

  const buildAddress = () => {
    const postalCode = normalizePostalCode(document.getElementById('postal_code')?.value || '');
    if (postalCode.length >= 8) {
      return [postalCode, 'Brazil'].join(', ');
    }

    return [fields.street?.value, fields.lot?.value, fields.city?.value, fields.state?.value, 'Brazil']
      .map((s) => String(s || '').trim())
      .filter(Boolean)
      .join(', ');
  };

  const hasCityState = () => {
    const city = String(fields.city?.value || '').trim();
    const state = String(fields.state?.value || '').trim();
    return city.length >= 2 && state.length >= 2;
  };

  let geocodeTimeout = null;

  const triggerAutoGeocode = () => {
    if (!findBtn) return;

    const postalCode = normalizePostalCode(document.getElementById('postal_code')?.value || '');
    if (postalCode.length < 8 && !hasCityState()) {
      return;
    }

    if (geocodeTimeout) {
      window.clearTimeout(geocodeTimeout);
    }

    geocodeTimeout = window.setTimeout(() => {
      findBtn.click();
    }, 650);
  };

  findBtn?.addEventListener('click', async function () {
    const address = buildAddress();
    if (!address) {
      setFeedback('Preencha endereço, lote, cidade e estado.', true);
      return;
    }

    try {
      findBtn.disabled = true;
      setFeedback('Buscando localização...', false);

      const body = new URLSearchParams({
        action: 'mapa_politico_geocode_address',
        nonce: cfg.nonce || '',
        address,
      });

      const response = await fetch(cfg.ajaxUrl || '', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: body.toString(),
      });

      const json = await response.json();
      if (!json?.success) {
        throw new Error(json?.data?.message || 'Não foi possível geocodificar o endereço.');
      }

      const lat = Number(json.data.lat);
      const lng = Number(json.data.lng);
      if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        throw new Error('Coordenadas inválidas retornadas pelo serviço.');
      }

      marker.setLatLng([lat, lng]);
      map.setView([lat, lng], 16);
      setCoordFields(lat.toFixed(7), lng.toFixed(7));
      setFeedback('Localização atualizada. Você ainda pode ajustar manualmente o marcador.', false);
    } catch (error) {
      setFeedback(error.message || 'Falha ao buscar localização. Ajuste manualmente no mapa.', true);
    } finally {
      findBtn.disabled = false;
    }
  });

  ['postal_code', 'city', 'state'].forEach((id) => {
    const field = document.getElementById(id);
    field?.addEventListener('change', triggerAutoGeocode);
    field?.addEventListener('blur', triggerAutoGeocode);
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
})();
