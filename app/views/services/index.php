<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Serviços</h1>
    <a href="<?= e(base_url('/servicos/criar')) ?>" class="btn btn-primary btn-sm">Novo serviço</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nome</th>
                    <th>Categoria</th>
                    <th>Duração</th>
                    <th>Valor</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($services)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">Nenhum serviço cadastrado.</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($services as $service): ?>
                <tr>
                    <td><?= (int) $service['id'] ?></td>
                    <td><?= e($service['nome']) ?></td>
                    <td><?= e($service['categoria_nome']) ?></td>
                    <td><?= (int) $service['duracao_minutos'] ?> min</td>
                    <td>R$ <?= number_format((float) $service['valor'], 2, ',', '.') ?></td>
                    <td>
                        <?php if ((int) $service['ativo'] === 1): ?>
                            <span class="badge text-bg-success">Ativo</span>
                        <?php else: ?>
                            <span class="badge text-bg-secondary">Inativo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex justify-content-end gap-1 flex-wrap">
                            <a href="<?= e(base_url('/servicos/editar?id=' . (int) $service['id'])) ?>" class="btn btn-outline-secondary btn-sm">Editar</a>

                            <form method="post" action="<?= e(base_url('/servicos/status')) ?>" class="m-0">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
                                <button type="submit" class="btn btn-outline-warning btn-sm">
                                    <?= (int) $service['ativo'] === 1 ? 'Desativar' : 'Ativar' ?>
                                </button>
                            </form>

                            <form method="post" action="<?= e(base_url('/servicos/excluir')) ?>" class="m-0" onsubmit="return confirm('Excluir logicamente este serviço?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
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
