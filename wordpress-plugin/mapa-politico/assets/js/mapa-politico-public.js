(function () {
  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function openModal(location) {
    const modal = document.getElementById('mapa-politico-modal');
    const body = document.getElementById('mapa-politico-modal-body');
    if (!modal || !body) return;

    const politiciansHtml = (location.politicians || []).length
      ? location.politicians.map((p) => `
          <article class="mapa-politico-card">
            <h3>${escapeHtml(p.full_name)}</h3>
            ${p.photo_url ? `<img src="${escapeHtml(p.photo_url)}" alt="Foto de ${escapeHtml(p.full_name)}">` : ''}
            <p><strong>Cargo:</strong> ${escapeHtml(p.position)}</p>
            <p><strong>Partido:</strong> ${escapeHtml(p.party)}</p>
            <p><strong>Idade:</strong> ${escapeHtml(p.age)}</p>
            <p><strong>Biografia:</strong> ${escapeHtml(p.biography)}</p>
            <p><strong>Histórico:</strong> ${escapeHtml(p.career_history)}</p>
            <p><strong>História local:</strong> ${escapeHtml(p.municipality_history)}</p>
            <p><strong>Contato:</strong> ${escapeHtml(p.phone)} / ${escapeHtml(p.email)}</p>
            <p><strong>Assessores:</strong> ${escapeHtml(p.advisors)}</p>
          </article>
      `).join('')
      : '<p>Nenhuma figura política vinculada a esta localidade.</p>';

    body.innerHTML = `
      <h2>${escapeHtml(location.location_name)}</h2>
      <p><strong>Município:</strong> ${escapeHtml(location.city_info)}</p>
      <p><strong>Região:</strong> ${escapeHtml(location.region_info)}</p>
      ${politiciansHtml}
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

  async function initMap() {
    const mapEl = document.getElementById('mapa-politico-map');
    if (!mapEl) return;

    const map = new google.maps.Map(mapEl, {
      center: { lat: 12, lng: 0 },
      zoom: 2,
      minZoom: 2,
      streetViewControl: false,
      mapTypeControl: false,
    });

    const params = new URLSearchParams({
      action: 'mapa_politico_data',
      nonce: MapaPoliticoConfig.nonce,
    });

    const res = await fetch(`${MapaPoliticoConfig.ajaxUrl}?${params.toString()}`);
    const payload = await res.json();
    const locations = payload?.data?.locations || [];

    locations.forEach((location) => {
      const marker = new google.maps.Marker({
        map,
        position: { lat: Number(location.latitude), lng: Number(location.longitude) },
        title: location.location_name,
      });

      marker.addListener('click', () => openModal(location));
    });
  }

  function loadGoogleMaps() {
    const mapEl = document.getElementById('mapa-politico-map');
    if (!mapEl) return;

    const key = MapaPoliticoConfig.googleMapsApiKey || '';
    if (!key) {
      mapEl.innerHTML = '<p>Configure a Google Maps API Key no painel do plugin.</p>';
      return;
    }

    window.initMapaPoliticoMap = initMap;
    const script = document.createElement('script');
    script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(key)}&callback=initMapaPoliticoMap`;
    script.async = true;
    script.defer = true;
    document.head.appendChild(script);
  }

  document.addEventListener('DOMContentLoaded', loadGoogleMaps);
  document.addEventListener('click', (event) => {
    if (event.target?.id === 'mapa-politico-close' || event.target?.id === 'mapa-politico-modal') {
      closeModal();
    }
  });
})();
