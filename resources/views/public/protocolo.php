<!doctype html><html><body><h2>Consulta</h2>
<form><select name='tipo'><option value='protocolo'>Pesquisar por Protocolo</option><option value='telefone'>Pesquisar por Telefone</option></select>
<input name='q' required><button>Pesquisar</button></form>
<?php foreach($results as $r): ?><div><strong><?=$r['protocolo']?></strong> - <?=$r['status']?> - <?=$r['data_agendada']?></div><?php endforeach; ?>
</body></html>
