<!doctype html><html><head><link rel='stylesheet' href='https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'></head><body><main class='container'><div class='card'>
<?php if(!$request): ?><p>Não encontrado.</p><?php else: ?>
<h2><?= htmlspecialchars($request['protocolo']) ?></h2><p><?= htmlspecialchars($request['nome']) ?> - <?= htmlspecialchars($request['telefone_whatsapp']) ?></p>
<p><?= htmlspecialchars($request['endereco']) ?></p>
<?php if($request['foto_path']): ?><img src='<?= $_ENV['APP_BASE_PATH'] ?>/<?= htmlspecialchars($request['foto_path']) ?>' width='240'><?php endif; ?>
<div id='adminMap'></div>
<p><a target='_blank' href='https://www.openstreetmap.org/?mlat=<?= $request['latitude'] ?>&mlon=<?= $request['longitude'] ?>#map=17/<?= $request['latitude'] ?>/<?= $request['longitude'] ?>'>Como chegar</a> | <a href='tel:<?= htmlspecialchars($request['telefone_whatsapp']) ?>'>Telefonar</a> | <a target='_blank' href='https://wa.me/55<?= htmlspecialchars($request['telefone_whatsapp']) ?>'>WhatsApp</a></p>
<script src='https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'></script><script>const m=L.map('adminMap').setView([<?= $request['latitude'] ?>,<?= $request['longitude'] ?>],16);L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19}).addTo(m);L.marker([<?= $request['latitude'] ?>,<?= $request['longitude'] ?>]).addTo(m);</script>
<?php endif; ?></div></main></body></html>
