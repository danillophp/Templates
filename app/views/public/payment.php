<?php $studio = studio_settings(); ?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Pagamento da Entrada</h1>
                <p class="text-muted">Escolha PIX, cartão de crédito ou pagamento manual para confirmar seu horário.</p>

                <div class="row g-3 mb-3">
                    <div class="col-md-6"><strong>Cliente:</strong> <?= e($appointment['cliente_nome']) ?></div>
                    <div class="col-md-6"><strong>Serviço:</strong> <?= e($appointment['servico_nome']) ?></div>
                    <div class="col-md-6"><strong>Data:</strong> <?= e((string) $appointment['data_agendamento']) ?></div>
                    <div class="col-md-6"><strong>Horário:</strong> <?= e((string) $appointment['hora_inicio']) ?> - <?= e((string) $appointment['hora_fim']) ?></div>
                    <div class="col-md-6"><strong>Status agendamento:</strong> <span class="badge text-bg-secondary"><?= e((string) $appointment['status']) ?></span></div>
                    <div class="col-md-6"><strong>Pré-reserva válida até:</strong> <?= e((string) ($appointment['pre_reserva_expira_em'] ?? '-')) ?></div>
                </div>

                <div class="alert alert-info">
                    <div><strong>Valor total:</strong> R$ <?= number_format((float) $appointment['valor_total'], 2, ',', '.') ?></div>
                    <div><strong>Entrada (agora):</strong> R$ <?= number_format((float) $appointment['valor_entrada'], 2, ',', '.') ?></div>
                    <div><strong>Restante:</strong> R$ <?= number_format((float) $appointment['valor_restante'], 2, ',', '.') ?></div>
                </div>

                <?php if ((int) ($studio['ativar_convite_google'] ?? 0) === 1): ?>
                    <div class="studio-google-invite mb-3">
                        <?= e($studio['texto_convite_google'] ?? '') ?>
                        <?php if (!empty($studio['link_avaliacao_google'])): ?>
                            <a href="<?= e($studio['link_avaliacao_google']) ?>" target="_blank" rel="noopener">Deixar avaliação</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (($payment['status'] ?? '') === 'pago'): ?>
                    <div class="alert alert-success">Pagamento confirmado e agendamento confirmado.</div>
                <?php else: ?>
                    <form method="post" action="<?= e(base_url('/agendamento/pagamento/confirmar')) ?>" class="row g-2 mb-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="agendamento_id" value="<?= (int) $appointment['id'] ?>">
                        <div class="col-md-6">
                            <label class="form-label">Método de pagamento *</label>
                            <select class="form-select" name="metodo_pagamento" required>
                                <option value="">Selecione</option>
                                <option value="pix">PIX (geração imediata)</option>
                                <option value="cartao">Cartão de crédito</option>
                                <option value="dinheiro">Pagamento manual</option>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button class="btn btn-success" type="submit">Iniciar pagamento</button>
                        </div>
                    </form>
                <?php endif; ?>

                <?php if (!empty($payment)): ?>
                    <?php $payload = json_decode((string) ($payment['payload'] ?? ''), true) ?: []; ?>
                    <div class="card border-0 bg-light mb-3">
                        <div class="card-body">
                            <h2 class="h6 mb-2">Detalhes da cobrança</h2>
                            <div><strong>Método:</strong> <?= e((string) ($payment['metodo_pagamento'] ?? '-')) ?></div>
                            <div><strong>Status:</strong> <?= e((string) ($payment['status'] ?? '-')) ?></div>
                            <div><strong>Referência:</strong> <?= e((string) ($payment['referencia_externa'] ?? '-')) ?></div>
                            <?php if (!empty($payload['pix_key'])): ?><div><strong>Chave PIX:</strong> <?= e((string) $payload['pix_key']) ?></div><?php endif; ?>
                            <?php if (!empty($payload['qr_code_url'])): ?><div class="mt-2"><img src="<?= e((string) $payload['qr_code_url']) ?>" alt="QR Code PIX" style="max-width:220px"></div><?php endif; ?>
                            <?php if (!empty($payload['checkout_url'])): ?><div class="mt-2"><a class="btn btn-outline-primary btn-sm" href="<?= e((string) $payload['checkout_url']) ?>">Ir para checkout do cartão</a></div><?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <a href="<?= e(base_url('/agendamento')) ?>" class="btn btn-outline-secondary">Novo agendamento</a>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const appointmentId = <?= (int) $appointment['id'] ?>;
    const endpoint = `<?= e(base_url('/pagamentos/status')) ?>?agendamento_id=${appointmentId}`;

    let tries = 0;
    const maxTries = 20;

    const timer = setInterval(async () => {
        tries++;
        if (tries > maxTries) {
            clearInterval(timer);
            return;
        }

        try {
            const res = await fetch(endpoint, {headers: {'Accept': 'application/json'}});
            const data = await res.json();
            if (data?.ok && data?.payment?.status === 'pago') {
                clearInterval(timer);
                window.location.reload();
            }
        } catch {}
    }, 8000);
})();
</script>
