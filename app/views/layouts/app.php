<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Painel Administrativo') ?> - Estúdio Bruna Nayara</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="/dashboard">Estúdio Bruna Nayara</a>
        <div class="d-flex align-items-center gap-2 text-white">
            <span class="small">Olá, <?= e($user['name'] ?? 'Usuário') ?></span>
            <form method="post" action="/logout" class="m-0">
                <?= csrf_field() ?>
                <button class="btn btn-outline-light btn-sm" type="submit">Sair</button>
            </form>
        </div>
    </div>
</nav>

<main class="container py-4">
    <?php if ($success = flash('success')): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif; ?>

    <?= $content ?>
</main>
</body>
</html>
