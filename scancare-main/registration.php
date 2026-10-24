<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration — ScanCare</title>
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

        .step-indicator {
            display: flex;
            gap: 8px;
            margin-bottom: 2rem;
        }

        .step-dot {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .step-dot.active {
            background-color: #454B1B;
            color: white;
        }

        .step-dot.completed {
            background-color: #10b981;
            color: white;
        }

        .step-dot.inactive {
            background-color: #e5e7eb;
            color: #9ca3af;
        }

        .form-section {
            display: none;
        }

        .form-section.active {
            display: block;
        }

        .validation-warning {
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        input:invalid:not(:placeholder-shown),
        select:invalid:not([value=""]),
        textarea:invalid:not(:placeholder-shown) {
            border-color: #fecaca;
        }
    </style>
</head>
<body class="bg-[#F8FAF6] text-slate-800 font-body antialiased min-h-screen selection:bg-[#454B1B] selection:text-white">

    <!
    <nav class="border-b border-[#454B1B]/10 sticky top-0 w-full bg-white/90 backdrop-blur-md z-50 shadow-sm">
        <div class="sc-nav-container px-6 lg:px-12 py-4 h-20 flex items-center justify-between">
            <!
            <a href="landing.php" class="flex items-center gap-1 hover:opacity-80 transition-opacity flex-shrink-0">
                <img src="images/SClogo.png" alt="ScanCare Logo" class="h-12 w-12 object-contain">
                <span style="font-family: 'Plus Jakarta Sans', sans-serif;" class="text-xl font-extrabold tracking-tight text-slate-900">
                    Scan<span style="color: #454B1B;">Care</span>
                </span>
            </a>

            <!
            <div class="nav-links-desktop">
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'resident'): ?>
                <a href="dashboard.php#schedule-appointment" style="color: inherit;" class="hover:text-[#454B1B] transition-colors">Appointment</a>
                <?php endif; ?>
                <a href="system-goals.php" style="color: inherit;" class="hover:text-[#454B1B] transition-colors">System Goals</a>
                <a href="service-areas.php" style="color: inherit;" class="hover:text-[#454B1B] transition-colors">Service Areas</a>
                <a href="surveillance.php" style="color: inherit;" class="hover:text-[#454B1B] transition-colors">Surveillance</a>
                <a href="compliance.php" style="color: inherit;" class="hover:text-[#454B1B] transition-colors">Compliance</a>
            </div>

            <!
            <div class="nav-links-desktop border-l border-slate-200 pl-6 items-center gap-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div style="color: #454B1B; font-weight: 600;">
                        <?php echo htmlspecialchars(substr($_SESSION['user_name'] ?? 'User', 0, 20)); ?>
                    </div>
                    <a href="logout.php" style="background-color: #454B1B; color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600;">Log Out</a>
                <?php else: ?>
                    <a href="login.php" style="background-color: #454B1B; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600;">Login / Signup</a>
                <?php endif; ?>
            </div>

            <!
            <button id="toggleBtn" class="nav-hamburger-btn" aria-label="Toggle mobile menu">
                <i class="fa-solid fa-bars" style="color: #475569;"></i>
            </button>
        </div>

        <!
        <div id="mobileMenuPanel" style="display: none;" class="border-t border-slate-200 bg-white flex-col px-6 py-4">
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'resident'): ?>
                <a href="dashboard.php#schedule-appointment" style="color: #475569; padding: 12px; text-decoration: none; font-weight: 500;">Appointment</a>
                <?php endif; ?>
                <a href="system-goals.php" style="color: #475569; padding: 12px; text-decoration: none; font-weight: 500;">System Goals</a>
                <a href="service-areas.php" style="color: #475569; padding: 12px; text-decoration: none; font-weight: 500;">Service Areas</a>
                <a href="surveillance.php" style="color: #475569; padding: 12px; text-decoration: none; font-weight: 500;">Surveillance</a>
                <a href="compliance.php" style="color: #475569; padding: 12px; text-decoration: none; font-weight: 500;">Compliance</a>

                <div style="border-top: 1px solid #e2e8f0; margin-top: 12px; padding-top: 12px;">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div style="color: #454B1B; font-weight: 600; margin-bottom: 12px;">
                            <?php echo htmlspecialchars(substr($_SESSION['user_name'] ?? 'User', 0, 20)); ?>
                        </div>
                        <a href="logout.php" style="background-color: #454B1B; color: white; padding: 12px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; display: block; text-align: center;">Log Out</a>
                    <?php else: ?>
                        <a href="login.php" style="background-color: #454B1B; color: white; padding: 12px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; display: block; text-align: center;">Login / Signup</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggleBtn = document.getElementById('toggleBtn');
            const mobileMenuPanel = document.getElementById('mobileMenuPanel');
            if (toggleBtn && mobileMenuPanel) {
                toggleBtn.addEventListener('click', function () {
                    mobileMenuPanel.style.display = mobileMenuPanel.style.display === 'none' ? 'flex' : 'none';
                });
            }
        });
    </script>

    <main class="bg-dark-grid w-full">

        <!
        <section class="relative w-full min-h-[50vh] flex items-center border-b border-[#454B1B]/10 overflow-hidden py-20">
            <div class="absolute inset-0 z-0 bg-[linear-gradient(to_right,#454b1b05_1px,transparent_1px),linear-gradient(to_bottom,#454b1b05_1px,transparent_1px)] bg-[size:24px_24px]"></div>
            
            <div class="w-full mx-auto px-6 lg:px-12 relative z-20 text-center">
                <div class="max-w-3xl mx-auto mb-8 space-y-3">
                    <div class="inline-flex items-center gap-1.5 text-xs font-bold bg-[#454B1B]/10 text-[#454B1B] border border-[#454B1B]/20 px-3 py-1 rounded-full tracking-wide">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#454B1B] animate-pulse"></span>
                        Join ScanCare
                    </div>
                    <h1 class="text-4xl lg:text-5xl font-heading font-extrabold text-slate-900 leading-[1.1] tracking-tight">
                        Create Your Health <span style="color: #454B1B;">Profile</span>
                    </h1>
                    <p class="text-base text-slate-600 font-medium leading-relaxed">
                        Register now to access your digital health identity and receive personalized care at Barangay Gutad Health Center.
                    </p>
                </div>
            </div>
        </section>

        <!
        <section class="py-20 bg-white border-b border-slate-200 relative z-20">
            <div class="max-w-2xl mx-auto px-6 lg:px-12">
                
                <!
                <div class="step-indicator flex justify-between mb-12">
                    <div class="step-dot active" data-step="1">1</div>
                    <div class="step-dot inactive" data-step="2">2</div>
                    <div class="step-dot inactive" data-step="3">3</div>
                    <div class="step-dot inactive" data-step="4">4</div>
                </div>

                <!
                <form id="registrationForm" class="space-y-8">

                    <!
                    <div class="form-section active" data-section="1">
                        <div class="mb-8">
                            <h2 class="text-2xl font-heading font-bold text-slate-900 mb-2">Personal Information</h2>
                            <p class="text-slate-600">Let's start with your basic details.</p>
                        </div>

                        <div class="space-y-6">
                            <div class="grid md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-bold text-slate-900 mb-2">First Name *</label>
                                    <input type="text" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent" placeholder="Juan">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-900 mb-2">Last Name *</label>
                                    <input type="text" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent" placeholder="Dela Cruz">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-900 mb-2">Date of Birth *</label>
                                <input type="date" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent">
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-900 mb-2">Biological Sex *</label>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="sex" value="male" required class="w-4 h-4">
                                        <span class="text-slate-600">Male</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="sex" value="female" required class="w-4 h-4">
                                        <span class="text-slate-600">Female</span>
                                    </label>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-900 mb-2">Civil Status *</label>
                                <select required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent">
                                    <option value="">Select...</option>
                                    <option value="single">Single</option>
                                    <option value="married">Married</option>
                                    <option value="widowed">Widowed</option>
                                    <option value="separated">Separated</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-900 mb-2">Permanent Address *</label>
                                <textarea required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent" rows="3" placeholder="House No., Street, Barangay Gutad"></textarea>
                            </div>
                        </div>
                    </div>

                    <!
                    <div class="form-section" data-section="2">
                        <div class="mb-8">
                            <h2 class="text-2xl font-heading font-bold text-slate-900 mb-2">Contact Information</h2>
                            <p class="text-slate-600">How can we reach you?</p>
                        </div>

                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-bold text-slate-900 mb-2">Mobile Number (Primary) *</label>
                                <input type="tel" required minlength="11" maxlength="11" inputmode="numeric" pattern="[0-9]{11}" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)" id="mobilePrimary" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent" placeholder="09XX XXX XXXX">
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-900 mb-2">Mobile Number (Alternate)</label>
                                <input type="tel" minlength="11" maxlength="11" inputmode="numeric" pattern="[0-9]{11}" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)" id="mobileAlternate" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent" placeholder="09XX XXX XXXX (Optional)">
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-900 mb-2">Email Address</label>
                                <input type="email" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent" placeholder="your.email@example.com (Optional)">
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-900 mb-2">Emergency Contact Name *</label>
                                <input type="text" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent" placeholder="Name of contact">
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-900 mb-2">Emergency Contact Number *</label>
                                <input type="tel" required minlength="11" maxlength="11" inputmode="numeric" pattern="[0-9]{11}" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)" id="emergencyPhone" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent" placeholder="09XX XXX XXXX">
                            </div>
                        </div>
                    </div>

                    <!
                    <div class="form-section" data-section="3">
                        <div class="mb-8">
                            <h2 class="text-2xl font-heading font-bold text-slate-900 mb-2">Household & Community Role</h2>
                            <p class="text-slate-600">Help us serve you better.</p>
                        </div>

                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-bold text-slate-900 mb-2">Which Purok are you from? *</label>
                                <select required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent">
                                    <option value="">Select Purok...</option>
                                    <option value="purok1">Purok 1 (Town Center)</option>
                                    <option value="purok2">Purok 2 (East Residential)</option>
                                    <option value="purok3">Purok 3 (West Agricultural)</option>
                                    <option value="purok4">Purok 4 (Upland/Remote)</option>
                                    <option value="purok5">Purok 5 (Industrial Zone)</option>
                                    <option value="purok6">Purok 6 (Waterfront/Fishing)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-900 mb-2">What is your role in the community? *</label>
                                <select required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent">
                                    <option value="">Select role...</option>
                                    <option value="resident">Regular Resident / Patient</option>
                                    <option value="bhw">Barangay Health Worker (BHW)</option>
                                    <option value="barangay">Barangay Official</option>
                                    <option value="admin">Administrator</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-900 mb-2">Household Size (Number of Family Members) *</label>
                                <input type="number" min="1" max="20" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent" placeholder="5">
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-900 mb-2">Do you have any known medical conditions? (Optional)</label>
                                <div class="space-y-2">
                                    <label class="flex items-start gap-3 cursor-pointer">
                                        <input type="checkbox" value="hypertension" class="w-4 h-4 mt-1">
                                        <span class="text-slate-600">Hypertension (High Blood Pressure)</span>
                                    </label>
                                    <label class="flex items-start gap-3 cursor-pointer">
                                        <input type="checkbox" value="diabetes" class="w-4 h-4 mt-1">
                                        <span class="text-slate-600">Diabetes</span>
                                    </label>
                                    <label class="flex items-start gap-3 cursor-pointer">
                                        <input type="checkbox" value="asthma" class="w-4 h-4 mt-1">
                                        <span class="text-slate-600">Asthma / Respiratory Issues</span>
                                    </label>
                                    <label class="flex items-start gap-3 cursor-pointer">
                                        <input type="checkbox" value="allergy" class="w-4 h-4 mt-1">
                                        <span class="text-slate-600">Allergies</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!
                    <div class="form-section" data-section="4">
                        <div class="mb-8">
                            <h2 class="text-2xl font-heading font-bold text-slate-900 mb-2">Consent & Confirmation</h2>
                            <p class="text-slate-600">Please review and accept our data use policies.</p>
                        </div>

                        <div class="space-y-6">
                            <div class="bg-[#454B1B]/5 border border-[#454B1B]/20 rounded-lg p-6">
                                <h3 class="font-bold text-slate-900 mb-3 flex items-center gap-2">
                                    <i style="color: #454B1B;" class="fa-solid fa-lock"></i>
                                    Data Privacy & Consent
                                </h3>
                                <p class="text-sm text-slate-600 leading-relaxed mb-4">
                                    I understand that my personal and health information will be stored securely in the ScanCare system and used solely for healthcare delivery, treatment coordination, and epidemiological surveillance in accordance with RA 10173 (Data Privacy Act). I have read and understood the Privacy Notice.
                                </p>
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" required class="w-4 h-4 mt-1">
                                    <span class="text-slate-600 text-sm">I consent to data collection and processing</span>
                                </label>
                            </div>

                            <div class="bg-blue-50 border border-blue-100 rounded-lg p-6">
                                <h3 class="font-bold text-slate-900 mb-3 flex items-center gap-2">
                                    <i class="fa-solid fa-bell text-blue-600"></i>
                                    SMS Notifications
                                </h3>
                                <p class="text-sm text-slate-600 leading-relaxed mb-4">
                                    I agree to receive SMS notifications for health alerts, appointment reminders, and vaccination schedules.
                                </p>
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" class="w-4 h-4 mt-1">
                                    <span class="text-slate-600 text-sm">I consent to receive SMS notifications</span>
                                </label>
                            </div>

                            <div>
                                <label class="flex items-start gap-3 cursor-pointer mb-4">
                                    <input type="checkbox" required class="w-4 h-4 mt-1">
                                    <span class="text-slate-600 text-sm">I confirm that all information provided is accurate and true</span>
                                </label>
                            </div>

                            <div class="bg-emerald-50 border border-emerald-100 rounded-lg p-4 text-sm text-emerald-700 flex items-start gap-3">
                                <i class="fa-solid fa-check-circle mt-0.5 flex-shrink-0"></i>
                                <span>Your health information is protected by encryption and secure database storage.</span>
                            </div>
                        </div>
                    </div>

                    <!
                    <div class="flex justify-between pt-8 border-t border-slate-200">
                        <button type="button" onclick="previousStep()" class="px-6 py-3 border border-slate-300 text-slate-900 rounded-lg font-semibold hover:bg-slate-50 transition-colors disabled:opacity-50" id="prevBtn" disabled>
                            ← Previous
                        </button>
                        <button type="button" onclick="nextStep()" class="px-8 py-3 rounded-lg font-semibold text-white transition-all shadow-md" style="background-color: #454B1B;" id="nextBtn">
                            Next →
                        </button>
                        <button type="submit" class="px-8 py-3 rounded-lg font-semibold text-white transition-all shadow-md hidden" style="background-color: #10b981;" id="submitBtn">
                            <i class="fa-solid fa-check mr-2"></i> Complete Registration
                        </button>
                    </div>
                </form>

                <!
                <div id="successMessage" class="hidden text-center py-12">
                    <div class="mb-6">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 text-4xl mb-4">
                            <i class="fa-solid fa-check"></i>
                        </div>
                    </div>
                    <h2 class="text-3xl font-heading font-bold text-slate-900 mb-3">Registration Successful!</h2>
                    <p class="text-slate-600 mb-3 leading-relaxed">
                        Welcome to ScanCare. Your health profile has been created successfully. Your unique Patient ID has been generated and your QR health ID will be sent to your mobile number shortly.
                    </p>
                    <p class="text-slate-600 mb-8 leading-relaxed font-medium">
                        You can now log in to your private resident portal to book appointments and access free healthcare services.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="login.php" style="background-color: #454B1B;" class="inline-block text-white px-8 py-3 rounded-lg font-semibold hover:opacity-90 transition-opacity">
                            <i class="fa-solid fa-sign-in-alt mr-2"></i> Go to Login
                        </a>
                        <a href="landing.php" class="inline-block border-2 border-[#454B1B] text-[#454B1B] px-8 py-3 rounded-lg font-semibold hover:bg-[#454B1B] hover:text-white transition-colors">
                            Return to Home
                        </a>
                    </div>
                </div>

            </div>
        </section>

    </main>

    <footer class="w-full bg-slate-900 text-white py-12 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-6 lg:px-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div><h3 class="font-heading font-bold text-lg mb-4">ScanCare</h3><p class="text-slate-400 text-sm">Healthcare made simple for Barangay Gutad</p></div>
                <div><h4 class="font-semibold mb-4">Services</h4><ul class="space-y-2 text-sm text-slate-400"><li><a href="appointments.php" class="hover:text-white transition">Appointments</a></li><li><a href="system-goals.php" class="hover:text-white transition">System Goals</a></li><li><a href="service-areas.php" class="hover:text-white transition">Service Areas</a></li></ul></div>
                <div><h4 class="font-semibold mb-4">Legal</h4><ul class="space-y-2 text-sm text-slate-400"><li><a href="compliance.php" class="hover:text-white transition">Privacy Policy & Terms of Service</a></li></ul></div>
                <div><h4 class="font-semibold mb-4">Contact</h4><p class="text-slate-400 text-sm">Floridablanca RHU II - GUTAD<br><a href="mailto:floridablancarhu2@gmail.com" class="inline-flex items-center gap-2 hover:text-white transition"><i class="fa-solid fa-envelope w-4 text-center" aria-hidden="true"></i><span>floridablancarhu2@gmail.com</span></a><br><a href="tel:09480566872" class="inline-flex items-center gap-2 hover:text-white transition"><i class="fa-solid fa-phone w-4 text-center" aria-hidden="true"></i><span>0948 056 6872</span></a><br><a href="https://www.facebook.com/share/1DRzKzHcYD/" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 hover:text-white transition" aria-label="ScanCare on Facebook"><i class="fa-brands fa-facebook-f w-4 text-center" aria-hidden="true"></i><span>RHU II - Gutad</span></a></p></div>
            </div>
            <div class="border-t border-slate-800 pt-8 text-center text-slate-400 text-sm"><p>&copy; 2026 ScanCare. All rights reserved. Your data is encrypted and secure.</p></div>
        </div>
    </footer>

    <script>
        let currentStep = 1;
        const totalSteps = 4;

        function showStep(step) {
            
            document.querySelectorAll('.form-section').forEach(section => {
                section.classList.remove('active');
            });

            
            document.querySelector(`[data-section="${step}"]`).classList.add('active');

            
            document.querySelectorAll('.step-dot').forEach((dot, index) => {
                const dotStep = index + 1;
                dot.classList.remove('active', 'completed', 'inactive');
                
                if (dotStep < step) {
                    dot.classList.add('completed');
                } else if (dotStep === step) {
                    dot.classList.add('active');
                } else {
                    dot.classList.add('inactive');
                }
            });

            
            document.getElementById('prevBtn').disabled = step === 1;
            document.getElementById('nextBtn').style.display = step === totalSteps ? 'none' : 'block';
            document.getElementById('submitBtn').style.display = step === totalSteps ? 'block' : 'none';

            
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function validateStep(step) {
            const currentSection = document.querySelector(`[data-section="${step}"]`);
            const requiredFields = currentSection.querySelectorAll('[required]');
            let allFilled = true;
            let emptyFieldLabel = '';

            requiredFields.forEach(field => {
                
                let isEmpty = false;
                
                if (field.type === 'radio') {
                    
                    const radioGroup = document.querySelectorAll(`input[name="${field.name}"]`);
                    isEmpty = !Array.from(radioGroup).some(radio => radio.checked);
                } else if (field.type === 'checkbox') {
                    
                    isEmpty = !field.checked;
                } else if (field.tagName === 'SELECT') {
                    
                    isEmpty = field.value === '' || field.value === null;
                } else {
                    
                    isEmpty = field.value.trim() === '';
                }

                if (isEmpty) {
                    allFilled = false;
                    
                    const label = currentSection.querySelector(`label[for="${field.id}"]`) || 
                                  currentSection.querySelector(`label:has(+ ${field.tagName === 'SELECT' ? 'select' : field.type === 'radio' ? 'input[type="radio"]' : field.tagName.toLowerCase()})`);
                    if (!emptyFieldLabel) {
                        emptyFieldLabel = label ? label.textContent.trim() : field.name || 'A field';
                    }
                    
                    
                    field.style.borderColor = '#ef4444';
                    field.style.borderWidth = '2px';
                } else {
                    
                    field.style.borderColor = '';
                    field.style.borderWidth = '';
                }
            });

            if (!allFilled) {
                showWarning(emptyFieldLabel);
                return false;
            }

            
            if (step === 2) {
                const mobilePrimary = document.getElementById('mobilePrimary');
                const emergencyPhone = document.getElementById('emergencyPhone');
                const mobileAlternate = document.getElementById('mobileAlternate');

                
                if (mobilePrimary.value && mobilePrimary.value.trim().length !== 11) {
                    mobilePrimary.style.borderColor = '#ef4444';
                    mobilePrimary.style.borderWidth = '2px';
                    showWarning('Mobile Number (Primary) must be exactly 11 digits');
                    return false;
                } else {
                    mobilePrimary.style.borderColor = '';
                    mobilePrimary.style.borderWidth = '';
                }

                
                if (emergencyPhone.value && emergencyPhone.value.trim().length !== 11) {
                    emergencyPhone.style.borderColor = '#ef4444';
                    emergencyPhone.style.borderWidth = '2px';
                    showWarning('Emergency Contact Number must be exactly 11 digits');
                    return false;
                } else {
                    emergencyPhone.style.borderColor = '';
                    emergencyPhone.style.borderWidth = '';
                }

                
                if (mobileAlternate.value && mobileAlternate.value.trim().length !== 11) {
                    mobileAlternate.style.borderColor = '#ef4444';
                    mobileAlternate.style.borderWidth = '2px';
                    showWarning('Mobile Number (Alternate) must be exactly 11 digits');
                    return false;
                } else {
                    mobileAlternate.style.borderColor = '';
                    mobileAlternate.style.borderWidth = '';
                }
            }

            return true;
        }

        function showWarning(fieldLabel) {
            
            const existingWarning = document.querySelector('.validation-warning');
            if (existingWarning) {
                existingWarning.remove();
            }

            
            const warning = document.createElement('div');
            warning.className = 'validation-warning bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2 animate-pulse';
            warning.innerHTML = `
                <i class="fa-solid fa-exclamation-circle flex-shrink-0"></i>
                <span><strong>⚠️ You need to fill this up first:</strong> ${fieldLabel}</span>
            `;
            
            
            const currentSection = document.querySelector(`[data-section="${currentStep}"]`);
            currentSection.insertBefore(warning, currentSection.firstChild);

            
            setTimeout(() => {
                warning.remove();
            }, 4000);
        }

        function nextStep() {
            if (currentStep < totalSteps) {
                
                if (validateStep(currentStep)) {
                    currentStep++;
                    showStep(currentStep);
                }
            }
        }

        function previousStep() {
            if (currentStep > 1) {
                currentStep
                showStep(currentStep);
            }
        }

        
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            
            document.getElementById('registrationForm').style.display = 'none';
            
            
            document.getElementById('successMessage').classList.remove('hidden');
        });

        
        showStep(1);
    </script>

</body>
</html>

