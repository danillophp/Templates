const CORE_CACHE = 'prefsade-core-v2';
const RUNTIME_CACHE = 'prefsade-runtime-v2';
const OFFLINE_URL = '/app/offline.html';
const CORE_ASSETS = [
    '/app/offline.html',
    '/app/assets/css/style.css',
    '/app/assets/js/app.js',
    '/app/assets/img/prefsade-mark.svg',
    '/app/assets/icons/icon-app.svg'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CORE_CACHE).then((cache) => cache.addAll(CORE_ASSETS))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys
                .filter((key) => ![CORE_CACHE, RUNTIME_CACHE].includes(key))
                .map((key) => caches.delete(key))
        ))
    );
    self.clients.claim();
});

self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

async function networkFirst(request) {
    const cache = await caches.open(RUNTIME_CACHE);

    try {
        const response = await fetch(request, { cache: 'no-store' });
        if (response && response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        const cached = await cache.match(request);
        if (cached) {
            return cached;
        }

        if (request.mode === 'navigate') {
            return caches.match(OFFLINE_URL);
        }

        throw error;
    }
}

async function staleWhileRevalidate(request) {
    const cache = await caches.open(CORE_CACHE);
    const cached = await cache.match(request);
    const networkPromise = fetch(request)
        .then((response) => {
            if (response && response.ok) {
                cache.put(request, response.clone());
            }
            return response;
        })
        .catch(() => cached);

    return cached || networkPromise;
}

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    const requestUrl = new URL(event.request.url);
    if (requestUrl.origin !== self.location.origin) {
        return;
    }

    const isNavigation = event.request.mode === 'navigate';
    const isMirrorRoute = requestUrl.pathname.startsWith('/app/');
    const isCoreAsset = CORE_ASSETS.includes(requestUrl.pathname);

    if (isCoreAsset) {
        event.respondWith(staleWhileRevalidate(event.request));
        return;
    }

    if (isNavigation || isMirrorRoute) {
        event.respondWith(networkFirst(event.request));
    }
});
