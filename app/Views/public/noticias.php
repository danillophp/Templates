<h1>Notícias e Eventos</h1>
<?php foreach ($news as $item): ?><p><strong><?= htmlspecialchars($item['title']) ?></strong> - <?= htmlspecialchars($item['published_at']) ?></p><?php endforeach; ?>
