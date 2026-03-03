<h1>Portal Institucional da Educação</h1>
<p>Atalhos: Matrícula, Transparência, Mapa de Escolas/Creches e Primeira Infância.</p>
<ul>
    <li><a href="index.php?r=mapa-publico">Mapa das Escolas e Creches</a></li>
    <li><a href="index.php?r=primeira-infancia">Primeira Infância</a></li>
</ul>
<h2>Notícias recentes</h2>
<?php foreach ($news as $item): ?>
<div class="border p-2 mb-2">
    <strong><?= htmlspecialchars($item['title']) ?></strong>
    <div><?= htmlspecialchars($item['summary']) ?></div>
</div>
<?php endforeach; ?>
