<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Automated Registries | ScanCare</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'sans-serif'],
          },
          colors: {
            brand: {
              olive: '#4C5821',
              'olive-dark': '#384218',
              mint: '#10B981',
              'mint-light': '#E6F4EA',
              dark: '#0F172A',
              muted: '#475569',
              light: '#F8FAF9'
            }
          }
        }
      }
    }
  </script>
  <style>
    .bg-graph-grid {
      background-color: #F8FAF9;
      background-image: 
        linear-gradient(to right, rgba(0, 0, 0, 0.04) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(0, 0, 0, 0.04) 1px, transparent 1px);
      background-size: 24px 24px;
    }

    /* Animated Dotted Wave Pattern */
    .dotted-wave-pattern {
      background-image: radial-gradient(rgba(255, 255, 255, 0.35) 1.5px, transparent 1.5px);
      background-size: 18px 18px;
      animation: dotWave 8s linear infinite;
      mask-image: linear-gradient(to left, rgba(0,0,0,1) 40%, rgba(0,0,0,0) 100%);
      -webkit-mask-image: linear-gradient(to left, rgba(0,0,0,1) 40%, rgba(0,0,0,0) 100%);
    }

    @keyframes dotWave {
      0% {
        background-position: 0 0;
      }
      50% {
        background-position: 36px -18px;
      }
      100% {
        background-position: 0 -36px;
      }
    }
  </style>
