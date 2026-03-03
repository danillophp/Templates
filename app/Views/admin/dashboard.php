<h1>Dashboard Administrativo</h1>
<p>Total de escolas/creches: <strong><?= (int) $schoolCount ?></strong></p>
<h2>Alertas de estoque</h2>
<?php foreach ($stockAlerts as $alert): ?><p><?= htmlspecialchars($alert['name']) ?> - Qtd: <?= htmlspecialchars($alert['quantity_current']) ?></p><?php endforeach; ?>
