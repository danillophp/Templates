<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h1 class="h4">Painel do Funcionário</h1><small><?= htmlspecialchars($user['name'] ?? '') ?></small></div>
    <form method="post" action="<?= htmlspecialchars($baseUrl) ?>/public/admin/logout"><?= $csrfField ?><button class="btn btn-outline-secondary btn-sm">Sair</button></form>
</div>

<div class="row g-3">
<?php foreach ($requests as $r): ?>
<div class="col-md-6">
    <div class="card h-100"><div class="card-body">
        <h2 class="h6 mb-1"><?= htmlspecialchars($r['citizen_name']) ?></h2>
        <p class="mb-1"><strong>Endereço:</strong> <?= htmlspecialchars($r['address']) ?></p>
        <p class="mb-1"><strong>WhatsApp:</strong> <a href="https://wa.me/55<?= preg_replace('/\D+/', '', $r['whatsapp']) ?>" target="_blank"><?= htmlspecialchars($r['whatsapp']) ?></a></p>
        <p class="mb-2"><strong>Data/Hora:</strong> <?= htmlspecialchars((string) $r['scheduled_at']) ?></p>
        <div class="d-flex gap-2 mb-2">
            <a class="btn btn-sm btn-outline-primary" target="_blank" href="tel:<?= htmlspecialchars($r['whatsapp']) ?>">Ligar</a>
            <a class="btn btn-sm btn-outline-success" target="_blank" href="https://wa.me/55<?= preg_replace('/\D+/', '', $r['whatsapp']) ?>">WhatsApp</a>
            <a class="btn btn-sm btn-outline-dark" target="_blank" href="https://www.google.com/maps/dir/?api=1&destination=<?= urlencode($r['latitude'] . ',' . $r['longitude']) ?>">Como chegar</a>
        </div>
        <?php if (!empty($r['photo_path'])): ?><img src="<?= htmlspecialchars($r['photo_path']) ?>" class="img-fluid rounded mb-2"><?php endif; ?>
        <form method="post" action="<?= htmlspecialchars($baseUrl) ?>/public/funcionario/iniciar" class="d-inline"><?= $csrfField ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="btn btn-warning btn-sm">Iniciar coleta</button></form>
        <form method="post" action="<?= htmlspecialchars($baseUrl) ?>/public/funcionario/finalizar" class="d-inline"><?= $csrfField ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="btn btn-success btn-sm">Finalizar Cata Treco</button></form>
    </div></div>
</div>
<?php endforeach; ?>
</div>
