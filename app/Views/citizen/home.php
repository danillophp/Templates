<div class="row g-4 align-items-start citizen-layout">
  <div class="col-lg-6 order-1 order-lg-1" id="citizenFormColumn">
    <div class="card shadow-sm glass-card border-0">
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <h4 class="mb-1">Solicitação de Cata Treco</h4>
            <small class="text-muted">Atendimento exclusivo em Santo Antônio do Descoberto - GO.</small>
          </div>
          <div class="d-flex gap-2">
            <a href="<?= APP_BASE_PATH ?>/?r=citizen/track" class="btn btn-outline-secondary btn-sm">Consultar protocolo</a>
            <a href="<?= APP_BASE_PATH ?>/?r=auth/login" class="btn btn-outline-primary btn-sm">Acesso da equipe</a>
          </div>
        </div>

        <form id="citizenForm" enctype="multipart/form-data" novalidate>
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
          <div style="position:absolute;left:-9999px;top:-9999px;opacity:0;pointer-events:none">
            <label for="site_url">Não preencha</label>
            <input type="text" id="site_url" name="site_url" tabindex="-1" autocomplete="off">
          </div>

          <div class="row g-3">
            <div class="col-12"><label class="form-label">Nome completo</label><input class="form-control" name="full_name" required></div>
            <div class="col-md-8"><label class="form-label">Endereço completo</label><input class="form-control" id="address" name="address" required></div>
            <div class="col-md-4"><label class="form-label">CEP</label><input class="form-control" id="cep" name="cep" required maxlength="8" placeholder="73890000"></div>
            <div class="col-md-6">
              <label class="form-label">Bairro</label>
              <select class="form-select" id="bairro" name="bairro" required>
                <option value="">Selecione o bairro</option>
                <?php foreach (($bairrosPermitidos ?? []) as $bairro): ?>
                  <option value="<?= htmlspecialchars((string)$bairro) ?>"><?= htmlspecialchars((string)$bairro) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6"><label class="form-label">Telefone (WhatsApp)</label><input class="form-control" name="whatsapp" required></div>
            <div class="col-md-6"><label class="form-label">E-mail</label><input class="form-control" type="email" name="email" required></div>
            <div class="col-md-6"><label class="form-label">Data de coleta (somente quinta-feira)</label><input class="form-control" type="date" min="<?= date('Y-m-d') ?>" id="pickup_datetime" name="pickup_datetime" required><small class="text-muted">Agendamentos apenas às quintas-feiras.</small></div>
            <div class="col-md-12"><label class="form-label">Foto dos Trecos</label><input class="form-control" type="file" name="photo" accept="image/*" required></div>
          </div>

          <input type="hidden" id="latitude" name="latitude">
          <input type="hidden" id="longitude" name="longitude">
          <input type="hidden" id="localizacao_status" name="localizacao_status" value="PENDENTE">
          <input type="hidden" id="viacep_city" name="viacep_city">
          <input type="hidden" id="viacep_uf" name="viacep_uf">

          <div id="feedback" class="mt-3"></div>
          <div id="receipt" class="mt-3 d-none"></div>
          <button id="btnSubmitDesktop" class="btn btn-success mt-3 w-100">Enviar Solicitação</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-6 order-2 order-lg-2" id="citizenMapColumn">
    <div class="card shadow-sm glass-card border-0">
      <div class="card-body p-3">
        <h5 class="mb-2">Mapa de confirmação</h5>
        <div id="geoFeedback" class="mt-2"></div>
        <div class="mt-3">
          <h6 class="mb-2">Visualização Google Maps</h6>
          <iframe id="googleMapEmbed" class="w-100 rounded border d-none" style="height:260px" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://www.google.com/maps?q=-15.9439,-48.2585&z=14&output=embed"></iframe>
        </div>
      </div>
    </div>

    <button id="btnSubmitMobile" form="citizenForm" class="btn btn-success mt-3 w-100 d-lg-none">Enviar Solicitação</button>

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

<script>
window.CATA_MAP_CONFIG = {
  defaultLat: -15.9439,
  defaultLng: -48.2585,
  allowedCity: 'Santo Antônio do Descoberto',
  allowedUf: 'GO'
};
</script>
<script src="<?= APP_BASE_PATH ?>/assets/js/citizen-form.js" defer></script>
