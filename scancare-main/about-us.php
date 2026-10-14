<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us — ScanCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="theme-state.js"></script>
    <link rel="stylesheet" href="global-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap');
        
        .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-body { font-family: 'Inter', sans-serif; }

        
        .sc-nav-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 80rem;
            margin: 0 auto;
            width: 100%;
        }

        
        .nav-links-desktop {
            display: none !important;
        }
        .nav-hamburger-btn {
            display: block !important;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 24px;
            padding: 8px;
        }

        
        @media (min-width: 768px) {
            .nav-links-desktop {
                display: flex !important;
                align-items: center;
                gap: 24px;
                font-weight: 600;
                font-size: 14px;
                color: #475569;
            }
            .nav-hamburger-btn {
                display: none !important;
            }

                display: none !important;
            }
        }
    </style>
</head>
<body class="bg-[#F8FAF6] text-slate-800 font-body antialiased min-h-screen selection:bg-[#454B1B] selection:text-white">

    <nav class="border-b border-[#454B1B]/10 sticky top-0 w-full bg-white/90 backdrop-blur-md z-50 shadow-sm">
        <div class="max-w-7xl mx-auto h-20 px-6 lg:px-12 flex items-center justify-between">
            <a href="landing.php" class="flex items-center gap-1 hover:opacity-80 transition-opacity">
                <img src="images/SClogo.png" alt="ScanCare Logo" class="h-14 w-14 object-contain">
                <div>
                    <span class="text-2xl font-heading font-extrabold tracking-tight text-slate-900 block">
                        Scan<span style="color: #454B1B;">Care</span>
                    </span>
                </div>
            </a>
            
            <div class="flex items-center gap-3">
                <a href="dashboard.php" class="inline-flex items-center justify-center gap-2 rounded-full bg-[#454B1B] px-4 py-3 text-sm sm:text-base font-semibold text-white shadow-lg shadow-[#454B1B]/20 transition-all hover:bg-[#363812]">
                    <i class="fas fa-arrow-left"></i>
                    Dashboard
                </a>
                <div class="hidden md:flex items-center gap-4 font-semibold text-sm text-slate-600">
                    <a href="landing.php" class="hover:text-[#454B1B] transition-colors">Home</a>
                    <a href="about-us.php" style="color: #454B1B;" class="font-bold">About Us</a>
                    <a href="login.php" style="background-color: #454B1B;" class="hover:opacity-90 text-white px-5 py-2.5 rounded-xl font-bold transition-all shadow-md shadow-[#454B1B]/10">
                        Login / Signup
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="bg-dark-grid w-full">
        <!
        <section class="py-20 bg-white border-b border-slate-200">
            <div class="max-w-5xl mx-auto px-6 lg:px-12 text-center">
                <div class="mb-6 inline-flex items-center gap-1.5 text-xs font-bold bg-[#454B1B]/10 text-[#454B1B] border border-[#454B1B]/20 px-3 py-1 rounded-full tracking-wide">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#454B1B] animate-pulse"></span>
                    Our Story
                </div>
                <div class="mb-10 flex justify-center">
                    <div class="w-full max-w-xs sm:max-w-sm rounded-3xl bg-[#F1F5EB] border border-[#D8E0C6] p-1 shadow-sm shadow-[#94A3B8]/10">
                        <a href="dashboard.php" class="inline-flex items-center justify-center gap-2 rounded-3xl bg-[#454B1B] px-6 py-4 text-base sm:text-lg font-semibold text-white shadow-lg shadow-[#454B1B]/20 transition-all hover:bg-[#363812] w-full">
                            <i class="fas fa-arrow-left"></i>
                            Go to Dashboard
                        </a>
                    </div>
                </div>
                <h1 class="text-4xl lg:text-5xl font-heading font-extrabold text-slate-900 leading-[1.2] mb-6">
                    Transforming Healthcare in <span style="color: #454B1B;">Barangay Gutad</span>
                </h1>
                <p class="text-lg text-slate-600 max-w-2xl mx-auto leading-relaxed">
                    ScanCare was designed to bridge the gap between traditional paper-based health documentation and modern digital systems, bringing efficiency and accuracy to community healthcare.
                </p>
            </div>
        </section>

        <!
        <section class="py-20 bg-gradient-to-b from-white to-[#F8FAF6]">
            <div class="max-w-5xl mx-auto px-6 lg:px-12">
                <div class="grid md:grid-cols-2 gap-12">
                    <div class="flex flex-col justify-center">
                        <div style="color: #454B1B;" class="text-xs uppercase tracking-widest font-bold mb-3 block">
                            Our Mission
                        </div>
                        <h2 class="text-3xl font-heading font-bold text-slate-900 mb-6 leading-snug">
                            Eliminating healthcare inefficiencies through smart digital solutions
                        </h2>
                        <p class="text-slate-600 leading-relaxed mb-4">
                            We believe that every resident in Barangay Gutad deserves access to organized, efficient, and secure healthcare services. Our mission is to replace slow manual paper processes with instant, reliable digital systems that empower health workers and patients alike.
                        </p>
                        <p class="text-slate-600 leading-relaxed">
                            By providing a unified platform, we reduce wait times, minimize errors, and ensure that critical health information is always accessible when needed most.
                        </p>
                    </div>
                    <div class="flex flex-col justify-center">
                        <div style="color: #454B1B;" class="text-xs uppercase tracking-widest font-bold mb-3 block">
                            Our Vision
                        </div>
                        <h2 class="text-3xl font-heading font-bold text-slate-900 mb-6 leading-snug">
                            A healthier Barangay through transparent, data-driven community care
                        </h2>
                        <p class="text-slate-600 leading-relaxed mb-4">
                            We envision a future where health data flows seamlessly between residents, health workers, and barangay officials. A system where predictive analytics guide resource allocation and community members feel empowered to take charge of their own health.
                        </p>
                        <p class="text-slate-600 leading-relaxed">
                            ScanCare is just the beginning—a foundation for a smarter, more connected healthcare ecosystem.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!
        <section class="py-20 bg-white border-b border-slate-200">
            <div class="max-w-5xl mx-auto px-6 lg:px-12">
                <div class="text-center mb-16">
                    <span style="color: #454B1B;" class="text-xs uppercase tracking-wider font-bold block mb-2">Key Strengths</span>
                    <h2 class="text-3xl lg:text-4xl font-heading font-extrabold text-slate-900">
                        What Makes ScanCare Different
                    </h2>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="flex flex-col">
                        <div style="background-color: #454B1B;" class="w-14 h-14 rounded-xl flex items-center justify-center text-white text-xl mb-4">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <h3 class="text-lg font-heading font-bold text-slate-900 mb-2">Lightning Fast</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">
                            Instant access to patient records, real-time medicine stock updates, and rapid appointment processing.
                        </p>
                    </div>

                    <div class="flex flex-col">
                        <div style="background-color: #454B1B;" class="w-14 h-14 rounded-xl flex items-center justify-center text-white text-xl mb-4">
                            <i class="fas fa-lock"></i>
                        </div>
                        <h3 class="text-lg font-heading font-bold text-slate-900 mb-2">Highly Secure</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">
                            Role-based access control ensures only authorized personnel can view sensitive health information.
                        </p>
                    </div>

                    <div class="flex flex-col">
                        <div style="background-color: #454B1B;" class="w-14 h-14 rounded-xl flex items-center justify-center text-white text-xl mb-4">
                            <i class="fas fa-qrcode"></i>
                        </div>
                        <h3 class="text-lg font-heading font-bold text-slate-900 mb-2">QR-Based</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">
                            Unique QR codes for each patient enable quick, error-free clinic check-ins and record lookups.
                        </p>
                    </div>

                    <div class="flex flex-col">
                        <div style="background-color: #454B1B;" class="w-14 h-14 rounded-xl flex items-center justify-center text-white text-xl mb-4">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="text-lg font-heading font-bold text-slate-900 mb-2">Data-Driven</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">
                            Real-time analytics and reports help officials make informed decisions about health resource allocation.
                        </p>
                    </div>

                    <div class="flex flex-col">
                        <div style="background-color: #454B1B;" class="w-14 h-14 rounded-xl flex items-center justify-center text-white text-xl mb-4">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3 class="text-lg font-heading font-bold text-slate-900 mb-2">Responsive Design</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">
                            Works seamlessly on desktop, tablet, and mobile devices for on-the-go access.
                        </p>
                    </div>

                    <div class="flex flex-col">
                        <div style="background-color: #454B1B;" class="w-14 h-14 rounded-xl flex items-center justify-center text-white text-xl mb-4">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h3 class="text-lg font-heading font-bold text-slate-900 mb-2">User-Centric</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">
                            Designed with input from health workers and residents to ensure practical, real-world usability.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!
        <section class="py-20 bg-gradient-to-b from-[#F8FAF6] to-white">
            <div class="max-w-5xl mx-auto px-6 lg:px-12 text-center">
                <span style="color: #454B1B;" class="text-xs uppercase tracking-wider font-bold block mb-2">Development Team</span>
                <h2 class="text-3xl lg:text-4xl font-heading font-extrabold text-slate-900 mb-6">
                    Built for Community, by Community
                </h2>
                <p class="text-slate-600 text-lg max-w-2xl mx-auto leading-relaxed mb-12">
                    ScanCare was developed in close collaboration with the Barangay Gutad Health Office to address real challenges faced by healthcare workers and residents.
                </p>

                <div class="bg-white border border-slate-200 rounded-2xl p-8 text-center">
                    <div style="color: #454B1B;" class="text-2xl font-heading font-bold mb-2">
                        Barangay Gutad Health Office
                    </div>
                    <p class="text-slate-600">
                        Partnering to bring modern healthcare solutions to our community
                    </p>
                </div>
            </div>
        </section>

        <!
        <section class="py-20 bg-white border-t border-slate-200">
            <div class="max-w-5xl mx-auto px-6 lg:px-12 text-center">
                <h2 class="text-3xl lg:text-4xl font-heading font-extrabold text-slate-900 mb-6">
                    Ready to Experience Modern Healthcare?
                </h2>
                <p class="text-slate-600 text-lg max-w-2xl mx-auto leading-relaxed mb-8">
                    Join residents, health workers, and officials already using ScanCare to improve community health outcomes.
                </p>
                <a href="login.php" style="background-color: #454B1B;" class="hover:opacity-95 text-white px-8 py-4 rounded-xl font-semibold transition-all shadow-lg shadow-[#454B1B]/20 inline-flex items-center gap-3 group">
                    Get Started Now
                    <span class="inline-block transition-transform group-hover:translate-x-1">&rarr;</span>
                </a>
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

