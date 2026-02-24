const APP_BASE = (window.APP_BASE_PATH || '').replace(/\/$/, '');
const rows = document.getElementById('reqRows');
let chartInstance = null;
let calendarCursor = null;

const POLL_KEY = 'admin_notifications_last_id';
const pollUrl = window.ADMIN_NOTIFICATION_POLL_URL || `${APP_BASE}/app/api/poll_novos_agendamentos.php`;
const calendarUrl = window.ADMIN_CALENDAR_SUMMARY_URL || `${APP_BASE}/app/api/agenda_resumo_mes.php`;

const actionForm = (id) => `
  <div class="d-grid gap-2">
    <a class="btn btn-sm btn-outline-primary w-100" href="${APP_BASE}/?r=admin/request&id=${id}">Selecionar</a>
    <select class="form-select form-select-sm action" data-id="${id}"><option value="">Ação</option><option value="approve">Aprovar</option><option value="reject">Recusar</option><option value="schedule">Alterar data</option><option value="delete">Excluir</option></select>
    <input type="date" class="form-control form-control-sm pickup" data-id="${id}">
    <button class="btn btn-sm btn-success w-100 doAction" data-id="${id}">Aplicar</button>
  </div>`;


function renderPhotoThumb(photo) {
  if (!photo) return '<span class="text-muted small">Sem foto</span>';
  const src = `${APP_BASE}/uploads/${encodeURIComponent(photo)}`;
  return `<a href="${src}" target="_blank" rel="noopener"><img src="${src}" alt="Foto da solicitação" class="admin-thumb"></a>`;
}

function openWhatsAppMessagesModal(messages) {
  if (!Array.isArray(messages) || messages.length === 0) return;
  const body = document.getElementById('waMessagesBody');
  if (!body) return;

  body.innerHTML = messages.map((item) => {
    const safeText = String(item.message_text || item.message_preview || item.mensagem || '');
    const safeName = String(item.nome || 'Munícipe');
    const safePhone = String(item.phone_e164 || item.telefone || '');
    const safeLink = String(item.whatsapp_web_url || item.whatsapp_url || '#');
    const safeMobile = String(item.whatsapp_mobile_url || safeLink);

    return `<div class="border rounded p-2 mb-2">
      <div class="fw-semibold mb-1">${safeName} • ${safePhone}</div>
      <textarea class="form-control form-control-sm mb-2 wa-msg-text" rows="3">${safeText}</textarea>
      <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-outline-secondary btn-sm btnCopyWaMsg" type="button">Copiar</button>
        <a class="btn btn-success btn-sm" href="${safeLink}" target="_blank" rel="noopener">Abrir WhatsApp no computador</a>
        <a class="btn btn-outline-success btn-sm" href="${safeMobile}" target="_blank" rel="noopener">Abrir WhatsApp no celular</a>
      </div>
    </div>`;
  }).join('');

  const modalEl = document.getElementById('waMessageModal');
  if (!modalEl) return;
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modal.show();

  body.querySelectorAll('.btnCopyWaMsg').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const text = btn.closest('.border')?.querySelector('.wa-msg-text')?.value || '';
      try {
        await navigator.clipboard.writeText(text);
        showToast('Mensagem copiada.', 'success');
      } catch (_) {
        showToast('Não foi possível copiar automaticamente.', 'warning');
      }
    });
  });
}

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

async function showBrowserNotification(title, body) {
  if (!('Notification' in window)) return;
  if (Notification.permission === 'default') {
    try { await Notification.requestPermission(); } catch (_) { return; }
  }
  if (Notification.permission === 'granted') {
    new Notification(title, { body });
  }
}


function getLastId() {
  return Number(localStorage.getItem(POLL_KEY) || sessionStorage.getItem(POLL_KEY) || 0);
}

function setLastId(id) {
  const value = String(Math.max(0, Number(id || 0)));
  localStorage.setItem(POLL_KEY, value);
  sessionStorage.setItem(POLL_KEY, value);
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
      const msg = `${nome} • ${protocolo}\n${endereco}`;
      showAdminToast(msg);
      showBrowserNotification('Novo agendamento - Cata Treco', `${nome} (${protocolo})`);
    });

    setLastId(maxId);
    await loadRequests();
    await loadCalendarSummary();
  } catch (_) {}
}

