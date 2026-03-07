<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Configuração da Agenda</h1>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e(base_url('/agenda/configuracoes/salvar')) ?>" class="row g-3" novalidate>
            <?= csrf_field() ?>

            <div class="col-md-3">
                <label for="hora_inicio" class="form-label">Hora início *</label>
                <input id="hora_inicio" type="time" class="form-control" name="hora_inicio" required value="<?= e((string) ($config['hora_inicio'] ?? '08:00')) ?>">
            </div>

            <div class="col-md-3">
                <label for="hora_fim" class="form-label">Hora fim *</label>
                <input id="hora_fim" type="time" class="form-control" name="hora_fim" required value="<?= e((string) ($config['hora_fim'] ?? '18:00')) ?>">
            </div>

            <div class="col-md-3">
                <label for="intervalo_minutos" class="form-label">Intervalo (min) *</label>
                <input id="intervalo_minutos" type="number" min="5" step="5" class="form-control" name="intervalo_minutos" required value="<?= e((string) ($config['intervalo_minutos'] ?? 30)) ?>">
                <div class="form-text">Mínimo de 5 minutos.</div>
            </div>

            <div class="col-md-3">
                <label for="tempo_validade_pre_reserva" class="form-label">Validade pré-reserva (min) *</label>
                <input id="tempo_validade_pre_reserva" type="number" min="1" step="1" class="form-control" name="tempo_validade_pre_reserva" required value="<?= e((string) ($config['tempo_validade_pre_reserva'] ?? 60)) ?>">
            </div>

            <div class="col-12">
                <label for="dias_funcionamento" class="form-label">Dias de funcionamento *</label>
                <input id="dias_funcionamento" type="text" class="form-control" name="dias_funcionamento" required value="<?= e((string) ($config['dias_funcionamento'] ?? '1,2,3,4,5,6')) ?>">
                <div class="form-text">Informe os dias no formato: 1,2,3,4,5,6 (1=segunda, 7=domingo).</div>
            </div>

            <div class="col-12">
                <button class="btn btn-primary" type="submit">Salvar configuração</button>
            </div>
        </form>
    </div>
</div>
