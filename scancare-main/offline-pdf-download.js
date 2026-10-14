/* Save the resident's print-friendly medical-record HTML for offline printing. */
(() => {
    const DATABASE = 'scancare-resident-offline-files';
    const STORE = 'printable-records';

    function openDatabase() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(DATABASE, 2);
            request.onupgradeneeded = () => {
                if (!request.result.objectStoreNames.contains(STORE)) {
                    request.result.createObjectStore(STORE, { keyPath: 'url' });
                }
            };
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async function saveRecord(url, html) {
        const database = await openDatabase();
        await new Promise((resolve, reject) => {
            const transaction = database.transaction(STORE, 'readwrite');
            transaction.objectStore(STORE).put({ url, html, savedAt: Date.now() });
            transaction.oncomplete = resolve;
            transaction.onerror = () => reject(transaction.error);
            transaction.onabort = () => reject(transaction.error);
        });
        database.close();
    }

    async function getSavedRecord(url) {
        const database = await openDatabase();
        const saved = await new Promise((resolve, reject) => {
            const transaction = database.transaction(STORE, 'readonly');
            const request = transaction.objectStore(STORE).get(url);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
        database.close();
        return saved;
    }

    function addBaseUrl(html, responseUrl) {
        const base = `<base href="${new URL('.', responseUrl).href}">`;
        return /<head[^>]*>/i.test(html)
            ? html.replace(/<head([^>]*)>/i, `<head$1>${base}`)
            : `<!doctype html><html><head>${base}</head><body>${html}</body></html>`;
    }

    function showPrintableRecord(printWindow, html) {
        if (printWindow && !printWindow.closed) {
            printWindow.document.open();
            printWindow.document.write(html);
            printWindow.document.close();
            printWindow.focus();
            return;
        }

        const blobUrl = URL.createObjectURL(new Blob([html], { type: 'text/html' }));
        window.location.assign(blobUrl);
        setTimeout(() => URL.revokeObjectURL(blobUrl), 60000);
    }

    async function fetchPrintableRecord(url) {
        const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store' });
        if (!response.ok || !/text\/html/i.test(response.headers.get('content-type') || '')) {
            throw new Error('The server did not return the printable record.');
        }

        return addBaseUrl(await response.text(), response.url);
    }

    async function cacheRecordFromNetwork(url) {
        const html = await fetchPrintableRecord(url);
        await saveRecord(url, html);
        return html;
    }

    async function preloadAcceptedRecords() {
        if (!navigator.onLine) return;

        const links = [...document.querySelectorAll('a[data-offline-record]')];
        for (const link of links) {
            const url = new URL(link.href, window.location.href).href;
            try {
                await cacheRecordFromNetwork(url);
                link.dataset.offlineReady = 'true';
            } catch (error) {
                // The normal click flow remains available even if preloading fails.
                console.warn('[ScanCare] Could not preload a printable record:', error);
            }
        }
    }

    document.addEventListener('click', async (event) => {
        const link = event.target.closest('a[data-offline-record]');
        if (!link) return;

        if (window.matchMedia('(max-width: 768px)').matches && link.href.includes('download_file_request.php')) {
            return;
        }

        if (link.hasAttribute('data-mobile-direct-download') && window.matchMedia('(max-width: 768px)').matches) {
            event.preventDefault();
            window.location.assign(link.dataset.mobileDownloadUrl || link.href);
            return;
        }

        event.preventDefault();
        // Open during the click event, so browsers do not treat it as an unwanted popup.
        const printWindow = window.open('', '_blank');
        const url = new URL(link.href, window.location.href).href;
        const original = link.innerHTML;
        link.setAttribute('aria-busy', 'true');
        link.style.pointerEvents = 'none';
        link.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Opening record...';

        try {
            const html = await fetchPrintableRecord(url);
            try {
                await saveRecord(url, html);
            } catch (storageError) {
                console.warn('[ScanCare] Could not save this record for offline use:', storageError);
            }
            showPrintableRecord(printWindow, html);
            link.innerHTML = '<i class="fas fa-check mr-1"></i> Ready to print';
        } catch (error) {
            try {
                const saved = await getSavedRecord(url);
                if (!saved || !saved.html) throw error;
                showPrintableRecord(printWindow, saved.html);
                link.innerHTML = '<i class="fas fa-print mr-1"></i> Opened saved record';
            } catch (_) {
                if (printWindow && !printWindow.closed) printWindow.close();
                link.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i> Record unavailable offline';
            }
        } finally {
            link.removeAttribute('aria-busy');
            setTimeout(() => {
                link.style.pointerEvents = '';
                link.innerHTML = original;
            }, 2200);
        }
    });

    // Once an accepted request appears on the dashboard, make its printable
    // record available offline before the resident presses the button.
    if (document.readyState === 'complete') {
        preloadAcceptedRecords();
    } else {
        window.addEventListener('load', preloadAcceptedRecords);
    }
})();
