

This document provides enterprise-grade frontend improvements for ScanCare focusing on UX, accessibility, offline-first PWA capabilities, and client-side analytics—without touching database code.



**Why:** Skeleton screens provide visual feedback during data loading, reducing perceived latency and improving perceived performance.

```html
<!
<div id="skeleton-loader" class="skeleton-container">
    <!
    <div class="skeleton-item">
        <div class="skeleton skeleton-text-sm" style="width: 40%;"></div>
        <div class="skeleton skeleton-text-xs" style="width: 60%; margin-top: 8px;"></div>
        <div class="skeleton skeleton-text-xs" style="width: 50%; margin-top: 8px;"></div>
    </div>
    <div class="skeleton-item">
        <div class="skeleton skeleton-text-sm" style="width: 40%;"></div>
        <div class="skeleton skeleton-text-xs" style="width: 60%; margin-top: 8px;"></div>
        <div class="skeleton skeleton-text-xs" style="width: 50%; margin-top: 8px;"></div>
    </div>
    <div class="skeleton-item">
        <div class="skeleton skeleton-text-sm" style="width: 40%;"></div>
        <div class="skeleton skeleton-text-xs" style="width: 60%; margin-top: 8px;"></div>
        <div class="skeleton skeleton-text-xs" style="width: 50%; margin-top: 8px;"></div>
    </div>
</div>
```

```css


.skeleton-container {
    display: flex;
    flex-direction: column;
    gap: 16px;
    padding: 16px;
    background: #fff;
    border-radius: 8px;
}

.skeleton-item {
    padding: 12px;
    background: #f5f5f5;
    border-radius: 6px;
    min-height: 80px;
}

.skeleton {
    display: block;
    height: 12px;
    background: linear-gradient(
        90deg,



    );
    background-size: 200% 100%;
    border-radius: 4px;
    animation: skeleton-loading 2s infinite;
}

.skeleton-text-sm {
    height: 18px;
    border-radius: 4px;
}

.skeleton-text-xs {
    height: 14px;
    border-radius: 4px;
}

@keyframes skeleton-loading {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}


.skeleton-container.hidden {
    display: none;
}
```

```javascript

class SkeletonLoader {
    constructor(skeletonSelector, contentSelector) {
        this.skeleton = document.querySelector(skeletonSelector);
        this.content = document.querySelector(contentSelector);
    }

    show() {
        if (this.skeleton) {
            this.skeleton.classList.remove('hidden');
        }
        if (this.content) {
            this.content.style.opacity = '0.5';
        }
    }

    hide() {
        if (this.skeleton) {
            this.skeleton.classList.add('hidden');
        }
        if (this.content) {
            this.content.style.opacity = '1';
        }
    }
}
```


```css



.dashboard-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
    padding: 12px;
}

.appointment-card {
    padding: 12px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    background: #fff;
    font-size: 14px;
    line-height: 1.5;
}

.appointment-card-title {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.appointment-card-meta {
    font-size: 13px;
    color: #666;
    display: flex;
    flex-direction: column;
    gap: 4px;
}


@media (min-width: 640px) {
    .dashboard-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 18px;
        padding: 16px;
    }

    .appointment-card {
        padding: 14px;
        font-size: 15px;
    }

    .appointment-card-title {
        font-size: 17px;
    }
}


@media (min-width: 768px) {
    .dashboard-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        padding: 20px;
    }

    .appointment-card {
        padding: 16px;
        font-size: 15px;
    }
}


@media (min-width: 1024px) {
    .dashboard-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 24px;
        padding: 24px;
    }

    .appointment-card {
        padding: 18px;
        font-size: 16px;
    }
}


@media (min-width: 1280px) {
    .dashboard-grid {
        grid-template-columns: repeat(5, 1fr);
        gap: 28px;
    }
}




.btn-primary,
.btn-secondary,
input[type="button"],
input[type="submit"],
button {
    min-height: 48px;
    min-width: 48px;
    padding: 12px 16px;
}


.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 6px;
    color: #333;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px;
    font-size: 16px; 
    border: 1px solid #d0d0d0;
    border-radius: 6px;
}

@media (min-width: 768px) {
    .form-group {
        margin-bottom: 20px;
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }
}



.navbar {
    position: sticky;
    top: 0;
    z-index: 100;
    background: #454B1B;
    padding: 8px 12px;
}

.navbar-brand {
    font-size: 16px;
    font-weight: 700;
    color: #fff;
}

@media (min-width: 768px) {
    .navbar {
        padding: 12px 20px;
    }

    .navbar-brand {
        font-size: 20px;
    }
}
```


