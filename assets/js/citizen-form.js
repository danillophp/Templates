let googleMap;
let pickupModal;
let selectedPointId = null;

const feedback = document.getElementById('feedback');
const latEl = document.getElementById('latitude');
const lngEl = document.getElementById('longitude');

flatpickr('#pickup_datetime', { enableTime: true, dateFormat: 'Y-m-d H:i', minDate: 'today', time_24hr: true });

const confirmMap = L.map('confirmMap').setView([-23.55, -46.63], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(confirmMap);
const confirmMarker = L.marker([-23.55, -46.63], { draggable: true }).addTo(confirmMap);

function setLatLng(lat, lng) { latEl.value = lat; lngEl.value = lng; }
setLatLng(-23.55, -46.63);
confirmMarker.on('dragend', (e) => setLatLng(e.target.getLatLng().lat, e.target.getLatLng().lng));

async function loadPoints() {
  const res = await fetch('?r=api/citizen/points');
  const json = await res.json();
  return json.data || [];
}

window.initCitizenGoogleMap = async function initCitizenGoogleMap() {
  pickupModal = new bootstrap.Modal(document.getElementById('requestModal'));
  googleMap = new google.maps.Map(document.getElementById('mainGoogleMap'), { center: { lat: -23.55, lng: -46.63 }, zoom: 12 });
  const points = await loadPoints();

  points.forEach((point) => {
    const marker = new google.maps.Marker({ position: { lat: Number(point.latitude), lng: Number(point.longitude) }, map: googleMap, title: point.titulo });
    marker.addListener('click', () => {
      selectedPointId = point.id;
      setLatLng(Number(point.latitude), Number(point.longitude));
      confirmMarker.setLatLng([Number(point.latitude), Number(point.longitude)]);
      confirmMap.setView([Number(point.latitude), Number(point.longitude)], 16);
      pickupModal.show();
      setTimeout(() => confirmMap.invalidateSize(), 250);
    });
  });
};

if (!window.GOOGLE_MAPS_KEY) {
  document.getElementById('mainGoogleMap').innerHTML = '<div class="alert alert-warning">Configure GOOGLE_MAPS_API_KEY para habilitar o mapa principal.</div>';
}

let geocodeTimer;
const geocode = async () => {
  const address = document.getElementById('address').value.trim();
  const cep = document.getElementById('cep').value.trim();
  if (!address || !cep) return;

  const q = encodeURIComponent(`${address}, ${cep}, Brasil`);
  const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${q}`, { headers: { 'Accept-Language': 'pt-BR' } });
  const data = await res.json();
  if (!data[0]) return;

  const lat = Number(data[0].lat);
  const lng = Number(data[0].lon);
  confirmMarker.setLatLng([lat, lng]);
  confirmMap.setView([lat, lng], 16);
  setLatLng(lat, lng);
};

['address', 'cep'].forEach((id) => {
  document.getElementById(id).addEventListener('input', () => {
    clearTimeout(geocodeTimer);
    geocodeTimer = setTimeout(geocode, 450);
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
  fd.append('point_id', selectedPointId || '');
  feedback.innerHTML = '<div class="alert alert-info">Enviando solicitação...</div>';

  const res = await fetch('?r=api/citizen/create', { method: 'POST', body: fd });
  const json = await res.json();
  feedback.innerHTML = `<div class="alert ${json.ok ? 'alert-success' : 'alert-danger'}">${json.message} ${json.protocolo ? `<br><strong>Protocolo:</strong> ${json.protocolo}` : ''}</div>`;

  if (json.ok) {
    showToast('Solicitação registrada com sucesso.', 'success');
    form.reset();
    form.classList.remove('was-validated');
  }
});

document.getElementById('btnTrack').addEventListener('click', async () => {
  const protocol = document.getElementById('trackProtocol').value;
  const phone = document.getElementById('trackPhone').value;
  const result = document.getElementById('trackResult');
  result.innerHTML = '<div class="text-muted">Consultando...</div>';

  const params = new URLSearchParams({ protocol, phone });
  const res = await fetch(`?r=api/citizen/track&${params.toString()}`);
  const json = await res.json();

  if (!json.ok) {
    result.innerHTML = `<div class="alert alert-danger">${json.message}</div>`;
    return;
  }

  result.innerHTML = `<div class="alert alert-success"><strong>Status:</strong> ${json.data.status}<br><strong>Data prevista:</strong> ${json.data.data_solicitada}</div>`;
});
