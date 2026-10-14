(() => {
    const PAGE_CACHE_NAME = 'scancare-resident-v10';

    if (!('serviceWorker' in navigator)) {
        return;
    }

    const setNetworkState = (online) => {
        document.documentElement.dataset.network = online ? 'online' : 'offline';
        const existing = document.getElementById('scancare-network-status');
        if (existing) {
            existing.textContent = online ? 'Online' : 'Offline - showing saved data';
            existing.classList.toggle('is-offline', !online);
        }
    };

    const addNetworkStatus = () => {
        if (document.getElementById('scancare-network-status')) {
            return;
        }

        const status = document.createElement('div');
        status.id = 'scancare-network-status';
        status.setAttribute('role', 'status');
        status.textContent = navigator.onLine ? 'Online' : 'Offline - showing saved data';
        status.className = navigator.onLine ? '' : 'is-offline';
        document.body.appendChild(status);

        if (!document.getElementById('scancare-network-status-style')) {
            const style = document.createElement('style');
            style.id = 'scancare-network-status-style';
            style.textContent = `
                #scancare-network-status {
                    position: fixed;
                    right: 1rem;
                    bottom: 1rem;
                    z-index: 10000;
                    padding: .45rem .7rem;
                    border: 1px solid rgba(69, 75, 27, .2);
                    border-radius: 999px;
                    background: rgba(255, 255, 255, .94);
                    color: #454B1B;
                    font: 600 12px/1.2 sans-serif;
                    box-shadow: 0 4px 14px rgba(15, 23, 42, .12);
                }
                #scancare-network-status.is-offline {
                    border-color: #f59e0b;
                    color: #92400e;
                }
                :is(html.dark, html.dark-mode, body.dark-mode) #scancare-network-status {
                    border-color: #475569;
                    background: rgba(15, 23, 42, .96);
                    color: #bbf7d0;
                    box-shadow: 0 4px 14px rgba(0, 0, 0, .35);
                }
                :is(html.dark, html.dark-mode, body.dark-mode) #scancare-network-status.is-offline {
                    border-color: #a16207;
                    color: #fde68a;
                }
            `;
            document.head.appendChild(style);
        }
    };

    // A service worker cannot cache the first page it is installed from because
    // that navigation happened before the worker controlled the page. Save the
    // rendered page here so the dashboard is available after this first visit.
    const saveCurrentPageForOffline = async () => {
        if (!navigator.onLine || !window.caches || !location.pathname.endsWith('.php')) {
            return;
        }

        try {
            const html = '<!doctype html>\n' + document.documentElement.outerHTML;
            const response = new Response(html, {
                headers: { 'Content-Type': 'text/html; charset=utf-8' }
            });
            const cache = await caches.open(PAGE_CACHE_NAME);
            await cache.put(window.location.href, response);
        } catch (error) {
            console.warn('[ScanCare] Could not save this page for offline use:', error);
        }
    };

    const cacheResidentQr = async () => {
        const qrElement = document.getElementById('qrCodeImage');
        if (!navigator.onLine || !window.caches || !qrElement) {
            return;
        }

        try {
            const qrImage = qrElement.querySelector('img');
            if (qrImage && qrImage.src) {
                localStorage.setItem('scanCareResidentQrDataUrl', qrImage.src);
            }
        } catch (error) {
            console.warn('[ScanCare] Could not cache resident QR for offline use:', error);
        }
    };

    window.addEventListener('online', () => {
        setNetworkState(true);
        cacheResidentQr();
    });
    window.addEventListener('offline', () => setNetworkState(false));

    window.addEventListener('load', () => {
        addNetworkStatus();
        setNetworkState(navigator.onLine);
        saveCurrentPageForOffline();
        cacheResidentQr();
    });

    // Register immediately instead of waiting for the entire page to load. This
    // gives the worker time to take control before the resident goes offline.
    navigator.serviceWorker.register(new URL('service-worker.js', window.location.href), {
        scope: new URL('./', window.location.href).pathname
    }).catch((error) => {
        console.warn('[ScanCare] Offline support unavailable:', error);
    });
})();