```css


.btn-primary {
    background-color: #454B1B;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 12px 24px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    box-shadow: 0 2px 8px rgba(69, 75, 27, 0.15);
    position: relative;
    overflow: hidden;
}

.btn-primary:hover {
    background-color: #3a3e16;
    box-shadow: 0 4px 16px rgba(69, 75, 27, 0.25);
    transform: translateY(-2px);
}

.btn-primary:active {
    transform: translateY(0);
    box-shadow: 0 1px 4px rgba(69, 75, 27, 0.15);
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}


.btn-primary::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.5);
    transform: translate(-50%, -50%);
}

.btn-primary:active::before {
    animation: ripple 0.6s ease-out;
}

@keyframes ripple {
    0% {
        width: 0;
        height: 0;
    }
    100% {
        width: 300px;
        height: 300px;
    }
}



input[type="text"],
input[type="email"],
input[type="password"],
input[type="number"],
textarea,
select {
    transition: all 0.25s ease;
    border: 1px solid #d0d0d0;
    border-radius: 6px;
}

input:focus,
textarea:focus,
select:focus {
    outline: none;
    border-color: #454B1B;
    box-shadow: 0 0 0 3px rgba(69, 75, 27, 0.1);
    background-color: #fafbf7;
}


.form-group.focused label {
    color: #454B1B;
    font-size: 12px;
    transform: translateY(-24px);
}



.dropdown-menu {
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px) scaleY(0.95);
    transition: all 0.2s ease;
    transform-origin: top;
}

.dropdown-menu.visible {
    opacity: 1;
    visibility: visible;
    transform: translateY(0) scaleY(1);
}

.modal-overlay {
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.25s ease;
}

.modal-overlay.visible {
    opacity: 1;
    visibility: visible;
}

.modal-content {
    transform: scale(0.95);
    transition: transform 0.25s ease;
}

.modal-overlay.visible .modal-content {
    transform: scale(1);
}



.spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(69, 75, 27, 0.2);
    border-top-color: #454B1B;
    border-radius: 50%;
    animation: spinner-rotate 0.8s linear infinite;
}

@keyframes spinner-rotate {
    to {
        transform: rotate(360deg);
    }
}
```

```javascript

class FormInteractionManager {
    constructor() {
        this.setupFormInteractions();
    }

    setupFormInteractions() {
        const formInputs = document.querySelectorAll('input, textarea, select');
        
        formInputs.forEach(input => {
            
            input.addEventListener('focus', (e) => {
                e.target.closest('.form-group')?.classList.add('focused');
            });
            
            
            input.addEventListener('blur', (e) => {
                if (!e.target.value) {
                    e.target.closest('.form-group')?.classList.remove('focused');
                }
            });

            
            if (input.value) {
                input.closest('.form-group')?.classList.add('focused');
            }
        });
    }

    
    setButtonLoading(buttonSelector, isLoading = true) {
        const btn = document.querySelector(buttonSelector);
        if (!btn) return;

        if (isLoading) {
            btn.disabled = true;
            btn.dataset.originalText = btn.textContent;
            btn.innerHTML = '<span class="spinner"></span> Loading...';
        } else {
            btn.disabled = false;
            btn.textContent = btn.dataset.originalText || 'Submit';
        }
    }
}


document.addEventListener('DOMContentLoaded', () => {
    new FormInteractionManager();
});
```



```html
<!
<div class="analytics-dashboard">
    
    <!
    <div class="chart-container">
        <div class="chart-header">
            <h3>Monthly Appointment Volume</h3>
            <select id="appointment-year-filter" class="chart-filter">
                <option value="2024">2024</option>
                <option value="2025">2025</option>
                <option value="2026">2026</option>
            </select>
        </div>
        <canvas id="appointmentVolumeChart"></canvas>
        <div class="chart-legend">
            <span class="legend-item">
                <span class="legend-color" style="background: #454B1B;"></span>
                Scheduled
            </span>
            <span class="legend-item">
                <span class="legend-color" style="background: #90EE90;"></span>
                Completed
            </span>
            <span class="legend-item">
                <span class="legend-color" style="background: #FFB6C1;"></span>
                Cancelled
            </span>
        </div>
    </div>

    <!
    <div class="chart-container">
        <div class="chart-header">
            <h3>Medicine Inventory Status</h3>
        </div>
        <div id="inventoryGaugeContainer" class="gauge-grid">
            <!
        </div>
    </div>

    <!
    <div class="chart-container">
        <div class="chart-header">
            <h3>Disease Surveillance - Last 12 Weeks</h3>
            <select id="disease-filter" class="chart-filter">
                <option value="all">All Diseases</option>
                <option value="fever">Fever</option>
                <option value="cough">Cough/Cold</option>
                <option value="dengue">Dengue</option>
                <option value="diarrhea">Diarrhea</option>
            </select>
        </div>
        <canvas id="diseaseSurveillanceChart"></canvas>
    </div>

</div>

<!
<script src="https:
```


