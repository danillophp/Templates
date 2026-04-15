<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0a1433">
    <title><?= htmlspecialchars(FestivalConfig::APP_NAME) ?></title>
    <link rel="manifest" href="public/manifest.json">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/assets/css/festival.css">
</head>
<body data-vote-start="<?= (int) ($_SESSION['festival_vote_form_started_at'] ?? time()) ?>">
<header class="festival-header">
    <div class="container header-grid">
        <a href="?r=festival/home" class="logo-wrap">
            <img src="public/assets/img/logo-festival.png" alt="Festival 14 de Maio">
        </a>
        <nav id="menu" class="header-nav" aria-label="Menu principal">
            <a href="#inicio">Início</a>
            <a href="#candidatas">Candidatas</a>
            <a href="#ranking">Ranking</a>
            <a href="#atracoes">Atrações</a>
            <a href="#regulamento">Regulamento</a>
            <a href="?r=festival-admin/login" class="pill-link">Painel</a>
        </nav>
        <button class="menu-toggle" id="menuToggle" type="button" aria-label="Abrir menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>
