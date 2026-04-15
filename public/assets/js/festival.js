(() => {
  const menuToggle = document.getElementById('menuToggle');
  const menu = document.getElementById('menu');
  const voteForm = document.getElementById('voteForm');
  const voteMessage = document.getElementById('voteMessage');

  if (menuToggle && menu) {
    menuToggle.addEventListener('click', () => menu.classList.toggle('open'));
  }

  const startedAt = Number(document.body.dataset.voteStart || Math.floor(Date.now() / 1000));
  const interactionInput = document.getElementById('interactionSeconds');

  const updateInteractionSeconds = () => {
    if (!interactionInput) return;
    const now = Math.floor(Date.now() / 1000);
    interactionInput.value = String(Math.max(0, now - startedAt));
  };

  document.addEventListener('mousemove', updateInteractionSeconds, { passive: true });
  document.addEventListener('touchstart', updateInteractionSeconds, { passive: true });
  updateInteractionSeconds();

  if (voteForm) {
    voteForm.addEventListener('submit', async (event) => {
      const submitter = event.submitter;
      if (!submitter) return;

      event.preventDefault();
      updateInteractionSeconds();

      const formData = new FormData(voteForm);
      formData.set('candidate_id', submitter.value);

      submitter.disabled = true;
      try {
        const response = await fetch(voteForm.action, {
          method: 'POST',
          body: formData,
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin'
        });

        const payload = await response.json();
        voteMessage.textContent = payload.message || 'Resposta recebida.';
        voteMessage.style.color = payload.ok ? '#57e38b' : '#ff908f';

        if (Array.isArray(payload.ranking)) {
          renderRanking(payload.ranking);
        }
      } catch (error) {
        voteMessage.textContent = 'Erro de conexão. Tente novamente.';
        voteMessage.style.color = '#ff908f';
      } finally {
        submitter.disabled = false;
      }
    });
  }

  document.querySelectorAll('[data-ranking-limit]').forEach((button) => {
    button.addEventListener('click', async () => {
      const limit = Number(button.dataset.rankingLimit || 10);
      document.querySelectorAll('[data-ranking-limit]').forEach((b) => b.classList.remove('active'));
      button.classList.add('active');

      const response = await fetch(`?r=api/festival/ranking&limit=${limit}`, { credentials: 'same-origin' });
      const payload = await response.json();
      if (payload.ok) renderRanking(payload.ranking);
    });
  });

  function renderRanking(items) {
    const container = document.getElementById('rankingList');
    if (!container) return;

    container.innerHTML = items.map((item) => {
      const top3 = Number(item.posicao) <= 3 ? 'top3' : '';
      return `
        <div class="ranking-item ${top3}">
          <span class="position">#${item.posicao}</span>
          <span class="name">${escapeHtml(item.nome)}</span>
          <span class="votes">${item.votos_total} votos</span>
        </div>
      `;
    }).join('');
  }

  function escapeHtml(value) {
    return String(value)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#39;');
  }
})();
