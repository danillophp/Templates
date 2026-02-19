<div class="row g-4">
  <div class="col-lg-7">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="mb-0">Solicitação do Cidadão</h4>
          <a href="?r=auth/login" class="btn btn-outline-secondary btn-sm">Acesso da equipe</a>
        </div>

        <form id="citizenForm" enctype="multipart/form-data" novalidate>
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
          <div class="row g-2">
            <div class="col-12">
              <label class="form-label">Nome completo</label>
              <input class="form-control" name="full_name" placeholder="Nome completo" required>
            </div>
            <div class="col-md-8">
              <label class="form-label">Endereço completo</label>
              <input class="form-control" id="address" name="address" placeholder="Rua, número, complemento" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">CEP</label>
              <input class="form-control" id="cep" name="cep" placeholder="00000-000" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Bairro / Localidade</label>
              <input class="form-control" id="district" name="district" placeholder="Bairro">
            </div>
            <div class="col-md-6">
              <label class="form-label">Telefone (WhatsApp)</label>
              <input class="form-control" name="whatsapp" placeholder="(11) 99999-9999" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Foto dos trecos</label>
              <input class="form-control" type="file" name="photo" accept="image/*" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Data e hora disponíveis</label>
              <input class="form-control" id="pickup_datetime" name="pickup_datetime" required>
            </div>
          </div>

          <input type="hidden" id="latitude" name="latitude">
          <input type="hidden" id="longitude" name="longitude">

          <div class="form-check mt-3">
            <input class="form-check-input" id="consent" name="consent" value="1" type="checkbox" required>
            <label for="consent" class="form-check-label">Consinto com o uso dos meus dados para execução da coleta (LGPD).</label>
          </div>

          <div id="feedback" class="mt-3"></div>
          <button class="btn btn-success mt-3">Enviar Solicitação</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <div id="map" style="height:420px"></div>
        <small class="text-muted">O ponto será atualizado automaticamente. Arraste o marcador para ajuste manual.</small>
      </div>
    </div>
  </div>
</div>
<script src="../assets/js/citizen-form.js"></script>