```css


.analytics-dashboard {
    display: grid;
    grid-template-columns: 1fr;
    gap: 24px;
    padding: 20px;
    background: #F8FAF6;
    border-radius: 8px;
}

@media (min-width: 768px) {
    .analytics-dashboard {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1200px) {
    .analytics-dashboard {
        grid-template-columns: repeat(3, 1fr);
    }
}


.chart-container {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    border: 1px solid #e8e8e8;
    transition: all 0.3s ease;
}

.chart-container:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    flex-wrap: wrap;
    gap: 12px;
}

.chart-header h3 {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    margin: 0;
}

.chart-filter {
    padding: 6px 12px;
    border: 1px solid #d0d0d0;
    border-radius: 4px;
    font-size: 13px;
    background: #fff;
    cursor: pointer;
}

canvas {
    max-height: 300px;
}


.chart-legend {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-top: 12px;
    flex-wrap: wrap;
    font-size: 13px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.legend-color {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 2px;
}



.gauge-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 16px;
    margin-top: 16px;
}

.gauge-item {
    padding: 16px;
    background: #f9f9f9;
    border-radius: 8px;
    text-align: center;
    border-left: 4px solid #454B1B;
}

.gauge-item.low-stock {
    border-left-color: #ff6b6b;
    background: #ffe0e0;
}

.gauge-item.medium-stock {
    border-left-color: #ffd93d;
    background: #fff9e0;
}

.gauge-item.high-stock {
    border-left-color: #51cf66;
    background: #e8f9e8;
}

.gauge-label {
    font-size: 12px;
    font-weight: 500;
    color: #666;
    margin-bottom: 8px;
}

.gauge-value {
    font-size: 24px;
    font-weight: 700;
    color: #333;
}

.gauge-bar {
    width: 100%;
    height: 8px;
    background: #e0e0e0;
    border-radius: 4px;
    margin-top: 8px;
    overflow: hidden;
}

.gauge-bar-fill {
    height: 100%;
    background: #454B1B;
    transition: width 0.5s ease;
}
```


