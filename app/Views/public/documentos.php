<h1>Documentos / Portarias / Editais</h1>
<?php foreach ($documents as $item): ?><p><?= htmlspecialchars($item['year_ref']) ?> - <?= htmlspecialchars($item['title']) ?></p><?php endforeach; ?>
