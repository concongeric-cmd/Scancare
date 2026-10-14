



```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ScanCare - Barangay Health Information System</title>
    
    <!
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https:
</head>
<body>
    <!
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/service-worker.js')
                    .then((registration) => {
                        console.log('[PWA] Service Worker registered');
                    })
                    .catch((error) => {
                        console.error('[PWA] Service Worker registration failed:', error);
                    });
            });
        }
    </script>

    <!
    <!

    <!
    <main>
        <!
    </main>

    <!
         SCRIPT INCLUDES - ADD THESE AT BOTTOM
         ============================================ 
    
    <!
    <script src="/offline-indicator.js"></script>

    <!
    <script src="/accessibility-manager.js"></script>

    <!
    <script src="https:

    <!
    <script src="/analytics-dashboard.js"></script>

    <!
    <script src="js/app.js"></script>
</body>
</html>
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
```


```html
<!

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
<script>
    
    window.analyticsData = {
        appointments: <?php 
            echo json_encode([
                ['date' => '2026-01-15', 'status' => 'scheduled'],
                ['date' => '2026-01-20', 'status' => 'completed'],
                
            ]);
        ?>,
        inventory: <?php
            echo json_encode([
                ['name' => 'Paracetamol', 'currentStock' => 45, 'maxStock' => 100],
                ['name' => 'Amoxicillin', 'currentStock' => 12, 'maxStock' => 100],
                ['name' => 'Ibuprofen', 'currentStock' => 78, 'maxStock' => 100],
                
            ]);
        ?>,
        diseases: <?php
            echo json_encode([
                ['date' => '2026-01-10', 'symptom' => 'Fever'],
                ['date' => '2026-01-11', 'symptom' => 'Cough'],
                ['date' => '2026-01-15', 'symptom' => 'Dengue'],
                
            ]);
        ?>
    };
</script>
```


```html
<!
<div id="skeleton-loader" class="skeleton-container">
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

<!
<div id="appointment-list" class="appointment-list">
    <!
</div>

<script>
    
    const skeletonLoader = new SkeletonLoader('#skeleton-loader', '#appointment-list');

    
    skeletonLoader.show();

    
    setTimeout(() => {
        
        document.getElementById('appointment-list').innerHTML = '...your appointments...';
        
        
        skeletonLoader.hide();
    }, 2000);
</script>
```


```html
<!
<form id="appointment-form" aria-label="Schedule appointment">
    
    <fieldset>
        <legend>Patient Information</legend>
        
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
            >
            <small id="date-hint">Select available date</small>
        </div>

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
    </fieldset>

    <div class="form-actions">
        <button type="submit" class="btn-primary" aria-label="Submit appointment form">
            Schedule Appointment
        </button>
        <button type="reset" class="btn-secondary" aria-label="Clear form and start over">
            Clear
        </button>
    </div>
</form>
```


Add to your `<head>` section:

```html
<style>



</style>
```

Or better - create separate CSS files and link them:

```html
<!
<link rel="stylesheet" href="/css/responsive.css">

<!
<link rel="stylesheet" href="/css/interactions.css">

<!
<link rel="stylesheet" href="/css/analytics.css">

<!
<link rel="stylesheet" href="/css/accessibility.css">
```


- [ ] Test on 320px viewport (phone)
- [ ] Test on 768px viewport (tablet)
- [ ] Test on 1024px+ (desktop)
- [ ] Check touch targets are ≥48px
- [ ] Verify text is readable on all sizes

- [ ] Test with screen reader (NVDA/JAWS/VoiceOver)
- [ ] Tab through all interactive elements
- [ ] Test keyboard shortcuts (Alt+C, Alt++, Alt+-)
- [ ] Verify color contrast (WCAG AA minimum)
- [ ] Toggle high contrast mode

- [ ] Open DevTools → Network → Go Offline
- [ ] Verify offline banner appears
- [ ] Refresh page - should still load
- [ ] Go back Online - banner disappears
- [ ] Check service worker in DevTools → Application

- [ ] Charts render with data
- [ ] Filters work (year, disease type)
- [ ] Inventory gauges show correct colors
- [ ] Responsive on mobile

- [ ] Button hover states work
- [ ] Form focus states work
- [ ] Ripple effects visible on click
- [ ] Dropdown transitions smooth


| File | Purpose | Location |
|
| `service-worker.js` | PWA offline caching | `/` (root) |
| `offline-indicator.js` | Connection status UI | `/` (root) |
| `accessibility-manager.js` | A11y features | `/` (root) |
| `analytics-dashboard.js` | Client-side charts | `/` (root) |
| `frontend-enhancements.md` | Complete documentation | `/` (root) |


1. **Minify JavaScript files** (optional but recommended)
2. **Enable GZIP compression** on your server
3. **Set Cache headers** for service worker
4. **Test on 3G/4G** mobile connection
5. **Verify offline functionality** on mobile devices
6. **Monitor Core Web Vitals** with Lighthouse


**Solution**: Ensure `/service-worker.js` is at root and HTTPS is used (or localhost)

**Solution**: Ensure `offline-indicator.js` is loaded before closing `</body>`

**Solution**: Verify Chart.js is loaded AND data is passed to `window.analyticsData`

**Solution**: Check that accessibility controls are in HTML and CSS is loaded

**Solution**: Ensure high-contrast CSS is using `!important` flags


