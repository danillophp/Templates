const APP_BASE = (window.APP_BASE_PATH || "").replace(/\/$/, "");
const feedback = document.getElementById('feedback');
const latEl = document.getElementById('latitude');
const lngEl = document.getElementById('longitude');

flatpickr('#pickup_datetime', {
  enableTime: true,
  dateFormat: 'Y-m-d H:i',
  minDate: 'today',
  time_24hr: true,
});

const map = L.map('map').setView([-23.55, -46.63], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  maxZoom: 19,
  attribution: '&copy; OpenStreetMap',
}).addTo(map);

const marker = L.marker([-23.55, -46.63], { draggable: true }).addTo(map);
setLatLng(-23.55, -46.63);

function setLatLng(lat, lng) {
  latEl.value = lat;
  lngEl.value = lng;
}

marker.on('dragend', (event) => {
  const pos = event.target.getLatLng();
  setLatLng(pos.lat, pos.lng);
});

async function loadPoints() {
  try {
    const res = await fetch(`${APP_BASE}/?r=api/citizen/points`);
    const json = await res.json();
    if (!json.ok || !Array.isArray(json.data)) return;

    json.data.forEach((point) => {
      L.circleMarker([Number(point.latitude), Number(point.longitude)], {
        radius: 7,
        color: point.cor_pin || '#1d7de2',
        fillColor: point.cor_pin || '#1d7de2',
        fillOpacity: 0.85,
      }).addTo(map).bindPopup(point.titulo || 'Ponto de coleta');
    });
  } catch (err) {
    console.error('Falha ao carregar pontos:', err);
  }
}

let geocodeTimer;
const geocode = async () => {
  const address = document.getElementById('address').value.trim();
  const cep = document.getElementById('cep').value.trim();
  if (!address || !cep) return;

  try {
    const q = encodeURIComponent(`${address}, ${cep}, Brasil`);
    const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${q}`, {
      headers: { 'Accept-Language': 'pt-BR' },
    });
    const data = await res.json();
    if (!data[0]) return;

    const lat = Number(data[0].lat);
    const lng = Number(data[0].lon);
    marker.setLatLng([lat, lng]);
    map.setView([lat, lng], 16);
    setLatLng(lat, lng);
  } catch (err) {
    console.error('Falha no geocoding:', err);
  }
};

['address', 'cep'].forEach((id) => {
  document.getElementById(id).addEventListener('input', () => {
    clearTimeout(geocodeTimer);
    geocodeTimer = setTimeout(geocode, 500);
  });
});

document.getElementById('citizenForm').addEventListener('submit', async (event) => {
  event.preventDefault();
  const form = event.currentTarget;

  if (!form.checkValidity()) {
    form.classList.add('was-validated');
    feedback.innerHTML = '<div class="alert alert-danger">Verifique os campos obrigatórios.</div>';
    return;
  }

  const fd = new FormData(form);
  feedback.innerHTML = '<div class="alert alert-info">Enviando solicitação...</div>';

  try {
    const res = await fetch(`${APP_BASE}/?r=api/citizen/create`, { method: 'POST', body: fd });
    const json = await res.json();

    feedback.innerHTML = `<div class="alert ${json.ok ? 'alert-success' : 'alert-danger'}">${json.message} ${json.protocolo ? `<br><strong>Protocolo:</strong> ${json.protocolo}` : ''}</div>`;

    if (json.ok) {
      showToast('Solicitação registrada com sucesso.', 'success');
      form.reset();
      form.classList.remove('was-validated');
    }
  } catch (err) {
    feedback.innerHTML = '<div class="alert alert-danger">Erro de comunicação. Tente novamente.</div>';
  }
});

document.getElementById('btnTrack').addEventListener('click', async () => {
  const protocol = document.getElementById('trackProtocol').value;
  const phone = document.getElementById('trackPhone').value;
  const result = document.getElementById('trackResult');
  result.innerHTML = '<div class="text-muted">Consultando...</div>';

  try {
    const params = new URLSearchParams({ protocol, phone });
    const res = await fetch(`${APP_BASE}/?r=api/citizen/track&${params.toString()}`);
    const json = await res.json();

    if (!json.ok) {
      result.innerHTML = `<div class="alert alert-danger">${json.message}</div>`;
      return;
    }

    result.innerHTML = `<div class="alert alert-success"><strong>Status:</strong> ${json.data.status}<br><strong>Data prevista:</strong> ${json.data.data_solicitada}</div>`;
  } catch (err) {
    result.innerHTML = '<div class="alert alert-danger">Erro ao consultar protocolo.</div>';
  }
});

loadPoints();
