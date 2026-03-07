<?php $studio = studio_settings(); ?>
<div class="row mb-3">
  <div class="col-lg-10 mx-auto">
    <div class="studio-google-invite small">
      <?php if ((int) ($studio['ativar_convite_google'] ?? 0) === 1): ?>
        <?= e($studio['texto_convite_google'] ?? '') ?>
        <?php if (!empty($studio['link_avaliacao_google'])): ?>
          <a class="ms-2" href="<?= e($studio['link_avaliacao_google']) ?>" target="_blank" rel="noopener">Avaliar agora</a>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Agendamento Online</h1>
            <span class="badge text-bg-primary">Calendário interativo</span>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h2 class="h6 mb-3">1) Selecione a data no calendário</h2>
                <div id="publicCalendar"></div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="post" action="<?= e(base_url('/agendamento/reservar')) ?>" id="booking-form" class="row g-3">
                    <?= csrf_field() ?>

                    <div class="col-md-4">
                        <label class="form-label">2. Categoria *</label>
                        <select class="form-select" name="categoria_id" id="categoria_id" required>
                            <option value="">Selecione</option>
                            <?php foreach ($categories as $category): ?>
                                <?php if ((int) $category['ativo'] === 1): ?>
                                    <option value="<?= (int) $category['id'] ?>"><?= e($category['nome']) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">3. Serviço *</label>
                        <select class="form-select" name="servico_id" id="servico_id" required disabled>
                            <option value="">Selecione a categoria primeiro</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">4. Data *</label>
                        <input type="date" class="form-control" name="data" id="data" required min="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">5. Horário disponível *</label>
                        <select class="form-select" name="hora" id="hora" required disabled>
                            <option value="">Escolha serviço e data</option>
                        </select>
                    </div>

                    <div class="col-12"><hr><h2 class="h6">6. Seus dados</h2></div>

                    <div class="col-md-6"><label class="form-label">Nome *</label><input class="form-control" name="nome" required maxlength="160"></div>
                    <div class="col-md-3"><label class="form-label">Telefone *</label><input class="form-control" name="telefone" required maxlength="30"></div>
                    <div class="col-md-3"><label class="form-label">WhatsApp *</label><input class="form-control" name="whatsapp" required maxlength="30"></div>
                    <div class="col-md-6"><label class="form-label">E-mail *</label><input type="email" class="form-control" name="email" required maxlength="160"></div>
                    <div class="col-md-6"><label class="form-label">Observações</label><textarea class="form-control" name="observacoes" rows="2"></textarea></div>

                    <div class="col-12"><button class="btn btn-primary px-4" type="submit">7. Avançar para pagamento</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
(() => {
    const services = <?= json_encode($services, JSON_UNESCAPED_UNICODE) ?>;
    const categorySelect = document.getElementById('categoria_id');
    const serviceSelect = document.getElementById('servico_id');
    const dateInput = document.getElementById('data');
    const timeSelect = document.getElementById('hora');

    function populateServices() {
        const categoryId = Number(categorySelect.value || 0);
        const filtered = services.filter(s => Number(s.categoria_id) === categoryId && Number(s.ativo) === 1);

        serviceSelect.innerHTML = '<option value="">Selecione</option>';
        for (const s of filtered) {
            const option = document.createElement('option');
            option.value = s.id;
            option.textContent = `${s.nome} (${s.duracao_minutos} min)`;
            serviceSelect.appendChild(option);
        }

        serviceSelect.disabled = filtered.length === 0;
        loadTimes();
    }

    async function loadTimes() {
        const serviceId = serviceSelect.value;
        const date = dateInput.value;

        timeSelect.innerHTML = '<option value="">Carregando...</option>';
        timeSelect.disabled = true;

        if (!serviceId || !date) {
            timeSelect.innerHTML = '<option value="">Escolha serviço e data</option>';
            return;
        }

        try {
            const res = await fetch(`<?= e(base_url('/api/agenda/horarios-disponiveis')) ?>?data=${encodeURIComponent(date)}&servico_id=${encodeURIComponent(serviceId)}`);
            const list = await res.json();

            if (!Array.isArray(list) || list.length === 0) {
                timeSelect.innerHTML = '<option value="">Sem horários disponíveis</option>';
                return;
            }

            timeSelect.innerHTML = '<option value="">Selecione</option>';
            for (const time of list) {
                const option = document.createElement('option');
                option.value = time;
                option.textContent = time;
                timeSelect.appendChild(option);
            }
            timeSelect.disabled = false;
        } catch {
            timeSelect.innerHTML = '<option value="">Falha ao carregar horários</option>';
        }
    }

    categorySelect.addEventListener('change', populateServices);
    serviceSelect.addEventListener('change', loadTimes);
    dateInput.addEventListener('change', loadTimes);

    const calEl = document.getElementById('publicCalendar');
    if (calEl) {
        const calendar = new FullCalendar.Calendar(calEl, {
            locale: 'pt-br',
            initialView: 'dayGridMonth',
            selectable: true,
            dateClick(info) {
                dateInput.value = info.dateStr;
                dateInput.dispatchEvent(new Event('change'));
                window.scrollTo({top: dateInput.getBoundingClientRect().top + window.scrollY - 100, behavior: 'smooth'});
            }
        });
        calendar.render();
    }
})();
</script>
