<?php $isEdit = is_array($category); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= $isEdit ? 'Editar Categoria' : 'Nova Categoria' ?></h1>
    <a href="/categorias" class="btn btn-outline-secondary btn-sm">Voltar</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= $isEdit ? '/categorias/atualizar' : '/categorias/salvar' ?>" novalidate>
            <?= csrf_field() ?>
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= (int) $category['id'] ?>">
            <?php endif; ?>

            <div class="mb-3">
                <label for="nome" class="form-label">Nome *</label>
                <input
                    id="nome"
                    class="form-control"
                    name="nome"
                    maxlength="120"
                    minlength="3"
                    required
                    value="<?= e($category['nome'] ?? '') ?>"
                >
                <div class="form-text">Mínimo de 3 caracteres.</div>
            </div>

            <div class="mb-3">
                <label for="descricao" class="form-label">Descrição</label>
                <textarea id="descricao" class="form-control" name="descricao" rows="4" maxlength="255"><?= e($category['descricao'] ?? '') ?></textarea>
            </div>

            <div class="mb-3">
                <label for="ativo" class="form-label">Status</label>
                <select id="ativo" class="form-select" name="ativo">
                    <option value="1" <?= ((int) ($category['ativo'] ?? 1) === 1) ? 'selected' : '' ?>>Ativa</option>
                    <option value="0" <?= ((int) ($category['ativo'] ?? 1) === 0) ? 'selected' : '' ?>>Inativa</option>
                </select>
            </div>

            <button class="btn btn-primary" type="submit">Salvar categoria</button>
        </form>
    </div>
</div>
