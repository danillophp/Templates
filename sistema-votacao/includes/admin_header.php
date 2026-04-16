<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e(APP_NAME) ?> - Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="<?= e(app_path('assets/css/custom.css')) ?>">
</head>
<body class="bg-slate-100 min-h-screen">
<nav class="bg-slate-900 text-white p-4">
  <div class="max-w-7xl mx-auto flex justify-between">
    <div class="font-semibold"><?= e(APP_NAME) ?> - Painel</div>
    <div class="space-x-3 text-sm">
      <a href="<?= e(app_path('index.php')) ?>" class="hover:underline">Público</a>
      <a href="<?= e(app_path('logout.php')) ?>" class="hover:underline">Sair</a>
    </div>
  </div>
</nav>
<main class="max-w-7xl mx-auto p-4">