```javascript


class AnalyticsDashboard {
    constructor(appointmentData, inventoryData, diseaseSurveillanceData) {
        this.appointmentData = appointmentData;
        this.inventoryData = inventoryData;
        this.diseaseSurveillanceData = diseaseSurveillanceData;
        
        this.charts = {};
        this.init();
    }

    init() {
        this.initAppointmentVolumeChart();
        this.initInventoryGauges();
        this.initDiseaseSurveillanceChart();
        this.setupFilterListeners();
    }

    
    initAppointmentVolumeChart() {
        const ctx = document.getElementById('appointmentVolumeChart')?.getContext('2d');
        if (!ctx) return;

        
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const scheduled = new Array(12).fill(0);
        const completed = new Array(12).fill(0);
        const cancelled = new Array(12).fill(0);

        
        this.appointmentData.forEach(apt => {
            const month = new Date(apt.date).getMonth();
            if (apt.status === 'scheduled') scheduled[month]++;
            if (apt.status === 'completed') completed[month]++;
            if (apt.status === 'cancelled') cancelled[month]++;
        });

        this.charts.appointment = new Chart(ctx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Scheduled',
                        data: scheduled,
                        borderColor: '#454B1B',
                        backgroundColor: 'rgba(69, 75, 27, 0.05)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    },
                    {
                        label: 'Completed',
                        data: completed,
                        borderColor: '#90EE90',
                        backgroundColor: 'rgba(144, 238, 144, 0.05)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    },
                    {
                        label: 'Cancelled',
                        data: cancelled,
                        borderColor: '#FFB6C1',
                        backgroundColor: 'rgba(255, 182, 193, 0.05)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 13 },
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 5
                        }
                    }
                }
            }
        });
    }

    
    initInventoryGauges() {
        const container = document.getElementById('inventoryGaugeContainer');
        if (!container) return;

        container.innerHTML = ''; 

        this.inventoryData.forEach(item => {
            const percentFull = (item.currentStock / item.maxStock) * 100;
            const statusClass = this.getStockStatus(percentFull);

            const gaugeHTML = `
                <div class="gauge-item ${statusClass}">
                    <div class="gauge-label">${item.name}</div>
                    <div class="gauge-value">${item.currentStock}</div>
                    <div style="font-size: 11px; color: #999;">of ${item.maxStock} units</div>
                    <div class="gauge-bar">
                        <div class="gauge-bar-fill" style="width: ${percentFull}%"></div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', gaugeHTML);
        });
    }

    
    getStockStatus(percentFull) {
        if (percentFull < 25) return 'low-stock';
        if (percentFull < 75) return 'medium-stock';
        return 'high-stock';
    }

    
    initDiseaseSurveillanceChart() {
        const ctx = document.getElementById('diseaseSurveillanceChart')?.getContext('2d');
        if (!ctx) return;

        
        const weeks = this.getLast12Weeks();
        const diseaseCounts = this.aggregateDiseaseData(weeks);

        this.charts.disease = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: weeks.map(d => d.label),
                datasets: [
                    {
                        label: 'Fever',
                        data: diseaseCounts.fever,
                        backgroundColor: '#FF6B6B',
                    },
                    {
                        label: 'Cough/Cold',
                        data: diseaseCounts.cough,
                        backgroundColor: '#4ECDC4',
                    },
                    {
                        label: 'Dengue',
                        data: diseaseCounts.dengue,
                        backgroundColor: '#FFE66D',
                    },
                    {
                        label: 'Diarrhea',
                        data: diseaseCounts.diarrhea,
                        backgroundColor: '#95E1D3',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                indexAxis: 'x',
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true
                    }
                }
            }
        });
    }

    
    getLast12Weeks() {
        const weeks = [];
        for (let i = 11; i >= 0; i
            const date = new Date();
            date.setDate(date.getDate() - i * 7);
            weeks.push({
                label: `W${Math.ceil((date.getDate()) / 7)}`,
                start: new Date(date),
                end: new Date(date.getTime() + 7 * 24 * 60 * 60 * 1000)
            });
        }
        return weeks;
    }

    
    aggregateDiseaseData(weeks) {
        const diseaseTypes = {
            fever: new Array(weeks.length).fill(0),
            cough: new Array(weeks.length).fill(0),
            dengue: new Array(weeks.length).fill(0),
            diarrhea: new Array(weeks.length).fill(0)
        };

        this.diseaseSurveillanceData.forEach(record => {
            const recordDate = new Date(record.date);
            const weekIndex = weeks.findIndex(w => 
                recordDate >= w.start && recordDate <= w.end
            );

            if (weekIndex !== -1) {
                const diseaseType = record.symptom.toLowerCase();
                if (diseaseTypes.hasOwnProperty(diseaseType)) {
                    diseaseTypes[diseaseType][weekIndex]++;
                }
            }
        });

        return diseaseTypes;
    }

    
    setupFilterListeners() {
        const diseaseFilter = document.getElementById('disease-filter');
        if (diseaseFilter) {
            diseaseFilter.addEventListener('change', (e) => {
                this.filterDiseaseChart(e.target.value);
            });
        }

        const yearFilter = document.getElementById('appointment-year-filter');
        if (yearFilter) {
            yearFilter.addEventListener('change', () => {
                this.initAppointmentVolumeChart();
            });
        }
    }

    
    filterDiseaseChart(disease) {
        if (!this.charts.disease) return;

        this.charts.disease.data.datasets.forEach((dataset, index) => {
            const diseaseMap = ['Fever', 'Cough/Cold', 'Dengue', 'Diarrhea'];
            if (disease === 'all' || dataset.label.toLowerCase() === disease.toLowerCase()) {
                this.charts.disease.getDatasetMeta(index).hidden = false;
            } else {
                this.charts.disease.getDatasetMeta(index).hidden = true;
            }
        });

        this.charts.disease.update();
    }
}






const appointmentData = [
    { date: '2026-01-15', status: 'scheduled' },
    { date: '2026-01-20', status: 'completed' },
    
];

const inventoryData = [
    { name: 'Paracetamol', currentStock: 45, maxStock: 100 },
    { name: 'Amoxicillin', currentStock: 12, maxStock: 100 },
    { name: 'Ibuprofen', currentStock: 78, maxStock: 100 },
    
];

const diseaseSurveillanceData = [
    { date: '2026-01-10', symptom: 'Fever' },
    { date: '2026-01-11', symptom: 'Cough' },
    { date: '2026-01-15', symptom: 'Dengue' },
    
];


document.addEventListener('DOMContentLoaded', () => {
    new AnalyticsDashboard(appointmentData, inventoryData, diseaseSurveillanceData);
});
```



```javascript



const CACHE_NAME = 'scancare-v1.0.0';
const ASSETS_TO_CACHE = [
    '/',
    '/index.html',
    '/dashboard.php',
    '/css/styles.css',
    '/css/tailwind.css',
    '/js/app.js',
    '/js/analytics.js',
    '/js/pwa-utilities.js',
    '/images/SClogo.png',
    '/images/icon-192x192.png',
    '/images/icon-512x512.png',
    '/fonts/inter.woff2',
    '/fonts/plus-jakarta-sans.woff2',
    'https:
];


self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('[ServiceWorker] Caching assets...');
            return cache.addAll(ASSETS_TO_CACHE);
        })
    );
    self.skipWaiting(); 
});


