<div class="row g-3 admin-compact">
  <div class="col-xl-3">
    <aside class="admin-sidebar glass-card p-3 h-100 sticky-top" style="top: 1rem;">
      <h6 class="mb-2 text-uppercase">Dashboard Administrativo</h6>
      <button class="btn btn-outline-success w-100 mb-2" data-bs-toggle="modal" data-bs-target="#waConnectModal">Conectar WhatsApp do Cata Treco</button>
      <small class="d-block mb-2 text-muted">Status: <?= $whatsAppReady ? 'Cloud API configurada' : 'Não configurado (painel segue normal)' ?></small>
      <a class="btn btn-outline-success w-100 mb-2" href="<?= APP_BASE_PATH ?>/?r=admin/reports/csv&date=<?= urlencode($today) ?>">Exportar CSV</a>
      <button id="btnExportPdf" class="btn btn-outline-secondary w-100 mb-2">Exportar PDF</button>
      <a class="btn btn-outline-info w-100 mb-2" href="<?= APP_BASE_PATH ?>/?r=admin/reports/comm-csv">Exportar Comunicação CSV</a>

      <hr>
      <h6 class="mb-2">Agenda do mês</h6>
      <div id="calendarWidget" class="calendar-widget"></div>

      <hr>
      <h6 class="mb-2">Cadastrar Ponto de Coleta</h6>
      <div class="mb-2"><input id="pTitle" class="form-control form-control-sm" placeholder="Título do ponto"></div>
      <div class="row g-2 mb-2">
        <div class="col-6"><input id="pLat" class="form-control form-control-sm" placeholder="Latitude"></div>
        <div class="col-6"><input id="pLng" class="form-control form-control-sm" placeholder="Longitude"></div>
      </div>
      <button id="btnPoint" class="btn btn-success btn-sm w-100 mb-3">Salvar ponto</button>
      <a class="btn btn-outline-primary w-100" href="<?= APP_BASE_PATH ?>/?r=auth/logout">Sair</a>
    </aside>
  </div>

  <div class="col-xl-9">
    <div class="card shadow-sm glass-card border-0 mb-2">
      <div class="card-body py-3">
        <div class="row g-2">
          <?php foreach ($summary as $k => $v): ?>
            <div class="col-6 col-lg-2">
              <div class="metric-box p-2 rounded-3">
                <small class="text-muted d-block"><?= htmlspecialchars((string)$k) ?></small>
                <h5 class="mb-0"><?= (int)$v ?></h5>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="card shadow-sm glass-card border-0 mb-2"><div class="card-body py-2"><canvas id="chartRequests" height="80"></canvas></div></div>

    <div class="card shadow-sm glass-card border-0 mb-2">
      <div class="card-body py-2">
        <h6 class="mb-2">Relatório de Comunicação</h6>
        <div class="row g-2 mb-2">
          <div class="col-md-2"><small class="text-muted d-block">Enviadas</small><strong id="commSent"><?= (int)($commReport['enviadas'] ?? 0) ?></strong></div>
          <div class="col-md-2"><small class="text-muted d-block">Erros</small><strong id="commErr"><?= (int)($commReport['erros'] ?? 0) ?></strong></div>
          <div class="col-md-3"><small class="text-muted d-block">Taxa de entrega</small><strong id="commRate"><?= (float)($commReport['taxa_entrega'] ?? 0) ?>%</strong></div>
          <div class="col-md-3"><small class="text-muted d-block">Tempo médio</small><strong id="commAvg"><?= (float)($commReport['tempo_medio'] ?? 0) ?>s</strong></div>
        </div>
        <div id="commFails" class="small"></div>
      </div>
    </div>

    <div class="card shadow-sm glass-card border-0 mb-2"><div class="card-body py-2">
      <div class="row g-2 align-items-end">
        <div class="col-md-4"><label class="form-label small mb-1">Data</label><input id="fDate" type="date" class="form-control form-control-sm" value="<?= htmlspecialchars($today) ?>"></div>
        <div class="col-md-4"><label class="form-label small mb-1">Status</label><select id="fStatus" class="form-select form-select-sm"><option value="">Todos</option><option>PENDENTE</option><option>APROVADO</option><option>RECUSADO</option><option>ALTERADO</option><option>FINALIZADO</option></select></div>
        <div class="col-md-4"><button id="btnFilter" class="btn btn-success btn-sm w-100">Filtrar agendamentos</button></div>
      </div>
    </div></div>

    <div class="d-flex justify-content-between align-items-center mb-2 px-1">
      <label class="form-check-label small"><input type="checkbox" id="selectAllRows" class="form-check-input me-1">Selecionar todos</label>
      <small class="text-muted">Ações em lote disponíveis.</small>
    </div>

    <div id="reqRows" class="d-grid gap-2"></div>
  </div>
</div>

<div class="modal fade" id="waConnectModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Conectar WhatsApp do Cata Treco</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <p class="small">Hospedagem compartilhada não permite automação persistente de WhatsApp Web. Use WhatsApp Cloud API para envio automático.</p>
        <div class="mb-2"><label class="form-label">Token Cloud API</label><input id="waToken" type="password" class="form-control" autocomplete="off"></div>
        <div class="mb-2"><label class="form-label">Phone Number ID</label><input id="waPhoneId" type="text" class="form-control" value="<?= htmlspecialchars($waPhoneId ?? '') ?>"></div>
        <small class="text-muted">Se vazio, o sistema tentará fallback manual por wa.me sem bloquear o painel.</small>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
        <button id="btnSaveWaConfig" type="button" class="btn btn-success">Salvar configuração</button>
      </div>
    </div>
  </div>
</div>


<script>
  window.CSRF = <?= json_encode($csrf) ?>;
  window.COMM_REPORT = <?= json_encode($commReport ?? []) ?>;
  window.ADMIN_NOTIFICATION_POLL_URL = <?= json_encode(APP_BASE_PATH . '/app/api/poll_novos_agendamentos.php') ?>;
  window.ADMIN_CALENDAR_SUMMARY_URL = <?= json_encode(APP_BASE_PATH . '/app/api/agenda_resumo_mes.php') ?>;
  window.ADMIN_SAVE_WA_CONFIG_URL = <?= json_encode(APP_BASE_PATH . '/?r=api/admin/whatsapp/config') ?>;
</script>
<script src="<?= APP_BASE_PATH ?>/assets/js/admin-dashboard.js"></script>
