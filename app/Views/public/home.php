<section class="hero mb-4">
  <h1 class="display-6 fw-bold">Portal Institucional da Educação</h1>
  <p class="mb-0">Gestão educacional, transparência e serviços ao cidadão em uma experiência moderna e acessível.</p>
</section>
<div class="row g-3 mb-4">
  <div class="col-md-3"><a class="card card-modern p-3 text-decoration-none" href="index.php?r=mapa-publico">Escolas/Creches no mapa</a></div>
  <div class="col-md-3"><a class="card card-modern p-3 text-decoration-none" href="index.php?r=transparencia">Transparência</a></div>
  <div class="col-md-3"><a class="card card-modern p-3 text-decoration-none" href="index.php?r=primeira-infancia">Primeira Infância</a></div>
  <div class="col-md-3"><a class="card card-modern p-3 text-decoration-none" href="index.php?r=contato">Fale Conosco</a></div>
</div>
<h2 class="h4 mb-3">Notícias recentes</h2>
<div class="row g-3">
<?php foreach ($news as $item): ?>
  <div class="col-md-4">
    <article class="card card-modern h-100 p-3">
      <h3 class="h6"><?= htmlspecialchars($item['title']) ?></h3>
      <p class="small text-muted mb-1"><?= htmlspecialchars((string) ($item['published_at'] ?? '')) ?></p>
      <p class="mb-0"><?= htmlspecialchars($item['summary']) ?></p>
    </article>
  </div>
<?php endforeach; ?>
</div>
