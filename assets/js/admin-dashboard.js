const APP_BASE = (window.APP_BASE_PATH || '').replace(/\/$/, '');
const rows = document.getElementById('reqRows');
let chartInstance = null;

const POLL_KEY = 'admin_notifications_last_id';
const pollUrl = window.ADMIN_NOTIFICATION_POLL_URL || `${APP_BASE}/app/api/poll_notificacoes.php`;

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

function showAdminToast(message) {
  const wrap = document.getElementById('adminNotifyWrap');
  if (!wrap) return;

  const toast = document.createElement('div');
  toast.className = 'admin-toast';
  toast.innerHTML = `<button class="admin-toast-close" aria-label="Fechar">×</button><div class="admin-toast-title">Novo agendamento</div><div class="admin-toast-body"></div>`;
  toast.querySelector('.admin-toast-body').textContent = message;

  wrap.appendChild(toast);
  toast.querySelector('.admin-toast-close')?.addEventListener('click', () => toast.remove());
  setTimeout(() => toast.remove(), 9000);
}

function getLastId() {
  return Number(sessionStorage.getItem(POLL_KEY) || localStorage.getItem(POLL_KEY) || 0);
}

function setLastId(id) {
  const value = String(Math.max(0, Number(id || 0)));
  sessionStorage.setItem(POLL_KEY, value);
  localStorage.setItem(POLL_KEY, value);
}

async function pollNotifications() {
  try {
    const response = await fetch(`${pollUrl}?last_id=${encodeURIComponent(getLastId())}`, { headers: { Accept: 'application/json' } });
    const json = await response.json();
    if (!json.ok || !Array.isArray(json.data) || json.data.length === 0) return;

    let maxId = getLastId();
    json.data.forEach((entry) => {
      const id = Number(entry.id || 0);
      if (id > maxId) maxId = id;

      const payload = entry.payload || {};
      const nome = payload.nome || 'Munícipe';
      const protocolo = payload.protocolo || `#${entry.solicitacao_id}`;
      const endereco = payload.endereco || 'Endereço não informado';
      showAdminToast(`${nome} • ${protocolo}\n${endereco}`);
    });

    setLastId(maxId);
    loadRequests();
  } catch (_) {
    // sem bloqueio visual em caso de rede intermitente
  }
}

function renderComm(report) {
  if (!report) return;
  const sent = document.getElementById('commSent');
  const err = document.getElementById('commErr');
  const rate = document.getElementById('commRate');
  const avg = document.getElementById('commAvg');
  const fails = document.getElementById('commFails');

  if (sent) sent.textContent = Number(report.enviadas || 0);
  if (err) err.textContent = Number(report.erros || 0);
  if (rate) rate.textContent = `${Number(report.taxa_entrega || 0)}%`;
  if (avg) avg.textContent = `${Number(report.tempo_medio || 0)}s`;

  if (fails) {
    const rows = report.falhas || [];
    if (!rows.length) {
      fails.innerHTML = '<span class="text-muted">Sem falhas recentes.</span>';
    } else {
      fails.innerHTML = rows.map((r) => {
        const url = `https://wa.me/55${(r.telefone_destino || '').replace(/\D+/g, '')}`;
        return `<div class="border rounded p-2 mb-1"><strong>Fila #${r.id}</strong> • Solicitação ${r.solicitacao_id} • Tentativas ${r.tentativas}<br><span class="text-danger">${r.erro_mensagem || 'Erro'}</span><br><a href="${url}" target="_blank">Envio manual necessário</a></div>`;
      }).join('');
    }
  }
}

async function loadCommReport() {
  try {
    const res = await fetch(`${APP_BASE}/?r=api/admin/comm-report`);
    const json = await res.json();
    if (json.ok) renderComm(json.data);
  } catch (_) {}
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
  await loadCommReport();
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

renderComm(window.COMM_REPORT || null);
loadRequests();
loadChart();
loadCommReport();
pollNotifications();
setInterval(pollNotifications, 12000);
