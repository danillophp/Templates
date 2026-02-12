async function initMap() {
  const mapElement = document.getElementById('map');
  if (!mapElement) return;

  const map = L.map(mapElement, {
    center: [15, 0],
    zoom: 2,
    minZoom: 2,
    worldCopyJump: true,
  });

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    maxZoom: 19,
  }).addTo(map);

  const res = await fetch('/api/map-data');
  const data = await res.json();

  data.locations.forEach((location) => {
    const marker = L.marker([location.latitude, location.longitude]).addTo(map);
    marker.on('click', () => openDetails(location));
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

function loadLeafletScript() {
  if (document.getElementById('map') === null) return;

  const leafletCss = document.createElement('link');
  leafletCss.rel = 'stylesheet';
  leafletCss.href = 'https://unpkg.com/leaflet/dist/leaflet.css';
  document.head.appendChild(leafletCss);

  const script = document.createElement('script');
  script.src = 'https://unpkg.com/leaflet/dist/leaflet.js';
  script.async = true;
  script.defer = true;
  script.onload = initMap;
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

document.addEventListener('DOMContentLoaded', loadLeafletScript);
