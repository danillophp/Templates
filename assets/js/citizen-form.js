const APP_BASE = (window.APP_BASE_PATH || '').replace(/\/$/, '');
const MAP_CONFIG = window.CATA_MAP_CONFIG || {};

const feedback = document.getElementById('feedback');
const receiptEl = document.getElementById('receipt');
const geoFeedback = document.getElementById('geoFeedback');
const latEl = document.getElementById('latitude');
const lngEl = document.getElementById('longitude');
const mapEl = document.getElementById('map');
const formEl = document.getElementById('citizenForm');
const pickupInput = document.getElementById('pickup_datetime');

let mapAdapter = null;
let mapReady = false;
let bound = false;
let lastGeoMeta = null;

const today = new Date().toISOString().slice(0, 10);
if (pickupInput) pickupInput.min = today;

const normalize = (v) => (v || '').toString().trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');

function showGeoMessage(message, type = 'info') {
  if (!geoFeedback) return;
  geoFeedback.innerHTML = `<div class="alert alert-${type} py-2 mb-0">${message}</div>`;
}

function setLatLng(lat, lng) {
  if (!latEl || !lngEl) return;
  latEl.value = Number(lat).toFixed(7);
  lngEl.value = Number(lng).toFixed(7);
}

function isAllowed(meta) {
  if (!meta) return false;
  const city = normalize(meta.city || meta.town || meta.municipality || meta.county);
  const state = normalize(meta.state);
  const country = normalize(meta.country);
  return city === normalize(MAP_CONFIG.allowedCity) && state === normalize(MAP_CONFIG.allowedState) && country === normalize(MAP_CONFIG.allowedCountry);
}

function hasGoogle() { return typeof window.google !== 'undefined' && window.google.maps; }

function initLeaflet(defaultLat, defaultLng) {
  if (!window.L || !mapEl) return false;
  const map = L.map('map').setView([defaultLat, defaultLng], 13);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);

  const marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);
  marker.on('dragend', async (event) => {
    const p = event.target.getLatLng();
    setLatLng(p.lat, p.lng);
    await reverseGeocode(p.lat, p.lng);
  });

  mapAdapter = {
    setMarker(lat, lng) { marker.setLatLng([lat, lng]); map.setView([lat, lng], 16); },
    addPoint(lat, lng, title, color) {
      L.circleMarker([lat, lng], { radius: 7, color: color || '#1d7de2', fillColor: color || '#1d7de2', fillOpacity: 0.85 }).addTo(map).bindPopup(title || 'Ponto de coleta');
    },
  };

  setLatLng(defaultLat, defaultLng);
  mapReady = true;
  showGeoMessage('Mapa carregado com Leaflet/OSM.', 'success');
  return true;
}

function initGoogle(defaultLat, defaultLng) {
  if (!hasGoogle() || !mapEl) return false;
  const center = { lat: defaultLat, lng: defaultLng };
  const map = new google.maps.Map(mapEl, { center, zoom: 13, mapTypeControl: false, streetViewControl: false });
  const marker = new google.maps.Marker({ position: center, map, draggable: true });
  marker.addListener('dragend', async () => {
    const p = marker.getPosition();
    if (!p) return;
    setLatLng(p.lat(), p.lng());
    await reverseGeocode(p.lat(), p.lng());
  });

  mapAdapter = {
    setMarker(lat, lng) { const p = { lat, lng }; marker.setPosition(p); map.setCenter(p); map.setZoom(16); },
    addPoint(lat, lng, title) {
      const p = new google.maps.Marker({ position: { lat, lng }, map });
      if (title) {
        const info = new google.maps.InfoWindow({ content: title });
        p.addListener('click', () => info.open({ anchor: p, map }));
      }
    },
  };

  setLatLng(defaultLat, defaultLng);
  mapReady = true;
  showGeoMessage('Mapa carregado com Google Maps.', 'success');
  return true;
}

function initMapEngine() {
  if (mapReady || !mapEl) return;
  const lat = Number(MAP_CONFIG.defaultLat || -15.9439);
  const lng = Number(MAP_CONFIG.defaultLng || -48.2585);
  if (MAP_CONFIG.provider === 'google' && MAP_CONFIG.hasGoogleKey && !window.__cataGoogleFailed && initGoogle(lat, lng)) return;
  initLeaflet(lat, lng);
}

window.cataInitGoogleMap = function cataInitGoogleMap() { initMapEngine(); bindAfterMapReady(); };

async function reverseGeocode(lat, lng) {
  try {
    const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}&addressdetails=1&accept-language=pt-BR`);
    const data = await res.json();
    lastGeoMeta = data.address || null;
    if (!isAllowed(lastGeoMeta)) {
      showGeoMessage('Local fora da área atendida (Santo Antônio do Descoberto - GO).', 'danger');
      return false;
    }
    showGeoMessage('Localização válida para atendimento.', 'success');
    return true;
  } catch (_) {
    showGeoMessage('Falha ao validar cidade/estado do endereço.', 'warning');
    return false;
  }
}

async function geocodeAddress() {
  if (!mapAdapter) return;
  const address = (document.getElementById('address')?.value || '').trim();
  const cep = (document.getElementById('cep')?.value || '').trim();
  if (!address && !cep) return;

  try {
    const q = encodeURIComponent(`${address}, ${cep}, Santo Antônio do Descoberto, Goiás, Brasil`);
    const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=br&addressdetails=1&q=${q}`, { headers: { 'Accept-Language': 'pt-BR' } });
    const data = await res.json();
    if (!Array.isArray(data) || !data[0]) {
      showGeoMessage('Endereço não localizado. Ajuste os dados.', 'warning');
      return;
    }

    lastGeoMeta = data[0].address || null;
    if (!isAllowed(lastGeoMeta)) {
      showGeoMessage('Atendimento exclusivo para Santo Antônio do Descoberto - GO.', 'danger');
      return;
    }

    const lat = Number(data[0].lat);
    const lng = Number(data[0].lon);
    mapAdapter.setMarker(lat, lng);
    setLatLng(lat, lng);
    showGeoMessage('Localização atualizada com sucesso.', 'success');
  } catch (_) {
    showGeoMessage('Falha ao consultar localização no momento.', 'warning');
  }
}

