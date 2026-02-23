const APP_BASE = (window.APP_BASE_PATH || '').replace(/\/$/, '');
const MAP_CONFIG = window.CATA_MAP_CONFIG || {};

const feedback = document.getElementById('feedback');
const receiptEl = document.getElementById('receipt');
const geoFeedback = document.getElementById('geoFeedback');
const latEl = document.getElementById('latitude');
const lngEl = document.getElementById('longitude');
const statusEl = document.getElementById('localizacao_status');
const viacepCityEl = document.getElementById('viacep_city');
const viacepUfEl = document.getElementById('viacep_uf');
const formEl = document.getElementById('citizenForm');
const pickupInput = document.getElementById('pickup_datetime');
const emergencyBtn = document.getElementById('btnEmergencyMode');

const cepInput = document.getElementById('cep');
const addressInput = document.getElementById('address');
const districtInput = document.getElementById('district');

let map;
let marker;
let lastValidCep = false;
let debounceTimer;

function showGeo(message, type = 'info') {
  if (!geoFeedback) return;
  geoFeedback.innerHTML = `<div class="alert alert-${type} py-2 mb-0">${message}</div>`;
}

function setStatus(value) {
  if (statusEl) statusEl.value = value;
}

function setLatLng(lat, lng) {
  if (latEl) latEl.value = Number(lat).toFixed(7);
  if (lngEl) lngEl.value = Number(lng).toFixed(7);
}

function normalize(v) {
  return (v || '').toString().trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
}

async function fetchWithTimeout(url, timeoutMs = 7000) {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), timeoutMs);
  try {
    const response = await fetch(url, { signal: controller.signal, headers: { Accept: 'application/json' } });
    return response;
  } finally {
    clearTimeout(timeout);
  }
}

function setEmergencyMode(reason) {
  console.error('[CATA_TRECO][GEO][EMERGENCIA]', reason);
  setStatus('EMERGENCIA_MANUAL');
  lastValidCep = true;

  const center = [Number(MAP_CONFIG.defaultLat || -15.9439), Number(MAP_CONFIG.defaultLng || -48.2585)];
  if (map && marker) {
    map.setView(center, 14);
    marker.setLatLng(center);
    setLatLng(center[0], center[1]);
  }

  showGeo('Não foi possível localizar automaticamente agora. Ative o modo de emergência e confirme a localização no mapa.', 'warning');
}

async function validarCepViaCep(cep) {
  const clean = (cep || '').replace(/\D+/g, '');
  if (clean.length !== 8) return { ok: false, message: 'CEP inválido.' };

  try {
    const res = await fetchWithTimeout(`https://viacep.com.br/ws/${clean}/json/`, 7000);
    const json = await res.json();
    if (json.erro) return { ok: false, emergency: true, message: 'CEP não encontrado no ViaCEP.' };

    const cityOk = normalize(json.localidade) === normalize(MAP_CONFIG.allowedCity);
    const ufOk = String(json.uf).toUpperCase() === String(MAP_CONFIG.allowedUf).toUpperCase();
    if (!cityOk || !ufOk) return { ok: false, message: 'Atendimento exclusivo para Santo Antônio do Descoberto - GO.' };

    return { ok: true, data: json };
  } catch (error) {
    return { ok: false, emergency: true, message: 'ViaCEP indisponível no momento.' };
  }
}

async function nominatimSearch(query) {
  const url = new URL('https://nominatim.openstreetmap.org/search');
  Object.entries(query).forEach(([key, value]) => value && url.searchParams.set(key, value));
  url.searchParams.set('format', 'jsonv2');
  url.searchParams.set('addressdetails', '1');
  url.searchParams.set('countrycodes', 'br');
  url.searchParams.set('limit', '1');

  try {
    const response = await fetchWithTimeout(url.toString(), 7000);
    const data = await response.json();
    return Array.isArray(data) && data[0] ? data[0] : null;
  } catch (_) {
    return null;
  }
}

