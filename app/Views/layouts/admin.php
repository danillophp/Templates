<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Admin') ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<main class="admin-container">
    <?= $content ?>
</main>
</body>
</html>
