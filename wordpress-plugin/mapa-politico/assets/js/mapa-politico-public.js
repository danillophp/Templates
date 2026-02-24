(function () {
  const cfg = window.MapaPoliticoConfig || {};
  const GOIAS_CENTER = [Number(cfg.defaultLat || -15.827), Number(cfg.defaultLng || -49.8362)];
  const GOIAS_ZOOM = Number(cfg.defaultZoom || 7);

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function setStatus(message, type) {
    const el = document.getElementById('mapa-politico-status');
    if (!el) return;
    el.textContent = message || '';
    el.dataset.type = type || 'info';
  }

  function sanitizePhoneToTel(phone) {
    const digits = String(phone || '').replace(/\D/g, '');
    return digits ? `+${digits}` : '';
  }

  function buildExternalRouteLink(lat, lng) {
    const wazeUrl = `https://waze.com/ul?ll=${encodeURIComponent(`${lat},${lng}`)}&navigate=yes`;
    const googleUrl = `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(`${lat},${lng}`)}`;

    if (/Android|iPhone|iPad|iPod|IEMobile|Opera Mini/i.test(navigator.userAgent || '')) {
      return wazeUrl;
    }

    return googleUrl;
  }

  function buildCustomFieldsHtml(entry, visibilityKey) {
    const fields = Array.isArray(entry.custom_fields) ? entry.custom_fields : [];
    const visible = fields.filter((field) => Number(field[visibilityKey] || 0) === 1);
    if (!visible.length) return '';

    return `<div class="mapa-politico-custom-fields">${visible.map((field) => `<p><strong>${escapeHtml(field.label)}:</strong> ${escapeHtml(field.value)}</p>`).join('')}</div>`;
  }

  function buildPopupHtml(entry) {
    const tel = sanitizePhoneToTel(entry.phone);
    const routeLink = buildExternalRouteLink(entry.location.latitude, entry.location.longitude);
    const phoneAction = tel
      ? `<a class="mapa-politico-call-btn" href="tel:${tel}">📞 Ligar</a>`
      : `<span class="mapa-politico-phone-text">📞 ${escapeHtml(entry.phone || 'Não informado')}</span>`;

    return `
      <div class="mapa-politico-popup">
        <strong>${escapeHtml(entry.full_name)}</strong>
        <div>${escapeHtml(entry.position)} · ${escapeHtml(entry.party)}</div>
        <div>${escapeHtml(entry.location.city)} - ${escapeHtml(entry.location.state || '')}</div>
        ${buildCustomFieldsHtml(entry, 'show_on_map')}
        <div class="mapa-politico-actions">
          <a class="mapa-politico-nav-btn" href="${routeLink}" target="_blank" rel="noopener noreferrer">➡️ Como chegar</a>
          ${phoneAction}
        </div>
      </div>
    `;
  }

  function openModal(entry) {
    const modal = document.getElementById('mapa-politico-modal');
    const body = document.getElementById('mapa-politico-modal-body');
    if (!modal || !body) return;

    const tel = sanitizePhoneToTel(entry.phone);
    const routeLink = buildExternalRouteLink(entry.location.latitude, entry.location.longitude);

    body.innerHTML = `
      <article class="mapa-politico-card">
        <h2>${escapeHtml(entry.full_name)}</h2>
        ${entry.photo_url ? `<img src="${escapeHtml(entry.photo_url)}" alt="Foto de ${escapeHtml(entry.full_name)}">` : ''}
        <p><strong>Cargo:</strong> ${escapeHtml(entry.position)}</p>
        <p><strong>Partido:</strong> ${escapeHtml(entry.party)}</p>
        <p><strong>Cidade:</strong> ${escapeHtml(entry.location.city)}</p>
        <p><strong>Estado:</strong> ${escapeHtml(entry.location.state)}</p>
        <p><strong>Biografia:</strong> ${escapeHtml(entry.biography)}</p>
        <p><strong>Histórico:</strong> ${escapeHtml(entry.career_history)}</p>
        ${buildCustomFieldsHtml(entry, 'show_on_profile')}
        <div class="mapa-politico-actions">
          <a class="mapa-politico-nav-btn" href="${routeLink}" target="_blank" rel="noopener noreferrer">➡️ Como chegar</a>
          ${tel ? `<a class="mapa-politico-call-btn" href="tel:${tel}">📞 Ligar</a>` : ''}
        </div>
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
      <div class="mapa-politico-result-item" data-id="${entry.politician_id}">
        <button type="button" class="mapa-politico-select-btn">
          <strong>${escapeHtml(entry.full_name)}</strong>
          <span>${escapeHtml(entry.party)} · ${escapeHtml(entry.location.city)} (${escapeHtml(entry.location.state)})</span>
        </button>
      </div>
    `).join('');

    list.querySelectorAll('.mapa-politico-result-item').forEach((container) => {
      const id = Number(container.dataset.id);
      const selected = entries.find((entry) => entry.politician_id === id);
      if (!selected) return;
      container.querySelector('.mapa-politico-select-btn')?.addEventListener('click', () => onSelect(selected));
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
      center: GOIAS_CENTER,
      zoom: GOIAS_ZOOM,
      minZoom: 3,
    });

    L.tileLayer(cfg.tilesUrl, {
      attribution: cfg.tilesAttribution,
      maxZoom: 19,
    }).addTo(map);

    const params = new URLSearchParams({ action: 'mapa_politico_data', nonce: cfg.nonce || '' });
    const response = await fetch(`${cfg.ajaxUrl}?${params.toString()}`);
    const payload = await response.json();

    if (!payload?.success) {
      setStatus('Falha ao carregar os dados do mapa.', 'error');
      return;
    }

    const allEntries = Array.isArray(payload.data?.entries) ? payload.data.entries : [];
    const markerLayer = L.layerGroup().addTo(map);

    const applyFilter = function () {
      const term = String(document.getElementById('filtro-geral')?.value || '').toLowerCase().trim();
      const filtered = allEntries.filter((entry) => {
        if (!term) return true;
        const source = `${entry.full_name} ${entry.party} ${entry.location.city}`.toLowerCase();
        return source.includes(term);
      });

      markerLayer.clearLayers();
      filtered.forEach((entry) => {
        const marker = L.marker([entry.location.latitude, entry.location.longitude]).addTo(markerLayer);
        marker.bindPopup(buildPopupHtml(entry));
        marker.on('click', function () { openModal(entry); });
      });

      renderResults(filtered, (entry) => {
        map.setView([entry.location.latitude, entry.location.longitude], 13);
        openModal(entry);
      });

      if (filtered.length > 0) {
        const bounds = L.latLngBounds(filtered.map((entry) => [entry.location.latitude, entry.location.longitude]));
        map.fitBounds(bounds, { padding: [20, 20], maxZoom: 13 });
      } else {
        map.setView(GOIAS_CENTER, GOIAS_ZOOM);
      }

      setStatus(`${filtered.length} resultado(s) encontrado(s).`, 'info');
    };

    document.getElementById('filtro-geral')?.addEventListener('input', applyFilter);
    applyFilter();
  }

  document.addEventListener('DOMContentLoaded', initLeafletMap);
  document.addEventListener('click', function (event) {
    if (event.target?.id === 'mapa-politico-close' || event.target?.id === 'mapa-politico-modal') {
      closeModal();
    }
  });
})();
