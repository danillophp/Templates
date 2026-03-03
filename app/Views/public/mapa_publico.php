<h1 class="h3 mb-3">Mapa Público de Escolas e Creches</h1>
<div class="card card-modern p-3 mb-3">
  <div class="row g-2">
    <div class="col-md-3">
      <select id="fType" class="form-select"><option value="">Todos os tipos</option><option value="escola">Escola</option><option value="creche">Creche</option></select>
    </div>
    <div class="col-md-3"><input id="fRegion" class="form-control" placeholder="Região"></div>
    <div class="col-md-3"><input id="fSlots" type="number" class="form-control" min="0" placeholder="Vagas mínimas"></div>
    <div class="col-md-3"><button id="applyFilters" class="btn btn-primary w-100">Aplicar filtros</button></div>
  </div>
</div>
<div id="map" style="height:460px"></div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const map = L.map('map').setView([-15.94, -48.26], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
let markers=[];
function loadMap(){
  markers.forEach(m=>map.removeLayer(m));
  markers=[];
  const params = new URLSearchParams({
    type: document.getElementById('fType').value,
    region: document.getElementById('fRegion').value,
    min_slots: document.getElementById('fSlots').value || '0'
  });
  fetch('index.php?r=api/public/escolas&'+params.toString())
    .then(r => r.status===304 ? null : r.json())
    .then(resp => {
      if(!resp) return;
      resp.features.forEach(f => {
        const p=f.properties;
        const m=L.marker([f.geometry.coordinates[1], f.geometry.coordinates[0]]).addTo(map)
          .bindPopup(`<strong>${p.name}</strong><br>${p.address}<br>Direção: ${p.director_name || '-'}<br>Vagas: ${p.available_slots}`);
        markers.push(m);
      });
    });
}
document.getElementById('applyFilters').addEventListener('click', loadMap);
loadMap();
</script>
