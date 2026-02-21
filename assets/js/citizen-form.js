const APP_BASE = (window.APP_BASE_PATH || '').replace(/\/$/, '');
const MAP_CONFIG = window.CATA_MAP_CONFIG || {};

const feedback = document.getElementById('feedback');
const receiptEl = document.getElementById('receipt');
const geoFeedback = document.getElementById('geoFeedback');
const latEl = document.getElementById('latitude');
const lngEl = document.getElementById('longitude');
const formEl = document.getElementById('citizenForm');
const pickupInput = document.getElementById('pickup_datetime');

const cepInput = document.getElementById('cep');
const addressInput = document.getElementById('address');
const districtInput = document.getElementById('district');

let map;
let marker;
let geocoder;
let lastValidCep = false;

function showGeo(message, type = 'info') {
  if (!geoFeedback) return;
  geoFeedback.innerHTML = `<div class="alert alert-${type} py-2 mb-0">${message}</div>`;
}

function setLatLng(lat, lng) {
  if (latEl) latEl.value = Number(lat).toFixed(7);
  if (lngEl) lngEl.value = Number(lng).toFixed(7);
}

function normalize(v) {
  return (v || '').toString().trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
}

async function validarCepViaCep(cep) {
  const clean = (cep || '').replace(/\D+/g, '');
  if (clean.length !== 8) {
    return { ok: false, message: 'CEP inválido.' };
  }

  const res = await fetch(`https://viacep.com.br/ws/${clean}/json/`);
  const json = await res.json();
  if (json.erro) return { ok: false, message: 'CEP não encontrado.' };

  if (normalize(json.localidade) !== normalize(MAP_CONFIG.allowedCity) || String(json.uf).toUpperCase() !== String(MAP_CONFIG.allowedUf).toUpperCase()) {
    return { ok: false, message: 'Atendimento exclusivo para Santo Antônio do Descoberto - GO.' };
  }

  return { ok: true, data: json };
}

function geocodeAddress() {
  if (!geocoder || !map || !marker) return;
  const query = `${addressInput.value}, ${districtInput.value}, Santo Antônio do Descoberto, GO, Brasil`;

  geocoder.geocode({ address: query, region: 'br' }, (results, status) => {
    if (status !== 'OK' || !results?.[0]) {
      showGeo('Endereço não localizado no Google Maps.', 'warning');
      return;
    }

    const loc = results[0].geometry.location;
    marker.setPosition(loc);
    map.setCenter(loc);
    map.setZoom(16);
    setLatLng(loc.lat(), loc.lng());
    showGeo('Localização validada e posicionada no mapa.', 'success');
  });
}

async function onCepChange() {
  try {
    const resp = await validarCepViaCep(cepInput.value);
    if (!resp.ok) {
      lastValidCep = false;
      showGeo(resp.message, 'danger');
      return;
    }
    lastValidCep = true;
    if (!districtInput.value && resp.data?.bairro) districtInput.value = resp.data.bairro;
    geocodeAddress();
  } catch (_) {
    lastValidCep = false;
    showGeo('Falha ao validar CEP no momento.', 'warning');
  }
}

function renderReceipt(receipt, emailDelivery) {
  if (!receiptEl || !receipt) return;
  const msg = emailDelivery?.ok ? 'Comprovante enviado para o e-mail cadastrado.' : (emailDelivery?.message || 'Falha ao enviar comprovante por e-mail.');
  receiptEl.classList.remove('d-none');
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

  if (!lastValidCep) {
    feedback.innerHTML = '<div class="alert alert-danger">CEP inválido para atendimento.</div>';
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
    lastValidCep = false;
    renderReceipt(json.receipt, json.email_delivery);
  } catch (_) {
    feedback.innerHTML = '<div class="alert alert-danger">Erro de comunicação.</div>';
  }
}

function bindTrackLookup() {
  const btn = document.getElementById('btnTrack');
  if (!btn) return;
  btn.addEventListener('click', async () => {
    const protocol = document.getElementById('trackProtocol')?.value || '';
    const phone = document.getElementById('trackPhone')?.value || '';
    const result = document.getElementById('trackResult');
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

window.cataInitGoogleMap = function cataInitGoogleMap() {
  const el = document.getElementById('map');
  if (!el || typeof google === 'undefined' || !google.maps) return;

  const center = { lat: Number(MAP_CONFIG.defaultLat || -15.9439), lng: Number(MAP_CONFIG.defaultLng || -48.2585) };
  map = new google.maps.Map(el, { center, zoom: 13, mapTypeControl: false, streetViewControl: false });
  marker = new google.maps.Marker({ position: center, map, draggable: true });
  geocoder = new google.maps.Geocoder();

  marker.addListener('dragend', () => {
    const p = marker.getPosition();
    if (p) setLatLng(p.lat(), p.lng());
  });

  setLatLng(center.lat, center.lng);
  showGeo('Google Maps carregado com sucesso.', 'success');
};

document.addEventListener('DOMContentLoaded', () => {
  const today = new Date().toISOString().slice(0, 10);
  if (pickupInput) pickupInput.min = today;

  cepInput?.addEventListener('blur', onCepChange);
  addressInput?.addEventListener('blur', () => { if (lastValidCep) geocodeAddress(); });
  districtInput?.addEventListener('blur', () => { if (lastValidCep) geocodeAddress(); });
  formEl?.addEventListener('submit', submitCitizenForm);
  bindTrackLookup();
});
