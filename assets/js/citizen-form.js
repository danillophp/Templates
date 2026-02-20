let googleMap;
let pickupModal;
let selectedPointId = null;

const feedback = document.getElementById('feedback');
const latEl = document.getElementById('latitude');
const lngEl = document.getElementById('longitude');

flatpickr('#pickup_datetime', {
  enableTime: true,
  dateFormat: 'Y-m-d H:i',
  minDate: 'today',
  time_24hr: true,
});

const confirmMap = L.map('confirmMap').setView([-23.55, -46.63], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  maxZoom: 19,
  attribution: '&copy; OpenStreetMap',
}).addTo(confirmMap);
const confirmMarker = L.marker([-23.55, -46.63], { draggable: true }).addTo(confirmMap);

function setLatLng(lat, lng) {
  latEl.value = lat;
  lngEl.value = lng;
}

setLatLng(-23.55, -46.63);
confirmMarker.on('dragend', (event) => {
  const pos = event.target.getLatLng();
  setLatLng(pos.lat, pos.lng);
});

async function loadPoints() {
  const res = await fetch('?r=api/citizen/points');
  const json = await res.json();
  return json.data || [];
}

window.initCitizenGoogleMap = async function initCitizenGoogleMap() {
  pickupModal = new bootstrap.Modal(document.getElementById('requestModal'));

  googleMap = new google.maps.Map(document.getElementById('mainGoogleMap'), {
    center: { lat: -23.55, lng: -46.63 },
    zoom: 12,
  });

  const points = await loadPoints();
  points.forEach((point) => {
    const marker = new google.maps.Marker({
      position: { lat: Number(point.latitude), lng: Number(point.longitude) },
      map: googleMap,
      title: point.titulo,
    });

    marker.addListener('click', () => {
      selectedPointId = point.id;
      setLatLng(Number(point.latitude), Number(point.longitude));
      confirmMarker.setLatLng([Number(point.latitude), Number(point.longitude)]);
      confirmMap.setView([Number(point.latitude), Number(point.longitude)], 16);
      pickupModal.show();
      setTimeout(() => confirmMap.invalidateSize(), 300);
    });
  });
};

if (!window.GOOGLE_MAPS_KEY) {
  document.getElementById('mainGoogleMap').innerHTML = '<div class="alert alert-warning">Defina GOOGLE_MAPS_API_KEY no config/app.php para habilitar o mapa principal com pins.</div>';
}

let geocodeTimer;
const geocode = async () => {
  const address = document.getElementById('address').value.trim();
  const cep = document.getElementById('cep').value.trim();
  if (!address || !cep) return;

  const q = encodeURIComponent(`${address}, ${cep}, Brasil`);
  const url = `https://nominatim.openstreetmap.org/search?format=json&q=${q}`;

  try {
    const res = await fetch(url, { headers: { 'Accept-Language': 'pt-BR' } });
    const data = await res.json();
    if (!data[0]) return;

    const lat = Number(data[0].lat);
    const lng = Number(data[0].lon);
    confirmMarker.setLatLng([lat, lng]);
    confirmMap.setView([lat, lng], 17);
    setLatLng(lat, lng);
  } catch (err) {
    console.error(err);
  }
};

document.getElementById('address').addEventListener('input', () => {
  clearTimeout(geocodeTimer);
  geocodeTimer = setTimeout(geocode, 500);
});
document.getElementById('cep').addEventListener('input', () => {
  clearTimeout(geocodeTimer);
  geocodeTimer = setTimeout(geocode, 500);
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
  feedback.innerHTML = `<div class="alert ${json.ok ? 'alert-success' : 'alert-danger'}">${json.message}</div>`;

  if (json.ok) {
    form.reset();
    form.classList.remove('was-validated');
  }
});
