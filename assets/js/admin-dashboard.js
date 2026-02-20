const rows = document.getElementById('reqRows');

const actionForm = (id) => {
  const employeeOptions = (window.EMPLOYEES || []).map((e) => `<option value="${e.id}">${e.nome}</option>`).join('');
  return `<div class="d-flex flex-column gap-1">
    <select class="form-select form-select-sm action" data-id="${id}"><option value="">Ação</option><option value="approve">Aprovar</option><option value="reject">Recusar</option><option value="schedule">Alterar data</option><option value="assign">Atribuir funcionário</option></select>
    <input type="datetime-local" class="form-control form-control-sm pickup" data-id="${id}">
    <select class="form-select form-select-sm employee" data-id="${id}"><option value="">Funcionário</option>${employeeOptions}</select>
    <button class="btn btn-sm btn-success doAction" data-id="${id}">Aplicar</button>
  </div>`;
};

async function loadRequests() {
  const params = new URLSearchParams({ status: document.getElementById('fStatus').value, date: document.getElementById('fDate').value });
  const response = await fetch(`?r=api/admin/requests&${params.toString()}`);
  const json = await response.json();
  rows.innerHTML = (json.data || []).map((r) => `<tr>
    <td>${r.id}</td><td>${r.protocolo || '-'}</td><td>${r.nome}<br><small>${r.telefone}</small></td><td>${r.endereco}</td><td>${r.data_solicitada}</td><td>${r.status}</td><td>${actionForm(r.id)}</td>
  </tr>`).join('');
}

async function loadChart() {
  const response = await fetch('?r=api/admin/dashboard');
  const json = await response.json();
  const labels = (json.data || []).map((row) => row.mes);
  const totals = (json.data || []).map((row) => Number(row.total));

  const ctx = document.getElementById('chartRequests');
  if (!ctx) return;
  new Chart(ctx, {
    type: 'line',
    data: { labels, datasets: [{ label: 'Solicitações por mês', data: totals, borderColor: '#198754', backgroundColor: 'rgba(25,135,84,.2)' }] },
    options: { responsive: true, plugins: { legend: { display: true } } },
  });
}

document.getElementById('btnFilter').addEventListener('click', loadRequests);

window.addEventListener('click', async (event) => {
  if (!event.target.classList.contains('doAction')) return;
  const id = event.target.dataset.id;

  const fd = new FormData();
  fd.append('_csrf', window.CSRF);
  fd.append('request_id', id);
  fd.append('action', document.querySelector(`.action[data-id="${id}"]`).value);
  fd.append('pickup_datetime', document.querySelector(`.pickup[data-id="${id}"]`).value);
  fd.append('employee_id', document.querySelector(`.employee[data-id="${id}"]`).value);

  const response = await fetch('?r=api/admin/update', { method: 'POST', body: fd });
  const json = await response.json();
  showToast(json.message, json.ok ? 'success' : 'danger');
  loadRequests();
});

document.getElementById('btnPoint').addEventListener('click', async () => {
  const fd = new FormData();
  fd.append('_csrf', window.CSRF);
  fd.append('titulo', document.getElementById('pTitle').value);
  fd.append('latitude', document.getElementById('pLat').value);
  fd.append('longitude', document.getElementById('pLng').value);

  const response = await fetch('?r=api/admin/point/create', { method: 'POST', body: fd });
  const json = await response.json();
  showToast(json.message, json.ok ? 'success' : 'danger');
});

loadRequests();
loadChart();
