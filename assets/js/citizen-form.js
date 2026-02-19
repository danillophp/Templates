// Formulário cidadão com UX moderna, validações e envio AJAX.
flatpickr('#pickup_datetime', {
  enableTime: true,
  dateFormat: 'Y-m-d H:i',
  minDate: 'today',
  time_24hr: true,
  minuteIncrement: 15,
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
const cepEl = document.getElementById('cep');
const addressEl = document.getElementById('address');

const setLatLng = (lat, lng) => {
  latEl.value = Number(lat).toFixed(7);
  lngEl.value = Number(lng).toFixed(7);
};
setLatLng(-23.55, -46.63);
marker.on('dragend', (e) => {
  const pos = e.target.getLatLng();
  setLatLng(pos.lat, pos.lng);
});

const setFeedback = (message, kind = 'info') => {
  feedback.innerHTML = `<div class="alert alert-${kind}">${message}</div>`;
};

const normalizeCep = (value) => value.replace(/\D+/g, '').slice(0, 8);
const formatCep = (value) => {
  const c = normalizeCep(value);
  return c.length > 5 ? `${c.slice(0, 5)}-${c.slice(5)}` : c;
};

cepEl.addEventListener('input', () => {
  cepEl.value = formatCep(cepEl.value);
});

let geocodeTimer = null;
const geocode = async () => {
  const address = addressEl.value.trim();
  const cep = cepEl.value.trim();

  if (address.length < 6 || normalizeCep(cep).length < 8) {
    return;
  }

  const q = encodeURIComponent(`${address}, ${cep}, Santo André, SP, Brasil`);
  const url = `https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${q}`;

  try {
    const res = await fetch(url, {
      headers: {
        'Accept-Language': 'pt-BR',
      },
    });

    if (!res.ok) return;

    const data = await res.json();
    if (Array.isArray(data) && data[0]) {
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
  geocodeTimer = setTimeout(geocode, 450);
};

addressEl.addEventListener('input', debouncedGeocode);
cepEl.addEventListener('input', debouncedGeocode);

const form = document.getElementById('citizenForm');
form.addEventListener('submit', async (e) => {
  e.preventDefault();

  if (!latEl.value || !lngEl.value) {
    setFeedback('Não foi possível obter a localização. Revise CEP/endereço e ajuste o marcador no mapa.', 'danger');
    return;
  }

  if (!form.checkValidity()) {
    form.classList.add('was-validated');
    setFeedback('Verifique os campos obrigatórios.', 'danger');
    return;
  }

  setFeedback('Enviando solicitação...', 'info');
  const fd = new FormData(form);

  try {
    const res = await fetch('?r=api/citizen/create', { method: 'POST', body: fd });
    const json = await res.json();

    setFeedback(json.message, json.ok ? 'success' : 'danger');
    if (json.ok) {
      form.reset();
      form.classList.remove('was-validated');
      setLatLng(-23.55, -46.63);
      marker.setLatLng([-23.55, -46.63]);
      map.setView([-23.55, -46.63], 12);
    }
  } catch (err) {
    setFeedback('Erro de comunicação. Tente novamente.', 'danger');
  }
});
