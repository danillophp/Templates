<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Pagamento da Entrada</h1>
                <p class="text-muted">Sua pré-reserva foi criada com sucesso. Para confirmar o agendamento, realize o pagamento da entrada.</p>

                <div class="row g-3 mb-3">
                    <div class="col-md-6"><strong>Cliente:</strong> <?= e($appointment['cliente_nome']) ?></div>
                    <div class="col-md-6"><strong>Serviço:</strong> <?= e($appointment['servico_nome']) ?></div>
                    <div class="col-md-6"><strong>Data:</strong> <?= e((string) $appointment['data_agendamento']) ?></div>
                    <div class="col-md-6"><strong>Horário:</strong> <?= e((string) $appointment['hora_inicio']) ?> - <?= e((string) $appointment['hora_fim']) ?></div>
                </div>

                <div class="alert alert-info">
                    <div><strong>Valor total:</strong> R$ <?= number_format((float) $appointment['valor_total'], 2, ',', '.') ?></div>
                    <div><strong>Entrada (agora):</strong> R$ <?= number_format((float) $appointment['valor_entrada'], 2, ',', '.') ?></div>
                    <div><strong>Restante:</strong> R$ <?= number_format((float) $appointment['valor_restante'], 2, ',', '.') ?></div>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-success" type="button" disabled>Ir para pagamento (integração futura)</button>
                    <a href="/agendamento" class="btn btn-outline-secondary">Novo agendamento</a>
                </div>
            </div>
        </div>
    </div>
</div>
