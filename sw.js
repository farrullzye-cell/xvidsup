self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', () => self.clients.claim());
// Simple cache for icon
self.addEventListener('fetch', (e) => {
    if (e.request.url.includes('icon.svg')) {
        e.respondWith(
            caches.open('xvidsup').then(c => c.match(e.request).then(r => r || fetch(e.request).then(res => { c.put(e.request, res.clone()); return res; })))
        );
    }
});
