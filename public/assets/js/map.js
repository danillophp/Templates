async function initMap() {
  const mapElement = document.getElementById('map');
  if (!mapElement) return;

  const map = new google.maps.Map(mapElement, {
    center: { lat: 15, lng: 0 },
    zoom: 2,
    minZoom: 2,
    streetViewControl: false,
    mapTypeControl: false,
  });

  const res = await fetch('/api/map-data');
  const data = await res.json();

  data.locations.forEach((location) => {
    const marker = new google.maps.Marker({
      position: { lat: location.latitude, lng: location.longitude },
      map,
      title: location.location_name,
    });

    marker.addListener('click', () => openDetails(location));
  });
}

function openDetails(location) {
  const modal = document.getElementById('details-modal');
  const body = document.getElementById('details-body');
  if (!modal || !body) return;

  const politiciansHtml = location.politicians.length
    ? location.politicians.map((p) => `
      <article class="politician-card">
        <h3>${escapeHtml(p.full_name)}</h3>
        ${p.photo_path ? `<img src="${p.photo_path}" alt="Foto de ${escapeHtml(p.full_name)}">` : ''}
        <p><strong>Cargo:</strong> ${escapeHtml(p.position || '-')}</p>
        <p><strong>Partido:</strong> ${escapeHtml(p.party || '-')}</p>
        <p><strong>Idade:</strong> ${escapeHtml(String(p.age || '-'))}</p>
        <p><strong>Biografia:</strong> ${escapeHtml(p.biography || '-')}</p>
        <p><strong>Histórico:</strong> ${escapeHtml(p.career_history || '-')}</p>
        <p><strong>Contato:</strong> ${escapeHtml(p.phone || '-')} / ${escapeHtml(p.email || '-')}</p>
        <p><strong>Assessores:</strong> ${escapeHtml(p.advisors || '-')}</p>
      </article>
    `).join('')
    : '<p>Nenhuma figura política vinculada a esta localidade.</p>';

  body.innerHTML = `
    <h2>${escapeHtml(location.location_name)}</h2>
    <p><strong>Município:</strong> ${escapeHtml(location.city_info || '-')}</p>
    <p><strong>Região:</strong> ${escapeHtml(location.region_info || '-')}</p>
    ${politiciansHtml}
  `;

  modal.classList.add('open');
  modal.setAttribute('aria-hidden', 'false');
}

function escapeHtml(value) {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function loadGoogleMapsScript() {
  if (document.getElementById('map') === null) return;
  const key = window.APP_CONFIG?.googleMapsApiKey;
  const script = document.createElement('script');
  script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(key)}&callback=initMap`;
  script.async = true;
  script.defer = true;
  window.initMap = initMap;
  document.head.appendChild(script);
}

document.addEventListener('click', (event) => {
  const modal = document.getElementById('details-modal');
  if (!modal) return;
  if (event.target.id === 'close-modal' || event.target === modal) {
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
  }
});

document.addEventListener('DOMContentLoaded', loadGoogleMapsScript);
