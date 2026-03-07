<?php $studio = studio_settings(); $logoUrl = studio_logo_url($studio); ?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Painel Administrativo') ?> - <?= e($studio['nome_studio'] ?? 'Studio Bruna Nayara') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(asset_url('css/studio-theme.css')) ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white border-bottom shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= e(base_url('/dashboard')) ?>">
            <?php if ($logoUrl): ?>
                <img src="<?= e($logoUrl) ?>" alt="Logo Studio">
            <?php endif; ?>
            <span class="fw-semibold"><?= e($studio['nome_studio'] ?? 'Studio Bruna Nayara') ?></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="<?= e(base_url('/dashboard')) ?>">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e(base_url('/clientes')) ?>">Clientes</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e(base_url('/categorias')) ?>">Categorias</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e(base_url('/servicos')) ?>">Serviços</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e(base_url('/agenda/configuracoes')) ?>">Agenda</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e(base_url('/calendario')) ?>">Calendário</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e(base_url('/configuracoes/studio')) ?>">Studio</a></li>
            </ul>
            <div class="d-flex align-items-center gap-2">
                <span class="small">Olá, <?= e($user['name'] ?? 'Usuário') ?></span>
                <form method="post" action="<?= e(base_url('/logout')) ?>" class="m-0">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline-secondary btn-sm" type="submit">Sair</button>
                </form>
            </div>
        </div>
    </div>
</nav>

<main class="container py-4">
    <?php if ($success = flash('success')): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error = flash('error')): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <?= $content ?>
</main>

<?php include __DIR__ . '/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