async function geocodeAddress() {
  if (!map || !marker || !lastValidCep) return;

  const street = addressInput?.value?.trim() || '';
  const district = districtInput?.value?.trim() || '';

  let result = await nominatimSearch({
    street: `${street}, ${district}`.trim(),
    city: MAP_CONFIG.allowedCity,
    state: 'Goiás',
    country: 'Brasil',
  });

  if (!result) result = await nominatimSearch({ q: `${street}, ${district}, ${MAP_CONFIG.allowedCity}, GO, Brasil` });

  if (!result) {
    setEmergencyMode('Nominatim sem resultado/timeout');
    return;
  }

  const lat = Number(result.lat);
  const lon = Number(result.lon);
  marker.setLatLng([lat, lon]);
  map.setView([lat, lon], 17);
  setLatLng(lat, lon);
  setStatus('AUTO_OK');
  showGeo('Endereço localizado automaticamente. Ajuste o marcador se necessário.', 'success');
}

function debounceGeocode() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(geocodeAddress, 700);
}

async function onCepChange() {
  const resp = await validarCepViaCep(cepInput.value);
  if (!resp.ok) {
    if (resp.emergency) {
      setEmergencyMode(resp.message);
      return;
    }

    lastValidCep = false;
    setStatus('PENDENTE');
    showGeo(resp.message, 'danger');
    return;
  }

  lastValidCep = true;
  setStatus('AUTO_OK');
  if (viacepCityEl) viacepCityEl.value = resp.data?.localidade || '';
  if (viacepUfEl) viacepUfEl.value = resp.data?.uf || '';
  if (!districtInput.value && resp.data?.bairro) districtInput.value = resp.data.bairro;
  if (!addressInput.value && resp.data?.logradouro) addressInput.value = resp.data.logradouro;
  debounceGeocode();
}

function enforceThursday() {
  if (!pickupInput || !pickupInput.value) return true;
  const d = new Date(`${pickupInput.value}T00:00:00`);
  const isThursday = d.getDay() === 4;
  pickupInput.setCustomValidity(isThursday ? '' : 'Agendamentos apenas às quintas-feiras.');
  return isThursday;
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

  if (!enforceThursday()) {
    feedback.innerHTML = '<div class="alert alert-danger">Agendamentos apenas às quintas-feiras.</div>';
    return;
  }

  if (!lastValidCep) {
    feedback.innerHTML = '<div class="alert alert-danger">CEP inválido para atendimento.</div>';
    return;
  }

  if (!latEl.value || !lngEl.value) {
    feedback.innerHTML = '<div class="alert alert-danger">Confirme a localização no mapa antes de enviar.</div>';
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
    setStatus('PENDENTE');
    renderReceipt(json.receipt, json.email_delivery);
  } catch (err) {
    console.error('[CATA_TRECO][SUBMIT]', err);
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

function initLeaflet() {
  const el = document.getElementById('map');
  if (!el || typeof L === 'undefined') return;

  const center = [Number(MAP_CONFIG.defaultLat || -15.9439), Number(MAP_CONFIG.defaultLng || -48.2585)];
  map = L.map(el).setView(center, 13);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors',
  }).addTo(map);

  marker = L.marker(center, { draggable: true }).addTo(map);
  setLatLng(center[0], center[1]);
  setStatus('PENDENTE');
  showGeo('Mapa carregado. Informe o CEP para tentar localização automática.', 'info');

  marker.on('dragend', () => {
    const pos = marker.getLatLng();
    setLatLng(pos.lat, pos.lng);
    if (statusEl?.value !== 'AUTO_OK') setStatus('EMERGENCIA_MANUAL');
    showGeo('Localização confirmada manualmente.', 'success');
  });

  map.on('click', (ev) => {
    marker.setLatLng(ev.latlng);
    setLatLng(ev.latlng.lat, ev.latlng.lng);
    setStatus('EMERGENCIA_MANUAL');
    showGeo('Localização definida por clique no mapa.', 'success');
  });
}

document.addEventListener('DOMContentLoaded', () => {
  const today = new Date().toISOString().slice(0, 10);
  if (pickupInput) pickupInput.min = today;

  initLeaflet();
  cepInput?.addEventListener('blur', onCepChange);
  cepInput?.addEventListener('input', () => {
    cepInput.value = cepInput.value.replace(/\D+/g, '').slice(0, 8);
  });

  emergencyBtn?.addEventListener('click', () => setEmergencyMode('Ativação manual'));
  addressInput?.addEventListener('input', debounceGeocode);
  districtInput?.addEventListener('input', debounceGeocode);
  pickupInput?.addEventListener('change', enforceThursday);
  formEl?.addEventListener('submit', submitCitizenForm);
  bindTrackLookup();
});
