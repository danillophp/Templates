const STATIC_CACHE = 'prefsade-static-v1';
const RUNTIME_CACHE = 'prefsade-runtime-v1';
const OFFLINE_URL = '/app/offline.html';

const APP_SHELL = [
    '/app/',
    '/app/index.php',
    '/app/offline.html',
    '/app/manifest.json',
    '/app/assets/css/style.css',
    '/app/assets/js/app.js',
    '/app/assets/img/prefsade-mark.svg',
    '/app/assets/icons/icon-app.svg'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => cache.addAll(APP_SHELL))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys
                .filter((key) => ![STATIC_CACHE, RUNTIME_CACHE].includes(key))
                .map((key) => caches.delete(key))
        ))
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    const requestUrl = new URL(event.request.url);
    const isSameOrigin = requestUrl.origin === self.location.origin;

    if (!isSameOrigin) {
        return;
    }

    if (APP_SHELL.includes(requestUrl.pathname)) {
        event.respondWith(
            caches.match(event.request).then((cached) => cached || fetch(event.request))
        );
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                if (!response || response.status !== 200 || response.type !== 'basic') {
                    return response;
                }

                const responseClone = response.clone();
                caches.open(RUNTIME_CACHE).then((cache) => cache.put(event.request, responseClone));
                return response;
            })
            .catch(async () => {
                const cached = await caches.match(event.request);
                if (cached) {
                    return cached;
                }

                if (event.request.mode === 'navigate') {
                    return caches.match(OFFLINE_URL);
                }

                return new Response('', { status: 503, statusText: 'Offline' });
            })
    );
});
