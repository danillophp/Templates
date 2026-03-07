<?php $studio = studio_settings(); $logoUrl = studio_logo_url($studio); ?>
<footer class="studio-footer">
    <div class="container">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <?php if ($logoUrl): ?>
                        <img src="<?= e($logoUrl) ?>" alt="Logo studio" style="height:38px;width:auto;">
                    <?php endif; ?>
                    <div class="fw-semibold"><?= e($studio['nome_studio'] ?? 'Studio Bruna Nayara') ?></div>
                </div>
                <div><?= e($studio['endereco'] ?? '') ?></div>
                <div>Telefone: <?= e($studio['telefone'] ?? '') ?></div>
            </div>
            <div class="col-md-4">
                <div class="fw-semibold mb-2">Horário de funcionamento</div>
                <div style="white-space: pre-line"><?= e($studio['horario_funcionamento'] ?? '') ?></div>
            </div>
            <div class="col-md-4">
                <div class="fw-semibold mb-2">Links úteis</div>
                <div><a href="<?= e($studio['instagram'] ?? '#') ?>" target="_blank" rel="noopener">Instagram</a></div>
                <div><a href="<?= e($studio['facebook'] ?? '#') ?>" target="_blank" rel="noopener">Facebook</a></div>
                <div><a href="<?= e($studio['link_localizacao'] ?? '#') ?>" target="_blank" rel="noopener">Localização</a></div>
                <?php if ((int) ($studio['ativar_convite_google'] ?? 0) === 1 && !empty($studio['link_avaliacao_google'])): ?>
                    <div><a href="<?= e($studio['link_avaliacao_google']) ?>" target="_blank" rel="noopener">Avaliar no Google</a></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="text-center mt-3 small">© <?= date('Y') ?> <?= e($studio['nome_studio'] ?? 'Studio Bruna Nayara') ?>. Todos os direitos reservados.</div>
    </div>
</footer>
