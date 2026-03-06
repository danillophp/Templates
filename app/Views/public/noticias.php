<h1 class="h3 mb-3">Notícias e Eventos</h1>
<div class="row g-3">
<?php foreach ($news as $item): ?>
  <div class="col-md-6">
    <article class="card card-modern p-3">
      <h2 class="h5"><?= htmlspecialchars($item['title']) ?></h2>
      <p class="text-muted small"><?= htmlspecialchars((string) $item['published_at']) ?></p>
      <p class="mb-0"><?= htmlspecialchars($item['summary']) ?></p>
    </article>
  </div>
<?php endforeach; ?>
</div>
