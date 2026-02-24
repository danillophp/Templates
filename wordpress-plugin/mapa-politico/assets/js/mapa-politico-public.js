(function () {
  const cfg = window.MapaPoliticoConfig || {};
  const GOIAS_CENTER = [Number(cfg.defaultLat || -15.8270), Number(cfg.defaultLng || -49.8362)];
  const GOIAS_ZOOM = Number(cfg.defaultZoom || 7);

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }


  function isMobileDevice() {
    return /Android|iPhone|iPad|iPod|IEMobile|Opera Mini/i.test(navigator.userAgent || '');
  }

  function setStatus(message, type = 'info') {
    const el = document.getElementById('mapa-politico-status');
    if (!el) return;
    el.textContent = message;
    el.dataset.type = type;
  }

  function sanitizePhoneToTel(phone) {
    const digits = String(phone || '').replace(/\D/g, '');
    if (!digits) return '';
    return `+${digits}`;
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

  function buildGoogleMapsDirectionUrl(originLat, originLng, destLat, destLng) {
    const url = new URL('https://www.google.com/maps/dir/');
    url.searchParams.set('api', '1');
    url.searchParams.set('origin', `${originLat},${originLng}`);
    url.searchParams.set('destination', `${destLat},${destLng}`);
    url.searchParams.set('travelmode', 'driving');
    return url.toString();
  }

  function buildWazeDirectionUrl(destLat, destLng) {
    const url = new URL('https://waze.com/ul');
    url.searchParams.set('ll', `${destLat},${destLng}`);
    url.searchParams.set('navigate', 'yes');
    return url.toString();
  }

  function buildPopupHtml(entry) {
    const tel = sanitizePhoneToTel(entry.phone);
    const phoneText = escapeHtml(entry.phone || 'Não informado');

    return `
      <div class="mapa-politico-popup">
        <strong>${escapeHtml(entry.full_name)}</strong>
        <div>${escapeHtml(entry.position)} · ${escapeHtml(entry.party)}</div>
        <div>${escapeHtml(entry.location.city)} - ${escapeHtml(entry.location.state || '')}</div>
        <div>CEP: ${escapeHtml(entry.location.postal_code || '-')}</div>
        <div class="mapa-politico-actions">
          <button type="button" class="mapa-politico-route-btn" data-route-id="${entry.politician_id}">🛣️ Traçar rota no mapa</button>
          <button type="button" class="mapa-politico-nav-btn" data-nav-id="${entry.politician_id}">📍 Como chegar</button>
          ${tel ? `<a class="mapa-politico-call-btn" href="tel:${tel}">📞 Ligar</a>` : `<span class="mapa-politico-phone-text">📞 ${phoneText}</span>`}
        </div>
      </div>
    `;
  }

  function openModal(entry) {
    const modal = document.getElementById('mapa-politico-modal');
    const body = document.getElementById('mapa-politico-modal-body');
    if (!modal || !body) return;

    const tel = sanitizePhoneToTel(entry.phone);
    const phoneBlock = tel
      ? `<p><a class="mapa-politico-call-btn" href="tel:${tel}">📞 Ligar: ${escapeHtml(entry.phone)}</a></p>`
      : `<p><strong>Telefone:</strong> ${escapeHtml(entry.phone || 'Não informado')}</p>`;

    body.innerHTML = `
      <article class="mapa-politico-card">
        <h2>${escapeHtml(entry.full_name)}</h2>
        ${entry.photo_url ? `<img src="${escapeHtml(entry.photo_url)}" alt="Foto de ${escapeHtml(entry.full_name)}">` : ''}
        <p><strong>Cargo:</strong> ${escapeHtml(entry.position)}</p>
        <p><strong>Partido:</strong> ${escapeHtml(entry.party)}</p>
        <p><strong>Cidade:</strong> ${escapeHtml(entry.location.city)}</p>
        <p><strong>Estado:</strong> ${escapeHtml(entry.location.state)}</p>
        <p><strong>CEP:</strong> ${escapeHtml(entry.location.postal_code)}</p>
        ${phoneBlock}
        <p><strong>Biografia:</strong> ${escapeHtml(entry.biography)}</p>
        <p><strong>Histórico:</strong> ${escapeHtml(entry.career_history)}</p>
        <div class="mapa-politico-actions">
          <button type="button" class="mapa-politico-route-btn" data-route-id="${entry.politician_id}">🛣️ Traçar rota no mapa</button>
          <button type="button" class="mapa-politico-nav-btn" data-nav-id="${entry.politician_id}">📍 Como chegar</button>
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

  function renderResults(entries, onSelect, onRoute, onNavigate) {
    const list = document.getElementById('mapa-politico-results-list');
    if (!list) return;

    if (!entries.length) {
      list.innerHTML = '<p>Nenhum resultado encontrado.</p>';
      return;
    }

    list.innerHTML = entries.map((entry) => {
      const tel = sanitizePhoneToTel(entry.phone);
      return `
      <div class="mapa-politico-result-item" data-id="${entry.politician_id}">
        <button type="button" class="mapa-politico-select-btn">
          <strong>${escapeHtml(entry.full_name)}</strong>
          <span>${escapeHtml(entry.party)} · ${escapeHtml(entry.location.city)} (${escapeHtml(entry.location.state)})</span>
          <small>CEP: ${escapeHtml(entry.location.postal_code)}</small>
        </button>
        <div class="mapa-politico-actions">
          <button type="button" class="mapa-politico-route-btn" data-route-id="${entry.politician_id}">🛣️ Traçar rota</button>
          <button type="button" class="mapa-politico-nav-btn" data-nav-id="${entry.politician_id}">📍 Como chegar</button>
          ${tel ? `<a class="mapa-politico-call-btn" href="tel:${tel}">📞 Ligar</a>` : `<span class="mapa-politico-phone-text">📞 ${escapeHtml(entry.phone || 'Não informado')}</span>`}
        </div>
      </div>`;
    }).join('');

    list.querySelectorAll('.mapa-politico-result-item').forEach((container) => {
      const id = Number(container.dataset.id);
      const selected = entries.find((entry) => entry.politician_id === id);
      if (!selected) return;

      container.querySelector('.mapa-politico-select-btn')?.addEventListener('click', () => onSelect(selected));
      container.querySelector('.mapa-politico-route-btn')?.addEventListener('click', () => onRoute(selected));
      container.querySelector('.mapa-politico-nav-btn')?.addEventListener('click', () => onNavigate(selected));
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

    L.tileLayer(cfg.tilesUrl, {
      attribution: cfg.tilesAttribution,
      maxZoom: 19,
    }).addTo(map);

    let currentEntries = [];
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

    function getCurrentPosition() {
      return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
          reject({ code: 'UNSUPPORTED' });
          return;
        }

        navigator.geolocation.getCurrentPosition(resolve, reject, {
          enableHighAccuracy: true,
          timeout: 12000,
          maximumAge: 30000,
        });
      });
    }

    async function navigateExternal(entry) {
      setStatus('Obtendo sua localização para abrir navegação externa...', 'loading');

      try {
        const pos = await getCurrentPosition();
        const originLat = pos.coords.latitude;
        const originLng = pos.coords.longitude;
        const destLat = Number(entry.location.latitude);
        const destLng = Number(entry.location.longitude);

        if (!Number.isFinite(destLat) || !Number.isFinite(destLng)) {
          setStatus('Destino inválido para navegação.', 'error');
          return;
        }

        const googleUrl = buildGoogleMapsDirectionUrl(originLat, originLng, destLat, destLng);
        const wazeUrl = buildWazeDirectionUrl(destLat, destLng);

        if (isMobileDevice()) {
          setStatus('Tentando abrir Waze... caso não abra, Google Maps será usado.', 'info');
          const fallback = setTimeout(() => {
            window.location.href = googleUrl;
          }, 900);
          window.location.href = wazeUrl;
          setTimeout(() => clearTimeout(fallback), 1400);
        } else {
          setStatus('Abrindo rota no Google Maps...', 'success');
          window.open(googleUrl, '_blank', 'noopener,noreferrer');
        }
      } catch (error) {
        if (error?.code === 'UNSUPPORTED') {
          setStatus('Seu dispositivo não suporta geolocalização.', 'error');
        } else if (error?.code === error.PERMISSION_DENIED) {
          setStatus('Permissão de localização negada. Ative o GPS para continuar.', 'error');
        } else if (error?.code === error.POSITION_UNAVAILABLE) {
          setStatus('Localização indisponível no momento.', 'error');
        } else if (error?.code === error.TIMEOUT) {
          setStatus('Tempo esgotado ao obter localização. Tente novamente.', 'error');
        } else {
          setStatus('Falha ao iniciar navegação externa.', 'error');
        }
      }
    }

    async function traceRoute(entry) {
      setStatus('Obtendo sua localização atual...', 'loading');

      try {
        const position = await getCurrentPosition();
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
            serviceUrl: cfg.osrmServiceUrl,
            profile: 'driving',
          }),
        }).addTo(map);

        routingControl.on('routingerror', function () {
          setStatus('Não foi possível calcular a rota agora. Tente novamente em instantes.', 'error');
        });

        routingControl.on('routesfound', function () {
          setStatus(`Rota traçada até ${entry.full_name}.`, 'success');
        });
      } catch (error) {
        if (error?.code === 'UNSUPPORTED') {
          setStatus('Seu dispositivo não suporta geolocalização.', 'error');
        } else if (error?.code === error.PERMISSION_DENIED) {
          setStatus('Permissão de localização negada. Ative o GPS para traçar a rota.', 'error');
        } else if (error?.code === error.POSITION_UNAVAILABLE) {
          setStatus('Localização indisponível no momento.', 'error');
        } else if (error?.code === error.TIMEOUT) {
          setStatus('Tempo esgotado ao obter localização. Tente novamente.', 'error');
        } else {
          setStatus('Não foi possível obter sua localização.', 'error');
        }
      }
    }

    const filters = {
      name: document.getElementById('filtro-nome'),
      party: document.getElementById('filtro-partido'),
      city: document.getElementById('filtro-cidade'),
      clear: document.getElementById('filtro-limpar'),
      clearRoute: document.getElementById('rota-limpar'),
    };

    const renderEntries = (entries) => {
      markerLayer.clearLayers();

      entries.forEach((entry) => {
        const marker = L.marker([entry.location.latitude, entry.location.longitude], { icon: markerIcon }).addTo(markerLayer);
        marker.bindPopup(buildPopupHtml(entry));
        marker.on('click', () => openModal(entry));
      });

      renderResults(
        entries,
        (entry) => {
          map.setView([entry.location.latitude, entry.location.longitude], 13);
          openModal(entry);
        },
        traceRoute,
        navigateExternal
      );

      if (entries.length > 0) {
        const bounds = L.latLngBounds(entries.map((entry) => [entry.location.latitude, entry.location.longitude]));
        map.fitBounds(bounds, { padding: [20, 20], maxZoom: 13 });
      } else {
        map.setView(GOIAS_CENTER, GOIAS_ZOOM);
      }

      setStatus(`${entries.length} resultado(s) encontrado(s).`, 'info');
    };

    const fetchEntries = async () => {
      const body = new URLSearchParams({
        action: 'mapa_politico_search',
        nonce: cfg.nonce || '',
        name: String(filters.name?.value || '').trim(),
        party: String(filters.party?.value || '').trim(),
        city: String(filters.city?.value || '').trim(),
      });

      const response = await fetch(cfg.ajaxUrl || '', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: body.toString(),
      });

      const payload = await response.json();
      if (!payload?.success) {
        throw new Error('Falha ao filtrar dados do mapa.');
      }

      return payload?.data?.entries || [];
    };

    let searchDebounce = null;

    const applyFilters = async () => {
      try {
        setStatus('Filtrando resultados...', 'loading');
        currentEntries = await fetchEntries();
        renderEntries(currentEntries);
      } catch (error) {
        setStatus(error.message || 'Falha ao filtrar resultados.', 'error');
      }
    };

    const scheduleFilter = () => {
      if (searchDebounce) {
        window.clearTimeout(searchDebounce);
      }
      searchDebounce = window.setTimeout(() => {
        applyFilters();
      }, 350);
    };

    [filters.name, filters.party, filters.city].forEach((input) => {
      input?.addEventListener('input', scheduleFilter);
    });

    filters.clear?.addEventListener('click', () => {
      [filters.name, filters.party, filters.city].forEach((input) => {
        if (input) input.value = '';
      });
      scheduleFilter();
    });

    filters.clearRoute?.addEventListener('click', clearRoute);

    // Delegação para botões em popups/modais
    document.addEventListener('click', (event) => {
      const routeBtn = event.target.closest('.mapa-politico-route-btn');
      if (routeBtn) {
        const id = Number(routeBtn.getAttribute('data-route-id'));
        const entry = currentEntries.find((item) => item.politician_id === id);
        if (!entry) {
          setStatus('Não foi possível localizar o político para traçar rota.', 'error');
          return;
        }
        traceRoute(entry);
        return;
      }

      const navBtn = event.target.closest('.mapa-politico-nav-btn');
      if (navBtn) {
        const id = Number(navBtn.getAttribute('data-nav-id'));
        const entry = currentEntries.find((item) => item.politician_id === id);
        if (!entry) {
          setStatus('Não foi possível localizar o político para navegação externa.', 'error');
          return;
        }
        navigateExternal(entry);
      }
    });

    await applyFilters();
  }

  document.addEventListener('DOMContentLoaded', initLeafletMap);
  document.addEventListener('click', (event) => {
    if (event.target?.id === 'mapa-politico-close' || event.target?.id === 'mapa-politico-modal') {
      closeModal();
    }
  });
})();