async function loadPoints() {
  try {
    const response = await fetch(`${APP_BASE}/?r=api/citizen/points`);
    const json = await response.json();
    if (!json.ok || !Array.isArray(json.data) || !mapAdapter) return;
    json.data.forEach((p) => mapAdapter.addPoint(Number(p.latitude), Number(p.longitude), p.titulo, p.cor_pin));
  } catch (_) {}
}

function bindGeocodeInputs() {
  let timer;
  ['address', 'cep'].forEach((id) => {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('input', () => {
      clearTimeout(timer);
      timer = setTimeout(geocodeAddress, 650);
    });
  });
}

function renderReceipt(receipt, emailDelivery) {
  if (!receiptEl || !receipt) return;
  receiptEl.classList.remove('d-none');
  const msg = emailDelivery?.ok ? 'Comprovante enviado para o e-mail cadastrado.' : (emailDelivery?.message || 'Falha ao enviar comprovante por e-mail.');
  receiptEl.innerHTML = `<div class="card border-success print-receipt"><div class="card-body">
    <h6 class="text-success mb-2">Comprovante de solicitação</h6>
    <p class="mb-1"><strong>Nome:</strong> ${receipt.nome}</p>
    <p class="mb-1"><strong>Endereço:</strong> ${receipt.endereco}</p>
    <p class="mb-1"><strong>Data:</strong> ${receipt.data_solicitada}</p>
    <p class="mb-1"><strong>Telefone:</strong> ${receipt.telefone}</p>
    <p class="mb-1"><strong>Email:</strong> ${receipt.email}</p>
    <p class="mb-1"><strong>Protocolo:</strong> ${receipt.protocolo}</p>
    <p class="mb-2"><strong>Status:</strong> ${receipt.status}</p>
    <div class="alert alert-info py-2">${msg}</div>
    <button type="button" id="btnPrintReceipt" class="btn btn-outline-primary btn-sm">Imprimir</button>
  </div></div>`;

  document.getElementById('btnPrintReceipt')?.addEventListener('click', () => window.print());
}

async function submitCitizenForm(event) {
  event.preventDefault();
  if (!formEl.checkValidity()) {
    formEl.classList.add('was-validated');
    feedback.innerHTML = '<div class="alert alert-danger">Preencha os campos obrigatórios.</div>';
    return;
  }
  if (pickupInput && pickupInput.value < today) {
    feedback.innerHTML = '<div class="alert alert-danger">Data não pode ser no passado.</div>';
    return;
  }
  if (!isAllowed(lastGeoMeta)) {
    feedback.innerHTML = '<div class="alert alert-danger">Informe endereço válido em Santo Antônio do Descoberto - GO.</div>';
    return;
  }

  const fd = new FormData(formEl);
  feedback.innerHTML = '<div class="alert alert-info">Enviando...</div>';
  try {
    const res = await fetch(`${APP_BASE}/?r=api/citizen/create`, { method: 'POST', body: fd });
    const json = await res.json();
    feedback.innerHTML = `<div class="alert ${json.ok ? 'alert-success' : 'alert-danger'}">${json.message}</div>`;
    if (!json.ok) return;
    formEl.reset();
    formEl.classList.remove('was-validated');
    renderReceipt(json.receipt, json.email_delivery);
  } catch (_) {
    feedback.innerHTML = '<div class="alert alert-danger">Erro de comunicação.</div>';
  }
}

function bindTrackLookup() {
  const trackButton = document.getElementById('btnTrack');
  if (!trackButton) return;
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
      result.innerHTML = json.ok
        ? `<div class="alert alert-success"><strong>Protocolo:</strong> ${json.data.protocolo}<br><strong>Status:</strong> ${json.data.status}<br><strong>Data:</strong> ${json.data.data_solicitada}</div>`
        : `<div class="alert alert-danger">${json.message}</div>`;
    } catch (_) {
      result.innerHTML = '<div class="alert alert-danger">Erro na consulta.</div>';
    }
  });
}

function bindAfterMapReady() {
  if (!mapReady || bound) return;
  bound = true;
  loadPoints();
  bindGeocodeInputs();
}

document.addEventListener('DOMContentLoaded', () => {
  initMapEngine();
  bindAfterMapReady();
  if (MAP_CONFIG.provider === 'google' && MAP_CONFIG.hasGoogleKey) {
    setTimeout(() => {
      if (!mapReady) {
        showGeoMessage('Google Maps indisponível, usando mapa gratuito.', 'warning');
        initMapEngine();
        bindAfterMapReady();
      }
    }, 2200);
  }

  formEl?.addEventListener('submit', submitCitizenForm);
  bindTrackLookup();
});
