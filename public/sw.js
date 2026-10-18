/* PractisBase service worker — installability only.
   v3 changes the script bytes so an installed app replaces the worker that
   still carries the certificate from before the 12 Sep 2026 renewal.
   Keep the fetch listener empty: Chrome needs one for the install prompt,
   and an empty listener leaves every navigation on the network. */
const CACHE = 'practisbase-shell-v3';
const PRECACHE = ['/offline.html', '/images/icons/icon-192.png'];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

/* Fetch listener required for install prompts. Pass through all requests so a
   slow or sleeping origin cannot hang page loads via the service worker. */
self.addEventListener('fetch', () => {});
