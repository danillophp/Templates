<?php $logoUrl = studio_logo_url($settings); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Configurações institucionais</h1>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e(base_url('/configuracoes/studio/salvar')) ?>" class="row g-3" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="col-md-6">
                <label class="form-label">Nome do studio *</label>
                <input class="form-control" name="nome_studio" required value="<?= e($settings['nome_studio'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Telefone *</label>
                <input class="form-control" name="telefone" required value="<?= e($settings['telefone'] ?? '') ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Endereço *</label>
                <input class="form-control" name="endereco" required value="<?= e($settings['endereco'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Instagram</label>
                <input class="form-control" name="instagram" value="<?= e($settings['instagram'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Facebook</label>
                <input class="form-control" name="facebook" value="<?= e($settings['facebook'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Link de localização</label>
                <input class="form-control" name="link_localizacao" value="<?= e($settings['link_localizacao'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Upload da logo (PNG, JPG, WEBP até 5MB)</label>
                <input type="file" class="form-control" name="logo" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp">
                <?php if ($logoUrl): ?>
                    <div class="mt-2"><img src="<?= e($logoUrl) ?>" alt="Logo atual" style="max-height:70px"></div>
                <?php else: ?>
                    <div class="form-text">Nenhuma logo enviada. O sistema exibirá o nome do studio.</div>
                <?php endif; ?>
            </div>
            <div class="col-12">
                <label class="form-label">Horário de funcionamento</label>
                <textarea class="form-control" rows="4" name="horario_funcionamento"><?= e($settings['horario_funcionamento'] ?? '') ?></textarea>
            </div>

            <div class="col-12 mt-2">
                <h2 class="h6">Convite de avaliação Google</h2>
            </div>
            <div class="col-md-4 form-check ms-2">
                <input class="form-check-input" type="checkbox" name="ativar_convite_google" id="ativar_convite_google" <?= (int) ($settings['ativar_convite_google'] ?? 0) === 1 ? 'checked' : '' ?>>
                <label class="form-check-label" for="ativar_convite_google">Ativar convite amigável</label>
            </div>
            <div class="col-md-8">
                <label class="form-label">Link de avaliação Google</label>
                <input class="form-control" name="link_avaliacao_google" value="<?= e($settings['link_avaliacao_google'] ?? '') ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Texto do convite</label>
                <textarea class="form-control" rows="2" name="texto_convite_google"><?= e($settings['texto_convite_google'] ?? '') ?></textarea>
            </div>
            <div class="col-12">
                <button class="btn btn-primary" type="submit">Salvar configurações</button>
            </div>
        </form>
    </div>
</div>
