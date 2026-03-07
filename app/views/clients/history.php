<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 mb-0">Histórico do Cliente</h1>
        <small class="text-muted"><?= e($client['nome']) ?> · <?= e($client['telefone']) ?></small>
    </div>
    <a href="<?= e(base_url('/clientes')) ?>" class="btn btn-outline-secondary btn-sm">Voltar</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Data</th>
                    <th>Horário</th>
                    <th>Serviço</th>
                    <th>Categoria</th>
                    <th>Status</th>
                    <th>Valor</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($history)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Sem histórico de agendamentos para este cliente.</td></tr>
            <?php endif; ?>

            <?php foreach ($history as $item): ?>
                <tr>
                    <td><?= (int) $item['id'] ?></td>
                    <td><?= e((string) $item['data_agendamento']) ?></td>
                    <td><?= e((string) $item['hora_inicio']) ?> - <?= e((string) $item['hora_fim']) ?></td>
                    <td><?= e($item['servico_nome']) ?></td>
                    <td><?= e($item['categoria_nome']) ?></td>
                    <td><?= e($item['status']) ?></td>
                    <td>R$ <?= number_format((float) $item['valor_total'], 2, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
