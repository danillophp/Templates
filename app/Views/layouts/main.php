<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? $appName) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
</head>
<body>
<header class="site-header">
    <h1><?= htmlspecialchars($appName) ?></h1>
    <nav><a href="/admin/login">Área administrativa</a></nav>
</header>
<main>
    <?= $content ?>
</main>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="/assets/js/map.js" defer></script>
</body>
</html>
