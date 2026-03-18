(function () {
    const searchInput = document.querySelector('[data-service-search]');
    const cards = Array.from(document.querySelectorAll('[data-service-card]'));
    const emptyState = document.querySelector('[data-empty-state]');

    function normalize(value) {
        return (value || '')
            .toString()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim()
            .toLowerCase();
    }

    function filterServices(term) {
        const normalizedTerm = normalize(term);
        let visibleCount = 0;

        cards.forEach((card) => {
            const index = normalize(card.getAttribute('data-search'));
            const matches = normalizedTerm === '' || index.includes(normalizedTerm);

            card.hidden = !matches;
            if (matches) {
                visibleCount += 1;
            }
        });

        if (emptyState) {
            emptyState.hidden = visibleCount !== 0;
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', (event) => {
            filterServices(event.target.value);
        });
    }

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('./sw.js').catch((error) => {
                console.warn('Falha ao registrar o service worker do PrefSADE.', error);
            });
        });
    }
})();
