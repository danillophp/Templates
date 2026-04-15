<?php require __DIR__ . '/partials/header.php'; ?>
<section class="panel">
  <h2>Monitor antifraude</h2>
  <table>
    <thead><tr><th>ID</th><th>Motivo</th><th>IP Hash</th><th>UA Hash</th><th>Data</th></tr></thead>
    <tbody>
      <?php foreach ($attempts as $attempt): ?>
        <tr>
          <td><?= (int) $attempt['id'] ?></td>
          <td><?= htmlspecialchars($attempt['motivo']) ?></td>
          <td><?= htmlspecialchars($attempt['ip_hash']) ?></td>
          <td><?= htmlspecialchars($attempt['user_agent_hash']) ?></td>
          <td><?= htmlspecialchars($attempt['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
