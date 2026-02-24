(function () {
  const cfg = window.MPGMapConfig;
  if (!cfg) return;

  const mapEl = document.getElementById('mpg-map');
  const resultsEl = document.getElementById('mpg-results');
  if (!mapEl || !resultsEl) return;

  const map = L.map(mapEl).setView([cfg.goiasCenter.lat, cfg.goiasCenter.lng], 7);
  L.tileLayer(cfg.tilesUrl, { attribution: cfg.tilesAttribution }).addTo(map);

  const qName = document.getElementById('mpg-q-name');
  const qParty = document.getElementById('mpg-q-party');
  const qCity = document.getElementById('mpg-q-city');
  const qCep = document.getElementById('mpg-q-cep');
  const qClear = document.getElementById('mpg-q-clear');

  let allEntries = [];
  let markers = [];

  const normalize = (s) => (s || '').toString().toLowerCase().trim();

  const render = (entries) => {
    markers.forEach((m) => map.removeLayer(m));
    markers = [];
    resultsEl.innerHTML = '';

    entries.forEach((entry) => {
      const marker = L.marker([entry.latitude, entry.longitude]).addTo(map);
      const routeUrl = `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(entry.latitude + ',' + entry.longitude)}&travelmode=driving`;
      const wazeUrl = `https://www.waze.com/ul?ll=${encodeURIComponent(entry.latitude + ',' + entry.longitude)}&navigate=yes`;

      marker.bindPopup(`
        <div style="max-width:260px;">
          ${entry.foto_url ? `<img src="${entry.foto_url}" alt="${entry.prefeito_nome}" style="width:100%;height:auto;border-radius:6px;margin-bottom:6px;">` : ''}
          <strong>${entry.prefeito_nome}</strong><br>
          Cargo: ${entry.cargo || 'Prefeito'}<br>
          Vice: ${entry.vice_nome || 'Não informado'}<br>
          Partido: ${entry.partido || 'Não informado'}<br>
          Município: ${entry.municipio_nome}<br>
          ${entry.biografia_resumida ? `<small>${entry.biografia_resumida.substring(0, 180)}...</small><br>` : ''}
          <a href="${routeUrl}" target="_blank" rel="noopener">Traçar rota (Google)</a> | <a href="${wazeUrl}" target="_blank" rel="noopener">Waze</a>
          ${entry.telefone ? ` | <a href="tel:${entry.telefone}">Ligar</a>` : ''}
        </div>
      `);

      markers.push(marker);

      const card = document.createElement('div');
      card.className = 'mpg-card';
      card.innerHTML = `
        <h4>${entry.prefeito_nome}</h4>
        <p>${entry.municipio_nome} • ${entry.partido || 'Não informado'} • ${entry.cargo || 'Prefeito'}</p>
        <p><a href="${routeUrl}" target="_blank" rel="noopener">Traçar rota (Google)</a> | <a href="${wazeUrl}" target="_blank" rel="noopener">Waze</a> ${entry.telefone ? `| <a href="tel:${entry.telefone}">Ligar</a>` : ''}</p>
      `;
      card.addEventListener('click', () => {
        map.setView([entry.latitude, entry.longitude], 14);
        marker.openPopup();
      });
      resultsEl.appendChild(card);
    });
  };

  const applyFilters = () => {
    const n = normalize(qName?.value);
    const p = normalize(qParty?.value);
    const c = normalize(qCity?.value);
    const cep = normalize(qCep?.value);

    const filtered = allEntries.filter((e) => {
      return (!n || normalize(e.prefeito_nome).includes(n))
        && (!p || normalize(e.partido).includes(p))
        && (!c || normalize(e.municipio_nome).includes(c))
        && (!cep || normalize(e.cep).includes(cep));
    });

    render(filtered);
  };

  [qName, qParty, qCity, qCep].forEach((el) => el?.addEventListener('input', applyFilters));
  qClear?.addEventListener('click', () => {
    if (qName) qName.value = '';
    if (qParty) qParty.value = '';
    if (qCity) qCity.value = '';
    if (qCep) qCep.value = '';
    applyFilters();
  });

  const body = new URLSearchParams({ action: 'mpg_map_data', nonce: cfg.nonce });
  fetch(cfg.ajaxUrl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
    body: body.toString(),
  })
    .then((r) => r.json())
    .then((json) => {
      if (!json.success) throw new Error('Falha ao carregar dados');
      allEntries = Array.isArray(json.data?.entries) ? json.data.entries : [];
      render(allEntries);
    })
    .catch(() => {
      resultsEl.innerHTML = '<p>Falha ao carregar dados do mapa.</p>';
    });
})();
