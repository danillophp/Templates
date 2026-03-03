<h4 class="mb-3">Painel do Funcionário</h4>
<div class="row g-3">
<?php foreach ($requests as $r): ?>
  <div class="col-12">
    <div class="card shadow-sm"><div class="card-body">
      <div class="d-flex justify-content-between">
        <strong><?= htmlspecialchars($r['protocolo'] ?? ('#' . $r['id'])) ?> - <?= htmlspecialchars($r['nome']) ?></strong>
        <span class="badge bg-primary"><?= htmlspecialchars($r['status']) ?></span>
      </div>
      <div class="small text-muted mb-2"><?= htmlspecialchars($r['endereco']) ?> | <?= htmlspecialchars($r['data_solicitada']) ?></div>
      <div class="d-flex flex-wrap gap-2 mb-2">
        <a class="btn btn-sm btn-outline-secondary" href="tel:+55<?= preg_replace('/\D+/', '', $r['telefone']) ?>">Ligar</a>
        <a class="btn btn-sm btn-outline-success" target="_blank" href="https://wa.me/55<?= preg_replace('/\D+/', '', $r['telefone']) ?>">WhatsApp</a>
        <a class="btn btn-sm btn-outline-primary" target="_blank" href="https://www.google.com/maps/dir/?api=1&destination=<?= urlencode($r['latitude'] . ',' . $r['longitude']) ?>">Como chegar</a>
        <a class="btn btn-sm btn-outline-dark" target="_blank" href="<?= APP_BASE_PATH ?>/uploads/<?= htmlspecialchars($r['foto']) ?>">Foto</a>
      </div>
      <iframe class="w-100 rounded border" style="height:220px" loading="lazy" src="https://www.google.com/maps?q=<?= urlencode((string)$r['latitude'] . ',' . (string)$r['longitude']) ?>&z=15&output=embed"></iframe>
      <div class="mt-3 d-flex gap-2">
        <button class="btn btn-warning btnStart" data-id="<?= (int)$r['id'] ?>">Iniciar Cata Treco</button>
        <button class="btn btn-success btnFinish" data-id="<?= (int)$r['id'] ?>">Finalizar Cata Treco</button>
      </div>
    </div></div>
  </div>
<?php endforeach; ?>
</div>
<script>window.CSRF = <?= json_encode($csrf) ?>;</script>
<script src="<?= APP_BASE_PATH ?>/assets/js/employee-dashboard.js"></script>
