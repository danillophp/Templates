(() => {
  const votesData = window.__votesPerDay;
  if (Array.isArray(votesData) && document.getElementById('votesChart') && window.Chart) {
    const labels = votesData.map((item) => item.dia);
    const values = votesData.map((item) => Number(item.total || 0));

    // eslint-disable-next-line no-new
    new Chart(document.getElementById('votesChart'), {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: 'Votos válidos por dia',
          data: values,
          borderColor: '#f6c453',
          backgroundColor: 'rgba(246,196,83,.2)',
          tension: 0.25,
          fill: true,
        }],
      },
      options: {
        responsive: true,
        plugins: { legend: { labels: { color: '#fff' } } },
        scales: {
          x: { ticks: { color: '#c3cdef' }, grid: { color: 'rgba(255,255,255,.07)' } },
          y: { ticks: { color: '#c3cdef' }, grid: { color: 'rgba(255,255,255,.07)' } },
        },
      },
    });
  }

  ['candidateForm', 'settingsForm'].forEach((formId) => {
    const form = document.getElementById(formId);
    if (!form) return;

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const formData = new FormData(form);

      try {
        const response = await fetch(form.action, {
          method: 'POST',
          body: formData,
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin',
        });

        const payload = await response.json();
        alert(payload.message || 'Operação executada.');
        if (payload.ok) window.location.reload();
      } catch (error) {
        alert('Falha ao salvar dados.');
      }
    });
  });
})();
