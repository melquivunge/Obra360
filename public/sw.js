const CACHE_NAME = 'obra360-v1';

self.addEventListener('install', function (event) {
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(clients.claim());
});

self.addEventListener('push', function (event) {
    if (!event.data) return;

    let payload;
    try {
        payload = event.data.json();
    } catch {
        payload = { title: 'Obra360', body: event.data.text() };
    }

    const title = payload.title || 'Obra360';
    const options = {
        body: payload.body || '',
        icon: '/icon-192.png',
        badge: '/icon-192.png',
        vibrate: [150, 50, 150],
        data: { url: payload.url || '/portal' },
        actions: [
            { action: 'open', title: 'Ver no portal' },
            { action: 'close', title: 'Fechar' },
        ],
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    if (event.action === 'close') return;

    const targetUrl = event.notification.data?.url || '/portal';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (windowClients) {
            for (const client of windowClients) {
                if (client.url === targetUrl && 'focus' in client) {
                    return client.focus();
                }
            }
            return clients.openWindow(targetUrl);
        })
    );
});
