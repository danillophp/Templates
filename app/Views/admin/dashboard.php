<div class="d-flex justify-content-between align-items-center mb-3">
  <h4>Painel Administrativo</h4>
  <a class="btn btn-outline-secondary" href="?r=auth/logout">Sair</a>
</div>
<div class="row g-3 mb-3">
  <?php foreach ($summary as $k => $v): ?>
    <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm"><div class="card-body"><small><?= $k ?></small><h3><?= $v ?></h3></div></div></div>
  <?php endforeach; ?>
</div>
<div class="card shadow-sm mb-3"><div class="card-body">
  <div class="row g-2">
    <div class="col-md-3"><input id="fDate" type="date" class="form-control"></div>
    <div class="col-md-3"><select id="fStatus" class="form-select"><option value="">Status</option><option>PENDENTE</option><option>APROVADO</option><option>RECUSADO</option><option>EM_ANDAMENTO</option><option>FINALIZADO</option></select></div>
    <div class="col-md-3"><input id="fDistrict" class="form-control" placeholder="Bairro"></div>
    <div class="col-md-3"><button id="btnFilter" class="btn btn-success w-100">Filtrar</button></div>
  </div>
</div></div>
<div class="card shadow-sm"><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>#</th><th>Cidadão</th><th>Data</th><th>Status</th><th>Bairro</th><th>Ações</th></tr></thead><tbody id="reqRows"></tbody></table></div></div>
<script>window.EMPLOYEES = <?= json_encode($employees) ?>; window.CSRF = <?= json_encode($csrf) ?>;</script>
<script src="../assets/js/admin-dashboard.js"></script>