</head>
<body class="bg-graph-grid font-sans text-brand-dark antialiased min-h-screen">

  <!-- Header Navigation -->
  <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
      <a href="landing.php" class="flex items-center gap-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-olive focus:ring-offset-2" aria-label="ScanCare home">
        <img src="images/SClogo.png" alt="ScanCare Logo" class="h-10 w-10 object-contain">
        <span class="font-bold text-xl tracking-tight text-brand-dark">ScanCare</span>
      </a>
      <a href="landing.php" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-semibold bg-gray-100 hover:bg-gray-200 text-brand-dark transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-brand-olive focus:ring-offset-2" aria-label="Go to ScanCare home">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10.5 12 3l9 7.5M5 9.5V21h14V9.5M9 21v-6h6v6"></path></svg>
        Home
      </a>
    </div>
  </header>

  <!-- Full-Width Elevated Olive Hero Section -->
  <section class="relative w-full bg-brand-olive text-white py-16 md:py-20 shadow-xl overflow-hidden border-b border-brand-olive-dark">
    
    <!-- Animated Dotted Wave Overlay -->
    <div class="absolute inset-y-0 right-0 w-full md:w-2/3 pointer-events-none dotted-wave-pattern opacity-70"></div>
    
    <!-- Ambient Soft Glow -->
    <div class="absolute -right-20 -top-20 w-96 h-96 bg-brand-mint/20 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-6 relative z-10">
      <div class="max-w-3xl space-y-5">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/10 text-brand-mint-light text-xs font-semibold uppercase tracking-wider backdrop-blur-md border border-white/15 shadow-sm">
          <span class="w-2.5 h-2.5 rounded-full bg-brand-mint animate-pulse"></span>
          System Modernization
        </div>
        
        <h1 class="text-3xl md:text-5xl font-extrabold tracking-tight leading-tight">
          Automated Registries & Resource Management
        </h1>
        
        <p class="text-gray-200 text-base md:text-lg leading-relaxed max-w-2xl font-normal">
          Transforming community health operations from slow, error-prone paper logbooks into an instant, auditable digital ecosystem.
        </p>
      </div>
    </div>
  </section>

  <main class="max-w-7xl mx-auto px-6 py-12 space-y-16">

    <!-- Key Metrics Highlight Bar -->
    <section class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 -mt-8 relative z-20">
      <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-md hover:shadow-lg transition-all duration-200">
        <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Lookup Speed</div>
        <div class="text-3xl font-extrabold text-brand-dark mb-1">&lt; 1 sec</div>
        <span class="inline-block text-xs font-semibold text-brand-olive bg-brand-mint-light/60 px-2.5 py-0.5 rounded-full">QR & Instant Query</span>
      </div>

      <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-md hover:shadow-lg transition-all duration-200">
        <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Record Accuracy</div>
        <div class="text-3xl font-extrabold text-brand-dark mb-1">100%</div>
        <span class="inline-block text-xs font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full">Zero Illegibility</span>
      </div>

      <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-md hover:shadow-lg transition-all duration-200">
        <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Auditability</div>
        <div class="text-3xl font-extrabold text-brand-dark mb-1">Automated</div>
        <span class="inline-block text-xs font-semibold text-brand-olive bg-brand-mint-light/60 px-2.5 py-0.5 rounded-full">Timestamped Logs</span>
      </div>

      <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-md hover:shadow-lg transition-all duration-200">
        <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Security Control</div>
        <div class="text-3xl font-extrabold text-brand-dark mb-1">Encrypted</div>
        <span class="inline-block text-xs font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full">Role-Based Access</span>
      </div>
    </section>

    <!-- Operational Efficiency Pillars -->
    <section class="space-y-8">
      <div class="text-center max-w-2xl mx-auto space-y-2">
        <span class="text-xs font-bold text-brand-olive uppercase tracking-widest">Operational Efficiency Pillars</span>
        <h2 class="text-2xl md:text-3xl font-bold tracking-tight">Core System Transformation</h2>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:-translate-y-1 transition-all duration-200 flex flex-col justify-between">
          <div class="space-y-4">
            <div class="w-10 h-10 rounded-xl bg-brand-mint-light text-brand-olive flex items-center justify-center font-extrabold text-sm">01</div>
            <h3 class="text-lg font-bold text-brand-dark">Instant Access & Retrieval</h3>
            <p class="text-sm text-brand-muted leading-relaxed">
              Eliminates manual binder searches with instant QR verification and digital searches, cutting wait times from minutes to milliseconds.
            </p>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:-translate-y-1 transition-all duration-200 flex flex-col justify-between">
          <div class="space-y-4">
            <div class="w-10 h-10 rounded-xl bg-brand-mint-light text-brand-olive flex items-center justify-center font-extrabold text-sm">02</div>
            <h3 class="text-lg font-bold text-brand-dark">Error & Duplication Control</h3>
            <p class="text-sm text-brand-muted leading-relaxed">
              Standardized input forms prevent missing pages, duplicate profiles, and unreadable handwriting across all barangay health centers.
            </p>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:-translate-y-1 transition-all duration-200 flex flex-col justify-between">
          <div class="space-y-4">
            <div class="w-10 h-10 rounded-xl bg-brand-mint-light text-brand-olive flex items-center justify-center font-extrabold text-sm">03</div>
            <h3 class="text-lg font-bold text-brand-dark">Real-Time Resource Visibility</h3>
            <p class="text-sm text-brand-muted leading-relaxed">
              Tracks medical inventory and supplies dynamically, triggering automated stock alerts before critical shortages occur.
            </p>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:-translate-y-1 transition-all duration-200 flex flex-col justify-between">
          <div class="space-y-4">
            <div class="w-10 h-10 rounded-xl bg-brand-mint-light text-brand-olive flex items-center justify-center font-extrabold text-sm">04</div>
            <h3 class="text-lg font-bold text-brand-dark">Automated Audit Trails</h3>
            <p class="text-sm text-brand-muted leading-relaxed">
              Logs every action, timestamp, and worker interaction into an auditable ledger automatically.
            </p>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:-translate-y-1 transition-all duration-200 flex flex-col justify-between">
          <div class="space-y-4">
            <div class="w-10 h-10 rounded-xl bg-brand-mint-light text-brand-olive flex items-center justify-center font-extrabold text-sm">05</div>
            <h3 class="text-lg font-bold text-brand-dark">Data Security & Loss Prevention</h3>
            <p class="text-sm text-brand-muted leading-relaxed">
              Protects sensitive records against physical loss, damage, or unauthorized access with role-based encryption.
            </p>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:-translate-y-1 transition-all duration-200 flex flex-col justify-between">
          <div class="space-y-4">
            <div class="w-10 h-10 rounded-xl bg-brand-mint-light text-brand-olive flex items-center justify-center font-extrabold text-sm">06</div>
            <h3 class="text-lg font-bold text-brand-dark">Analytics & Outbreak Tracking</h3>
            <p class="text-sm text-brand-muted leading-relaxed">
              Aggregates health trends from digital registries to instantly analyze disease incidence and supply consumption.
            </p>
          </div>
        </div>

      </div>
    </section>

    <!-- Side-by-Side Comparison Matrix -->
    <section class="bg-white rounded-3xl p-8 md:p-10 border border-gray-100 shadow-sm space-y-8">
      <div>
        <span class="text-xs font-bold text-brand-olive uppercase tracking-widest">Comparative System Analysis</span>
        <h2 class="text-2xl font-bold text-brand-dark">Legacy Paper Logbooks vs. Automated Digital Registry</h2>
        <p class="text-sm text-brand-muted mt-1">Evaluating operational capabilities between legacy paper logbooks and automated digital registries.</p>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse min-w-[600px]">
          <thead>
            <tr class="border-b border-gray-100 text-xs font-bold uppercase text-gray-400 tracking-wider">
              <th class="py-4 px-4 w-1/3">Feature / Dimension</th>
              <th class="py-4 px-4 w-1/3 text-rose-600">Legacy Paper Logbooks</th>
              <th class="py-4 px-4 w-1/3 text-brand-olive">Automated Digital Registry</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 text-sm">
            <tr class="hover:bg-gray-50/50">
              <td class="py-4 px-4 font-semibold text-brand-dark">Record Lookup Time</td>
              <td class="py-4 px-4 text-gray-500">3 – 10 minutes per patient</td>
              <td class="py-4 px-4 font-semibold text-emerald-700 bg-emerald-50/60 rounded-lg">&lt; 1 second via digital search / QR</td>
            </tr>
            <tr class="hover:bg-gray-50/50">
              <td class="py-4 px-4 font-semibold text-brand-dark">Data Integrity</td>
              <td class="py-4 px-4 text-gray-500">Prone to illegible writing and missing pages</td>
              <td class="py-4 px-4 font-semibold text-emerald-700 bg-emerald-50/60 rounded-lg">Validation rules and complete history</td>
            </tr>
            <tr class="hover:bg-gray-50/50">
              <td class="py-4 px-4 font-semibold text-brand-dark">Inventory Management</td>
              <td class="py-4 px-4 text-gray-500">Manual periodic tallying and delayed stockouts</td>
              <td class="py-4 px-4 font-semibold text-emerald-700 bg-emerald-50/60 rounded-lg">Automated tracking with low-stock alerts</td>
            </tr>
            <tr class="hover:bg-gray-50/50">
              <td class="py-4 px-4 font-semibold text-brand-dark">Security & Privacy</td>
              <td class="py-4 px-4 text-gray-500">Unrestricted physical access when left open</td>
              <td class="py-4 px-4 font-semibold text-emerald-700 bg-emerald-50/60 rounded-lg">Role-based authentication and encryption</td>
            </tr>
            <tr class="hover:bg-gray-50/50">
              <td class="py-4 px-4 font-semibold text-brand-dark">Reporting Speed</td>
              <td class="py-4 px-4 text-gray-500">Weeks of manual tallying for monthly reports</td>
              <td class="py-4 px-4 font-semibold text-emerald-700 bg-emerald-50/60 rounded-lg">Instant reports and disease trend analysis</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

  </main>

  <footer class="w-full bg-slate-900 text-white py-12 border-t border-slate-800">
    <div class="max-w-7xl mx-auto px-6 lg:px-12">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
        <div><h3 class="font-heading font-bold text-lg mb-4">ScanCare</h3><p class="text-slate-400 text-sm">Healthcare made simple for Barangay Gutad</p></div>
        <div><h4 class="font-semibold mb-4">Services</h4><ul class="space-y-2 text-sm text-slate-400"><li><a href="appointments.php" class="hover:text-white transition">Appointments</a></li><li><a href="system-goals.php" class="hover:text-white transition">System Goals</a></li><li><a href="service-areas.php" class="hover:text-white transition">Service Areas</a></li></ul></div>
        <div><h4 class="font-semibold mb-4">Legal</h4><ul class="space-y-2 text-sm text-slate-400"><li><a href="compliance.php" class="hover:text-white transition">Privacy Policy & Terms of Service</a></li></ul></div>
        <div><h4 class="font-semibold mb-4">Contact</h4><p class="text-slate-400 text-sm">Floridablanca RHU II - GUTAD<br>Email: floridablancarhu2@gmail.com<br>Contact: 0948 056 6872</p></div>
      </div>
      <div class="border-t border-slate-800 pt-8 text-center text-slate-400 text-sm"><p>&copy; 2026 ScanCare. All rights reserved. Your data is encrypted and secure.</p></div>
    </div>
  </footer>

</body>
</html>