<?php require __DIR__ . '/partials/header.php'; ?>
<section class="cards">
  <article><h3>Candidatas Ativas</h3><p><?= (int) ($stats['total_candidatas'] ?? 0) ?></p></article>
  <article><h3>Votos Válidos</h3><p><?= (int) ($stats['total_votos_validos'] ?? 0) ?></p></article>
  <article><h3>Alertas de Fraude (7d)</h3><p><?= (int) ($stats['alertas_fraude_7d'] ?? 0) ?></p></article>
</section>

<section class="panel">
  <h2>Evolução de votos</h2>
  <canvas id="votesChart" height="120"></canvas>
</section>

<section class="panel">
  <h2>Top 5 candidatas</h2>
  <table>
    <thead><tr><th>#</th><th>Nome</th><th>Votos</th></tr></thead>
    <tbody>
    <?php foreach ($ranking as $idx => $item): ?>
      <tr><td><?= $idx + 1 ?></td><td><?= htmlspecialchars($item['nome']) ?></td><td><?= (int) $item['votos_total'] ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>
<script>
window.__votesPerDay = <?= json_encode($votesPerDay, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<?php require __DIR__ . '/partials/footer.php'; ?>
