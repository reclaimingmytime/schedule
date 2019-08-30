self.addEventListener('install', e => {
e.waitUntil(
   caches.open('airhorner').then(cache => {
       return cache.addAll([
           '/',
       ])
           .then(() => self.skipWaiting());
   })
 )
});

self.addEventListener('activate', event => {
	event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', function(event) {
  event.respondWith(
    fetch(event.request).catch(function() {
      return caches.match(event.request);
    })
  );
});