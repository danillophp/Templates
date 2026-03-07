<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Clientes</h1>
    <a href="<?= e(base_url('/clientes/criar')) ?>" class="btn btn-primary btn-sm">Novo cliente</a>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="get" action="<?= e(base_url('/clientes')) ?>" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label for="telefone" class="form-label">Buscar por telefone/WhatsApp</label>
                <input id="telefone" type="text" name="telefone" class="form-control" value="<?= e($phoneSearch ?? '') ?>" placeholder="Ex.: 11999998888">
            </div>
            <div class="col-auto">
                <button class="btn btn-outline-primary" type="submit">Buscar</button>
            </div>
            <div class="col-auto">
                <a class="btn btn-outline-secondary" href="<?= e(base_url('/clientes')) ?>">Limpar</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nome</th>
                    <th>Telefone</th>
                    <th>WhatsApp</th>
                    <th>E-mail</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($clients)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Nenhum cliente encontrado.</td></tr>
            <?php endif; ?>

            <?php foreach ($clients as $client): ?>
                <tr>
                    <td><?= (int) $client['id'] ?></td>
                    <td><?= e($client['nome']) ?></td>
                    <td><?= e($client['telefone']) ?></td>
                    <td><?= e($client['whatsapp'] ?? '-') ?></td>
                    <td><?= e($client['email'] ?? '-') ?></td>
                    <td>
                        <div class="d-flex justify-content-end gap-1 flex-wrap">
                            <a href="<?= e(base_url('/clientes/historico?id=' . (int) $client['id'])) ?>" class="btn btn-outline-info btn-sm">Histórico</a>
                            <a href="<?= e(base_url('/clientes/editar?id=' . (int) $client['id'])) ?>" class="btn btn-outline-secondary btn-sm">Editar</a>
                            <form method="post" action="<?= e(base_url('/clientes/excluir')) ?>" class="m-0" onsubmit="return confirm('Remover cliente?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $client['id'] ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm">Excluir</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
