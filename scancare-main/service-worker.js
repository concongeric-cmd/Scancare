

const CACHE_NAME = 'scancare-resident-v11';
const PDF_CACHE_NAME = 'scancare-resident-pdfs-v3';
const BASE_PATH = new URL('./', self.registration.scope);
const ASSETS_TO_CACHE = [
    'landing.php',
    'manifest.json',
    'pwa-offline.js?v=4',
    'offline-pdf-download.js?v=3',
    'service-worker.js',
    'theme-state.js',
    'images/SClogo.png'
].map((path) => new URL(path, BASE_PATH).href);

function cacheResponse(request, response) {
    if (!response || !response.ok) {
        return Promise.resolve();
    }

    try {
        const responseCopy = response.clone();
        return caches.open(CACHE_NAME)
            .then((cache) => cache.put(request, responseCopy))
            .catch((error) => console.warn('[ServiceWorker] Cache write skipped:', error));
    } catch (error) {
        return Promise.resolve();
    }
}


self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('[ServiceWorker] Caching assets...');
            return Promise.allSettled(ASSETS_TO_CACHE.map((url) => cache.add(url)));
        })
    );
    self.skipWaiting();
});


self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME && cacheName !== PDF_CACHE_NAME) {
                        console.log('[ServiceWorker] Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim();
});


self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    
    if (request.method !== 'GET') {
        return;
    }

    if (url.origin === 'https://api.qrserver.com' && url.pathname === '/v1/create-qr-code/') {
        event.respondWith((async () => {
            const cache = await caches.open(CACHE_NAME);
            const cachedResponse = await cache.match(request);
            if (cachedResponse) {
                return cachedResponse;
            }

            try {
                const response = await fetch(request);
                if (response.ok || response.type === 'opaque') {
                    try {
                        await cache.put(request, response.clone());
                    } catch (error) {
                        console.warn('[ServiceWorker] Resident QR cache write skipped:', error);
                    }
                }
                return response;
            } catch (error) {
                return new Response('', {
                    status: 503,
                    statusText: 'Resident QR unavailable offline'
                });
            }
        })());
        return;
    }

    
    if (url.origin !== location.origin || !url.pathname.startsWith(BASE_PATH.pathname)) {
        return;
    }

    if (url.pathname.endsWith('/download_file_request.php')) {
        event.respondWith(
            (async () => {
                const pdfCache = await caches.open(PDF_CACHE_NAME);
                try {
                    const response = await fetch(request);
                    if (response.ok) {
                        await pdfCache.put(request, response.clone());
                    }
                    return response;
                } catch (error) {
                    const cachedResponse = await pdfCache.match(request);
                    return cachedResponse || new Response('PDF is not available offline. Download it once while online.', {
                        status: 503,
                        statusText: 'Offline PDF unavailable',
                        headers: { 'Content-Type': 'text/plain; charset=utf-8' }
                    });
                }
            })()
        );
        return;
    }

    if (url.pathname.endsWith('/get_pending_logins.php')) {
        event.respondWith(
            fetch(request).catch(() => new Response(JSON.stringify({ success: true, pending: [], offline: true }), {
                status: 200,
                headers: { 'Content-Type': 'application/json; charset=utf-8' }
            }))
        );
        return;
    }

    if (url.pathname.endsWith('/view_record.php')) {
        event.respondWith(fetch(request));
        return;
    }

    
    if (request.destination === 'image' || 
        request.destination === 'style' || 
        request.destination === 'script' ||
        request.destination === 'font') {
        event.respondWith(
            caches.match(request).then((response) => {
                return response || fetch(request).then((fetchResponse) => {
                    cacheResponse(request, fetchResponse);
                    return fetchResponse;
                });
            }).catch(() => {
                return new Response('Resource not available offline', {
                    status: 503,
                    statusText: 'Service Unavailable'
                });
            })
        );
    } 
    
    else if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((fetchResponse) => {
                    cacheResponse(request, fetchResponse);
                    return fetchResponse;
                })
                .catch(() => {
                    return caches.match(request).then((cachedResponse) => {
                        return cachedResponse || new Response(
                            '<h1>ScanCare is offline</h1><p>Open the resident dashboard or landing page after reconnecting once to make it available offline.</p>',
                            { headers: { 'Content-Type': 'text/html' } }
                        );
                    });
                })
        );
    } else {
        event.respondWith(
            fetch(request).catch(() => caches.match(request).then((cachedResponse) => {
                return cachedResponse || new Response('', { status: 503, statusText: 'Offline' });
            }))
        );
    }
});


self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
