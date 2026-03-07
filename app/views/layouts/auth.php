<?php $studio = studio_settings(); ?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Login') ?> - <?= e($studio['nome_studio'] ?? 'Studio Bruna Nayara') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(asset_url('css/studio-theme.css')) ?>" rel="stylesheet">
</head>
<body class="bg-body-tertiary">
<main class="container py-5">
    <?= $content ?>
</main>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
