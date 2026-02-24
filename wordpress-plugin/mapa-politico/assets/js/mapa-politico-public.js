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

  function normalizeText(value) {
    return String(value || '').toLowerCase().trim();
  }

  function setStatus(message, type) {
    const el = document.getElementById('mapa-politico-status');
    if (!el) return;
    el.textContent = message || '';
    el.dataset.type = type || 'info';
  }

  function sanitizePhoneDigits(phone) {
    return String(phone || '').replace(/\D/g, '');
  }

  function buildTelLink(phone) {
    const digits = sanitizePhoneDigits(phone);
    return digits ? `+${digits}` : '';
  }

  function buildWhatsappLink(phone) {
    const digits = sanitizePhoneDigits(phone);
    if (!digits) return '';
    const national = digits.startsWith('55') ? digits.slice(2) : digits;
    return national ? `https://wa.me/55${national}` : '';
  }

  function buildRouteLink(lat, lng) {
    const destination = encodeURIComponent(`${lat},${lng}`);
    const wazeUrl = `https://waze.com/ul?ll=${destination}&navigate=yes`;
    const googleUrl = `https://www.google.com/maps/dir/?api=1&destination=${destination}`;

    if (/Android|iPhone|iPad|iPod|IEMobile|Opera Mini/i.test(navigator.userAgent || '')) {
      return wazeUrl;
    }

    return googleUrl;
  }

  function buildCustomFieldValue(field) {
    const type = String(field?.type || 'text');
    const value = String(field?.value || '').trim();
    if (!value) return '';

    if (type === 'email') {
      return `<a href="mailto:${escapeHtml(value)}">${escapeHtml(value)}</a>`;
    }

    if (type === 'url') {
      const safeUrl = value.startsWith('http://') || value.startsWith('https://') ? value : `https://${value}`;
      return `<a href="${escapeHtml(safeUrl)}" target="_blank" rel="noopener noreferrer">${escapeHtml(value)}</a>`;
    }

    return escapeHtml(value);
  }

  function buildCustomFieldsHtml(entry) {
    const fields = Array.isArray(entry.custom_fields) ? entry.custom_fields : [];
    const visible = fields.filter((field) => Number(field.show_on_map || 0) === 1 && String(field.value || '').trim() !== '');

    if (!visible.length) return '';

    return `
      <section class="mapa-politico-custom-fields">
        <h4>Campos personalizados</h4>
        ${visible.map((field) => `<p><strong>${escapeHtml(field.label)}:</strong> ${buildCustomFieldValue(field)}</p>`).join('')}
      </section>
    `;
  }

  function buildContactActions(entry) {
    const tel = buildTelLink(entry.phone);
    const whatsapp = buildWhatsappLink(entry.phone);

    return `
      <a class="mapa-politico-nav-btn" href="${buildRouteLink(entry.location.latitude, entry.location.longitude)}" target="_blank" rel="noopener noreferrer">➡️ Como chegar</a>
      ${tel ? `<a class="mapa-politico-call-btn" href="tel:${tel}">📞 Ligar</a>` : ''}
      ${whatsapp ? `<a class="mapa-politico-whatsapp-btn" href="${whatsapp}" target="_blank" rel="noopener noreferrer">🟢 WhatsApp</a>` : ''}
    `;
  }

  function buildModalHtml(entry) {
    const address = String(entry.location?.address || '').trim();
    const biography = String(entry.biography || '').trim();
    const history = String(entry.career_history || '').trim();

    return `
      <article class="mapa-politico-modal-article">
        ${entry.photo_url ? `<img class="mapa-politico-modal-photo" src="${escapeHtml(entry.photo_url)}" alt="Foto de ${escapeHtml(entry.full_name)}">` : ''}
        <h3>${escapeHtml(entry.full_name)}</h3>
        <p><strong>Cargo:</strong> ${escapeHtml(entry.position || '-')}</p>
        <p><strong>Partido:</strong> ${escapeHtml(entry.party || '-')}</p>
        <p><strong>Cidade:</strong> ${escapeHtml(entry.location.city || '-')}</p>
        <p><strong>Estado:</strong> ${escapeHtml(entry.location.state || '-')}</p>
        ${address ? `<p><strong>Endereço:</strong> ${escapeHtml(address)}</p>` : ''}
        ${biography ? `<p><strong>Biografia:</strong> ${escapeHtml(biography)}</p>` : ''}
        ${history ? `<p><strong>Histórico:</strong> ${escapeHtml(history)}</p>` : ''}
        ${buildCustomFieldsHtml(entry)}
        <div class="mapa-politico-actions">${buildContactActions(entry)}</div>
      </article>
    `;
  }

  function openModal(entry) {
    const modal = document.getElementById('mapa-politico-modal');
    const body = document.getElementById('mapa-politico-modal-body');
    if (!modal || !body) return;

    body.innerHTML = buildModalHtml(entry);
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
      <article class="mapa-politico-result-item" data-id="${entry.politician_id}">
        <button type="button" class="mapa-politico-select-btn" aria-label="Selecionar ${escapeHtml(entry.full_name)}">
          <strong>${escapeHtml(entry.full_name)}</strong>
          <span>${escapeHtml(entry.position || '-')}</span>
          <span>${escapeHtml(entry.party || '-')} · ${escapeHtml(entry.location.city || '-')}</span>
        </button>
      </article>
    `).join('');

    list.querySelectorAll('.mapa-politico-result-item').forEach((container) => {
      const id = Number(container.dataset.id);
      const selected = entries.find((entry) => entry.politician_id === id);
      if (!selected) return;

      container.querySelector('.mapa-politico-select-btn')?.addEventListener('click', function () {
        onSelect(selected);
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
      center: GOIAS_CENTER,
      zoom: GOIAS_ZOOM,
      minZoom: 3,
      zoomControl: true,
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
    const markersById = new Map();

    function focusEntry(entry) {
      map.setView([entry.location.latitude, entry.location.longitude], 13);
      openModal(entry);
    }

    function applyFilter() {
      const term = normalizeText(document.getElementById('filtro-geral')?.value || '');
      const filtered = allEntries.filter((entry) => {
        if (!term) return true;
        const source = normalizeText(`${entry.full_name} ${entry.party} ${entry.location.city}`);
        return source.includes(term);
      });

      markerLayer.clearLayers();
      markersById.clear();

      filtered.forEach((entry) => {
        const marker = L.marker([entry.location.latitude, entry.location.longitude]).addTo(markerLayer);
        marker.on('click', function () {
          focusEntry(entry);
        });
        markersById.set(entry.politician_id, marker);
      });

      renderResults(filtered, focusEntry);

      if (filtered.length > 0) {
        const bounds = L.latLngBounds(filtered.map((entry) => [entry.location.latitude, entry.location.longitude]));
        map.fitBounds(bounds, { padding: [20, 20], maxZoom: 13 });
      } else {
        map.setView(GOIAS_CENTER, GOIAS_ZOOM);
      }

      setStatus(`${filtered.length} resultado(s) encontrado(s).`, 'info');
    }

    document.getElementById('filtro-geral')?.addEventListener('input', applyFilter);

    const listToggle = document.getElementById('mapa-politico-toggle-list');
    const resultsPanel = document.querySelector('.mapa-politico-results');
    listToggle?.addEventListener('click', function () {
      resultsPanel?.classList.toggle('mapa-politico-results-open');
    });

    document.getElementById('mapa-politico-close')?.addEventListener('click', closeModal);
    document.getElementById('mapa-politico-modal')?.addEventListener('click', function (event) {
      if (event.target?.id === 'mapa-politico-modal') {
        closeModal();
      }
    });

    applyFilter();
  }

  document.addEventListener('DOMContentLoaded', initLeafletMap);
})();
