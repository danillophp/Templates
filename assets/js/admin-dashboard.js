const rows = document.getElementById('reqRows');

const actionForm = (id) => {
  const employeeOptions = (window.EMPLOYEES || []).map((employee) => `<option value="${employee.id}">${employee.nome}</option>`).join('');
  return `<div class="d-flex flex-column gap-1">
    <select class="form-select form-select-sm action" data-id="${id}"><option value="">Ação</option><option value="approve">Aprovar</option><option value="reject">Recusar</option><option value="schedule">Alterar data</option><option value="assign">Atribuir funcionário</option></select>
    <input type="datetime-local" class="form-control form-control-sm pickup" data-id="${id}">
    <select class="form-select form-select-sm employee" data-id="${id}"><option value="">Funcionário</option>${employeeOptions}</select>
    <button class="btn btn-sm btn-success doAction" data-id="${id}">Aplicar</button>
  </div>`;
};

async function loadRequests() {
  const params = new URLSearchParams({
    status: document.getElementById('fStatus').value,
    date: document.getElementById('fDate').value,
  });
  const response = await fetch(`?r=api/admin/requests&${params.toString()}`);
  const json = await response.json();
  rows.innerHTML = (json.data || []).map((request) => `<tr>
    <td>${request.id}</td>
    <td>${request.nome}<br><small>${request.telefone}</small></td>
    <td>${request.endereco}</td>
    <td>${request.data_solicitada}</td>
    <td>${request.status}</td>
    <td>${actionForm(request.id)}</td>
  </tr>`).join('');
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
  alert(json.message);
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
  alert(json.message);
});

loadRequests();
