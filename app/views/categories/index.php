<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Categorias de Serviços</h1>
    <a href="/categorias/criar" class="btn btn-primary btn-sm">Nova categoria</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>Nome</th><th>Status</th><th>Ações</th></tr></thead>
            <tbody>
            <?php foreach ($categories as $category): ?>
                <tr>
                    <td><?= (int) $category['id'] ?></td>
                    <td><?= e($category['nome']) ?></td>
                    <td><?= (int) $category['ativo'] === 1 ? 'Ativa' : 'Inativa' ?></td>
                    <td class="d-flex gap-1">
                        <a href="/categorias/editar?id=<?= (int) $category['id'] ?>" class="btn btn-outline-secondary btn-sm">Editar</a>
                        <form method="post" action="/categorias/excluir" onsubmit="return confirm('Remover categoria?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $category['id'] ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
