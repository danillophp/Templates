const APP_BASE = (window.APP_BASE_PATH || "").replace(/\/$/, "");
const feedback = document.getElementById('feedback');
const receiptEl = document.getElementById('receipt');
const geoFeedback = document.getElementById('geoFeedback');
const latEl = document.getElementById('latitude');
const lngEl = document.getElementById('longitude');
const mapEl = document.getElementById('map');
const formEl = document.getElementById('citizenForm');

const today = new Date().toISOString().slice(0, 10);
const pickupInput = document.getElementById('pickup_datetime');
if (pickupInput) {
  pickupInput.setAttribute('type', 'date');
  pickupInput.setAttribute('min', today);
}

let map = null;
let marker = null;

function setLatLng(lat, lng) {
  if (!latEl || !lngEl) return;
  latEl.value = lat;
  lngEl.value = lng;
}

function showGeoMessage(message, type = 'info') {
  if (!geoFeedback) return;
  geoFeedback.innerHTML = `<div class="alert alert-${type} py-2 mb-0">${message}</div>`;
}

async function loadPoints() {
  if (!map) return;
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

if (mapEl && latEl && lngEl) {
  map = L.map('map').setView([-23.55, -46.63], 12);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap',
  }).addTo(map);

  marker = L.marker([-23.55, -46.63], { draggable: true }).addTo(map);
  setLatLng(-23.55, -46.63);

  marker.on('dragend', (event) => {
    const pos = event.target.getLatLng();
    setLatLng(pos.lat, pos.lng);
  });

  let geocodeTimer;
  const geocode = async () => {
    const address = (document.getElementById('address')?.value || '').trim();
    const cep = (document.getElementById('cep')?.value || '').trim();
    if (!address && !cep) return;

    try {
      const q = encodeURIComponent(`${address}, ${cep}, Brasil`);
      const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${q}`, {
        headers: { 'Accept-Language': 'pt-BR' },
      });
      const data = await res.json();
      if (!Array.isArray(data) || !data[0]) {
        showGeoMessage('Endereço não localizado. Ajuste os dados ou arraste o marcador manualmente.', 'warning');
        return;
      }

      const lat = Number(data[0].lat);
      const lng = Number(data[0].lon);
      marker.setLatLng([lat, lng]);
      map.setView([lat, lng], 16);
      setLatLng(lat, lng);
      showGeoMessage('Localização atualizada automaticamente no mapa.', 'success');
    } catch (err) {
      showGeoMessage('Falha ao consultar localização. Você pode posicionar o marcador manualmente.', 'warning');
      console.error('Falha no geocoding:', err);
    }
  };

  ['address', 'cep'].forEach((id) => {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('input', () => {
      clearTimeout(geocodeTimer);
      geocodeTimer = setTimeout(geocode, 650);
    });
  });

  loadPoints();
}

if (formEl) {
  formEl.addEventListener('submit', async (event) => {
    event.preventDefault();

    if (!formEl.checkValidity()) {
      formEl.classList.add('was-validated');
      if (feedback) feedback.innerHTML = '<div class="alert alert-danger">Verifique os campos obrigatórios.</div>';
      return;
    }

    if (pickupInput && pickupInput.value < today) {
      if (feedback) feedback.innerHTML = '<div class="alert alert-danger">A data de coleta não pode ser no passado.</div>';
      return;
    }

    const fd = new FormData(formEl);
    if (feedback) feedback.innerHTML = '<div class="alert alert-info">Enviando solicitação...</div>';

    try {
      const res = await fetch(`${APP_BASE}/?r=api/citizen/create`, { method: 'POST', body: fd });
      const json = await res.json();

      if (feedback) {
        feedback.innerHTML = `<div class="alert ${json.ok ? 'alert-success' : 'alert-danger'}">${json.message}</div>`;
      }

      if (json.ok) {
        showToast('Solicitação registrada com sucesso.', 'success');
        formEl.reset();
        formEl.classList.remove('was-validated');

        if (receiptEl && json.receipt) {
          receiptEl.classList.remove('d-none');
          receiptEl.innerHTML = `
            <div class="card border-success">
              <div class="card-body">
                <h6 class="text-success mb-2">Comprovante de solicitação</h6>
                <p class="mb-1"><strong>Nome:</strong> ${json.receipt.nome}</p>
                <p class="mb-1"><strong>Endereço:</strong> ${json.receipt.endereco}</p>
                <p class="mb-1"><strong>Data solicitada:</strong> ${json.receipt.data_solicitada}</p>
                <p class="mb-1"><strong>Telefone:</strong> ${json.receipt.telefone}</p>
                <p class="mb-1"><strong>Protocolo:</strong> ${json.receipt.protocolo}</p>
                <p class="mb-0"><strong>Status:</strong> ${json.receipt.status}</p>
              </div>
            </div>`;
        }
      }
    } catch (err) {
      if (feedback) feedback.innerHTML = '<div class="alert alert-danger">Erro de comunicação. Tente novamente.</div>';
    }
  });
}

const trackButton = document.getElementById('btnTrack');
if (trackButton) {
  trackButton.addEventListener('click', async () => {
    const protocol = document.getElementById('trackProtocol')?.value || '';
    const phone = document.getElementById('trackPhone')?.value || '';
    const result = document.getElementById('trackResult');
    if (!result) return;

    result.innerHTML = '<div class="text-muted">Consultando...</div>';

    try {
      const params = new URLSearchParams({ protocol, phone });
      const res = await fetch(`${APP_BASE}/?r=api/citizen/track&${params.toString()}`);
      const json = await res.json();

      if (!json.ok) {
        result.innerHTML = `<div class="alert alert-danger">${json.message}</div>`;
        return;
      }

      result.innerHTML = `<div class="alert alert-success"><strong>Protocolo:</strong> ${json.data.protocolo}<br><strong>Status:</strong> ${json.data.status}<br><strong>Data prevista:</strong> ${json.data.data_solicitada}</div>`;
    } catch (err) {
      result.innerHTML = '<div class="alert alert-danger">Erro ao consultar protocolo.</div>';
    }
  });
}
