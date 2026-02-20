<div class="d-flex justify-content-between align-items-center mb-3">
  <h4>Super Admin SaaS</h4>
  <a class="btn btn-outline-secondary" href="?r=auth/logout">Sair</a>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="card"><div class="card-body"><small>Prefeituras ativas</small><h3><?= (int)$metrics['tenants_ativos'] ?></h3></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><small>Total solicitações</small><h3><?= (int)$metrics['total_solicitacoes'] ?></h3></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><small>Receita estimada</small><h3>R$ <?= number_format((float)$metrics['receita_estimada'], 2, ',', '.') ?></h3></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><small>Inadimplentes</small><h3><?= (int)$metrics['inadimplentes'] ?></h3></div></div></div>
</div>

<div class="card shadow-sm mb-3">
  <div class="card-body">
    <h6>Nova prefeitura (tenant)</h6>
    <div class="row g-2">
      <input type="hidden" id="csrf" value="<?= htmlspecialchars($csrf) ?>">
      <div class="col-md-4"><input id="tNome" class="form-control" placeholder="Nome da prefeitura"></div>
      <div class="col-md-3"><input id="tSlug" class="form-control" placeholder="slug (prefeitura1)"></div>
      <div class="col-md-3"><input id="tDominio" class="form-control" placeholder="prefeitura1.catatreco.com"></div>
      <div class="col-md-2"><button id="btnTenant" class="btn btn-success w-100">Criar</button></div>
    </div>
  </div>
</div>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table mb-0">
      <thead><tr><th>ID</th><th>Nome</th><th>Slug</th><th>Domínio</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($tenants as $t): ?>
        <tr><td><?= (int)$t['id'] ?></td><td><?= htmlspecialchars($t['nome']) ?></td><td><?= htmlspecialchars($t['slug']) ?></td><td><?= htmlspecialchars($t['dominio']) ?></td><td><?= (int)$t['ativo'] === 1 ? 'Ativo' : 'Inativo' ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.getElementById('btnTenant').addEventListener('click', async () => {
  const fd = new FormData();
  fd.append('_csrf', document.getElementById('csrf').value);
  fd.append('nome', document.getElementById('tNome').value);
  fd.append('slug', document.getElementById('tSlug').value);
  fd.append('dominio', document.getElementById('tDominio').value);
  const res = await fetch('?r=api/superadmin/tenant/create', { method: 'POST', body: fd });
  const json = await res.json();
  showToast(json.message, json.ok ? 'success' : 'danger');
  if (json.ok) setTimeout(() => location.reload(), 600);
});
</script>
