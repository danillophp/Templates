<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Serviços</h1>
    <a href="/servicos/criar" class="btn btn-primary btn-sm">Novo serviço</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>Serviço</th><th>Categoria</th><th>Valor</th><th>Duração</th><th>Status</th><th>Ações</th></tr></thead>
            <tbody>
            <?php foreach ($services as $service): ?>
                <tr>
                    <td><?= (int) $service['id'] ?></td>
                    <td><?= e($service['nome']) ?></td>
                    <td><?= e($service['categoria_nome']) ?></td>
                    <td>R$ <?= number_format((float) $service['valor_total'], 2, ',', '.') ?></td>
                    <td><?= (int) $service['duracao_minutos'] ?> min</td>
                    <td><?= (int) $service['ativo'] === 1 ? 'Ativo' : 'Inativo' ?></td>
                    <td class="d-flex gap-1">
                        <a href="/servicos/editar?id=<?= (int) $service['id'] ?>" class="btn btn-outline-secondary btn-sm">Editar</a>
                        <form method="post" action="/servicos/excluir" onsubmit="return confirm('Remover serviço?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
