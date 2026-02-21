<!doctype html><html><body>
<h2>Comprovante de Solicitação</h2>
<?php if($request): ?>
<p>Protocolo: <b><?=$request['protocolo']?></b></p><p>Nome: <?=$request['nome']?></p><p>Status: <?=$request['status']?></p><p>Data: <?=$request['data_agendada']?></p>
<button onclick='window.print()'>Imprimir</button>
<a href='mailto:<?=$request['email']?>?subject=Comprovante <?=$request['protocolo']?>'>Enviar por email</a>
<?php else: ?>Não encontrado<?php endif; ?>
</body></html>
