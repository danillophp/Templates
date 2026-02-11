<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? $appName) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="site-header">
    <h1><?= htmlspecialchars($appName) ?></h1>
    <nav><a href="/admin/login">Área administrativa</a></nav>
</header>
<main>
    <?= $content ?>
</main>
<script>
    window.APP_CONFIG = {
        googleMapsApiKey: "<?= htmlspecialchars($googleMapsApiKey, ENT_QUOTES) ?>"
    };
</script>
<script src="/assets/js/map.js" defer></script>
</body>
</html>
