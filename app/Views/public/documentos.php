<h1 class="h3 mb-3">Documentos, Portarias e Editais</h1>
<div class="table-responsive card card-modern p-2">
<table class="table table-hover mb-0">
  <thead><tr><th>Ano</th><th>Título</th><th>Categoria</th></tr></thead>
  <tbody>
  <?php foreach ($documents as $item): ?>
    <tr>
      <td><?= htmlspecialchars((string) $item['year_ref']) ?></td>
      <td><?= htmlspecialchars($item['title']) ?></td>
      <td><?= htmlspecialchars($item['category']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
