(function () {
  const GOIAS_CENTER = [-15.8270, -49.8362];
  const GOIAS_ZOOM = 7;

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

  function setStatus(message, type = 'info') {
    const el = document.getElementById('mapa-politico-status');
    if (!el) return;
    el.textContent = message;
    el.dataset.type = type;
  }

  function createMarkerIcon() {
    return L.divIcon({
      className: 'mapa-politico-custom-marker',
      html: '<span></span>',
      iconSize: [18, 18],
      iconAnchor: [9, 9],
      popupAnchor: [0, -10],
    });
  }

  function createUserIcon() {
    return L.divIcon({
      className: 'mapa-politico-user-marker',
      html: '<span></span>',
      iconSize: [18, 18],
      iconAnchor: [9, 9],
      popupAnchor: [0, -10],
    });
  }

  function buildPopupHtml(entry) {
    return `
      <div class="mapa-politico-popup">
        <strong>${escapeHtml(entry.full_name)}</strong>
        <div>${escapeHtml(entry.position)} · ${escapeHtml(entry.party)}</div>
        <div>${escapeHtml(entry.location.city)} - ${escapeHtml(entry.location.state || '')}</div>
        <div>CEP: ${escapeHtml(entry.location.postal_code || '-')}</div>
        <button type="button" class="mapa-politico-route-btn" data-route-id="${entry.politician_id}">📍 Traçar rota</button>
      </div>
    `;
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
        <p><button type="button" class="mapa-politico-route-btn" data-route-id="${entry.politician_id}">📍 Traçar rota até este político</button></p>
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

  function renderResults(entries, onSelect, onRoute) {
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
          <small>CEP: ${escapeHtml(entry.location.postal_code)}</small>
        </button>
        <button type="button" class="mapa-politico-route-btn" data-route-id="${entry.politician_id}">📍 Traçar rota</button>
      </div>
    `).join('');

    list.querySelectorAll('.mapa-politico-result-item').forEach((container) => {
      const id = Number(container.dataset.id);
      const selected = entries.find((entry) => entry.politician_id === id);
      if (!selected) return;

      container.querySelector('.mapa-politico-select-btn')?.addEventListener('click', () => onSelect(selected));
      container.querySelector('.mapa-politico-route-btn')?.addEventListener('click', () => onRoute(selected));
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

    if (!payload?.success) {
      mapEl.innerHTML = '<p>Erro ao carregar dados do mapa.</p>';
      setStatus('Falha ao carregar os dados do mapa.', 'error');
      return;
    }

    const allEntries = payload?.data?.entries || [];
    const markerLayer = L.layerGroup().addTo(map);
    const markerIcon = createMarkerIcon();
    const userIcon = createUserIcon();

    let routingControl = null;
    let userMarker = null;

    function clearRoute() {
      if (routingControl) {
        map.removeControl(routingControl);
        routingControl = null;
      }
      if (userMarker) {
        map.removeLayer(userMarker);
        userMarker = null;
      }
      setStatus('Rota limpa.', 'info');
    }

    async function traceRoute(entry) {
      if (!navigator.geolocation) {
        setStatus('Seu dispositivo não suporta geolocalização.', 'error');
        return;
      }

      setStatus('Obtendo sua localização atual...', 'loading');

      navigator.geolocation.getCurrentPosition((position) => {
        const userLat = position.coords.latitude;
        const userLng = position.coords.longitude;
        const targetLat = Number(entry.location.latitude);
        const targetLng = Number(entry.location.longitude);

        if (!Number.isFinite(targetLat) || !Number.isFinite(targetLng)) {
          setStatus('Localização do político inválida para traçar rota.', 'error');
          return;
        }

        if (routingControl) {
          map.removeControl(routingControl);
          routingControl = null;
        }

        if (userMarker) {
          map.removeLayer(userMarker);
        }

        userMarker = L.marker([userLat, userLng], { icon: userIcon }).addTo(map).bindPopup('Sua localização atual');

        routingControl = L.Routing.control({
          waypoints: [
            L.latLng(userLat, userLng),
            L.latLng(targetLat, targetLng),
          ],
          lineOptions: {
            styles: [{ color: '#1f4e8c', opacity: 0.9, weight: 5 }],
            addWaypoints: false,
          },
          showAlternatives: false,
          draggableWaypoints: false,
          fitSelectedRoutes: true,
          routeWhileDragging: false,
          createMarker: function (i, waypoint) {
            if (i === 0) {
              return L.marker(waypoint.latLng, { icon: userIcon }).bindPopup('Sua localização atual');
            }
            return L.marker(waypoint.latLng, { icon: markerIcon }).bindPopup(entry.full_name);
          },
          router: L.Routing.osrmv1({
            serviceUrl: MapaPoliticoConfig.osrmServiceUrl,
            profile: 'driving',
          }),
        }).addTo(map);

        routingControl.on('routingerror', function () {
          setStatus('Não foi possível calcular a rota agora. Tente novamente em instantes.', 'error');
        });

        routingControl.on('routesfound', function () {
          setStatus(`Rota traçada até ${entry.full_name}.`, 'success');
        });
      }, (error) => {
        if (error.code === error.PERMISSION_DENIED) {
          setStatus('Permissão de localização negada. Ative o GPS para traçar a rota.', 'error');
        } else if (error.code === error.POSITION_UNAVAILABLE) {
          setStatus('Localização indisponível no momento.', 'error');
        } else if (error.code === error.TIMEOUT) {
          setStatus('Tempo esgotado ao obter localização. Tente novamente.', 'error');
        } else {
          setStatus('Não foi possível obter sua localização.', 'error');
        }
      }, {
        enableHighAccuracy: true,
        timeout: 12000,
        maximumAge: 30000,
      });
    }

    const filters = {
      name: document.getElementById('filtro-nome'),
      party: document.getElementById('filtro-partido'),
      city: document.getElementById('filtro-cidade'),
      cep: document.getElementById('filtro-cep'),
      clear: document.getElementById('filtro-limpar'),
      clearRoute: document.getElementById('rota-limpar'),
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
        const marker = L.marker([entry.location.latitude, entry.location.longitude], { icon: markerIcon }).addTo(markerLayer);
        marker.bindPopup(buildPopupHtml(entry));
        marker.on('click', () => openModal(entry));
      });

      renderResults(
        filtered,
        (entry) => {
          map.setView([entry.location.latitude, entry.location.longitude], 13);
          openModal(entry);
        },
        traceRoute
      );

      setStatus(`${filtered.length} resultado(s) encontrado(s).`, 'info');
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

    filters.clearRoute?.addEventListener('click', clearRoute);

    // Delegação para botões de rota presentes em popups/modais
    document.addEventListener('click', (event) => {
      const routeBtn = event.target.closest('.mapa-politico-route-btn');
      if (!routeBtn) return;

      const id = Number(routeBtn.getAttribute('data-route-id'));
      const entry = allEntries.find((item) => item.politician_id === id);
      if (!entry) {
        setStatus('Não foi possível localizar o político para traçar rota.', 'error');
        return;
      }

      traceRoute(entry);
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
