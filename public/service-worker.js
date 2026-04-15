const CACHE_VERSION = 'festival-v1.0.0';
const STATIC_ASSETS = [
  '/?r=festival/home',
  '/public/assets/css/festival.css',
  '/public/assets/js/festival.js',
  '/public/assets/img/logo-festival.png',
  '/public/assets/img/logo-garota-sade.png',
];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE_VERSION).then((cache) => cache.addAll(STATIC_ASSETS)));
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.filter((key) => key !== CACHE_VERSION).map((key) => caches.delete(key))))
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const request = event.request;

  if (request.method !== 'GET') {
    return;
  }

  event.respondWith(
    caches.match(request).then((cached) => {
      if (cached) {
        return cached;
      }

      return fetch(request)
        .then((response) => {
          if (response.ok && request.url.includes('/public/assets/')) {
            const cloned = response.clone();
            caches.open(CACHE_VERSION).then((cache) => cache.put(request, cloned));
          }
          return response;
        })
        .catch(() => caches.match('/?r=festival/home'));
    })
  );
});
