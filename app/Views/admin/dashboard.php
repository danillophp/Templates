<div class="row g-3">
  <div class="col-lg-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <h6 class="mb-3">Menu Admin</h6>
        <a class="btn btn-outline-success w-100 mb-2" href="?r=admin/reports/csv">Exportar CSV</a>
        <button class="btn btn-outline-secondary w-100 mb-2" disabled>Exportar PDF (roadmap)</button>
        <button class="btn btn-outline-secondary w-100" disabled>Exportar XLSX (roadmap)</button>
        <?php if (!empty($subscription)): ?>
          <hr>
          <small class="text-muted d-block">Plano: <?= htmlspecialchars($subscription['plano_nome']) ?></small>
          <small class="text-muted d-block">Limite mensal: <?= (int)$subscription['limite_solicitacoes_mes'] ?></small>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-9">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4>Painel Administrativo</h4>
      <a class="btn btn-outline-secondary" href="?r=auth/logout">Sair</a>
    </div>

    <div class="row g-3 mb-3">
      <?php foreach ($summary as $k => $v): ?>
        <div class="col-6 col-lg-3"><div class="card shadow-sm"><div class="card-body"><small><?= $k ?></small><h3><?= $v ?></h3></div></div></div>
      <?php endforeach; ?>
    </div>

    <div class="card shadow-sm mb-3"><div class="card-body"><canvas id="chartRequests" height="100"></canvas></div></div>

    <div class="card shadow-sm mb-3">
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

    <div class="card shadow-sm mb-3"><div class="card-body">
      <div class="row g-2">
        <div class="col-md-4"><input id="fDate" type="date" class="form-control"></div>
        <div class="col-md-4"><select id="fStatus" class="form-select"><option value="">Status</option><option>PENDENTE</option><option>APROVADO</option><option>RECUSADO</option><option>FINALIZADO</option></select></div>
        <div class="col-md-4"><button id="btnFilter" class="btn btn-success w-100">Filtrar</button></div>
      </div>
    </div></div>

    <div class="card shadow-sm"><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>#</th><th>Protocolo</th><th>Nome</th><th>Endereço</th><th>Data</th><th>Status</th><th>Ações</th></tr></thead><tbody id="reqRows"></tbody></table></div></div>
  </div>
</div>

<script>window.EMPLOYEES = <?= json_encode($employees) ?>; window.CSRF = <?= json_encode($csrf) ?>;</script>
<script src="../assets/js/admin-dashboard.js"></script>
