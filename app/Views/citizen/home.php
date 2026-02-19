<div class="row g-4">
  <div class="col-lg-7">
    <div class="card shadow-sm">
      <div class="card-body">
        <h4 class="mb-3">Solicitação do Cidadão</h4>
        <form id="citizenForm" enctype="multipart/form-data">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
          <div class="row g-2">
            <div class="col-12"><input class="form-control" name="full_name" placeholder="Nome completo" required></div>
            <div class="col-md-8"><input class="form-control" id="address" name="address" placeholder="Endereço" required></div>
            <div class="col-md-4"><input class="form-control" id="cep" name="cep" placeholder="CEP" required></div>
            <div class="col-md-6"><input class="form-control" id="district" name="district" placeholder="Bairro"></div>
            <div class="col-md-6"><input class="form-control" name="whatsapp" placeholder="WhatsApp" required></div>
            <div class="col-md-6"><input class="form-control" type="file" name="photo" accept="image/*" required></div>
            <div class="col-md-6"><input class="form-control" id="pickup_datetime" name="pickup_datetime" required></div>
          </div>
          <input type="hidden" id="latitude" name="latitude"><input type="hidden" id="longitude" name="longitude">
          <div class="form-check mt-3"><input class="form-check-input" id="consent" type="checkbox" required><label for="consent" class="form-check-label">Consinto com o uso dos meus dados para execução da coleta (LGPD).</label></div>
          <div id="feedback" class="mt-3"></div>
          <button class="btn btn-success mt-3">Enviar Solicitação</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-5"><div class="card shadow-sm"><div class="card-body"><div id="map" style="height:420px"></div><small class="text-muted">Ajuste o marcador manualmente se necessário.</small></div></div></div>
</div>
<script src="../assets/js/citizen-form.js"></script>
