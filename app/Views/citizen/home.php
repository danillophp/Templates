<div class="row g-4">
  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h4 class="mb-1">Mapa de Pontos de Coleta - Cata Treco</h4>
            <small class="text-muted">Clique em um ponto para abrir a solicitação.</small>
          </div>
          <a href="?r=auth/login" class="btn btn-outline-secondary btn-sm">Acesso da equipe</a>
        </div>
        <div id="mainGoogleMap" style="height: 520px"></div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="requestModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Nova Solicitação de Cata Treco</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-4">
          <div class="col-lg-7">
            <form id="citizenForm" enctype="multipart/form-data" novalidate>
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
              <div class="row g-2">
                <div class="col-12">
                  <label class="form-label">Nome completo</label>
                  <input class="form-control" name="full_name" required>
                </div>
                <div class="col-md-8">
                  <label class="form-label">Endereço completo</label>
                  <input class="form-control" id="address" name="address" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label">CEP</label>
                  <input class="form-control" id="cep" name="cep" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Telefone (WhatsApp)</label>
                  <input class="form-control" name="whatsapp" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Data de coleta</label>
                  <input class="form-control" id="pickup_datetime" name="pickup_datetime" required>
                </div>
                <div class="col-12">
                  <label class="form-label">Foto</label>
                  <input class="form-control" type="file" name="photo" accept="image/*" required>
                </div>
              </div>

              <input type="hidden" id="latitude" name="latitude">
              <input type="hidden" id="longitude" name="longitude">

              <div id="feedback" class="mt-3"></div>
              <button class="btn btn-success mt-3">Enviar Solicitação</button>
            </form>
          </div>

          <div class="col-lg-5">
            <div id="confirmMap" style="height: 400px"></div>
            <small class="text-muted">Mapa Leaflet de confirmação com ajuste manual do marcador.</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>window.GOOGLE_MAPS_KEY = <?= json_encode($googleMapsKey ?? '') ?>;</script>
<script src="../assets/js/citizen-form.js"></script>
<?php if (!empty($googleMapsKey)): ?>
  <script src="https://maps.googleapis.com/maps/api/js?key=<?= urlencode($googleMapsKey) ?>&callback=initCitizenGoogleMap" async defer></script>
<?php endif; ?>
