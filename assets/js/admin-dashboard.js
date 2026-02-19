const rows = document.getElementById('reqRows');

const actionForm = (id) => {
  const employeeOptions = (window.EMPLOYEES || []).map(e => `<option value="${e.id}">${e.full_name}</option>`).join('');
  return `<div class="d-flex flex-column gap-1">
    <select class="form-select form-select-sm action" data-id="${id}"><option value="">Ação</option><option value="approve">Aprovar</option><option value="reject">Recusar</option><option value="schedule">Alterar data</option><option value="assign">Atribuir</option></select>
    <input type="datetime-local" class="form-control form-control-sm pickup" data-id="${id}">
    <select class="form-select form-select-sm employee" data-id="${id}"><option value="">Funcionário</option>${employeeOptions}</select>
    <button class="btn btn-sm btn-success doAction" data-id="${id}">Aplicar</button>
  </div>`;
};

async function loadRequests() {
  const p = new URLSearchParams({
    status: document.getElementById('fStatus').value,
    date: document.getElementById('fDate').value,
    district: document.getElementById('fDistrict').value,
  });
  const res = await fetch(`?r=api/admin/requests&${p.toString()}`);
  const json = await res.json();
  rows.innerHTML = json.data.map(r => `<tr><td>${r.id}</td><td>${r.full_name}<br><small>${r.whatsapp}</small></td><td>${r.pickup_datetime}</td><td>${r.status}</td><td>${r.district || ''}</td><td>${actionForm(r.id)}</td></tr>`).join('');
}

document.getElementById('btnFilter').addEventListener('click', loadRequests);
document.addEventListener('click', async (e) => {
  if (!e.target.classList.contains('doAction')) return;
  const id = e.target.dataset.id;
  const fd = new FormData();
  fd.append('_csrf', window.CSRF);
  fd.append('request_id', id);
  fd.append('action', document.querySelector(`.action[data-id="${id}"]`).value);
  fd.append('pickup_datetime', document.querySelector(`.pickup[data-id="${id}"]`).value);
  fd.append('employee_id', document.querySelector(`.employee[data-id="${id}"]`).value);
  const res = await fetch('?r=api/admin/update', { method: 'POST', body: fd });
  const json = await res.json();
  alert(json.message + (json.whatsapp?.url ? `\nWhatsApp fallback: ${json.whatsapp.url}` : ''));
  loadRequests();
});

loadRequests();
