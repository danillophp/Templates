<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Pagamento da Entrada</h1>
                <p class="text-muted">Sua pré-reserva foi criada. O agendamento só será confirmado após o pagamento da entrada.</p>

                <div class="row g-3 mb-3">
                    <div class="col-md-6"><strong>Cliente:</strong> <?= e($appointment['cliente_nome']) ?></div>
                    <div class="col-md-6"><strong>Serviço:</strong> <?= e($appointment['servico_nome']) ?></div>
                    <div class="col-md-6"><strong>Data:</strong> <?= e((string) $appointment['data_agendamento']) ?></div>
                    <div class="col-md-6"><strong>Horário:</strong> <?= e((string) $appointment['hora_inicio']) ?> - <?= e((string) $appointment['hora_fim']) ?></div>
                    <div class="col-md-6"><strong>Status:</strong> <span class="badge text-bg-secondary"><?= e((string) $appointment['status']) ?></span></div>
                    <div class="col-md-6"><strong>Pré-reserva válida até:</strong> <?= e((string) ($appointment['pre_reserva_expira_em'] ?? '-')) ?></div>
                </div>

                <div class="alert alert-info">
                    <div><strong>Valor total:</strong> R$ <?= number_format((float) $appointment['valor_total'], 2, ',', '.') ?></div>
                    <div><strong>Entrada (agora):</strong> R$ <?= number_format((float) $appointment['valor_entrada'], 2, ',', '.') ?></div>
                    <div><strong>Restante:</strong> R$ <?= number_format((float) $appointment['valor_restante'], 2, ',', '.') ?></div>
                </div>


                <?php $studio = studio_settings(); ?>
                <?php if ((int) ($studio['ativar_convite_google'] ?? 0) === 1): ?>
                    <div class="studio-google-invite mb-3">
                        <?= e($studio['texto_convite_google'] ?? '') ?>
                        <?php if (!empty($studio['link_avaliacao_google'])): ?>
                            <a href="<?= e($studio['link_avaliacao_google']) ?>" target="_blank" rel="noopener">Deixar avaliação</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if ($appointment['status'] !== 'confirmado'): ?>
                    <form method="post" action="<?= e(base_url('/agendamento/pagamento/confirmar')) ?>" class="row g-2 mb-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="agendamento_id" value="<?= (int) $appointment['id'] ?>">
                        <div class="col-md-6">
                            <label class="form-label">Método de pagamento *</label>
                            <select class="form-select" name="metodo_pagamento" required>
                                <option value="">Selecione</option>
                                <option value="pix">Pix</option>
                                <option value="cartao">Cartão</option>
                                <option value="dinheiro">Dinheiro</option>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button class="btn btn-success" type="submit">Confirmar pagamento da entrada</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-success">Pagamento confirmado e agendamento confirmado.</div>
                <?php endif; ?>

                <a href="<?= e(base_url('/agendamento')) ?>" class="btn btn-outline-secondary">Novo agendamento</a>
            </div>
        </div>
    </div>
</div>
