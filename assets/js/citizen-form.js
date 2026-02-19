flatpickr('#pickup_datetime', {
  enableTime: true,
  dateFormat: 'Y-m-d H:i',
  minDate: 'today',
  time_24hr: true
});

const map = L.map('map').setView([-23.55052, -46.633308], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  maxZoom: 19,
  attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

const marker = L.marker([-23.55052, -46.633308], { draggable: true }).addTo(map);

function updateLatLng(lat, lng) {
  document.getElementById('latitude').value = lat;
  document.getElementById('longitude').value = lng;
}

updateLatLng(-23.55052, -46.633308);

marker.on('dragend', (event) => {
  const pos = event.target.getLatLng();
  updateLatLng(pos.lat, pos.lng);
});

async function geocodeAddress() {
  const address = document.getElementById('address').value.trim();
  const cep = document.getElementById('cep').value.trim();
  if (!address || !cep) return;

  const query = encodeURIComponent(`${address}, ${cep}, Brasil`);
  const url = `https://nominatim.openstreetmap.org/search?format=json&q=${query}`;

  try {
    const response = await fetch(url, { headers: { 'Accept-Language': 'pt-BR' } });
    const data = await response.json();
    if (!data.length) return;

    const lat = parseFloat(data[0].lat);
    const lon = parseFloat(data[0].lon);
    marker.setLatLng([lat, lon]);
    map.setView([lat, lon], 17);
    updateLatLng(lat, lon);
  } catch (e) {
    console.error('Geocoding falhou', e);
  }
}

document.getElementById('address').addEventListener('blur', geocodeAddress);
document.getElementById('cep').addEventListener('blur', geocodeAddress);
