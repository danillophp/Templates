<?php require __DIR__ . '/partials/header.php'; ?>
<section class="panel">
  <h2>Gestão de votos</h2>
  <div class="table-scroll">
    <table>
      <thead><tr><th>ID</th><th>Candidata</th><th>Origem</th><th>Status</th><th>Data</th></tr></thead>
      <tbody>
      <?php foreach ($votes as $vote): ?>
        <tr>
          <td><?= (int) $vote['id'] ?></td>
          <td>#<?= (int) $vote['candidata_numero'] ?> - <?= htmlspecialchars($vote['candidata_nome']) ?></td>
          <td><?= htmlspecialchars($vote['origem']) ?></td>
          <td><?= htmlspecialchars($vote['status']) ?></td>
          <td><?= htmlspecialchars($vote['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
