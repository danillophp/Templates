<div class="row g-3">
  <div class="col-xl-3">
    <aside class="admin-sidebar glass-card p-3 h-100">
      <h6 class="mb-3 text-uppercase">Painel Admin</h6>
      <a class="btn btn-outline-success w-100 mb-2" href="<?= APP_BASE_PATH ?>/?r=admin/reports/csv">Exportar CSV</a>
      <button class="btn btn-outline-secondary w-100 mb-2" disabled>Exportar PDF</button>
      <button class="btn btn-outline-secondary w-100 mb-2" disabled>Exportar XLSX</button>
      <a class="btn btn-outline-primary w-100" href="<?= APP_BASE_PATH ?>/?r=auth/logout">Sair</a>

      <?php if (!empty($subscription)): ?>
        <hr>
        <small class="d-block text-muted">Plano: <?= htmlspecialchars($subscription['plano_nome']) ?></small>
        <small class="d-block text-muted">Limite mensal: <?= (int)$subscription['limite_solicitacoes_mes'] ?></small>
      <?php endif; ?>
    </aside>
  </div>

  <div class="col-xl-9">
    <div class="card shadow-sm glass-card border-0 mb-3">
      <div class="card-body">
        <h4 class="mb-3">Dashboard Administrativo</h4>
        <div class="row g-3">
          <?php foreach ($summary as $k => $v): ?>
            <div class="col-6 col-lg-3">
              <div class="metric-box p-3 rounded-3">
                <small class="text-muted"><?= $k ?></small>
                <h3 class="mb-0"><?= $v ?></h3>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="card shadow-sm glass-card border-0 mb-3"><div class="card-body"><canvas id="chartRequests" height="100"></canvas></div></div>

    <div class="card shadow-sm glass-card border-0 mb-3">
      <div class="card-body">
        <h6>Cadastrar ponto de coleta</h6>
        <div class="row g-2">
          <div class="col-md-4"><input id="pTitle" class="form-control" placeholder="Título do ponto"></div>
          <div class="col-md-3"><input id="pLat" class="form-control" placeholder="Latitude"></div>
          <div class="col-md-3"><input id="pLng" class="form-control" placeholder="Longitude"></div>
          <div class="col-md-2"><button id="btnPoint" class="btn btn-success w-100">Salvar ponto</button></div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm glass-card border-0 mb-3"><div class="card-body">
      <div class="row g-2">
        <div class="col-md-4"><input id="fDate" type="date" class="form-control"></div>
        <div class="col-md-4"><select id="fStatus" class="form-select"><option value="">Status</option><option>PENDENTE</option><option>APROVADO</option><option>RECUSADO</option><option>ALTERADO</option><option>FINALIZADO</option></select></div>
        <div class="col-md-4"><button id="btnFilter" class="btn btn-success w-100">Filtrar</button></div>
      </div>
    </div></div>

    <div class="card shadow-sm glass-card border-0"><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>#</th><th>Protocolo</th><th>Nome</th><th>Endereço</th><th>Data</th><th>Status</th><th>Ações</th></tr></thead><tbody id="reqRows"></tbody></table></div></div>
  </div>
</div>

<script>window.EMPLOYEES = <?= json_encode($employees) ?>; window.CSRF = <?= json_encode($csrf) ?>;</script>
<script src="<?= APP_BASE_PATH ?>/assets/js/admin-dashboard.js"></script>
