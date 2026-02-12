(function () {
  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function normalize(value) {
    return String(value || '').toLowerCase().trim();
  }

  function openModal(entry) {
    const modal = document.getElementById('mapa-politico-modal');
    const body = document.getElementById('mapa-politico-modal-body');
    if (!modal || !body) return;

    body.innerHTML = `
      <article class="mapa-politico-card">
        <h2>${escapeHtml(entry.full_name)}</h2>
        ${entry.photo_url ? `<img src="${escapeHtml(entry.photo_url)}" alt="Foto de ${escapeHtml(entry.full_name)}">` : ''}
        <p><strong>Cargo:</strong> ${escapeHtml(entry.position)}</p>
        <p><strong>Partido:</strong> ${escapeHtml(entry.party)}</p>
        <p><strong>Cidade:</strong> ${escapeHtml(entry.location.city)}</p>
        <p><strong>Estado:</strong> ${escapeHtml(entry.location.state)}</p>
        <p><strong>CEP:</strong> ${escapeHtml(entry.location.postal_code)}</p>
        <p><strong>Biografia:</strong> ${escapeHtml(entry.biography)}</p>
        <p><strong>Histórico:</strong> ${escapeHtml(entry.career_history)}</p>
      </article>
    `;

    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
  }

  function closeModal() {
    const modal = document.getElementById('mapa-politico-modal');
    if (!modal) return;
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
  }

  function renderResults(entries, onSelect) {
    const list = document.getElementById('mapa-politico-results-list');
    if (!list) return;

    if (!entries.length) {
      list.innerHTML = '<p>Nenhum resultado encontrado.</p>';
      return;
    }

    list.innerHTML = entries.map((entry) => `
      <button type="button" class="mapa-politico-result-item" data-id="${entry.politician_id}">
        <strong>${escapeHtml(entry.full_name)}</strong>
        <span>${escapeHtml(entry.party)} · ${escapeHtml(entry.location.city)} (${escapeHtml(entry.location.state)})</span>
        <small>CEP: ${escapeHtml(entry.location.postal_code)}</small>
      </button>
    `).join('');

    list.querySelectorAll('.mapa-politico-result-item').forEach((button) => {
      button.addEventListener('click', () => {
        const id = Number(button.dataset.id);
        const selected = entries.find((entry) => entry.politician_id === id);
        if (selected) onSelect(selected);
      });
    });
  }

  async function initLeafletMap() {
    const mapEl = document.getElementById('mapa-politico-map');
    if (!mapEl) return;

    if (typeof L === 'undefined') {
      mapEl.innerHTML = '<p>Não foi possível carregar o Leaflet.</p>';
      return;
    }

    const map = L.map(mapEl, {
      center: [-14.235, -51.9253],
      zoom: 4,
      minZoom: 2,
      zoomControl: true,
      worldCopyJump: true,
    });

    L.tileLayer(MapaPoliticoConfig.tilesUrl, {
      attribution: MapaPoliticoConfig.tilesAttribution,
      maxZoom: 19,
    }).addTo(map);

    const params = new URLSearchParams({
      action: 'mapa_politico_data',
      nonce: MapaPoliticoConfig.nonce,
    });

    const res = await fetch(`${MapaPoliticoConfig.ajaxUrl}?${params.toString()}`);
    const payload = await res.json();
    const allEntries = payload?.data?.entries || [];

    const markerLayer = L.layerGroup().addTo(map);

    const filters = {
      name: document.getElementById('filtro-nome'),
      party: document.getElementById('filtro-partido'),
      city: document.getElementById('filtro-cidade'),
      cep: document.getElementById('filtro-cep'),
      clear: document.getElementById('filtro-limpar'),
    };

    function applyFilters() {
      const name = normalize(filters.name?.value);
      const party = normalize(filters.party?.value);
      const city = normalize(filters.city?.value);
      const cep = normalize(filters.cep?.value);

      const filtered = allEntries.filter((entry) => {
        const matchName = !name || normalize(entry.full_name).includes(name);
        const matchParty = !party || normalize(entry.party).includes(party);
        const matchCity = !city || normalize(entry.location.city).includes(city);
        const matchCep = !cep || normalize(entry.location.postal_code).includes(cep);
        return matchName && matchParty && matchCity && matchCep;
      });

      markerLayer.clearLayers();

      filtered.forEach((entry) => {
        const marker = L.marker([entry.location.latitude, entry.location.longitude]).addTo(markerLayer);
        marker.on('click', () => openModal(entry));
      });

      renderResults(filtered, (entry) => {
        map.setView([entry.location.latitude, entry.location.longitude], 13);
        openModal(entry);
      });
    }

    [filters.name, filters.party, filters.city, filters.cep].forEach((input) => {
      input?.addEventListener('input', applyFilters);
    });

    filters.clear?.addEventListener('click', () => {
      [filters.name, filters.party, filters.city, filters.cep].forEach((input) => {
        if (input) input.value = '';
      });
      applyFilters();
    });

    applyFilters();
  }

  document.addEventListener('DOMContentLoaded', initLeafletMap);
  document.addEventListener('click', (event) => {
    if (event.target?.id === 'mapa-politico-close' || event.target?.id === 'mapa-politico-modal') {
      closeModal();
    }
  });
})();
