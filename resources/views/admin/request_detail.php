<!doctype html><html><head><link rel='stylesheet' href='https://unpkg.com/leaflet/dist/leaflet.css'></head><body>
<h2>Detalhes solicitação</h2><?php if($request): ?><p><?=$request['protocolo']?> - <?=$request['status']?></p>
<p><?=$request['nome']?> | <?=$request['email']?> | <?=$request['telefone_whatsapp']?></p><p><?=$request['endereco']?> CEP <?=$request['cep']?></p>
<?php if($request['foto_path']): ?><img src='<?=base_path('/'.$request['foto_path'])?>' style='max-width:240px'><?php endif; ?>
<div id='map' style='height:300px'></div>
<a target='_blank' href='https://www.google.com/maps/dir/?api=1&destination=<?=$request['latitude']?>,<?=$request['longitude']?>'>Como chegar</a>
<a href='tel:<?=$request['telefone_whatsapp']?>'>Telefonar</a>
<a target='_blank' href='https://wa.me/<?=preg_replace('/\D+/','',$request['telefone_whatsapp'])?>'>WhatsApp</a>
<script src='https://unpkg.com/leaflet/dist/leaflet.js'></script><script>var m=L.map('map').setView([<?=$request['latitude']?>,<?=$request['longitude']?>],16);L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(m);L.marker([<?=$request['latitude']?>,<?=$request['longitude']?>]).addTo(m);</script>
<?php endif; ?></body></html>
