<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php?r=home">Educa SADE</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="index.php?r=secretaria">A Secretaria</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php?r=noticias">Notícias</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php?r=documentos">Documentos</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php?r=transparencia">Transparência</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php?r=primeira-infancia">Primeira Infância</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php?r=mapa-publico">Mapa</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php?r=contato">Contato</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="d-flex justify-content-end gap-2 mb-3">
    <button id="toggleContrast" class="btn btn-outline-dark btn-sm">Contraste</button>
    <button id="toggleFont" class="btn btn-outline-dark btn-sm">Fonte +</button>
  </div>
  <?php require $viewPath; ?>
</div>

<footer class="footer">
  <div class="container d-flex flex-wrap justify-content-between">
    <span>Secretaria Municipal de Educação - Santo Antônio do Descoberto/GO</span>
    <a class="text-white" href="index.php?r=auth/login">Área restrita</a>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/accessibility.js"></script>
</body>
</html>
