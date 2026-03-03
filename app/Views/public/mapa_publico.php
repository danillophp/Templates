<h1>Mapa Público de Escolas e Creches</h1>
<div id="map" style="height:400px"></div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const map = L.map('map').setView([-15.94,-48.26], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
fetch('index.php?r=api/public/escolas').then(r => r.json()).then(resp => {
  resp.data.forEach(s => L.marker([s.latitude, s.longitude]).addTo(map).bindPopup(`<b>${s.name}</b><br>${s.address}<br>Vagas: ${s.available_slots}`));
});
</script>
