<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_admin();
save_access_log('admin.php');

require_once __DIR__ . '/includes/admin_header.php';
?>
<div class="mb-4 flex flex-wrap gap-2 items-center justify-between">
  <h1 class="text-2xl font-bold">Dashboard</h1>
  <div class="flex gap-2">
    <button id="toggle-manutencao" class="px-3 py-2 bg-amber-500 text-white rounded-xl">Alternar manutenção</button>
    <a href="<?= e(app_path('ajax/admin_exportar_csv.php?tipo=votos')) ?>" class="px-3 py-2 bg-emerald-600 text-white rounded-xl">Exportar votos CSV</a>
  </div>
</div>
<div id="aviso-manutencao" class="hidden p-3 rounded-xl bg-amber-100 border border-amber-300 text-amber-800 mb-3">Modo manutenção ativo.</div>

<div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4" id="cards"></div>

<div class="grid md:grid-cols-2 gap-4">
  <section class="card p-4">
    <h2 class="font-semibold mb-3">Ranking</h2>
    <canvas id="grafico"></canvas>
    <table class="w-full text-sm mt-4" id="ranking"></table>
  </section>
  <section class="card p-4">
    <h2 class="font-semibold mb-3">Suspeitas e acessos</h2>
    <div id="suspeitas" class="text-sm space-y-2"></div>
    <h3 class="font-medium mt-4">Últimos acessos</h3>
    <div id="acessos" class="text-xs mt-2 space-y-1"></div>
  </section>
</div>
<script>
window.ADMIN_APP = {
  csrf: <?= json_encode(csrf_token()) ?>,
  basePath: <?= json_encode(APP_BASE_PATH) ?>
};
</script>
<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
