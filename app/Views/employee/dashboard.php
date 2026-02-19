<h4 class="mb-3">Painel do Funcionário</h4>
<div class="row g-3">
<?php foreach ($requests as $r): ?>
  <div class="col-12">
    <div class="card shadow-sm"><div class="card-body">
      <div class="d-flex justify-content-between"><strong>#<?= (int)$r['id'] ?> - <?= htmlspecialchars($r['full_name']) ?></strong><span class="badge bg-primary"><?= htmlspecialchars($r['status']) ?></span></div>
      <div class="small text-muted"><?= htmlspecialchars($r['address']) ?> | <?= htmlspecialchars($r['pickup_datetime']) ?></div>
      <div class="mt-2 d-flex flex-wrap gap-2">
        <a class="btn btn-sm btn-outline-secondary" href="tel:+55<?= preg_replace('/\D+/', '', $r['whatsapp']) ?>">Ligar</a>
        <a class="btn btn-sm btn-outline-success" target="_blank" href="https://wa.me/55<?= preg_replace('/\D+/', '', $r['whatsapp']) ?>">WhatsApp</a>
        <a class="btn btn-sm btn-outline-primary" target="_blank" href="https://www.google.com/maps/dir/?api=1&destination=<?= urlencode($r['latitude'] . ',' . $r['longitude']) ?>">Como chegar</a>
        <a class="btn btn-sm btn-outline-dark" target="_blank" href="../uploads/<?= htmlspecialchars($r['photo_path']) ?>">Foto</a>
      </div>
      <div class="mt-3 d-flex gap-2">
        <button class="btn btn-warning btnStart" data-id="<?= (int)$r['id'] ?>">Iniciar coleta</button>
        <button class="btn btn-success btnFinish" data-id="<?= (int)$r['id'] ?>">Finalizar coleta</button>
      </div>
    </div></div>
  </div>
<?php endforeach; ?>
</div>
<script>window.CSRF = <?= json_encode($csrf) ?>;</script>
<script src="../assets/js/employee-dashboard.js"></script>
