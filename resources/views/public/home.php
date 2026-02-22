<!doctype html><html><head><meta charset='utf-8'><title>Cata Treco</title><link rel='stylesheet' href='https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'><link rel='stylesheet' href='<?= $_ENV['APP_BASE_PATH'] ?>/resources/assets/css/style.css'></head><body><main class='container'>
<div class='card'><h1>Cata Treco</h1><p>Agende sua coleta.</p><div id='map'></div></div>
<div class='card'><form action='<?= $_ENV['APP_BASE_PATH'] ?>/solicitar' method='post' enctype='multipart/form-data'>
<input name='nome' placeholder='Nome' required> <input type='email' name='email' placeholder='Email' required>
<input name='telefone' placeholder='Telefone/WhatsApp' required>
<input id='cep' name='cep' placeholder='CEP' required> <button type='button' onclick='buscarCep()'>Buscar CEP</button><br>
<input id='endereco' name='endereco' placeholder='Endereço completo' required>
<input type='hidden' id='cidade' name='cidade'><input type='hidden' id='uf' name='uf'>
<input type='date' name='data_agendada' min='<?= date('Y-m-d') ?>' required>
<input type='file' name='foto' accept='image/*'>
<div id='mapConfirm'></div>
<input type='hidden' id='latitude' name='latitude'><input type='hidden' id='longitude' name='longitude'>
<button>Enviar Solicitação</button></form></div>
</main>
<script src='https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'></script><script src='<?= $_ENV['APP_BASE_PATH'] ?>/resources/assets/js/app.js'></script>
<script>
const map=L.map('map').setView([-15.9,-48.1],12);L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19}).addTo(map);
<?php foreach($points as $p): ?>L.marker([<?= $p['latitude'] ?>,<?= $p['longitude'] ?>]).addTo(map).bindPopup('<b><?= htmlspecialchars($p['nome']) ?></b><br><?= htmlspecialchars($p['descricao']) ?>');<?php endforeach; ?>
window.confirmMapObj=initConfirmMap();
</script></body></html>
