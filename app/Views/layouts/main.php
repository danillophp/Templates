<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? $appName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl) ?>/public/assets/css/style.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-success">
    <div class="container"><span class="navbar-brand mb-0 h1"><?= htmlspecialchars($appName) ?></span><a class="btn btn-outline-light btn-sm" href="<?= htmlspecialchars($baseUrl) ?>/public/admin/login">Área interna</a></div>
</nav>
<main class="container py-4"><?= $content ?></main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= htmlspecialchars($baseUrl) ?>/public/assets/js/map.js"></script>
<script>window.APP_BASE_URL = <?= json_encode($baseUrl) ?>;</script>
</body>
</html>
