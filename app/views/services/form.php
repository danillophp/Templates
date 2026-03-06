<?php $isEdit = is_array($service); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= $isEdit ? 'Editar Serviço' : 'Novo Serviço' ?></h1>
    <a href="/servicos" class="btn btn-outline-secondary btn-sm">Voltar</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= $isEdit ? '/servicos/atualizar' : '/servicos/salvar' ?>" novalidate>
            <?= csrf_field() ?>
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int) $service['id'] ?>"><?php endif; ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="categoria_id">Categoria *</label>
                    <select id="categoria_id" class="form-select" name="categoria_id" required>
                        <option value="">Selecione</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) $category['id'] ?>" <?= ((int) ($service['categoria_id'] ?? 0) === (int) $category['id']) ? 'selected' : '' ?>>
                                <?= e($category['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-8">
                    <label class="form-label" for="nome">Nome do serviço *</label>
                    <input id="nome" class="form-control" name="nome" minlength="3" maxlength="160" required value="<?= e($service['nome'] ?? '') ?>">
                </div>

                <div class="col-md-12">
                    <label class="form-label" for="descricao">Descrição</label>
                    <textarea id="descricao" class="form-control" rows="3" name="descricao"><?= e($service['descricao'] ?? '') ?></textarea>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="duracao_minutos">Duração (min) *</label>
                    <input id="duracao_minutos" type="number" min="15" step="5" class="form-control" name="duracao_minutos" required value="<?= e((string) ($service['duracao_minutos'] ?? 60)) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="valor">Valor (R$) *</label>
                    <input id="valor" type="number" step="0.01" min="0.01" class="form-control" name="valor" required value="<?= e((string) ($service['valor'] ?? '')) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="percentual_entrada">Percentual entrada (%) *</label>
                    <input id="percentual_entrada" type="number" step="0.01" min="0.01" max="100" class="form-control" name="percentual_entrada" required value="<?= e((string) ($service['percentual_entrada'] ?? 20)) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="ativo">Status</label>
                    <select id="ativo" class="form-select" name="ativo">
                        <option value="1" <?= ((int) ($service['ativo'] ?? 1) === 1) ? 'selected' : '' ?>>Ativo</option>
                        <option value="0" <?= ((int) ($service['ativo'] ?? 1) === 0) ? 'selected' : '' ?>>Inativo</option>
                    </select>
                </div>
            </div>

            <button class="btn btn-primary mt-3" type="submit">Salvar serviço</button>
        </form>
    </div>
</div>
