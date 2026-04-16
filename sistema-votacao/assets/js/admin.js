(function(){
  const api = (path) => `${window.ADMIN_APP.basePath}${path}`;
  let chart;

  async function loadData(){
    const res = await fetch(api('/ajax/admin_dados.php'));
    const data = await res.json();
    if (!data.ok) return;

    const cards = document.getElementById('cards');
    cards.innerHTML = '';
    [
      ['Total votos', data.cards.total_votos],
      ['Tokens usados', data.cards.tokens_usados],
      ['Disponíveis', data.cards.tokens_disponiveis],
      ['Participantes', data.cards.participantes],
      ['Acessos', data.cards.acessos],
    ].forEach(([label,val]) => {
      const el = document.createElement('div');
      el.className = 'card p-3';
      el.innerHTML = `<div class="text-xs text-slate-500">${label}</div><div class="text-2xl font-bold">${val}</div>`;
      cards.appendChild(el);
    });

    const ranking = document.getElementById('ranking');
    ranking.innerHTML = '<tr><th class="text-left">Pos</th><th class="text-left">Candidato</th><th class="text-right">Votos</th></tr>';
    data.ranking.forEach((r,i) => {
      ranking.innerHTML += `<tr><td>${i+1}</td><td>${r.nome}</td><td class="text-right">${r.votos}</td></tr>`;
    });

    const labels = data.ranking.map(r => r.nome);
    const values = data.ranking.map(r => Number(r.votos));
    if (chart) chart.destroy();
    chart = new Chart(document.getElementById('grafico'), {
      type: 'bar',
      data: { labels, datasets:[{ label:'Votos', data: values }] },
      options: { responsive: true, plugins: { legend: { display: false } } }
    });

    const suspeitas = document.getElementById('suspeitas');
    suspeitas.innerHTML = `<div><strong>Top IPs:</strong> ${data.suspeitas.top_ips.map(i=>`${i.ip} (${i.total})`).join(', ') || '-'}</div>
      <div><strong>Fingerprints repetidos:</strong> ${data.suspeitas.fingerprints.map(i=>`${i.fingerprint.slice(0,12)}... (${i.total})`).join(', ') || '-'}</div>
      <div><strong>Tentativas bloqueadas:</strong> ${data.suspeitas.tentativas_bloqueadas}</div>`;

    const acessos = document.getElementById('acessos');
    acessos.innerHTML = data.acessos.map(a => `<div>${a.created_at} - ${a.ip} - ${a.rota}</div>`).join('');
    document.getElementById('aviso-manutencao').classList.toggle('hidden', !data.manutencao);
  }

  document.getElementById('toggle-manutencao').addEventListener('click', async () => {
    const form = new URLSearchParams({ csrf_token: window.ADMIN_APP.csrf });
    const res = await fetch(api('/ajax/admin_toggle_manutencao.php'), { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: form });
    const data = await res.json();
    if (data.ok) loadData();
  });

  loadData();
  setInterval(loadData, 60000);
})();
