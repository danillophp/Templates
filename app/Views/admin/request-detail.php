<div class="card shadow-sm glass-card border-0">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">Detalhes da Solicitação #<?= (int)$request['id'] ?></h5>
      <a href="<?= APP_BASE_PATH ?>/?r=admin/dashboard" class="btn btn-outline-secondary btn-sm">Voltar</a>
    </div>

    <div class="row g-3">
      <div class="col-md-6"><strong>Protocolo:</strong> <?= htmlspecialchars((string)$request['protocolo']) ?></div>
      <div class="col-md-6"><strong>Status:</strong> <?= htmlspecialchars((string)$request['status']) ?></div>
      <div class="col-md-6"><strong>Nome:</strong> <?= htmlspecialchars((string)$request['nome']) ?></div>
      <div class="col-md-6"><strong>Telefone:</strong> <?= htmlspecialchars((string)$request['telefone']) ?></div>
      <div class="col-md-8"><strong>Endereço:</strong> <?= htmlspecialchars((string)$request['endereco']) ?></div>
      <div class="col-md-4"><strong>Data:</strong> <?= htmlspecialchars((string)$request['data_solicitada']) ?></div>
      <div class="col-md-12"><strong>E-mail:</strong> <?= htmlspecialchars((string)($request['email'] ?? '-')) ?></div>
    </div>

    <hr>

    <div class="d-flex gap-2 mb-3 flex-wrap">
      <a target="_blank" class="btn btn-outline-primary btn-sm" href="https://www.google.com/maps/dir/?api=1&destination=<?= urlencode((string)$request['latitude'] . ',' . (string)$request['longitude']) ?>">Como chegar</a>
      <a class="btn btn-outline-success btn-sm" href="tel:<?= htmlspecialchars((string)$request['telefone']) ?>">Telefonar</a>
      <a target="_blank" class="btn btn-outline-success btn-sm" href="https://wa.me/55<?= preg_replace('/\D+/', '', (string)$request['telefone']) ?>">WhatsApp</a>
      <a target="_blank" class="btn btn-outline-dark btn-sm" href="<?= APP_BASE_PATH ?>/uploads/<?= htmlspecialchars((string)$request['foto']) ?>">Ver foto anexada</a>
    </div>

    <iframe
      title="Mapa da solicitação"
      class="w-100 rounded border"
      style="height:320px"
      loading="lazy"
      referrerpolicy="no-referrer-when-downgrade"
      src="https://www.google.com/maps?q=<?= urlencode((string)$request['latitude'] . ',' . (string)$request['longitude']) ?>&z=16&output=embed"></iframe>
  </div>
</div>
