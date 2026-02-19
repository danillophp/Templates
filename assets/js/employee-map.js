document.querySelectorAll('.mini-map').forEach((el, index) => {
  const lat = parseFloat(el.dataset.lat);
  const lng = parseFloat(el.dataset.lng);
  const map = L.map(el, { attributionControl: false, zoomControl: false }).setView([lat, lng], 16);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19
  }).addTo(map);

  L.marker([lat, lng]).addTo(map);
});
