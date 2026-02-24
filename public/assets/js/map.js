(() => {
  const mapEl = document.getElementById('map');
  const form = document.getElementById('requestForm');
  if (!mapEl || !form || typeof L === 'undefined') return;

  const latInput = document.getElementById('latitude');
  const lngInput = document.getElementById('longitude');
  const addressInput = document.getElementById('address');
  const cepInput = document.getElementById('cep');
  const feedback = document.getElementById('formFeedback');

  const map = L.map('map').setView([-23.5505, -46.6333], 12);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

  const marker = L.marker([-23.5505, -46.6333], { draggable: true }).addTo(map);
  const syncLatLng = (lat, lng) => {
    latInput.value = lat.toFixed(7);
    lngInput.value = lng.toFixed(7);
  };

  marker.on('dragend', () => {
    const p = marker.getLatLng();
    syncLatLng(p.lat, p.lng);
  });
  syncLatLng(-23.5505, -46.6333);

  async function geocode(q) {
    const url = `https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(q)}`;
    const res = await fetch(url, { headers: { 'Accept-Language': 'pt-BR' } });
    const data = await res.json();
    if (!Array.isArray(data) || !data[0]) return;
    const lat = parseFloat(data[0].lat);
    const lon = parseFloat(data[0].lon);
    marker.setLatLng([lat, lon]);
    map.setView([lat, lon], 16);
    syncLatLng(lat, lon);
  }

  let timer;
  [addressInput, cepInput].forEach((el) => {
    el?.addEventListener('input', () => {
      clearTimeout(timer);
      timer = setTimeout(() => {
        const q = [addressInput.value, cepInput.value, 'Brasil'].filter(Boolean).join(', ');
        if (q.length > 6) geocode(q);
      }, 800);
    });
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    feedback.innerHTML = '<div class="alert alert-info">Enviando...</div>';
    try {
      const fd = new FormData(form);
      const res = await fetch((window.APP_BASE_URL || '') + '/public/api/solicitacoes', { method: 'POST', body: fd });
      const json = await res.json();
      if (!json.ok) throw new Error(json.message || 'Falha no envio');
      feedback.innerHTML = `<div class="alert alert-success">${json.message} Protocolo #${json.id}</div>`;
      form.reset();
    } catch (err) {
      feedback.innerHTML = `<div class="alert alert-danger">${err.message}</div>`;
    }
  });
})();
