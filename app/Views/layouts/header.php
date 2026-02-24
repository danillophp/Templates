<?php

use App\Core\Auth;
use App\Services\BrandingService;
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars(($_config['nome_prefeitura'] ?? APP_NAME) . ' - Cata Treco') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
  <link href="<?= APP_BASE_PATH ?>/assets/css/style.css" rel="stylesheet">
  <style>:root { --tenant-primary: <?= htmlspecialchars($_config['cor_primaria'] ?? '#198754') ?>; }</style>
</head>
<body>
<?php
$logoCataPath = __DIR__ . '/../../../resources/assets/img/logo-cata-treco.png';
$logoPrefPath = __DIR__ . '/../../../resources/assets/img/logo-prefeitura.png';
$logoCataUrl = BrandingService::logoUrl($_config ?? []) ?: APP_BASE_PATH . '/resources/assets/img/logo-cata-treco.png';
$logoPrefUrl = APP_BASE_PATH . '/resources/assets/img/logo-prefeitura.png';
?>
<header class="institutional-header">
  <div class="container-fluid px-4 py-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div class="inst-logo">
      <?php if (is_file($logoCataPath) || !empty(BrandingService::logoUrl($_config ?? []))): ?>
        <img src="<?= htmlspecialchars($logoCataUrl) ?>" alt="Logo Cata Treco">
      <?php else: ?>
        <div class="logo-placeholder">Cata Treco</div>
      <?php endif; ?>
    </div>
    <div class="inst-title">Cata Treco — Prefeitura de Santo Antônio do Descoberto – GO</div>
    <div class="inst-logo">
      <?php if (is_file($logoPrefPath)): ?>
        <img src="<?= htmlspecialchars($logoPrefUrl) ?>" alt="Logo Prefeitura de Santo Antônio do Descoberto">
      <?php else: ?>
        <div class="logo-placeholder">Prefeitura SAD-GO</div>
      <?php endif; ?>
    </div>
  </div>
</header>
<nav class="navbar navbar-expand-lg navbar-dark" style="background: var(--tenant-primary)">
  <div class="container-fluid px-4">
    <a class="navbar-brand" href="<?= APP_BASE_PATH ?>/?r=citizen/home"><?= htmlspecialchars($_config['nome_prefeitura'] ?? APP_NAME) ?> • Cata Treco</a>
    <div class="ms-auto text-white small">
      <?php if (Auth::check()): ?>
        <?= htmlspecialchars(Auth::user()['nome']) ?> (<?= htmlspecialchars(Auth::user()['tipo']) ?>) |
        <a class="text-white" href="<?= APP_BASE_PATH ?>/?r=auth/logout">Sair</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<script>window.APP_BASE_PATH = <?= json_encode(APP_BASE_PATH) ?>;</script>
<div class="container-fluid px-4 py-4">