function renderCalendar(monthData, yearMonth) {
  const container = document.getElementById('calendarWidget');
  if (!container) return;

  const [year, month] = yearMonth.split('-').map((v) => Number(v));
  const firstDay = new Date(year, month - 1, 1);
  const startWeekday = firstDay.getDay();
  const daysInMonth = new Date(year, month, 0).getDate();
  const labels = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

  const monthLabel = new Date(year, month - 1, 1).toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
  const html = [`<div class="calendar-head">${monthLabel}</div><div class="calendar-grid">`];
  labels.forEach((d) => html.push(`<div class="calendar-cell calendar-weekday">${d}</div>`));
  for (let i = 0; i < startWeekday; i += 1) html.push('<div class="calendar-cell calendar-empty"></div>');

  for (let day = 1; day <= daysInMonth; day += 1) {
    const date = `${yearMonth}-${String(day).padStart(2, '0')}`;
    const count = Number(monthData[date] || 0);
    const active = count > 0;
    html.push(`<button class="calendar-cell calendar-day ${active ? 'is-active' : ''}" data-date="${date}" title="${count} agendamentos" data-tooltip="${count} agendamentos">${day}${active ? `<span class=\"calendar-badge\">${count}</span>` : ''}</button>`);
  }

  html.push('</div>');
  container.innerHTML = html.join('');
}

async function loadCalendarSummary() {
  const dateInput = document.getElementById('fDate');
  if (!calendarCursor) {
    const initial = (dateInput?.value || new Date().toISOString().slice(0, 10));
    const [y, m] = initial.split('-').map((v) => Number(v));
    calendarCursor = { year: y, month: m };
  }

  try {
    const params = new URLSearchParams({ year: String(calendarCursor.year), month: String(calendarCursor.month).padStart(2, '0') });
    const res = await fetch(`${calendarUrl}?${params.toString()}`);
    const json = await res.json();
    renderCalendar(json.data || {}, `${calendarCursor.year}-${String(calendarCursor.month).padStart(2, '0')}`);
  } catch (_) {
    renderCalendar({}, `${calendarCursor.year}-${String(calendarCursor.month).padStart(2, '0')}`);
  }
}

function shiftCalendarMonth(step) {
  if (!calendarCursor) return;
  let y = calendarCursor.year;
  let m = calendarCursor.month + step;
  if (m < 1) { m = 12; y -= 1; }
  if (m > 12) { m = 1; y += 1; }
  calendarCursor = { year: y, month: m };
  loadCalendarSummary();
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
    const list = report.falhas || [];
    if (!list.length) {
      fails.innerHTML = '<span class="text-muted">Sem falhas recentes.</span>';
    } else {
      fails.innerHTML = list.map((r) => {
        const url = `https://wa.me/55${(r.telefone_destino || '').replace(/\D+/g, '')}`;
        return `<div class="border rounded p-2 mb-1"><strong>Fila #${r.id}</strong> • Solicitação ${r.solicitacao_id} • Tentativas ${r.tentativas}<br><span class="text-danger">${r.erro_mensagem || 'Erro'}</span><br><a href="${url}" target="_blank" rel="noopener">Enviar manual</a></div>`;
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
    <div class="card shadow-sm glass-card border-0 admin-request-card">
      <div class="card-body py-2 px-3">
        <div class="admin-request-grid">
          <div class="admin-request-photo">
            ${renderPhotoThumb(r.foto)}
          </div>
          <div class="admin-request-main">
            <div class="d-flex align-items-center gap-2 mb-1">
              <input class="row-check form-check-input" type="checkbox" value="${r.id}">
              <div class="fw-semibold">${r.nome}</div>
            </div>
            <div class="small text-muted mb-1">${r.telefone} • ${r.protocolo || '-'}</div>
            <div class="small"><strong>Bairro:</strong> ${r.bairro || '-'}</div>
            <div class="small"><strong>Endereço:</strong> ${r.endereco}</div>
            <div class="small"><strong>Data:</strong> ${String(r.data_solicitada).slice(0, 10)}</div>
          </div>
          <div class="admin-request-actions">
            <span class="badge text-bg-light border mb-2">${r.status}</span>
            ${actionForm(r.id)}
          </div>
        </div>
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

document.addEventListener('click', (event) => {
  if (event.target.closest('.calendar-day')) {
    const btn = event.target.closest('.calendar-day');
    const date = btn.getAttribute('data-date');
    const dateInput = document.getElementById('fDate');
    if (dateInput && date) {
      dateInput.value = date;
      loadRequests();
    }
  }
});

document.getElementById('btnPrevMonth')?.addEventListener('click', () => shiftCalendarMonth(-1));
document.getElementById('btnNextMonth')?.addEventListener('click', () => shiftCalendarMonth(1));

document.getElementById('btnFilter')?.addEventListener('click', async () => {
  await loadRequests();
  await loadCalendarSummary();
});
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
  if (json.ok) openWhatsAppMessagesModal(json.whatsapp_messages || []);
  await loadRequests();
  await loadCommReport();
  await loadCalendarSummary();
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
loadCalendarSummary();
pollNotifications();
setInterval(pollNotifications, 12000);
