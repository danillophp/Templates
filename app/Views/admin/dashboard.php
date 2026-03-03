<h1>Dashboard Administrativo</h1>
<p>Total de escolas/creches: <strong><?= (int) $schoolCount ?></strong></p>
<p><a class="btn btn-outline-secondary btn-sm" href="index.php?r=admin/auditoria">Ver auditoria</a></p>
<h2>Alertas de estoque</h2>
<?php foreach ($stockAlerts as $alert): ?>
    <p><?= \App\Core\Security::e($alert['name']) ?> - Qtd: <?= \App\Core\Security::e((string) $alert['quantity_current']) ?></p>
<?php endforeach; ?>
