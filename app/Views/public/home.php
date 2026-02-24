<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h4">Solicitação de Cata Treco</h2>
                <p class="text-muted">Preencha seus dados e escolha data/hora disponível para coleta.</p>
                <form id="requestForm" enctype="multipart/form-data">
                    <?= $csrfField ?>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Nome completo</label><input required name="citizen_name" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Telefone (WhatsApp)</label><input required name="whatsapp" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">CEP</label><input name="cep" id="cep" class="form-control"></div>
                        <div class="col-md-8"><label class="form-label">Endereço completo</label><input required name="address" id="address" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Data e hora da coleta</label><input required type="datetime-local" name="scheduled_at" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Foto dos trecos</label><input type="file" name="photo" accept="image/*" class="form-control"></div>
                    </div>
                    <input type="hidden" name="latitude" id="latitude">
                    <input type="hidden" name="longitude" id="longitude">
                    <div class="form-check mt-3">
                        <input required class="form-check-input" type="checkbox" value="1" name="consent_lgpd" id="consent">
                        <label class="form-check-label" for="consent">Autorizo o uso dos dados para execução da coleta (LGPD).</label>
                    </div>
                    <button class="btn btn-success mt-3" type="submit">Enviar solicitação</button>
                    <div id="formFeedback" class="mt-3"></div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm"><div class="card-body">
            <h3 class="h6">Localização da coleta</h3>
            <div id="map" style="height: 420px"></div>
            <small class="text-muted">Você pode arrastar o marcador para ajustar o ponto exato.</small>
        </div></div>
    </div>
</div>
