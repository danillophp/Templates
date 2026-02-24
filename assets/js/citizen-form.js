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
const googleEmbedEl = document.getElementById('googleMapEmbed');

const cepInput = document.getElementById('cep');
const addressInput = document.getElementById('address');
const districtInput = document.getElementById('bairro');

let lastValidCep = false;
let debounceTimer;

function showGeo(message, type = 'info') {
  if (!geoFeedback) return;
  geoFeedback.innerHTML = `<div class="alert alert-${type} py-2 mb-0">${message}</div>`;
}

function setStatus(value) {
  if (statusEl) statusEl.value = value;
}

function clearLatLng() {
  if (latEl) latEl.value = '';
  if (lngEl) lngEl.value = '';
  if (googleEmbedEl) googleEmbedEl.classList.add('d-none');
}

function setLatLng(lat, lng) {
  const latNum = Number(lat);
  const lngNum = Number(lng);
  if (!Number.isFinite(latNum) || !Number.isFinite(lngNum)) {
    clearLatLng();
    return;
  }

  if (latEl) latEl.value = latNum.toFixed(7);
  if (lngEl) lngEl.value = lngNum.toFixed(7);

  if (googleEmbedEl) {
    googleEmbedEl.src = `https://www.google.com/maps?q=${encodeURIComponent(`${latNum},${lngNum}`)}&z=16&output=embed`;
    googleEmbedEl.classList.remove('d-none');
  }
}

function normalize(value) {
  return (value || '')
    .toString()
    .trim()
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '');
}

async function fetchWithTimeout(url, timeoutMs = 7000) {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), timeoutMs);
  try {
    return await fetch(url, { signal: controller.signal, headers: { Accept: 'application/json' } });
  } finally {
    clearTimeout(timeout);
  }
}

async function validarCepViaCep(cep) {
  const clean = (cep || '').replace(/\D+/g, '');
  if (clean.length !== 8) return { ok: false, message: 'CEP inválido. Digite 8 números.' };

  try {
    const res = await fetchWithTimeout(`https://viacep.com.br/ws/${clean}/json/`, 7000);
    const json = await res.json();
    if (json.erro) return { ok: false, message: 'CEP não encontrado no ViaCEP.' };

    const cityOk = normalize(json.localidade) === normalize(MAP_CONFIG.allowedCity);
    const ufOk = String(json.uf || '').toUpperCase() === String(MAP_CONFIG.allowedUf || '').toUpperCase();
    if (!cityOk || !ufOk) {
      return { ok: false, message: 'Atendimento exclusivo para Santo Antônio do Descoberto - GO.' };
    }

    return { ok: true, data: json };
  } catch (error) {
    console.error('[CATA_TRECO][ViaCEP]', error);
    return { ok: false, message: 'ViaCEP indisponível no momento. Tente novamente.' };
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
  } catch (error) {
    console.error('[CATA_TRECO][Nominatim]', error);
    return null;
  }
}

async function geocodeFromAddress() {
  if (!lastValidCep) return;

  const street = addressInput?.value?.trim() || '';
  const district = districtInput?.value?.trim() || '';

  let result = await nominatimSearch({
    street: `${street}, ${district}`.trim(),
    city: MAP_CONFIG.allowedCity,
    state: 'Goiás',
    country: 'Brasil',
  });

  if (!result) {
    result = await nominatimSearch({
      q: `${street}, ${district}, ${MAP_CONFIG.allowedCity}, GO, Brasil`,
    });
  }

  if (!result) {
    setStatus('PENDENTE');
    clearLatLng();
    showGeo('Não foi possível localizar automaticamente o endereço. Revise os dados e tente novamente.', 'warning');
    return;
  }

  const lat = Number(result.lat);
  const lon = Number(result.lon);
  setLatLng(lat, lon);
  setStatus('AUTO_OK');
  showGeo('Endereço localizado. Mapa do Google atualizado automaticamente.', 'success');
}

function debounceGeocode() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(geocodeFromAddress, 700);
}

async function onCepChange() {
  const resp = await validarCepViaCep(cepInput?.value || '');
  if (!resp.ok) {
    lastValidCep = false;
    setStatus('PENDENTE');
    clearLatLng();
    showGeo(resp.message, 'danger');
    return;
  }

  lastValidCep = true;
  if (viacepCityEl) viacepCityEl.value = resp.data?.localidade || '';
  if (viacepUfEl) viacepUfEl.value = resp.data?.uf || '';
  if (!districtInput?.value && resp.data?.bairro) districtInput.value = resp.data.bairro;
  if (!addressInput?.value && resp.data?.logradouro) addressInput.value = resp.data.logradouro;
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
  const msg = emailDelivery?.ok
    ? 'Comprovante enviado para o e-mail cadastrado.'
    : (emailDelivery?.message || 'Falha ao enviar comprovante por e-mail.');

  receiptEl.classList.remove('d-none');
  receiptEl.innerHTML = `<div class="card border-success print-receipt"><div class="card-body">
    <h6 class="text-success mb-2">Comprovante de solicitação</h6>
    <p class="mb-1"><strong>Nome:</strong> ${receipt.nome}</p>
    <p class="mb-1"><strong>Endereço:</strong> ${receipt.endereco}</p>
    <p class="mb-1"><strong>Bairro:</strong> ${receipt.bairro || '-'}</p>
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
  if (!formEl?.checkValidity()) {
    formEl?.classList.add('was-validated');
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

  if (!latEl?.value || !lngEl?.value) {
    feedback.innerHTML = '<div class="alert alert-danger">Não foi possível localizar o endereço para gerar o mapa. Revise os dados e tente novamente.</div>';
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
    clearLatLng();
    renderReceipt(json.receipt, json.email_delivery);
  } catch (error) {
    console.error('[CATA_TRECO][SUBMIT]', error);
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
    } catch (error) {
      console.error('[CATA_TRECO][TRACK]', error);
      result.innerHTML = '<div class="alert alert-danger">Erro na consulta.</div>';
    }
  });
}

document.addEventListener('DOMContentLoaded', () => {
  const today = new Date().toISOString().slice(0, 10);
  if (pickupInput) pickupInput.min = today;

  setStatus('PENDENTE');
  clearLatLng();
  showGeo('Digite o CEP para carregar o mapa do Google automaticamente.', 'info');

  cepInput?.addEventListener('blur', onCepChange);
  cepInput?.addEventListener('input', () => {
    cepInput.value = cepInput.value.replace(/\D+/g, '').slice(0, 8);
  });

  addressInput?.addEventListener('input', debounceGeocode);
  districtInput?.addEventListener('change', debounceGeocode);
  pickupInput?.addEventListener('change', enforceThursday);
  formEl?.addEventListener('submit', submitCitizenForm);

  bindTrackLookup();
});
