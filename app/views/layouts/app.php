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
        <a class="navbar-brand" href="/dashboard">Studio Bruna Nayara</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="/dashboard">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="/clientes">Clientes</a></li>
                <li class="nav-item"><a class="nav-link" href="/categorias">Categorias</a></li>
                <li class="nav-item"><a class="nav-link" href="/servicos">Serviços</a></li>
            </ul>
            <div class="d-flex align-items-center gap-2 text-white">
                <span class="small">Olá, <?= e($user['name'] ?? 'Usuário') ?></span>
                <form method="post" action="/logout" class="m-0">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline-light btn-sm" type="submit">Sair</button>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
