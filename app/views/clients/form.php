<?php $isEdit = is_array($client); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= $isEdit ? 'Editar Cliente' : 'Novo Cliente' ?></h1>
    <a href="/clientes" class="btn btn-outline-secondary btn-sm">Voltar</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= $isEdit ? '/clientes/atualizar' : '/clientes/salvar' ?>" novalidate>
            <?= csrf_field() ?>
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int) $client['id'] ?>"><?php endif; ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nome *</label>
                    <input class="form-control" name="nome" required maxlength="160" value="<?= e($client['nome'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Telefone *</label>
                    <input class="form-control" name="telefone" required maxlength="30" value="<?= e($client['telefone'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">WhatsApp</label>
                    <input class="form-control" name="whatsapp" maxlength="30" value="<?= e($client['whatsapp'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data nascimento</label>
                    <input type="date" class="form-control" name="data_nascimento" value="<?= e($client['data_nascimento'] ?? '') ?>">
                </div>
                <div class="col-md-9">
                    <label class="form-label">E-mail</label>
                    <input type="email" class="form-control" name="email" maxlength="160" value="<?= e($client['email'] ?? '') ?>">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Observações</label>
                    <textarea class="form-control" name="observacoes" rows="3"><?= e($client['observacoes'] ?? '') ?></textarea>
                </div>
            </div>

            <button class="btn btn-primary mt-3" type="submit">Salvar cliente</button>
        </form>
    </div>
</div>
