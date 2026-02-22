<!doctype html><html><body><main class='container'><div class='card'>
<h2>Consultar Protocolo ou Telefone</h2>
<form><input name='q' value='<?= htmlspecialchars($_GET['q'] ?? '') ?>' placeholder='CAT-2026-000001 ou telefone'><button>Buscar</button></form>
<?php foreach($results as $r): ?><p><b><?= htmlspecialchars($r['protocolo']) ?></b> - <?= htmlspecialchars($r['status']) ?> - <?= htmlspecialchars($r['data_agendada']) ?></p><?php endforeach; ?>
</div></main></body></html>
