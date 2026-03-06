<h1 class="h4 mb-3">Auditoria</h1>
<form class="row g-2 mb-3" method="get" action="index.php">
  <input type="hidden" name="r" value="admin/auditoria">
  <div class="col-md-4"><input name="entity" class="form-control" placeholder="Entidade" value="<?= \App\Core\Security::e($filters['entity'] ?? '') ?>"></div>
  <div class="col-md-4"><input name="action" class="form-control" placeholder="Ação" value="<?= \App\Core\Security::e($filters['action'] ?? '') ?>"></div>
  <div class="col-md-4 d-flex gap-2">
    <button class="btn btn-primary">Filtrar</button>
    <a class="btn btn-outline-secondary" href="index.php?r=admin/auditoria/exportar&amp;entity=<?= urlencode($filters['entity'] ?? '') ?>&amp;action=<?= urlencode($filters['action'] ?? '') ?>">Exportar CSV</a>
  </div>
</form>
<div class="table-responsive">
<table class="table table-striped table-sm">
  <thead><tr><th>ID</th><th>Data</th><th>Usuário</th><th>Ação</th><th>Entidade</th><th>IP</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $row): ?>
    <tr>
      <td><?= (int) $row['id'] ?></td>
      <td><?= \App\Core\Security::e($row['created_at']) ?></td>
      <td><?= \App\Core\Security::e($row['username'] ?? 'sistema') ?></td>
      <td><?= \App\Core\Security::e($row['action']) ?></td>
      <td><?= \App\Core\Security::e($row['entity']) ?> #<?= (int) $row['entity_id'] ?></td>
      <td><?= \App\Core\Security::e($row['ip']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
