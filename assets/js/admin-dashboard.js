const APP_BASE = (window.APP_BASE_PATH || '').replace(/\/$/, '');
const rows = document.getElementById('reqRows');
let chartInstance = null;

const actionForm = (id) => `
  <div class="row g-2 mt-1">
    <div class="col-md-3"><a class="btn btn-sm btn-outline-primary w-100" href="${APP_BASE}/?r=admin/request&id=${id}">Selecionar</a></div>
    <div class="col-md-3"><select class="form-select form-select-sm action" data-id="${id}"><option value="">Ação</option><option value="approve">Aprovar</option><option value="reject">Recusar</option><option value="schedule">Alterar data</option><option value="delete">Excluir</option></select></div>
    <div class="col-md-3"><input type="date" class="form-control form-control-sm pickup" data-id="${id}"></div>
    <div class="col-md-3"><button class="btn btn-sm btn-success w-100 doAction" data-id="${id}">Aplicar</button></div>
  </div>`;

function selectedIds(singleId = null) {
  if (singleId) {
    const selected = Array.from(document.querySelectorAll('.row-check:checked')).map((el) => el.value);
    return selected.length ? selected : [singleId];
  }
  return Array.from(document.querySelectorAll('.row-check:checked')).map((el) => el.value);
}

async function loadRequests() {
  const params = new URLSearchParams({ status: document.getElementById('fStatus').value, date: document.getElementById('fDate').value });
  const response = await fetch(`${APP_BASE}/?r=api/admin/requests&${params.toString()}`);
  const json = await response.json();
  const list = json.data || [];

  rows.innerHTML = list.map((r) => `
    <div class="card shadow-sm glass-card border-0">
      <div class="card-body py-2 px-3">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div class="d-flex align-items-center gap-2">
            <input class="row-check form-check-input" type="checkbox" value="${r.id}">
            <div>
              <div class="fw-semibold">${r.nome}</div>
              <small class="text-muted">${r.telefone} • ${r.protocolo || '-'}</small>
            </div>
          </div>
          <span class="badge text-bg-light border">${r.status}</span>
        </div>
        <div class="small mt-2"><strong>Endereço:</strong> ${r.endereco}</div>
        <div class="small"><strong>Data:</strong> ${String(r.data_solicitada).slice(0, 10)}</div>
        ${actionForm(r.id)}
      </div>
    </div>`).join('');
}

async function loadChart() {
  const response = await fetch(`${APP_BASE}/?r=api/admin/dashboard`);
  const json = await response.json();
  const labels = (json.data || []).map((row) => row.mes);
  const totals = (json.data || []).map((row) => Number(row.total));

  const ctx = document.getElementById('chartRequests');
  if (!ctx) return;
  if (chartInstance) chartInstance.destroy();

  chartInstance = new Chart(ctx, {
    type: 'line',
    data: { labels, datasets: [{ label: 'Solicitações por mês', data: totals, borderColor: '#0f8a6b', backgroundColor: 'rgba(15,138,107,.15)' }] },
    options: { responsive: true, maintainAspectRatio: false },
  });
}

document.getElementById('btnFilter')?.addEventListener('click', loadRequests);
document.getElementById('fDate')?.addEventListener('change', loadRequests);
document.getElementById('selectAllRows')?.addEventListener('change', (event) => {
  document.querySelectorAll('.row-check').forEach((el) => { el.checked = event.target.checked; });
});

window.addEventListener('click', async (event) => {
  if (!event.target.classList.contains('doAction')) return;
  const id = event.target.dataset.id;

  const fd = new FormData();
  fd.append('_csrf', window.CSRF);
  fd.append('request_ids', selectedIds(id).join(','));
  fd.append('action', document.querySelector(`.action[data-id="${id}"]`).value);
  fd.append('pickup_datetime', document.querySelector(`.pickup[data-id="${id}"]`).value);

  const response = await fetch(`${APP_BASE}/?r=api/admin/update`, { method: 'POST', body: fd });
  const json = await response.json();
  showToast(json.message, json.ok ? 'success' : 'danger');
  await loadRequests();
});

document.getElementById('btnPoint')?.addEventListener('click', async () => {
  const fd = new FormData();
  fd.append('_csrf', window.CSRF);
  fd.append('titulo', document.getElementById('pTitle').value);
  fd.append('latitude', document.getElementById('pLat').value);
  fd.append('longitude', document.getElementById('pLng').value);

  const response = await fetch(`${APP_BASE}/?r=api/admin/point/create`, { method: 'POST', body: fd });
  const json = await response.json();
  showToast(json.message, json.ok ? 'success' : 'danger');
});

document.getElementById('btnExportPdf')?.addEventListener('click', async () => {
  const fd = new FormData();
  if (chartInstance?.toBase64Image) fd.append('chart_image', chartInstance.toBase64Image());
  const date = document.getElementById('fDate').value;
  const response = await fetch(`${APP_BASE}/?r=admin/reports/pdf&date=${encodeURIComponent(date)}`, { method: 'POST', body: fd });
  const json = await response.json();
  showToast(json.message, json.ok ? 'success' : 'danger');
  if (json.ok && json.file) window.open(json.file, '_blank');
});

loadRequests();
loadChart();
