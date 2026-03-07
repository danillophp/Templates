<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Categorias de Serviços</h1>
    <a href="<?= e(base_url('/categorias/criar')) ?>" class="btn btn-primary btn-sm">Nova categoria</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nome</th>
                    <th>Descrição</th>
                    <th>Status</th>
                    <th>Atualização</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($categories)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">Nenhuma categoria cadastrada.</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($categories as $category): ?>
                <tr>
                    <td><?= (int) $category['id'] ?></td>
                    <td><?= e($category['nome']) ?></td>
                    <td><?= e($category['descricao'] ?: '-') ?></td>
                    <td>
                        <?php if ((int) $category['ativo'] === 1): ?>
                            <span class="badge text-bg-success">Ativa</span>
                        <?php else: ?>
                            <span class="badge text-bg-secondary">Inativa</span>
                        <?php endif; ?>
                    </td>
                    <td class="small text-muted"><?= e((string) ($category['updated_at'] ?? '-')) ?></td>
                    <td>
                        <div class="d-flex justify-content-end gap-1 flex-wrap">
                            <a href="<?= e(base_url('/categorias/editar?id=' . (int) $category['id'])) ?>" class="btn btn-outline-secondary btn-sm">Editar</a>

                            <form method="post" action="<?= e(base_url('/categorias/status')) ?>" class="m-0">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $category['id'] ?>">
                                <button type="submit" class="btn btn-outline-warning btn-sm">
                                    <?= (int) $category['ativo'] === 1 ? 'Desativar' : 'Ativar' ?>
                                </button>
                            </form>

                            <form method="post" action="<?= e(base_url('/categorias/excluir')) ?>" class="m-0" onsubmit="return confirm('Excluir logicamente esta categoria?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $category['id'] ?>">
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
