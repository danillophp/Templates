const rows = document.getElementById('reqRows');

const nowLocalDateTime = () => {
  const dt = new Date();
  dt.setMinutes(dt.getMinutes() - dt.getTimezoneOffset());
  return dt.toISOString().slice(0, 16);
};

const actionForm = (id) => {
  const employeeOptions = (window.EMPLOYEES || []).map((e) => `<option value="${e.id}">${e.full_name}</option>`).join('');
  return `<div class="d-flex flex-column gap-1" style="min-width:230px">
    <select class="form-select form-select-sm action" data-id="${id}">
      <option value="">Ação</option>
      <option value="approve">Aprovar</option>
      <option value="reject">Recusar</option>
      <option value="schedule">Alterar data</option>
      <option value="assign">Atribuir</option>
    </select>
    <input type="datetime-local" min="${nowLocalDateTime()}" class="form-control form-control-sm pickup" data-id="${id}">
    <select class="form-select form-select-sm employee" data-id="${id}"><option value="">Funcionário</option>${employeeOptions}</select>
    <button class="btn btn-sm btn-success doAction" data-id="${id}">Aplicar</button>
  </div>`;
};

const flash = (message, kind = 'success') => {
  const el = document.createElement('div');
  el.className = `alert alert-${kind} position-fixed top-0 end-0 m-3 shadow`; 
  el.style.zIndex = '1100';
  el.innerText = message;
  document.body.appendChild(el);
  setTimeout(() => el.remove(), 4500);
};

async function loadRequests() {
  const p = new URLSearchParams({
    status: document.getElementById('fStatus').value,
    date: document.getElementById('fDate').value,
    district: document.getElementById('fDistrict').value,
  });

  const res = await fetch(`?r=api/admin/requests&${p.toString()}`);
  const json = await res.json();
  rows.innerHTML = json.data.map((r) => `
    <tr>
      <td>${r.id}</td>
      <td>${r.full_name}<br><small>${r.whatsapp}</small></td>
      <td>${r.pickup_datetime}</td>
      <td>${r.status}</td>
      <td>${r.district || ''}</td>
      <td>${actionForm(r.id)}</td>
    </tr>
  `).join('');
}

document.getElementById('btnFilter').addEventListener('click', loadRequests);
document.addEventListener('click', async (e) => {
  if (!e.target.classList.contains('doAction')) return;

  const id = e.target.dataset.id;
  const action = document.querySelector(`.action[data-id="${id}"]`).value;
  const pickup = document.querySelector(`.pickup[data-id="${id}"]`).value;
  const employee = document.querySelector(`.employee[data-id="${id}"]`).value;

  if (!action) {
    flash('Selecione uma ação.', 'danger');
    return;
  }

  if (action === 'schedule' && !pickup) {
    flash('Informe uma nova data/hora para reagendamento.', 'danger');
    return;
  }

  if (action === 'assign' && !employee) {
    flash('Selecione um funcionário para atribuição.', 'danger');
    return;
  }

  const fd = new FormData();
  fd.append('_csrf', window.CSRF);
  fd.append('request_id', id);
  fd.append('action', action);
  fd.append('pickup_datetime', pickup);
  fd.append('employee_id', employee);

  const res = await fetch('?r=api/admin/update', { method: 'POST', body: fd });
  const json = await res.json();

  if (!json.ok) {
    flash(json.message || 'Falha ao processar ação.', 'danger');
    return;
  }

  flash(json.message, 'success');
  if (json.whatsapp?.url) {
    flash('WhatsApp via fallback disponível para envio manual.', 'warning');
  }

  loadRequests();
});

loadRequests();