self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
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

    
    if (request.method !== 'GET' || url.origin !== location.origin) {
        return;
    }

    
    if (request.destination === 'image' || 
        request.destination === 'style' || 
        request.destination === 'script' ||
        request.destination === 'font') {
        event.respondWith(
            caches.match(request).then((response) => {
                return response || fetch(request).then((fetchResponse) => {
                    
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(request, fetchResponse.clone());
                    });
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
    
    else {
        event.respondWith(
            fetch(request)
                .then((fetchResponse) => {
                    
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(request, fetchResponse.clone());
                    });
                    return fetchResponse;
                })
                .catch(() => {
                    
                    return caches.match(request).then((cachedResponse) => {
                        return cachedResponse || new Response(
                            '<h1>Offline</h1><p>This page is not available offline.</p>',
                            { headers: { 'Content-Type': 'text/html' } }
                        );
                    });
                })
        );
    }
});


self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
```


```html
<!
<script>
    
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/service-worker.js')
                .then((registration) => {
                    console.log('[PWA] Service Worker registered:', registration);
                    
                    
                    setInterval(() => {
                        registration.update();
                    }, 60000); 
                })
                .catch((error) => {
                    console.error('[PWA] Service Worker registration failed:', error);
                });
        });

        
        let refreshing = false;
        navigator.serviceWorker.addEventListener('controllerchange', () => {
            if (refreshing) return;
            refreshing = true;
            window.location.reload();
        });
    }
</script>
```


```javascript


class OfflineIndicator {
    constructor() {
        this.isOnline = navigator.onLine;
        this.banner = null;
        this.init();
    }

    init() {
        
        this.createBanner();
        
        
        window.addEventListener('online', () => this.handleOnline());
        window.addEventListener('offline', () => this.handleOffline());
        
        
        if (!this.isOnline) {
            this.handleOffline();
        }
    }

    
    createBanner() {
        this.banner = document.createElement('div');
        this.banner.id = 'offline-indicator';
        this.banner.className = 'offline-banner hidden';
        this.banner.innerHTML = `
            <div class="offline-banner-content">
                <div class="offline-banner-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="1" y1="1" x2="23" y2="23"></line>
                        <path d="M16.72 4.02a9 9 0 0 1 2.7 9"></path>
                        <path d="M9.6 4.2A9 9 0 0 0 5.64 6.64"></path>
                        <path d="M22.65 13A16.06 16.06 0 0 0 12.82 1.25"></path>
                        <path d="M1 1v8h8"></path>
                    </svg>
                </div>
                <div class="offline-banner-text">
                    <strong>You're Offline</strong>
                    <span>Changes will sync automatically when connection returns</span>
                </div>
                <div class="offline-banner-close">
                    <button onclick="document.getElementById('offline-indicator').classList.add('hidden')">
                        ×
                    </button>
                </div>
            </div>
        `;
        
        document.body.insertBefore(this.banner, document.body.firstChild);
    }

    
    handleOnline() {
        this.isOnline = true;
        if (this.banner) {
            this.banner.classList.add('hidden');
        }
        
        
        this.showNotification('✓ Back online! Syncing changes...', 'success');
        
        
        this.triggerOfflineSyncEvents();
    }

    
    handleOffline() {
        this.isOnline = false;
        if (this.banner) {
            this.banner.classList.remove('hidden');
        }
        
        this.showNotification('⚠ You are offline', 'warning');
    }

    
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.classList.add('show');
        }, 10);

        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    
    triggerOfflineSyncEvents() {
        const event = new CustomEvent('offlineSync');
        window.dispatchEvent(event);
    }
}


document.addEventListener('DOMContentLoaded', () => {
    new OfflineIndicator();
});
```


```css


