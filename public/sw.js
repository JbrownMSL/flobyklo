// Mojo Lacrosse service worker — push notifications + light shell cache.
// Pattern adapted from optix-Trading/public/sw.js (Matt's working web-push).
const CACHE = 'mojo-v1';
const SHELL = ['/offline.html'];

self.addEventListener('install', e => {
  e.waitUntil(caches.open(CACHE).then(c => c.addAll(SHELL)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys().then(keys => Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('message', e => { if (e.data?.type === 'SKIP_WAITING') self.skipWaiting(); });

// Network-first for navigations; fall back to offline page if truly offline.
self.addEventListener('fetch', e => {
  const req = e.request;
  if (req.mode === 'navigate') {
    e.respondWith(fetch(req).catch(() => caches.match('/offline.html')));
  }
});

// ── Web Push ──────────────────────────────────────────────
self.addEventListener('push', e => {
  let data = { title: 'Mojo Lacrosse', body: '' };
  try { data = JSON.parse(e.data?.text() || '{}'); } catch {}
  e.waitUntil(self.registration.showNotification(data.title || 'Mojo Lacrosse', {
    body: data.body || '',
    icon: '/icon-192.png',
    badge: '/icon-192.png',
    tag: data.data?.type || 'mojo',
    renotify: true,
    data: data.data || {},
    vibrate: [100, 50, 100],
  }));
});

self.addEventListener('notificationclick', e => {
  e.notification.close();
  const url = (e.notification.data && e.notification.data.url) || '/';
  e.waitUntil(clients.matchAll({ type: 'window', includeUncontrolled: true }).then(all => {
    const existing = all.find(c => 'focus' in c);
    if (existing) { existing.focus(); if ('navigate' in existing) existing.navigate(url); return; }
    return clients.openWindow(url);
  }));
});
