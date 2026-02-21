<!doctype html><html><head><script src='https://cdn.jsdelivr.net/npm/chart.js'></script><link rel='stylesheet' href='<?=asset_url('css/app.css')?>'></head><body class='admin'>
<aside><a href='<?=base_path('/admin/dashboard')?>'>Dashboard</a><a href='<?=base_path('/admin/points')?>'>Pontos de Coleta</a><a href='<?=base_path('/admin/reports')?>'>Relatórios</a><a href='<?=base_path('/admin/notifications')?>'>Notificações</a><a href='<?=base_path('/logout')?>'>Sair</a></aside>
<main><h2>Agendamentos do dia</h2><form><input type='date' name='data' value='<?=$date?>'><button>Filtrar</button></form>
<table><tr><th><input type='checkbox' id='all'></th><th>Protocolo</th><th>Nome</th><th>Status</th><th>Ações</th></tr>
<?php foreach($requests as $r): ?><tr><td><input type='checkbox'></td><td><?=$r['protocolo']?></td><td><a href='<?=base_path('/admin/request?id='.$r['id'])?>'><?=$r['nome']?></a></td><td><?=$r['status']?></td><td>
<form method='post' action='<?=base_path('/admin/request/update')?>'><input type='hidden' name='_csrf' value='<?=$csrf?>'><input type='hidden' name='id' value='<?=$r['id']?>'>
<button name='acao' value='aprovar'>Aprovar</button><button name='acao' value='recusar'>Recusar</button><input type='date' name='nova_data'><button name='acao' value='alterar'>Alterar data</button><button name='acao' value='excluir'>Excluir</button></form></td></tr><?php endforeach; ?></table>
<canvas id='statusChart'></canvas><canvas id='monthChart'></canvas><audio id='notifySound' preload='none'><source src='<?=asset_url('sounds/notify.mp3')?>'></audio>
</main><script>window.statusData=<?=json_encode($statusData)?>;window.monthData=<?=json_encode($monthData)?>;window.pollUrl='<?=base_path('/api/poll')?>';</script><script src='<?=asset_url('js/admin.js')?>'></script></body></html>
