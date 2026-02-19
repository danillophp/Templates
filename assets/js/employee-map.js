document.querySelectorAll('.mini-map').forEach((el) => {
  const lat = parseFloat(el.dataset.lat);
  const lng = parseFloat(el.dataset.lng);
  const map = L.map(el, { zoomControl: false, attributionControl: false }).setView([lat, lng], 16);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
  L.marker([lat, lng]).addTo(map);
});
