<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Dashboard Administrativo</h1>
    <a class="btn btn-sm btn-primary" href="<?= e(base_url('/calendario')) ?>">Abrir calendário</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4 col-xl-2"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted">Agendamentos do dia</small><h3 class="mb-0"><?= (int) $summary['agendamentos_hoje'] ?></h3></div></div></div>
    <div class="col-md-4 col-xl-2"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted">Clientes do dia</small><h3 class="mb-0"><?= (int) $summary['clientes_do_dia'] ?></h3></div></div></div>
    <div class="col-md-4 col-xl-2"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted">Pendentes</small><h3 class="mb-0"><?= (int) ($summary['pagamentos_pendentes'] ?? 0) ?></h3></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted">Faturamento do dia</small><h3 class="mb-0">R$ <?= number_format((float) $summary['pagamentos_recebidos_hoje'], 2, ',', '.') ?></h3></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted">Saldo a receber</small><h3 class="mb-0">R$ <?= number_format((float) $summary['saldo_a_receber_hoje'], 2, ',', '.') ?></h3></div></div></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <h2 class="h6 mb-3">Atendimentos por dia (últimos 7 dias)</h2>
            <div class="chart-shell"><canvas id="appointmentsChart"></canvas></div>
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <h2 class="h6 mb-3">Faturamento mensal</h2>
            <div class="chart-shell"><canvas id="revenueChart"></canvas></div>
        </div></div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body pb-0"><h2 class="h6">Próximos agendamentos do dia</h2></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th>#</th><th>Horário</th><th>Cliente</th><th>Serviço</th><th>Status</th><th>Valor</th></tr></thead>
            <tbody>
            <?php if (empty($todayAppointments)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Nenhum agendamento para hoje.</td></tr>
            <?php endif; ?>
            <?php foreach ($todayAppointments as $row): ?>
                <?php
                    $status = (string) $row['status'];
                    $badge = match ($status) {
                        'confirmado' => 'text-bg-success',
                        'aguardando_pagamento' => 'text-bg-warning',
                        'cancelado' => 'text-bg-danger',
                        'realizado' => 'text-bg-primary',
                        default => 'text-bg-secondary',
                    };
                ?>
                <tr>
                    <td><?= (int) $row['id'] ?></td>
                    <td><?= e(substr((string) $row['hora_inicio'], 0, 5)) ?> - <?= e(substr((string) $row['hora_fim'], 0, 5)) ?></td>
                    <td><?= e($row['cliente_nome']) ?></td>
                    <td><?= e($row['servico_nome']) ?></td>
                    <td><span class="badge <?= e($badge) ?>"><?= e($status) ?></span></td>
                    <td>R$ <?= number_format((float) $row['valor_total'], 2, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.chart-shell{position:relative;height:260px;max-height:260px;overflow:hidden}
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(() => {
    if (window.__dashboardChartsInited) return;
    window.__dashboardChartsInited = true;

    const appointmentsLabels = <?= json_encode(array_column($appointmentsByDay, 'label'), JSON_UNESCAPED_UNICODE) ?>;
    const appointmentsData = <?= json_encode(array_map('intval', array_column($appointmentsByDay, 'total'))) ?>;
    const revenueLabels = <?= json_encode(array_column($monthlyRevenue, 'label'), JSON_UNESCAPED_UNICODE) ?>;
    const revenueData = <?= json_encode(array_map('floatval', array_column($monthlyRevenue, 'total'))) ?>;

    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        animation: {duration: 300},
        resizeDelay: 150,
    };

    const apptEl = document.getElementById('appointmentsChart');
    const revEl = document.getElementById('revenueChart');
    if (!apptEl || !revEl) return;

    new Chart(apptEl, {
        type: 'line',
        data: {labels: appointmentsLabels, datasets: [{label: 'Atendimentos', data: appointmentsData, borderColor: '#b76e79', backgroundColor: 'rgba(183,110,121,.18)', tension: .35, fill: true}]},
        options: commonOptions,
    });

    new Chart(revEl, {
        type: 'bar',
        data: {labels: revenueLabels, datasets: [{label: 'Faturamento (R$)', data: revenueData, backgroundColor: 'rgba(183,110,121,.65)', borderColor: '#9f5e68', borderWidth: 1}]},
        options: commonOptions,
    });
})();
</script>
