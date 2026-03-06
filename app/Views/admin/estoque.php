<h1>Estoque Alimentar</h1>
<form method="post" action="index.php?r=admin/estoque/movimentar" class="row g-2">
<input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
<div class="col-3"><input class="form-control" name="stock_item_id" type="number" placeholder="ID Item"></div>
<div class="col-3"><input class="form-control" name="school_id" type="number" placeholder="ID Escola"></div>
<div class="col-2"><select class="form-select" name="movement_type"><option value="entrada">Entrada</option><option value="saida">Saída</option></select></div>
<div class="col-2"><input class="form-control" name="quantity" type="number" step="0.01" placeholder="Quantidade"></div>
<div class="col-12"><input class="form-control" name="notes" placeholder="Observações"></div>
<div class="col-12"><button class="btn btn-primary">Registrar</button></div>
</form>
<hr>
<?php foreach ($items as $item): ?><p><?= htmlspecialchars($item['name']) ?> - Atual: <?= htmlspecialchars($item['quantity_current']) ?></p><?php endforeach; ?>
