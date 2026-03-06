<?php $isEdit = is_array($service); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= $isEdit ? 'Editar Serviço' : 'Novo Serviço' ?></h1>
    <a href="/servicos" class="btn btn-outline-secondary btn-sm">Voltar</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= $isEdit ? '/servicos/atualizar' : '/servicos/salvar' ?>">
            <?= csrf_field() ?>
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int) $service['id'] ?>"><?php endif; ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Categoria *</label>
                    <select class="form-select" name="categoria_id" required>
                        <option value="">Selecione</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) $category['id'] ?>" <?= ((int) ($service['categoria_id'] ?? 0) === (int) $category['id']) ? 'selected' : '' ?>>
                                <?= e($category['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-8"><label class="form-label">Nome do serviço *</label><input class="form-control" name="nome" required value="<?= e($service['nome'] ?? '') ?>"></div>
                <div class="col-md-12"><label class="form-label">Descrição</label><textarea class="form-control" rows="3" name="descricao"><?= e($service['descricao'] ?? '') ?></textarea></div>
                <div class="col-md-3"><label class="form-label">Valor total (R$) *</label><input type="number" step="0.01" min="0" class="form-control" name="valor_total" required value="<?= e((string) ($service['valor_total'] ?? '')) ?>"></div>
                <div class="col-md-3"><label class="form-label">Duração (min)</label><input type="number" min="15" class="form-control" name="duracao_minutos" value="<?= e((string) ($service['duracao_minutos'] ?? 60)) ?>"></div>
                <div class="col-md-3"><label class="form-label">Status</label>
                    <select class="form-select" name="ativo">
                        <option value="1" <?= ((int) ($service['ativo'] ?? 1) === 1) ? 'selected' : '' ?>>Ativo</option>
                        <option value="0" <?= ((int) ($service['ativo'] ?? 1) === 0) ? 'selected' : '' ?>>Inativo</option>
                    </select>
                </div>
            </div>

            <button class="btn btn-primary mt-3" type="submit">Salvar</button>
        </form>
    </div>
</div>
