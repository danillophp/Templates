<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Clientes</h1>
    <a href="/clientes/criar" class="btn btn-primary btn-sm">Novo cliente</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>Nome</th><th>Telefone</th><th>E-mail</th><th>Ações</th></tr></thead>
            <tbody>
            <?php foreach ($clients as $client): ?>
                <tr>
                    <td><?= (int) $client['id'] ?></td>
                    <td><?= e($client['nome']) ?></td>
                    <td><?= e($client['telefone']) ?></td>
                    <td><?= e($client['email'] ?? '-') ?></td>
                    <td class="d-flex gap-1">
                        <a href="/clientes/editar?id=<?= (int) $client['id'] ?>" class="btn btn-outline-secondary btn-sm">Editar</a>
                        <form method="post" action="/clientes/excluir" onsubmit="return confirm('Remover cliente?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $client['id'] ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
