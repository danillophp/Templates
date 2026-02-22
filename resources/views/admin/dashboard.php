<!doctype html><html><head><link rel='stylesheet' href='<?= $_ENV['APP_BASE_PATH'] ?>/resources/assets/css/style.css'></head><body><div class='layout'>
<aside class='sidebar'><h3>Admin</h3><a href='<?= $_ENV['APP_BASE_PATH'] ?>/admin/dashboard'>Dashboard</a><a href='<?= $_ENV['APP_BASE_PATH'] ?>/admin/points'>Pontos</a><a href='<?= $_ENV['APP_BASE_PATH'] ?>/admin/reports'>Relatórios</a><a href='<?= $_ENV['APP_BASE_PATH'] ?>/logout'>Sair</a></aside>
<section class='content'><h2>Agendamentos do dia</h2><form method='get'><input type='date' name='date' value='<?= htmlspecialchars($date) ?>'><button>Filtrar</button></form>
<form method='post' action='<?= $_ENV['APP_BASE_PATH'] ?>/admin/requests/action'><input type='hidden' name='_csrf' value='<?= \App\Core\Csrf::token() ?>'>
<table border='1' cellpadding='5'><tr><th><input type='checkbox' onclick='document.querySelectorAll(".ck").forEach(c=>c.checked=this.checked)'></th><th>Protocolo</th><th>Nome</th><th>Data</th><th>Status</th><th>Detalhe</th></tr>
<?php foreach($requests as $r): ?><tr><td><input class='ck' type='checkbox' name='ids[]' value='<?= $r['id'] ?>'></td><td><?= htmlspecialchars($r['protocolo']) ?></td><td><?= htmlspecialchars($r['nome']) ?></td><td><?= htmlspecialchars($r['data_agendada']) ?></td><td><?= htmlspecialchars($r['status']) ?></td><td><a href='<?= $_ENV['APP_BASE_PATH'] ?>/admin/request-detail?id=<?= $r['id'] ?>'>Ver</a></td></tr><?php endforeach; ?>
</table>
<select name='action'><option value='aprovar'>Aprovar</option><option value='recusar'>Recusar</option><option value='alterar'>Alterar data</option><option value='finalizar'>Finalizar</option><option value='excluir'>Excluir</option></select>
<input type='date' name='nova_data'><button>Executar</button></form>
<div id='toast' class='toast'></div>
<script>
let lastId=0;setInterval(async()=>{const r=await fetch('<?= $_ENV['APP_BASE_PATH'] ?>/api/poll-novos-agendamentos?last_id='+lastId).then(x=>x.json());if(r.items&&r.items.length){lastId=r.items[r.items.length-1].id;const t=document.getElementById('toast');t.innerText='Novo agendamento recebido!';t.style.display='block';setTimeout(()=>t.style.display='none',3500);}},12000);
</script></section></div></body></html>
