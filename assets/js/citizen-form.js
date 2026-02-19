// Formulário cidadão com UX moderna e envio AJAX.
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

const latEl = document.getElementById('latitude');
const lngEl = document.getElementById('longitude');
const feedback = document.getElementById('feedback');

const setLatLng = (lat, lng) => { latEl.value = lat; lngEl.value = lng; };
setLatLng(-23.55, -46.63);
marker.on('dragend', (e) => setLatLng(e.target.getLatLng().lat, e.target.getLatLng().lng));

let geocodeTimer = null;
const geocode = async () => {
  const address = document.getElementById('address').value.trim();
  const cep = document.getElementById('cep').value.trim();
  if (!address || !cep) return;

  const q = encodeURIComponent(`${address}, ${cep}, Brasil`);
  const url = `https://nominatim.openstreetmap.org/search?format=json&q=${q}`;

  try {
    const res = await fetch(url, { headers: { 'Accept-Language': 'pt-BR' } });
    const data = await res.json();
    if (data[0]) {
      const lat = parseFloat(data[0].lat);
      const lng = parseFloat(data[0].lon);
      marker.setLatLng([lat, lng]);
      map.setView([lat, lng], 17);
      setLatLng(lat, lng);
    }
  } catch (err) {
    console.error('Falha geocoding:', err);
  }
};

const debouncedGeocode = () => {
  clearTimeout(geocodeTimer);
  geocodeTimer = setTimeout(geocode, 400);
};

document.getElementById('address').addEventListener('input', debouncedGeocode);
document.getElementById('cep').addEventListener('input', debouncedGeocode);

const form = document.getElementById('citizenForm');
form.addEventListener('submit', async (e) => {
  e.preventDefault();

  if (!form.checkValidity()) {
    form.classList.add('was-validated');
    feedback.innerHTML = '<div class="alert alert-danger">Verifique os campos obrigatórios.</div>';
    return;
  }

  feedback.innerHTML = '<div class="alert alert-info">Enviando solicitação...</div>';
  const fd = new FormData(form);

  try {
    const res = await fetch('?r=api/citizen/create', { method: 'POST', body: fd });
    const json = await res.json();
    feedback.innerHTML = `<div class="alert ${json.ok ? 'alert-success' : 'alert-danger'}">${json.message}</div>`;
    if (json.ok) {
      form.reset();
      form.classList.remove('was-validated');
    }
  } catch (err) {
    feedback.innerHTML = '<div class="alert alert-danger">Erro de comunicação. Tente novamente.</div>';
  }
});
