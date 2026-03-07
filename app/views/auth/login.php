<?php $studio = studio_settings(); $logoUrl = studio_logo_url($studio); ?>
<div class="row justify-content-center">
    <div class="col-12 col-sm-10 col-md-6 col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <div class="text-center mb-3">
                    <?php if ($logoUrl): ?>
                        <img src="<?= e($logoUrl) ?>" alt="Logo" style="max-height:70px">
                    <?php else: ?>
                        <span class="fw-semibold"><?= e($studio['nome_studio'] ?? 'Studio Bruna Nayara') ?></span>
                    <?php endif; ?>
                </div>
                <h1 class="h4 mb-3 text-center">Acessar painel</h1>
                <p class="text-muted text-center small">Estúdio Bruna Nayara</p>

                <?php if ($error = flash('error')): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>

                <?php if ($success = flash('success')): ?>
                    <div class="alert alert-success"><?= e($success) ?></div>
                <?php endif; ?>

                <form method="post" action="<?= e(base_url('/login')) ?>" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" id="email" name="email" class="form-control" required autocomplete="email" value="<?= old('email') ?>">
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Senha</label>
                        <input type="password" id="password" name="password" class="form-control" required autocomplete="current-password">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Entrar</button>
                </form>
            </div>
        </div>
    </div>
</div>
