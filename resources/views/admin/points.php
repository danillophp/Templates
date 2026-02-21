<!doctype html><html><body><h2>Pontos de Coleta</h2>
<form method='post' action='<?=base_path('/admin/points')?>'><input type='hidden' name='_csrf' value='<?=$csrf?>'>
<input name='nome' placeholder='Nome' required><input name='descricao' placeholder='Descrição'><input name='latitude' required><input name='longitude' required><button>Salvar</button></form>
<?php foreach($points as $p): ?><div><?=$p['nome']?> (<?=$p['latitude']?>,<?=$p['longitude']?>)</div><?php endforeach; ?></body></html>
