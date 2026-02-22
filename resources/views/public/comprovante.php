<!doctype html><html><body><main class='container'>
<div class='card'>
<h2>Comprovante de Solicitação</h2>
<?php if(!$req): ?><p>Nenhuma solicitação recente.</p><?php else: ?>
<p>Protocolo: <strong><?= htmlspecialchars($req['protocolo']) ?></strong></p>
<p>Nome: <?= htmlspecialchars($req['nome']) ?></p>
<p>Status: <?= htmlspecialchars($req['status']) ?></p>
<p>Data: <?= htmlspecialchars($req['data_agendada']) ?></p>
<button onclick='window.print()'>Imprimir</button>
<a href='mailto:<?= htmlspecialchars($req['email']) ?>?subject=Comprovante <?= urlencode($req['protocolo']) ?>'>Enviar comprovante por email</a>
<?php endif; ?>
</div></main></body></html>
