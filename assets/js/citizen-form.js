const APP_BASE = (window.APP_BASE_PATH || '').replace(/\/$/, '');
const MAP_CONFIG = window.CATA_MAP_CONFIG || {};

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

let mapAdapter = null;
let mapReady = false;
let mapFeaturesBound = false;

function setLatLng(lat, lng) {
  if (latEl && lngEl) {
    latEl.value = Number(lat).toFixed(7);
    lngEl.value = Number(lng).toFixed(7);
  }
}

function showGeoMessage(message, type = 'info') {
  if (!geoFeedback) return;
  geoFeedback.innerHTML = `<div class="alert alert-${type} py-2 mb-0">${message}</div>`;
}

function hasGoogleMaps() {
  return typeof window.google !== 'undefined' && window.google.maps;
}

function initLeafletMap(defaultLat, defaultLng) {
  if (!window.L || !mapEl) {
    showGeoMessage('Não foi possível carregar o mapa (Leaflet indisponível).', 'danger');
    return false;
  }

  const map = L.map('map').setView([defaultLat, defaultLng], 12);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap',
  }).addTo(map);

  const marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);
  marker.on('dragend', (event) => {
    const pos = event.target.getLatLng();
    setLatLng(pos.lat, pos.lng);
  });

  mapAdapter = {
    setMarker(lat, lng) {
      marker.setLatLng([lat, lng]);
      map.setView([lat, lng], 16);
    },
    addPoint(lat, lng, title, color) {
      L.circleMarker([lat, lng], {
        radius: 7,
        color: color || '#1d7de2',
        fillColor: color || '#1d7de2',
        fillOpacity: 0.85,
      }).addTo(map).bindPopup(title || 'Ponto de coleta');
    },
  };

  setLatLng(defaultLat, defaultLng);
  mapReady = true;
  showGeoMessage('Mapa carregado com Leaflet/OSM.', 'success');
  return true;
}

function initGoogleMap(defaultLat, defaultLng) {
  if (!hasGoogleMaps() || !mapEl) {
    return false;
  }

  const center = { lat: defaultLat, lng: defaultLng };
  const map = new google.maps.Map(mapEl, {
    center,
    zoom: 13,
    mapTypeControl: false,
    streetViewControl: false,
    fullscreenControl: true,
  });

  const marker = new google.maps.Marker({
    position: center,
    map,
    draggable: true,
  });

  marker.addListener('dragend', () => {
    const pos = marker.getPosition();
    if (!pos) return;
    setLatLng(pos.lat(), pos.lng());
  });

  mapAdapter = {
    setMarker(lat, lng) {
      const pos = { lat, lng };
      marker.setPosition(pos);
      map.setCenter(pos);
      map.setZoom(16);
    },
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
  if (!mapEl || mapReady) return;

  const defaultLat = Number(MAP_CONFIG.defaultLat || -23.55052);
  const defaultLng = Number(MAP_CONFIG.defaultLng || -46.633308);
  const wantsGoogle = MAP_CONFIG.provider === 'google' && MAP_CONFIG.hasGoogleKey;

  if (wantsGoogle && !window.__cataGoogleFailed && initGoogleMap(defaultLat, defaultLng)) {
    return;
  }

  initLeafletMap(defaultLat, defaultLng);
}

function ensureMapFeatures() {
  if (!mapReady || mapFeaturesBound) return;
  loadPoints();
  bindGeocodeInputs();
  mapFeaturesBound = true;
}

window.cataInitGoogleMap = function cataInitGoogleMap() {
  initMapEngine();
  ensureMapFeatures();
};

async function loadPoints() {
  try {
    const res = await fetch(`${APP_BASE}/?r=api/citizen/points`);
    const json = await res.json();
    if (!json.ok || !Array.isArray(json.data) || !mapAdapter) return;

    json.data.forEach((point) => {
      mapAdapter.addPoint(Number(point.latitude), Number(point.longitude), point.titulo, point.cor_pin);
    });
  } catch (error) {
    console.error('Falha ao carregar pontos:', error);
  }
}

async function geocodeAddress() {
  if (!mapAdapter) return;

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
    mapAdapter.setMarker(lat, lng);
    setLatLng(lat, lng);
    showGeoMessage('Localização atualizada automaticamente no mapa.', 'success');
  } catch (error) {
    showGeoMessage('Falha ao consultar localização. Você pode posicionar o marcador manualmente.', 'warning');
    console.error('Falha no geocoding:', error);
  }
}

function bindGeocodeInputs() {
  let geocodeTimer;

  ['address', 'cep'].forEach((id) => {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('input', () => {
      clearTimeout(geocodeTimer);
      geocodeTimer = setTimeout(geocodeAddress, 650);
    });
  });
}

async function submitCitizenForm(event) {
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
  } catch (error) {
    if (feedback) feedback.innerHTML = '<div class="alert alert-danger">Erro de comunicação. Tente novamente.</div>';
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

      if (!json.ok) {
        result.innerHTML = `<div class="alert alert-danger">${json.message}</div>`;
        return;
      }

      result.innerHTML = `<div class="alert alert-success"><strong>Protocolo:</strong> ${json.data.protocolo}<br><strong>Status:</strong> ${json.data.status}<br><strong>Data prevista:</strong> ${json.data.data_solicitada}</div>`;
    } catch (error) {
      result.innerHTML = '<div class="alert alert-danger">Erro ao consultar protocolo.</div>';
    }
  });
}

document.addEventListener('DOMContentLoaded', () => {
  initMapEngine();

  if (MAP_CONFIG.provider === 'google' && MAP_CONFIG.hasGoogleKey) {
    setTimeout(() => {
      if (!mapReady) {
        showGeoMessage('Google Maps não carregou (chave/domínio/403). Usando mapa gratuito automaticamente.', 'warning');
        initMapEngine();
        ensureMapFeatures();
      }
    }, 2200);
  }

  ensureMapFeatures();

  if (formEl) {
    formEl.addEventListener('submit', submitCitizenForm);
  }

  bindTrackLookup();
});