.offline-banner {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: linear-gradient(135deg, #FF6B6B 0%, #FF8C42 100%);
    color: white;
    padding: 0;
    z-index: 9999;
    box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
    animation: slideDown 0.3s ease;
    font-size: 14px;
}

.offline-banner.hidden {
    display: none;
}

@keyframes slideDown {
    from {
        transform: translateY(-100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.offline-banner-content {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    max-width: 1200px;
    margin: 0 auto;
}

.offline-banner-icon {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.offline-banner-icon svg {
    color: white;
    filter: drop-shadow(0 0 2px rgba(255,255,255,0.3));
}

.offline-banner-text {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.offline-banner-text strong {
    font-weight: 600;
    display: block;
}

.offline-banner-text span {
    font-size: 12px;
    opacity: 0.9;
}

.offline-banner-close button {
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    padding: 0 8px;
    opacity: 0.8;
    transition: opacity 0.2s;
}

.offline-banner-close button:hover {
    opacity: 1;
}



.notification {
    position: fixed;
    bottom: -100px;
    left: 50%;
    transform: translateX(-50%);
    padding: 12px 20px;
    border-radius: 6px;
    background: #333;
    color: white;
    font-size: 14px;
    z-index: 10000;
    transition: bottom 0.3s ease;
}

.notification.show {
    bottom: 20px;
}

.notification-success {
    background: #51CF66;
    color: #1a3a1a;
}

.notification-warning {
    background: #FFD93D;
    color: #3d3d1a;
}

.notification-error {
    background: #FF6B6B;
    color: white;
}

.notification-info {
    background: #454B1B;
    color: white;
}


body.offline {
    padding-top: 60px;
}
```



```javascript


class AccessibilityManager {
    constructor() {
        this.storageKey = 'scancare-accessibility-prefs';
        this.preferences = this.loadPreferences();
        this.init();
    }

    init() {
        this.applyPreferences();
        this.setupToggleButtons();
        this.setupKeyboardShortcuts();
    }

    
    loadPreferences() {
        const saved = localStorage.getItem(this.storageKey);
        return saved ? JSON.parse(saved) : {
            highContrast: false,
            fontSize: 100,
            reduceMotion: false
        };
    }

    
    savePreferences() {
        localStorage.setItem(this.storageKey, JSON.stringify(this.preferences));
    }

    
    applyPreferences() {
        const html = document.documentElement;
        
        
        if (this.preferences.highContrast) {
            html.classList.add('high-contrast-mode');
        } else {
            html.classList.remove('high-contrast-mode');
        }

        
        html.style.fontSize = `${this.preferences.fontSize}%`;

        
        if (this.preferences.reduceMotion) {
            html.classList.add('reduce-motion');
        } else {
            html.classList.remove('reduce-motion');
        }
    }

    
    toggleHighContrast() {
        this.preferences.highContrast = !this.preferences.highContrast;
        this.savePreferences();
        this.applyPreferences();
        
        const status = this.preferences.highContrast ? 'enabled' : 'disabled';
        console.log(`[A11y] High contrast ${status}`);
    }

    
    increaseFontSize() {
        if (this.preferences.fontSize < 150) {
            this.preferences.fontSize += 10;
            this.savePreferences();
            this.applyPreferences();
        }
    }

    
    decreaseFontSize() {
        if (this.preferences.fontSize > 80) {
            this.preferences.fontSize -= 10;
            this.savePreferences();
            this.applyPreferences();
        }
    }

    
    resetAccessibility() {
        this.preferences = {
            highContrast: false,
            fontSize: 100,
            reduceMotion: false
        };
        this.savePreferences();
        this.applyPreferences();
    }

    
    setupToggleButtons() {
        const contrastBtn = document.getElementById('btn-high-contrast');
        const increaseFontBtn = document.getElementById('btn-increase-font');
        const decreaseFontBtn = document.getElementById('btn-decrease-font');
        const resetBtn = document.getElementById('btn-reset-a11y');

        if (contrastBtn) {
            contrastBtn.addEventListener('click', () => this.toggleHighContrast());
            contrastBtn.setAttribute('aria-pressed', this.preferences.highContrast);
        }

        if (increaseFontBtn) {
            increaseFontBtn.addEventListener('click', () => this.increaseFontSize());
        }

        if (decreaseFontBtn) {
            decreaseFontBtn.addEventListener('click', () => this.decreaseFontSize());
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', () => this.resetAccessibility());
        }
    }

    
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            if (!e.altKey) return;

            if (e.key === 'c' || e.key === 'C') {
                e.preventDefault();
                this.toggleHighContrast();
            }

            if (e.key === '+' || e.key === '=') {
                e.preventDefault();
                this.increaseFontSize();
            }

            if (e.key === '-' || e.key === '_') {
                e.preventDefault();
                this.decreaseFontSize();
            }
        });
    }
}


document.addEventListener('DOMContentLoaded', () => {
    new AccessibilityManager();
});
```


```html
<!
<div id="accessibility-panel" class="accessibility-panel">
    <button id="btn-accessibility-toggle" 
            class="btn-accessibility-toggle" 
            aria-label="Open accessibility settings"
            title="Accessibility Settings (Alt + A)">
        ♿
    </button>
    
    <div id="accessibility-menu" class="accessibility-menu hidden" role="region" aria-label="Accessibility Controls">
        <div class="accessibility-menu-header">
            <h3>Accessibility Settings</h3>
            <button id="btn-close-a11y" 
                    class="btn-close" 
                    aria-label="Close accessibility menu">
                ×
            </button>
        </div>
        
        <div class="accessibility-controls">
            <!
            <div class="control-group">
                <label>
                    <input type="checkbox" 
                           id="btn-high-contrast" 
                           aria-label="Toggle high contrast mode">
                    <span>High Contrast Mode</span>
                </label>
                <small>Makes text and elements more distinguishable</small>
            </div>

            <!
            <div class="control-group">
                <label>Text Size:</label>
                <div class="font-size-controls">
                    <button id="btn-decrease-font" 
                            aria-label="Decrease text size">
                        A−
                    </button>
                    <span id="font-size-display" class="font-size-display">100%</span>
                    <button id="btn-increase-font" 
                            aria-label="Increase text size">
                        A+
                    </button>
                </div>
            </div>

            <!
            <div class="control-group">
                <button id="btn-reset-a11y" 
                        class="btn-reset" 
                        aria-label="Reset all accessibility settings to defaults">
                    Reset to Defaults
                </button>
            </div>
        </div>

        <div class="accessibility-menu-footer">
            <small>Keyboard Shortcuts:<br>
                   Alt + C = High Contrast<br>
                   Alt + + = Bigger Text<br>
                   Alt + − = Smaller Text
            </small>
        </div>
    </div>
</div>

<script>
    
    const accessibilityToggle = document.getElementById('btn-accessibility-toggle');
    const accessibilityMenu = document.getElementById('accessibility-menu');
    const closeAccessibilityBtn = document.getElementById('btn-close-a11y');
    const fontSizeDisplay = document.getElementById('font-size-display');

    if (accessibilityToggle) {
        accessibilityToggle.addEventListener('click', () => {
            accessibilityMenu.classList.toggle('hidden');
        });
    }

    if (closeAccessibilityBtn) {
        closeAccessibilityBtn.addEventListener('click', () => {
            accessibilityMenu.classList.add('hidden');
        });
    }

    
    setInterval(() => {
        const currentSize = parseInt(document.documentElement.style.fontSize || '100');
        fontSizeDisplay.textContent = currentSize + '%';
    }, 100);
</script>
```


```css


html.high-contrast-mode {
    
    
    
    
    
    
}

html.high-contrast-mode body {
    background-color: var(
    color: var(
}

html.high-contrast-mode * {
    border-color: var(
    color: var(
    background-color: var(
}

html.high-contrast-mode button,
html.high-contrast-mode a {
    outline: 2px solid var(
    outline-offset: 2px;
}

html.high-contrast-mode input,
html.high-contrast-mode textarea,
html.high-contrast-mode select {
    background-color: #1a1a1a !important;
    color: #ffff00 !important;
    border: 2px solid #ffff00 !important;
}



html.reduce-motion * {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
}



.accessibility-panel {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
}

.btn-accessibility-toggle {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: #454B1B;
    color: #fff;
    border: none;
    font-size: 24px;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease;
}

.btn-accessibility-toggle:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    transform: scale(1.1);
}

.btn-accessibility-toggle:focus {
    outline: 3px solid #ffff00;
    outline-offset: 2px;
}

.accessibility-menu {
    position: absolute;
    bottom: 70px;
    right: 0;
    background: white;
    border: 2px solid #454B1B;
    border-radius: 8px;
    padding: 16px;
    width: 280px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
    animation: slideUp 0.3s ease;
}

.accessibility-menu.hidden {
    display: none;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.accessibility-menu-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    border-bottom: 1px solid #e0e0e0;
    padding-bottom: 12px;
}

.accessibility-menu-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.btn-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
}

.accessibility-controls {
    margin-bottom: 16px;
}

.control-group {
    margin-bottom: 16px;
}

.control-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    font-size: 14px;
}

.control-group small {
    display: block;
    color: #666;
    font-size: 12px;
    margin-top: 4px;
}

.control-group input[type="checkbox"] {
    margin-right: 8px;
}

.font-size-controls {
    display: flex;
    align-items: center;
    gap: 8px;
}

.font-size-controls button {
    padding: 6px 12px;
    background: #454B1B;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
}

.font-size-controls button:hover {
    background: #3a3e16;
}

.font-size-display {
    text-align: center;
    min-width: 50px;
    font-weight: 600;
}

.btn-reset {
    width: 100%;
    padding: 10px;
    background: #454B1B;
    color: white;
    border: none;
    border-radius: 4px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-reset:hover {
    background: #3a3e16;
}

.accessibility-menu-footer {
    border-top: 1px solid #e0e0e0;
    padding-top: 12px;
    font-size: 12px;
    color: #666;
}



:focus-visible {
    outline: 3px solid #454B1B;
    outline-offset: 2px;
}

button:focus-visible,
a:focus-visible,
input:focus-visible {
    outline: 3px solid #454B1B;
    outline-offset: 2px;
}



.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border-width: 0;
}


.skip-link {
    position: absolute;
    top: -40px;
    left: 0;
    background: #000;
    color: white;
    padding: 8px;
    z-index: 100;
}

.skip-link:focus {
    top: 0;
}
```


```html
<!
<form id="appointment-form" aria-label="Schedule appointment">
    
    <fieldset>
        <legend>Patient Information</legend>
        
        <!
        <div class="form-group">
            <label for="patient-name">
                Full Name
                <span class="required" aria-label="required">*</span>
            </label>
            <input 
                id="patient-name"
                type="text" 
                name="full_name"
                required
                aria-required="true"
                aria-describedby="patient-name-hint"
                placeholder="Enter full name"
            >
            <small id="patient-name-hint">As registered in the health center</small>
        </div>

        <!
        <div class="form-group">
            <label for="appointment-date">
                Appointment Date
                <span class="required" aria-label="required">*</span>
            </label>
            <input 
                id="appointment-date"
                type="date" 
                name="appointment_date"
                required
                aria-required="true"
                aria-describedby="date-hint"
                min="{{ today }}"
            >
            <small id="date-hint">Select available date</small>
        </div>

        <!
        <div class="form-group">
            <label for="appointment-time">
                Preferred Time
                <span class="required" aria-label="required">*</span>
            </label>
            <select 
                id="appointment-time"
                name="appointment_time"
                required
                aria-required="true"
                aria-describedby="time-hint"
            >
                <option value="">
                <option value="08:00">8:00 AM</option>
                <option value="09:00">9:00 AM</option>
                <option value="10:00">10:00 AM</option>
                <option value="14:00">2:00 PM</option>
                <option value="15:00">3:00 PM</option>
            </select>
            <small id="time-hint">Operating hours: 8 AM - 5 PM</small>
        </div>

        <!
        <div class="form-group">
            <label for="visit-reason">
                Reason for Visit
                <span class="required" aria-label="required">*</span>
            </label>
            <textarea 
                id="visit-reason"
                name="visit_reason"
                required
                aria-required="true"
                aria-describedby="reason-hint"
                rows="4"
                placeholder="Describe your health concern"
            ></textarea>
            <small id="reason-hint">Be as detailed as possible to help our health workers prepare</small>
        </div>

        <!
        <div class="form-group">
            <label for="appointment-confirm">
                <input 
                    id="appointment-confirm"
                    type="checkbox" 
                    name="confirm"
                    required
                    aria-required="true"
                    aria-describedby="confirm-hint"
                >
                I confirm the information above is accurate
            </label>
            <small id="confirm-hint">Please ensure all details are correct before submitting</small>
        </div>
    </fieldset>

    <!
    <div id="form-errors" role="alert" aria-live="assertive" class="error-summary hidden">
        <h3>Please correct the following errors:</h3>
        <ul id="error-list"></ul>
    </div>

    <!
    <div class="form-actions">
        <button 
            type="submit" 
            class="btn-primary"
            aria-label="Submit appointment form"
        >
            Schedule Appointment
        </button>
        <button 
            type="reset" 
            class="btn-secondary"
            aria-label="Clear form and start over"
        >
            Clear
        </button>
    </div>
</form>

<script>
    
    document.getElementById('appointment-form')?.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const errors = [];
        const form = e.target;
        
        
        form.querySelectorAll('[required]').forEach(field => {
            if (!field.value.trim()) {
                errors.push(`${field.previousElementSibling?.textContent || field.name} is required`);
            }
        });

        const errorContainer = document.getElementById('form-errors');
        const errorList = document.getElementById('error-list');

        if (errors.length > 0) {
            
            errorList.innerHTML = errors.map(err => `<li>${err}</li>`).join('');
            errorContainer.classList.remove('hidden');
            errorContainer.focus();
        } else {
            
            errorContainer.classList.add('hidden');
            form.submit();
        }
    });
</script>
```


```javascript

class TextToSpeechHelper {
    constructor() {
        this.synth = window.speechSynthesis;
        this.setupControls();
    }

    setupControls() {
        const labels = document.querySelectorAll('label, h1, h2, h3, p, .appointment-card-title');
        labels.forEach(element => {
            element.addEventListener('dblclick', () => {
                this.speak(element.textContent);
            });
        });
    }

    speak(text) {
        
        this.synth.cancel();

        const utterance = new SpeechSynthesisUtterance(text);
        utterance.rate = 1.0;
        utterance.pitch = 1.0;
        utterance.volume = 1.0;

        this.synth.speak(utterance);
    }

    stop() {
        this.synth.cancel();
    }
}





```


- [ ] Add CSS media queries for mobile-first responsive design
- [ ] Implement SkeletonLoader class in dashboard data loading
- [ ] Add micro-interaction CSS and FormInteractionManager to pages
- [ ] Include Chart.js library and initialize AnalyticsDashboard
- [ ] Register service-worker.js for offline PWA support
- [ ] Add offline-indicator.js and monitor connection status
- [ ] Initialize AccessibilityManager on all pages
- [ ] Add accessibility panel HTML to navbar/layout
- [ ] Include ARIA attributes on all form inputs
- [ ] Test with screen readers (NVDA, JAWS, VoiceOver)
- [ ] Test responsive design on mobile devices (320px, 480px, 768px)
- [ ] Verify all keyboard navigation works
- [ ] Check color contrast ratios (WCAG AA minimum)


