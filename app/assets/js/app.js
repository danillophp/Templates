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

    async function registerMirrorServiceWorker() {
        if (!('serviceWorker' in navigator)) {
            return;
        }

        try {
            const registration = await navigator.serviceWorker.register('./sw.js', { scope: './' });

            if (registration.waiting) {
                registration.waiting.postMessage({ type: 'SKIP_WAITING' });
            }

            registration.addEventListener('updatefound', () => {
                const worker = registration.installing;
                if (!worker) {
                    return;
                }

                worker.addEventListener('statechange', () => {
                    if (worker.state === 'installed' && navigator.serviceWorker.controller) {
                        window.location.reload();
                    }
                });
            });
        } catch (error) {
            console.warn('Falha ao registrar o service worker espelhado do PrefSADE.', error);
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', (event) => {
            filterServices(event.target.value);
        });
    }

    window.addEventListener('load', registerMirrorServiceWorker);
})();
