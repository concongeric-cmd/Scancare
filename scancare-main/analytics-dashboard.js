

class AnalyticsDashboard {
    constructor(appointmentData = [], inventoryData = [], diseaseSurveillanceData = []) {
        this.appointmentData = appointmentData;
        this.inventoryData = inventoryData;
        this.diseaseSurveillanceData = diseaseSurveillanceData;
        
        this.charts = {};
        
        
        if (typeof Chart !== 'undefined') {
            this.init();
        } else {
            console.warn('[Analytics] Chart.js not loaded. Skipping initialization.');
        }
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
                    <div class="gauge-label">${this.escapeHtml(item.name)}</div>
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
                const diseaseType = record.symptom.toLowerCase().replace(/[^a-z]/g, '');
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

        const diseaseMap = {
            'fever': 'Fever',
            'cough': 'Cough/Cold',
            'dengue': 'Dengue',
            'diarrhea': 'Diarrhea'
        };

        this.charts.disease.data.datasets.forEach((dataset, index) => {
            if (disease === 'all') {
                this.charts.disease.getDatasetMeta(index).hidden = false;
            } else if (diseaseMap[disease] === dataset.label) {
                this.charts.disease.getDatasetMeta(index).hidden = false;
            } else {
                this.charts.disease.getDatasetMeta(index).hidden = true;
            }
        });

        this.charts.disease.update();
    }

    
    updateAppointmentData(data) {
        this.appointmentData = data;
        this.initAppointmentVolumeChart();
    }

    
    updateInventoryData(data) {
        this.inventoryData = data;
        this.initInventoryGauges();
    }

    
    updateDiseaseData(data) {
        this.diseaseSurveillanceData = data;
        this.initDiseaseSurveillanceChart();
    }

    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    
    destroy() {
        Object.values(this.charts).forEach(chart => {
            if (chart) chart.destroy();
        });
    }
}


if (typeof window.analyticsData !== 'undefined' && document.readyState !== 'loading') {
    window.analyticsDashboard = new AnalyticsDashboard(
        window.analyticsData.appointments || [],
        window.analyticsData.inventory || [],
        window.analyticsData.diseases || []
    );
}


document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.analyticsData !== 'undefined' && !window.analyticsDashboard) {
        window.analyticsDashboard = new AnalyticsDashboard(
            window.analyticsData.appointments || [],
            window.analyticsData.inventory || [],
            window.analyticsData.diseases || []
        );
    }
});

