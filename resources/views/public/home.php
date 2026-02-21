<!doctype html><html><head><meta charset='utf-8'><title>Cata Treco</title>
<link rel='stylesheet' href='https://unpkg.com/leaflet/dist/leaflet.css'><link rel='stylesheet' href='<?=asset_url('css/app.css')?>'></head><body>
<header><h1>Cata Treco</h1><a href='<?=base_path('/protocolo')?>'>Consultar protocolo</a></header>
<div id='map' style='height:320px'></div>
<form method='post' action='<?=base_path('/solicitar')?>' enctype='multipart/form-data'>
<input type='hidden' name='_csrf' value='<?=$csrf?>'>
<input name='nome' placeholder='Nome completo' required>
<input type='email' name='email' placeholder='Email' required>
<input name='telefone_whatsapp' placeholder='Telefone WhatsApp' required>
<input name='endereco' placeholder='Endereço' required>
<input name='cep' id='cep' placeholder='CEP' required>
<input type='date' name='data_agendada' min='<?=date('Y-m-d')?>' required>
<input type='file' name='foto' accept='image/*'>
<input type='hidden' name='latitude' id='latitude' required>
<input type='hidden' name='longitude' id='longitude' required>
<button>Solicitar coleta</button></form>
<div id='form-map' style='height:280px'></div>
<script>window.points=<?=json_encode($points,JSON_UNESCAPED_UNICODE)?>;window.basePath='<?=rtrim(base_path('/'),'/')?>';</script>
<script src='https://unpkg.com/leaflet/dist/leaflet.js'></script><script src='<?=asset_url('js/public.js')?>'></script>
</body></html>
