<?php $studio = studio_settings(); $logoUrl = studio_logo_url($studio); ?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Agendamento') ?> - <?= e($studio['nome_studio'] ?? 'Studio Bruna Nayara') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(asset_url('css/studio-theme.css')) ?>" rel="stylesheet">
</head>
<body class="bg-body-tertiary">
<nav class="navbar bg-white border-bottom shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= e(base_url('/agendamento')) ?>">
            <?php if ($logoUrl): ?>
                <img src="<?= e($logoUrl) ?>" alt="Logo Studio">
            <?php endif; ?>
            <span class="fw-semibold"><?= e($studio['nome_studio'] ?? 'Studio Bruna Nayara') ?></span>
        </a>
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
