<?php use App\Core\Auth; use App\Core\Csrf; ?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= APP_NAME ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
  <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-success mb-4">
  <div class="container"><a class="navbar-brand" href="?r=citizen/home">CATA TRECO</a>
    <div class="ms-auto text-white small">
      <?php if (Auth::check()): ?><?= htmlspecialchars(Auth::user()['name']) ?> | <a class="text-white" href="?r=auth/logout">Sair</a><?php endif; ?>
    </div>
  </div>
</nav>
<div class="container pb-5">
