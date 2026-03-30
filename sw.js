// sw.js
const CACHE_NAME = 'kingdom-sacco-v2';
const urlsToCache = [
  '/',
  '/index.php',
  '/dashboard.php',
  '/assets/css/styles.css',
  '/assets/css/theme.css',
  '/assets/js/main.js',
  '/assets/js/theme.js',
  '/assets/images/kingdomsacco_light.png',
  '/assets/images/kingdomsacco_dark.png'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(urlsToCache);
      })
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        if (response) {
          return response;
        }
        return fetch(event.request);
      }
    )
  );
});
