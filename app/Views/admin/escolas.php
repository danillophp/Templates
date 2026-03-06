<h1>Cadastro de Escolas/Creches</h1>
<form method="post" action="index.php?r=admin/escolas/salvar" class="row g-2">
<input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
<div class="col-4"><input class="form-control" name="name" placeholder="Nome" required></div>
<div class="col-2"><select class="form-select" name="type"><option value="escola">Escola</option><option value="creche">Creche</option></select></div>
<div class="col-6"><input class="form-control" name="address" placeholder="Endereço" required></div>
<div class="col-3"><input class="form-control" name="phone" placeholder="Telefone"></div>
<div class="col-3"><input class="form-control" name="director_name" placeholder="Diretor(a)"></div>
<div class="col-2"><input class="form-control" name="latitude" step="0.000001" placeholder="Latitude"></div>
<div class="col-2"><input class="form-control" name="longitude" step="0.000001" placeholder="Longitude"></div>
<div class="col-2"><input class="form-control" name="total_students" type="number" value="0"></div>
<div class="col-2"><input class="form-control" name="available_slots" type="number" value="0"></div>
<div class="col-2"><input class="form-control" name="rooms_count" type="number" value="0"></div>
<div class="col-2"><input class="form-control" name="region" placeholder="Região"></div>
<div class="col-12"><button class="btn btn-primary">Salvar</button></div>
</form>
<hr>
<?php foreach ($schools as $school): ?><p><?= htmlspecialchars($school['name']) ?> (<?= htmlspecialchars($school['type']) ?>)</p><?php endforeach; ?>
