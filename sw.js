/**
 * sw.js — Service Worker (Network-First Strategie)
 *
 * Strategie:
 *  - Assets (HTML/CSS/JS/Bilder): NETWORK FIRST mit Cache-Fallback
 *    → Beim Online-Reload immer aktuell, bei Offline der letzte gecachte Stand
 *  - API-Endpoints: Network-only (kein Cache, damit Daten aktuell bleiben)
 *  - Cache wird bei jeder Version-Bump geleert
 */
const CACHE_NAME = 'bauplan-v7';        // bei JEDER deployten Änderung anpassen
const STATIC_ASSETS = [
  '/',
  '/login.html',
];

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE_NAME).then((cache) =>
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
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (e) => {
  const url = new URL(e.request.url);

  // API-Endpoints nie cachen
  if (url.pathname.startsWith('/api/')) return;
  if (e.request.method !== 'GET') return;

  // Network-First: erst online versuchen, bei Fehler zum Cache fallen
  e.respondWith(
    fetch(e.request)
      .then((response) => {
        // Erfolgreiche Antwort cachen
        if (response && response.status === 200 && url.origin === location.origin) {
          const clone = response.clone();
          caches.open(CACHE_NAME).then((c) => c.put(e.request, clone));
        }
        return response;
      })
      .catch(() => caches.match(e.request))
  );
});
