<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 mb-0">Dashboard Administrativo</h1>
        <small>Olá, <?= htmlspecialchars($user['name'] ?? '') ?>.</small>
    </div>
    <form method="post" action="<?= htmlspecialchars($baseUrl) ?>/public/admin/logout"><?= $csrfField ?><button class="btn btn-outline-secondary">Sair</button></form>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body">Pendentes <h3><?= (int) $counts['PENDENTE'] ?></h3></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body">Aprovadas <h3><?= (int) $counts['APROVADA'] ?></h3></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body">Em andamento <h3><?= (int) $counts['EM_ANDAMENTO'] ?></h3></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body">Finalizadas <h3><?= (int) $counts['FINALIZADA'] ?></h3></div></div></div>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-md-3"><input class="form-control" type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from']) ?>"></div>
    <div class="col-md-3"><input class="form-control" type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to']) ?>"></div>
    <div class="col-md-3"><input class="form-control" name="locality" placeholder="Localidade" value="<?= htmlspecialchars($filters['locality']) ?>"></div>
    <div class="col-md-2"><select class="form-select" name="status"><option value="">Status</option><?php foreach (['PENDENTE','APROVADA','EM_ANDAMENTO','FINALIZADA','RECUSADA'] as $s): ?><option value="<?= $s ?>" <?= $filters['status']===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?></select></div>
    <div class="col-md-1"><button class="btn btn-success w-100">Filtrar</button></div>
</form>

<div class="card mb-3"><div class="table-responsive"><table class="table table-sm align-middle mb-0">
<thead><tr><th>Cidadão</th><th>Data/Hora</th><th>Status</th><th>Funcionário</th><th>Ações</th></tr></thead><tbody>
<?php foreach ($requests as $r): ?>
<tr>
<td><strong><?= htmlspecialchars($r['citizen_name']) ?></strong><br><small><?= htmlspecialchars($r['address']) ?></small></td>
<td><?= htmlspecialchars((string)$r['scheduled_at']) ?></td>
<td><span class="badge text-bg-secondary"><?= htmlspecialchars($r['status']) ?></span></td>
<td><?= htmlspecialchars($r['employee_name'] ?? '-') ?></td>
<td>
<form method="post" action="<?= htmlspecialchars($baseUrl) ?>/public/admin/solicitacao/update" class="row g-1">
<?= $csrfField ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
<div class="col-12"><select class="form-select form-select-sm" name="status"><?php foreach (['APROVADA','RECUSADA','EM_ANDAMENTO','FINALIZADA'] as $s): ?><option value="<?= $s ?>" <?= $r['status']===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?></select></div>
<div class="col-12"><input type="datetime-local" class="form-control form-control-sm" name="scheduled_at" value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime((string)$r['scheduled_at']))) ?>"></div>
<div class="col-12"><select class="form-select form-select-sm" name="assigned_user_id"><option value="">Sem atribuição</option><?php foreach($employees as $e): ?><option value="<?= (int)$e['id'] ?>" <?= (int)$r['assigned_user_id']===(int)$e['id']?'selected':'' ?>><?= htmlspecialchars($e['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-12"><input class="form-control form-control-sm" name="admin_notes" placeholder="Observação"></div>
<div class="col-12"><button class="btn btn-primary btn-sm w-100">Salvar</button></div>
</form>
</td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>

<h2 class="h6">Logs de auditoria</h2>
<div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Data</th><th>Usuário</th><th>Ação</th><th>Meta</th></tr></thead><tbody><?php foreach($logs as $l): ?><tr><td><?= htmlspecialchars($l['created_at']) ?></td><td><?= htmlspecialchars($l['user_name'] ?? 'sistema') ?></td><td><?= htmlspecialchars($l['action']) ?></td><td><small><?= htmlspecialchars($l['metadata_json']) ?></small></td></tr><?php endforeach; ?></tbody></table></div></div>
