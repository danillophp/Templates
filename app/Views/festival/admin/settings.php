<?php require __DIR__ . '/partials/header.php'; ?>
<section class="panel">
  <h2>Configurações gerais</h2>
  <form method="post" action="?r=api/festival-admin/settings/save" id="settingsForm" class="grid-form">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(App\Core\Csrf::token()) ?>">
    <label>Votação aberta (1/0)
      <input type="text" name="configs[votacao_aberta]" value="<?= htmlspecialchars($config['votacao_aberta'] ?? '1') ?>">
    </label>
    <label>Máx votos IP 24h
      <input type="number" name="configs[max_votos_ip_24h]" value="<?= htmlspecialchars($config['max_votos_ip_24h'] ?? '3') ?>">
    </label>
    <label>Máx votos dispositivo 24h
      <input type="number" name="configs[max_votos_dispositivo_24h]" value="<?= htmlspecialchars($config['max_votos_dispositivo_24h'] ?? '2') ?>">
    </label>
    <button type="submit">Salvar configurações</button>
  </form>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
