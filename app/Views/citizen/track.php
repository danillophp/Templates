<div class="row justify-content-center">
  <div class="col-lg-6">
    <div class="card shadow-sm glass-card border-0">
      <div class="card-body p-4">
        <h4 class="mb-3">Consulta pública de protocolo</h4>
        <p class="text-muted">Informe o protocolo OU o telefone usado no cadastro.</p>
        <div class="mb-2"><input id="trackProtocol" class="form-control" placeholder="CAT-2026-000123"></div>
        <div class="mb-2"><input id="trackPhone" class="form-control" placeholder="Telefone informado"></div>
        <button id="btnTrack" class="btn btn-primary w-100">Consultar status</button>
        <div id="trackResult" class="mt-3"></div>
      </div>
    </div>
  </div>
</div>

<script src="<?= APP_BASE_PATH ?>/assets/js/citizen-form.js"></script>
