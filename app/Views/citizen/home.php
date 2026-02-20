<?php if (!empty($tenantWarning)): ?>
  <div class="alert alert-info glass-card mb-4"><?= htmlspecialchars($tenantWarning) ?></div>
<?php endif; ?>

<div class="row g-4 align-items-start">
  <div class="col-lg-6">
    <div class="card shadow-sm glass-card border-0">
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h4 class="mb-1">Solicitação de Cata Treco</h4>
            <small class="text-muted">Preencha os dados para agendar sua coleta.</small>
          </div>
          <div class="d-flex gap-2">
            <a href="<?= APP_BASE_PATH ?>/?r=citizen/track" class="btn btn-outline-secondary btn-sm">Consultar protocolo</a>
            <a href="<?= APP_BASE_PATH ?>/?r=auth/login" class="btn btn-outline-primary btn-sm">Acesso da equipe</a>
          </div>
        </div>

        <form id="citizenForm" enctype="multipart/form-data" novalidate>
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">

          <div class="row g-3">
            <div class="col-12"><label class="form-label">Nome completo</label><input class="form-control" name="full_name" required></div>
            <div class="col-md-8"><label class="form-label">Endereço completo</label><input class="form-control" id="address" name="address" required></div>
            <div class="col-md-4"><label class="form-label">CEP</label><input class="form-control" id="cep" name="cep" required></div>
            <div class="col-md-6"><label class="form-label">Bairro</label><input class="form-control" id="district" name="district" required></div>
            <div class="col-md-6"><label class="form-label">Telefone (WhatsApp)</label><input class="form-control" name="whatsapp" required></div>
            <div class="col-md-6"><label class="form-label">Data de coleta</label><input class="form-control" type="date" min="<?= date('Y-m-d') ?>" id="pickup_datetime" name="pickup_datetime" required></div>
            <div class="col-md-6"><label class="form-label">Foto dos Trecos</label><input class="form-control" type="file" name="photo" accept="image/*" required></div>
          </div>

          <input type="hidden" id="latitude" name="latitude">
          <input type="hidden" id="longitude" name="longitude">

          <div id="feedback" class="mt-3"></div>
          <div id="receipt" class="mt-3 d-none"></div>
          <button class="btn btn-success mt-3 w-100">Enviar Solicitação</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card shadow-sm glass-card border-0">
      <div class="card-body p-3">
        <h5 class="mb-2">Mapa de confirmação (gratuito)</h5>
        <div id="map" style="height: 500px"></div>
        <small class="text-muted d-block mt-2">Leaflet + OpenStreetMap + Nominatim. Arraste o marcador para ajustar o local.</small>
        <div id="geoFeedback" class="mt-2"></div>
      </div>
    </div>

    <div class="card shadow-sm glass-card border-0 mt-3">
      <div class="card-body">
        <h6 class="mb-2">Consultar protocolo</h6>
        <div class="mb-2"><input id="trackProtocol" class="form-control" placeholder="CAT-2026-000123"></div>
        <div class="mb-2"><input id="trackPhone" class="form-control" placeholder="Telefone informado"></div>
        <button id="btnTrack" class="btn btn-primary w-100">Consultar status</button>
        <div id="trackResult" class="mt-3"></div>
      </div>
    </div>
  </div>
</div>

<script src="<?= APP_BASE_PATH ?>/assets/js/citizen-form.js"></script>
