// Service Worker for School Management System
// Provides offline capabilities and caching

const CACHE_NAME = 'sms-v1.0.0';
const CACHE_ASSETS = [
    '/school_management_system/',
    '/school_management_system/assets/css/bootstrap.min.css',
    '/school_management_system/assets/css/custom.css',
    '/school_management_system/assets/js/main.js',
    '/school_management_system/assets/js/bootstrap.min.js',
    '/school_management_system/assets/js/jquery.min.js',
    '/school_management_system/templates/main.php',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css',
    'https://code.jquery.com/jquery-3.7.1.min.js',
    'https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css',
    'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js',
    'https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js',
    'https://cdn.jsdelivr.net/npm/chart.js'
];

// Install event - cache essential assets
self.addEventListener('install', event => {
    console.log('Service Worker: Installing...');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Service Worker: Caching files...');
                return cache.addAll(CACHE_ASSETS);
            })
            .catch(err => console.log('Service Worker: Cache failed', err))
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    console.log('Service Worker: Activating...');
    
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cache => {
                    if (cache !== CACHE_NAME) {
                        console.log('Service Worker: Clearing old cache', cache);
                        return caches.delete(cache);
                    }
                })
            );
        })
    );
});

// Fetch event - serve cached content when offline
self.addEventListener('fetch', event => {
    // Skip cross-origin requests
    if (!event.request.url.startsWith(self.location.origin) && 
        !event.request.url.startsWith('https://cdn.')) {
        return;
    }
    
    event.respondWith(
        caches.match(event.request)
            .then(cachedResponse => {
                // Return cached version or fetch from network
                return cachedResponse || fetch(event.request)
                    .then(response => {
                        // Cache successful responses
                        if (response.status === 200) {
                            const responseClone = response.clone();
                            caches.open(CACHE_NAME)
                                .then(cache => {
                                    cache.put(event.request, responseClone);
                                });
                        }
                        return response;
                    })
                    .catch(() => {
                        // Offline fallback
                        if (event.request.destination === 'document') {
                            return caches.match('/school_management_system/offline.html');
                        }
                    });
            })
    );
});

// Background sync for offline actions
self.addEventListener('sync', event => {
    if (event.tag === 'background-sync') {
        console.log('Service Worker: Background sync');
        event.waitUntil(
            // Handle offline actions when connection is restored
            handleOfflineActions()
        );
    }
});

// Push notifications
self.addEventListener('push', event => {
    if (event.data) {
        const data = event.data.json();
        
        const options = {
            body: data.body || 'You have a new notification',
            icon: '/school_management_system/assets/images/icon-192.png',
            badge: '/school_management_system/assets/images/badge-72.png',
            vibrate: [200, 100, 200],
            tag: data.tag || 'notification',
            actions: [
                {
                    action: 'view',
                    title: 'View',
                    icon: '/school_management_system/assets/images/view-icon.png'
                },
                {
                    action: 'dismiss',
                    title: 'Dismiss',
                    icon: '/school_management_system/assets/images/dismiss-icon.png'
                }
            ]
        };
        
        event.waitUntil(
            self.registration.showNotification(data.title || 'SMS Notification', options)
        );
    }
});

// Notification click handling
self.addEventListener('notificationclick', event => {
    event.notification.close();
    
    if (event.action === 'view') {
        // Open specific page based on notification data
        const urlToOpen = new URL(event.notification.data?.url || '/school_management_system/', self.location.origin);
        
        event.waitUntil(
            clients.matchAll({
                type: 'window',
                includeUncontrolled: true
            }).then(clientList => {
                // Focus existing window if available
                for (const client of clientList) {
                    if (client.url === urlToOpen.href && 'focus' in client) {
                        return client.focus();
                    }
                }
                
                // Open new window
                if (clients.openWindow) {
                    return clients.openWindow(urlToOpen.pathname);
                }
            })
        );
    }
});

// Handle offline actions
async function handleOfflineActions() {
    try {
        const cache = await caches.open(CACHE_NAME + '-offline-actions');
        const requests = await cache.keys();
        
        for (const request of requests) {
            try {
                const response = await fetch(request);
                if (response.ok) {
                    await cache.delete(request);
                    console.log('Service Worker: Synced offline action');
                }
            } catch (error) {
                console.log('Service Worker: Failed to sync action', error);
            }
        }
    } catch (error) {
        console.log('Service Worker: Background sync failed', error);
    }
}

// Message handling from main app
self.addEventListener('message', event => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
