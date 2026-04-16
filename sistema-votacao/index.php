<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

if (maintenance_active() && empty($_SESSION['admin_logged'])) {
    header('Location: ' . app_path('manutencao.php'));
    exit;
}

save_access_log('index.php');

$candidatos = db()->query('SELECT id, nome, foto, numero FROM candidatos WHERE ativo = 1 ORDER BY ordem ASC, id ASC')->fetchAll();
$resultados = load_json_file(RESULTADOS_JSON, [
    'total_geral' => 0,
    'atualizado_em' => null,
    'ranking' => []
]);
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(APP_NAME) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="<?= e(app_path('assets/css/custom.css')) ?>">
</head>
<body class="bg-gray-50 text-slate-800">
<div class="max-w-5xl mx-auto p-4 sm:p-6">
  <h1 class="text-2xl font-bold text-center mb-2"><?= e(get_config('nome_evento', APP_NAME)) ?></h1>
  <p class="text-center text-sm text-slate-500 mb-6">Votação segura e oficial.</p>

  <div class="card p-4 mb-4">
    <h2 class="font-semibold mb-3">Painel público (cache)</h2>
    <div class="text-sm">Total geral de votos: <strong id="public-total"><?= (int)$resultados['total_geral'] ?></strong></div>
    <div class="text-xs text-slate-500">Última atualização: <span id="public-updated"><?= e((string)($resultados['atualizado_em'] ?? '-')) ?></span></div>
  </div>

  <div id="etapa-token" class="card p-4 step-active">
    <h3 class="text-lg font-semibold mb-3">1) Informe seu token</h3>
    <input id="token" maxlength="5" class="w-full border rounded-xl p-3 uppercase" placeholder="Ex.: AB2D9">
    <button id="btn-token" class="btn-primary mt-3">Continuar</button>
  </div>

  <div id="etapa-cadastro" class="card p-4 hidden">
    <h3 class="text-lg font-semibold mb-3">2) Cadastro</h3>
    <input id="nome" class="w-full border rounded-xl p-3 mb-2" placeholder="Nome completo">
    <input id="whatsapp" class="w-full border rounded-xl p-3" placeholder="WhatsApp com DDD">
    <button id="btn-cadastro" class="btn-primary mt-3">Avançar</button>
  </div>

  <div id="etapa-voto" class="card p-4 hidden">
    <h3 class="text-lg font-semibold mb-3">3) Escolha a candidata/candidato</h3>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3" id="grid-candidatos">
      <?php foreach ($candidatos as $c): ?>
      <button type="button" class="candidate-card" data-id="<?= (int)$c['id'] ?>" data-nome="<?= e($c['nome']) ?>">
        <img src="<?= e(app_path(ltrim($c['foto'], '/'))) ?>" alt="<?= e($c['nome']) ?>" onerror="this.src='https://via.placeholder.com/120'">
        <div class="font-medium text-sm mt-2"><?= e($c['nome']) ?></div>
        <div class="text-xs text-slate-500">Nº <?= (int)$c['numero'] ?></div>
      </button>
      <?php endforeach; ?>
    </div>
  </div>

  <div id="etapa-sucesso" class="card p-4 hidden text-center">
    <h3 class="text-xl font-bold text-emerald-700">Seu voto foi computado com sucesso.</h3>
    <p class="text-sm text-slate-600 mt-2">Obrigado por participar.</p>
  </div>
</div>

<script>
window.APP = {
  basePath: <?= json_encode(APP_BASE_PATH) ?>,
  csrf: <?= json_encode(csrf_token()) ?>,
  voted: <?= json_encode(!empty($_SESSION['voto_finalizado'])) ?>
};
</script>
<script src="<?= e(app_path('assets/js/app.js')) ?>"></script>
</body>
</html>
