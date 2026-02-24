const APP_BASE = (window.APP_BASE_PATH || '').replace(/\/$/, '');
const MAP_CONFIG = window.CATA_MAP_CONFIG || {};

const feedback = document.getElementById('feedback');
const receiptEl = document.getElementById('receipt');
const geoFeedback = document.getElementById('geoFeedback');
const formEl = document.getElementById('citizenForm');
const pickupInput = document.getElementById('pickup_datetime');
const googleEmbedEl = document.getElementById('googleMapEmbed');
const cepInput = document.getElementById('cep');
const addressInput = document.getElementById('address');
const districtInput = document.getElementById('bairro');
const viacepCityEl = document.getElementById('viacep_city');
const viacepUfEl = document.getElementById('viacep_uf');
const latEl = document.getElementById('latitude');
const lngEl = document.getElementById('longitude');
const statusEl = document.getElementById('localizacao_status');

let lastValidCep = false;

function showGeo(message, type = 'info') {
  if (!geoFeedback) return;
  geoFeedback.innerHTML = `<div class="alert alert-${type} py-2 mb-0">${message}</div>`;
}

function normalize(value) {
  return (value || '')
    .toString()
    .trim()
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '');
}

function setStatus(status) {
  if (statusEl) statusEl.value = status;
}

function updateGoogleEmbedByAddress(addressText) {
  if (!googleEmbedEl) return;
  const q = encodeURIComponent(addressText);
  googleEmbedEl.src = `https://www.google.com/maps?q=${q}&z=17&output=embed`;
  googleEmbedEl.classList.remove('d-none');
}

function clearMap() {
  if (googleEmbedEl) googleEmbedEl.classList.add('d-none');
  if (latEl) latEl.value = '';
  if (lngEl) lngEl.value = '';
}

async function fetchViaCep(cep) {
  const clean = (cep || '').replace(/\D+/g, '');
  if (clean.length !== 8) return { ok: false, message: 'CEP inválido. Digite 8 números.' };

  try {
    const response = await fetch(`https://viacep.com.br/ws/${clean}/json/`, { headers: { Accept: 'application/json' } });
    const json = await response.json();
    if (json.erro) return { ok: false, message: 'CEP não encontrado.' };

    const cityOk = normalize(json.localidade) === normalize('Santo Antônio do Descoberto');
    const ufOk = String(json.uf || '').toUpperCase() === 'GO';
    if (!cityOk || !ufOk) {
      return { ok: false, message: 'Este sistema aceita apenas endereços de Santo Antônio do Descoberto – GO.' };
    }

    return { ok: true, data: json, cleanCep: clean };
  } catch (error) {
    console.error('[CATA_TRECO][VIACEP]', error);
    return { ok: false, message: 'ViaCEP indisponível no momento. Tente novamente.' };
  }
}

function buildAddressForMap(viacep, cleanCep) {
  const street = (viacep.logradouro || '').trim();
  const district = (viacep.bairro || '').trim();

  if (street !== '') {
    return `${street}, ${district}, Santo Antônio do Descoberto, GO, Brasil, ${cleanCep}`;
  }

  return `Santo Antônio do Descoberto, GO, ${cleanCep}, Brasil`;
}

async function onCepChange() {
  const result = await fetchViaCep(cepInput?.value || '');
  if (!result.ok) {
    lastValidCep = false;
    setStatus('PENDENTE');
    clearMap();
    showGeo(result.message, 'danger');
    return;
  }

  const viacep = result.data;
  lastValidCep = true;
  setStatus('AUTO_OK');

  if (viacepCityEl) viacepCityEl.value = 'Santo Antônio do Descoberto';
  if (viacepUfEl) viacepUfEl.value = 'GO';
  if (!addressInput?.value && viacep.logradouro) addressInput.value = viacep.logradouro;
  if (!districtInput?.value && viacep.bairro) districtInput.value = viacep.bairro;

  updateGoogleEmbedByAddress(buildAddressForMap(viacep, result.cleanCep));
  showGeo('Mapa do Google atualizado com o endereço do CEP.', 'success');
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
    <p class="mb-1"><strong>Cidade/UF:</strong> ${receipt.cidade || 'Santo Antônio do Descoberto'} - ${receipt.uf || 'GO'}</p>
    <p class="mb-1"><strong>Data:</strong> ${receipt.data_solicitada}</p>
    <p class="mb-1"><strong>Telefone:</strong> ${receipt.telefone}</p>
    <p class="mb-1"><strong>Email:</strong> ${receipt.email}</p>
    <p class="mb-1"><strong>Protocolo:</strong> ${receipt.protocolo}</p>
    <p class="mb-2"><strong>Status:</strong> ${receipt.status}</p>
    <div class="alert alert-info py-2">${msg}</div>
  </div></div>`;
}

async function submitCitizenForm(event) {
  event.preventDefault();
  if (!formEl?.checkValidity()) {
    formEl.classList.add('was-validated');
    feedback.innerHTML = '<div class="alert alert-danger">Preencha os campos obrigatórios.</div>';
    return;
  }

  if (!enforceThursday()) {
    feedback.innerHTML = '<div class="alert alert-danger">Agendamentos apenas às quintas-feiras.</div>';
    return;
  }

  if (!lastValidCep) {
    feedback.innerHTML = '<div class="alert alert-danger">Informe um CEP válido de Santo Antônio do Descoberto - GO.</div>';
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
    clearMap();
    renderReceipt(json.receipt, json.email_delivery);
  } catch (error) {
    console.error('[CATA_TRECO][SUBMIT]', error);
    feedback.innerHTML = '<div class="alert alert-danger">Erro de comunicação.</div>';
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const today = new Date().toISOString().slice(0, 10);
  if (pickupInput) pickupInput.min = today;

  setStatus('PENDENTE');
  clearMap();
  showGeo('Digite o CEP para atualizar o mapa do Google.', 'info');

  cepInput?.addEventListener('input', () => {
    cepInput.value = cepInput.value.replace(/\D+/g, '').slice(0, 8);
  });
  cepInput?.addEventListener('blur', onCepChange);
  pickupInput?.addEventListener('change', enforceThursday);
  formEl?.addEventListener('submit', submitCitizenForm);
});
