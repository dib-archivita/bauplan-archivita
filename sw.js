/**
 * sw.js — Service Worker für PWA + Basic Offline Cache
 *
 * Strategie:
 *  - HTML: network-first (immer aktuell wenn online, fallback auf Cache)
 *  - JS/CSS/Fonts: stale-while-revalidate
 *  - API-Calls: network-only (keine Cache, sonst stale Daten)
 */
const CACHE_NAME = 'bauplan-v1';
const STATIC_ASSETS = [
  '/',
  '/login.html',
  '/assets/sync.js',
  '/assets/admin.js',
  '/assets/search.js',
  '/assets/sticky.js',
  '/assets/mobile.js',
];

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE_NAME).then((cache) =>
      // Best-effort: ignore failures (e.g. file not yet deployed)
      Promise.all(STATIC_ASSETS.map((u) => cache.add(u).catch(() => null)))
    )
  );
  self.skipWaiting();
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k))
      )
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', (e) => {
  const url = new URL(e.request.url);

  // API-Endpoints: immer Network, kein Cache
  if (url.pathname.startsWith('/api/')) {
    return; // default fetch behavior
  }

  // GET-Requests: Cache-First mit Network-Fallback
  if (e.request.method !== 'GET') return;

  e.respondWith(
    caches.match(e.request).then((cached) => {
      const fetchPromise = fetch(e.request)
        .then((response) => {
          if (response && response.status === 200 && url.origin === location.origin) {
            const clone = response.clone();
            caches.open(CACHE_NAME).then((c) => c.put(e.request, clone));
          }
          return response;
        })
        .catch(() => cached);
      return cached || fetchPromise;
    })
  );
});
