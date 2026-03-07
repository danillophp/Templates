<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Agenda em calendário</h1>
    <span class="text-muted small">Visão mensal, semanal e diária</span>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div id="adminCalendar"></div>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
(() => {
  const el = document.getElementById('adminCalendar');
  if (!el || el.dataset.inited === '1') return;
  el.dataset.inited = '1';

  const calendar = new FullCalendar.Calendar(el, {
    locale: 'pt-br',
    initialView: 'dayGridMonth',
    height: 'auto',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    events(fetchInfo, success, failure) {
      const url = `<?= e(base_url('/api/calendario/eventos')) ?>?start=${encodeURIComponent(fetchInfo.startStr)}&end=${encodeURIComponent(fetchInfo.endStr)}`;
      fetch(url).then(r => r.json()).then(success).catch(failure);
    },
    eventClick(info) {
      const st = info.event.extendedProps.status || '-';
      const valor = info.event.extendedProps.valor || 0;
      alert(`${info.event.title}\nStatus: ${st}\nValor: R$ ${Number(valor).toFixed(2)}`);
    }
  });

  calendar.render();
})();
</script>
