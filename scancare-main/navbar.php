<?php
if (!function_exists('isNavActive')) {
    function isNavActive($page) {
        $currentFile = basename($_SERVER['PHP_SELF']);
        if ($page === 'appointments' && in_array($currentFile, ['appointments.php', 'dashboard.php'])) return true;
        if ($page === 'system-goals' && $currentFile === 'system-goals.php') return true;
        if ($page === 'service-areas' && $currentFile === 'service-areas.php') return true;
        if ($page === 'surveillance' && $currentFile === 'surveillance.php') return true;
        if ($page === 'compliance' && $currentFile === 'compliance.php') return true;
        return false;
    }
}

if (!isset($rhuStatus)) {
    $currentHour = (int)date('G');
    $dayOfWeek = (int)date('w');
    $isWeekday = ($dayOfWeek >= 1 && $dayOfWeek <= 5);
    $isOpenHours = ($currentHour >= 8 && $currentHour < 17);
    
    $rhuIsOpen = $isWeekday && $isOpenHours;
    
    if ($rhuIsOpen) {
        $rhuStatus = [
            'open' => true,
            'borderClass' => 'border-emerald-200',
            'bgClass' => 'bg-emerald-50',
            'textClass' => 'text-emerald-700',
            'dotClass' => 'bg-emerald-500',
            'message' => 'RHU II is Now Open | will close at 5PM'
        ];
    } else {
        if ($isWeekday && $currentHour < 8) {
            $message = 'RHU II is Closed | Will resume today at 8am';
        } elseif ($isWeekday && $currentHour >= 17) {
            $message = 'RHU II is Closed | Will resume tomorrow at 8am';
        } else {
            $message = 'RHU II is Closed | Will resume Monday at 8am';
        }
        
        $rhuStatus = [
            'open' => false,
            'borderClass' => 'border-slate-300',
            'bgClass' => 'bg-slate-100',
            'textClass' => 'text-slate-600',
            'dotClass' => 'bg-slate-400',
            'message' => $message
        ];
    }
}

    $profile_name = $_SESSION['user_name'] ?? 'User';
    $profile_type = $_SESSION['user_type'] ?? 'resident';
    $profile_role = $profile_type === 'health_worker' ? 'Health Worker Account' : ($profile_type === 'admin' ? 'Administrator Account' : 'Resident Account');
    $profile_subtitle = $profile_type === 'health_worker' ? 'Health Worker • Barangay Gutad' : ($profile_type === 'admin' ? 'Administrator Account' : 'Resident • Barangay Gutad');
    $profile_dashboard_href = match ($profile_type) {
        'admin' => 'administrator_panel.php?section=dashboard',
        'health_worker' => 'dashboard_bhw.php',
        default => 'dashboard.php'
    };
    $profile_dashboard_label = $profile_type === 'health_worker' ? 'BHW Dashboard' : ($profile_type === 'admin' ? 'Admin Dashboard' : 'Resident Dashboard');
    $profile_name_parts = preg_split('/\s+/', trim($profile_name));
    $profile_initials = count($profile_name_parts) > 1
        ? strtoupper(substr($profile_name_parts[0], 0, 1) . substr($profile_name_parts[count($profile_name_parts) - 1], 0, 1))
        : strtoupper(substr($profile_name, 0, 2));
    $activeNavLabel = match (basename($_SERVER['PHP_SELF'])) {
        'appointments.php', 'dashboard.php' => 'Appointment',
        'system-goals.php' => 'System Goals',
        'service-areas.php' => 'Service Areas',
        'surveillance.php' => 'Surveillance',
        'compliance.php' => 'Compliance',
        default => 'ScanCare'
    };
    $profile_picture = $_SESSION['profile_pic'] ?? $_SESSION['profile_picture'] ?? '';
    if ($profile_picture === '' && isset($_SESSION['user_id'])) {
        try {
            require_once __DIR__ . '/db_config.php';
            $profilePdo = getDatabaseConnection();
            if ($profilePdo) {
                $profileType = $_SESSION['user_type'] ?? 'resident';
                $profileTable = match ($profileType) {
                    'health_worker' => 'health_worker',
                    'admin' => 'system_admins',
                    default => 'resident'
                };
                $profileIdColumn = match ($profileTable) {
                    'health_worker' => 'worker_id',
                    'system_admins' => 'admin_id',
                    default => 'resident_id'
                };
                $profileStmt = $profilePdo->prepare("SELECT profile_picture FROM {$profileTable} WHERE {$profileIdColumn} = ? LIMIT 1");
                $profileStmt->execute([intval($_SESSION['user_id'])]);
                $profile_picture = (string)($profileStmt->fetchColumn() ?: '');
                $_SESSION['profile_picture'] = $profile_picture;
                $_SESSION['profile_pic'] = $profile_picture;
            }
        } catch (Throwable $profileError) {
            $profile_picture = '';
        }
    }
?>


<!-- Main Header -->
<header class="sc-navbar-header sticky top-0 z-[80] w-full border-b border-slate-200 bg-white/95 dark:border-white/10 backdrop-blur-md">
    <div class="flex h-14 w-full items-center justify-between gap-3 px-4 sm:h-16 sm:px-6 lg:px-8">
        
        <div class="flex min-w-0 shrink-0 items-center gap-3">
            <!-- Brand Logo -->
            <a href="landing.php" class="flex shrink-0 items-center gap-2 rounded-lg py-1 text-2xl font-extrabold tracking-tight transition-opacity hover:opacity-90">
                <img src="images/SClogo.png" alt="ScanCare Logo" class="h-8 w-8 shrink-0 object-contain sm:h-9 sm:w-9">
                <span class="font-heading shrink-0 whitespace-nowrap"><span class="sc-brand-scan font-bold">Scan</span><span class="text-[#828e34] font-bold">Care</span></span>
            </a>
            <button id="mobileNavToggle" type="button" class="mobile-nav-toggle inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-slate-700 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-[#454B1B] dark:text-slate-200 dark:hover:bg-white/10" aria-label="Open navigation menu" aria-expanded="false" aria-controls="mobileMenuPanel">
                <i class="fas fa-bars" aria-hidden="true"></i>
            </button>
            <span class="mobile-nav-title hidden min-w-0 max-w-[104px] truncate rounded-full bg-[#454B1B]/10 px-2 py-1 text-[11px] font-semibold text-[#454B1B] md:hidden" aria-current="page">
                <?php echo htmlspecialchars($activeNavLabel, ENT_QUOTES, 'UTF-8'); ?>
            </span>
        </div>

        <!-- Borderless Pill Navigation Container -->
        <nav class="sc-pill-navbar" aria-label="Main Navigation">
            
            <!-- Appointment -->
            <a href="appointments.php" class="sc-pill-item <?php echo isNavActive('appointments') ? 'active' : ''; ?>">
                <div class="sc-icon-wrapper">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <span>Appointment</span>
            </a>

            <!-- System Goals -->
            <a href="system-goals.php" class="sc-pill-item <?php echo isNavActive('system-goals') ? 'active' : ''; ?>">
                <div class="sc-icon-wrapper">
                    <i class="fas fa-bullseye"></i>
                </div>
                <span>System Goals</span>
            </a>

            <!-- Service Areas -->
            <a href="service-areas.php" class="sc-pill-item <?php echo isNavActive('service-areas') ? 'active' : ''; ?>">
                <div class="sc-icon-wrapper">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <span>Service Areas</span>
            </a>

            <!-- Surveillance -->
            <a href="surveillance.php" class="sc-pill-item <?php echo isNavActive('surveillance') ? 'active' : ''; ?>">
                <div class="sc-icon-wrapper">
                    <i class="fas fa-chart-line"></i>
                </div>
                <span>Surveillance</span>
            </a>

            <!-- Compliance -->
            <a href="compliance.php" class="sc-pill-item <?php echo isNavActive('compliance') ? 'active' : ''; ?>">
                <div class="sc-icon-wrapper">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <span>Compliance</span>
            </a>

        </nav>

        <!-- Right Controls (RHU Status & Session) -->
        <div class="flex min-w-0 shrink-0 items-center gap-3">
            <a href="landing.php" class="sc-home-button inline-flex items-center gap-1 px-2 py-1 text-[11px] font-medium text-slate-700 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-full transition ml-2 shadow-sm">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001 1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span>Home</span>
            </a>
            <button type="button" class="nav-theme-toggle inline-flex h-11 w-11 items-center justify-center rounded-lg text-slate-700 transition hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 dark:text-slate-200 dark:hover:bg-white/10 dark:focus-visible:ring-slate-500" aria-label="Toggle dark mode" title="Toggle dark mode">
                <i class="fas fa-moon" aria-hidden="true"></i>
            </button>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="login.php?switch_account=1" class="inline-flex min-h-[36px] items-center justify-center rounded-lg bg-[#454B1B] px-3.5 py-1.5 text-xs font-bold text-white transition-all hover:bg-[#363A15] active:scale-95">Login / Signup</a>
            <?php endif; ?>
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="sc-profile relative" data-profile-menu>
                    <button type="button" class="sc-profile-trigger" data-profile-trigger aria-expanded="false" aria-controls="profileMenu" aria-haspopup="true">
                        <span class="sc-profile-copy">
                            <span class="sc-profile-name"><?php echo htmlspecialchars($profile_name, ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="sc-profile-subtitle"><?php echo htmlspecialchars($profile_subtitle, ENT_QUOTES, 'UTF-8'); ?></span>
                        </span>
                        <span class="sc-profile-avatar" aria-hidden="true">
                            <img data-profile-avatar-image src="<?php echo htmlspecialchars(!empty($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : 'assets/default-avatar.png', ENT_QUOTES, 'UTF-8'); ?>?t=<?php echo time(); ?>" class="w-10 h-10 rounded-full object-cover shrink-0 border border-slate-200" onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=User&background=454B1B&color=fff';" alt="User Profile">
                        </span>
                        <span class="sr-only">Open profile menu</span>
                    </button>

                    <div id="profileMenu" class="sc-profile-menu" data-profile-menu-panel role="menu" aria-hidden="true">
                        <div class="sc-profile-menu-header bg-slate-50 dark:bg-[#0d1310] border-b border-slate-200 dark:border-white/10">
                            <span class="sc-profile-avatar sc-profile-avatar-small" aria-hidden="true">
                                <img data-profile-avatar-image src="<?php echo htmlspecialchars(!empty($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : 'assets/default-avatar.png', ENT_QUOTES, 'UTF-8'); ?>?t=<?php echo time(); ?>" class="w-10 h-10 rounded-full object-cover shrink-0 border border-slate-200" onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=User&background=454B1B&color=fff';" alt="User Profile">
                            </span>
                            <span>
                                <span class="sc-profile-menu-name text-slate-900 dark:text-white font-bold"><?php echo htmlspecialchars($profile_name, ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="sc-profile-menu-role text-slate-500 dark:text-slate-400"><?php echo htmlspecialchars($profile_role, ENT_QUOTES, 'UTF-8'); ?></span>
                            </span>
                        </div>
                        <div class="sc-profile-divider" role="separator"></div>
                        <a href="<?php echo htmlspecialchars($profile_dashboard_href, ENT_QUOTES, 'UTF-8'); ?>" class="sc-profile-menu-item" role="menuitem">
                            <i class="fas fa-th-large text-[#a3b14e]" aria-hidden="true"></i>
                            <span class="font-medium text-slate-700 dark:text-slate-200"><?php echo htmlspecialchars($profile_dashboard_label, ENT_QUOTES, 'UTF-8'); ?></span>
                        </a>
                        <a href="profile.php" class="sc-profile-menu-item" role="menuitem">
                            <i class="fas fa-camera text-[#a3b14e]" aria-hidden="true"></i>
                            <span class="font-medium text-slate-700 dark:text-slate-200">Change Photo</span>
                        </a>
                        <button type="button" class="sc-profile-menu-item w-full text-left px-4 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-[#19251f] flex items-center gap-2" role="menuitem" onclick="openSettingsModal()">
                            <i class="fas fa-cog text-[#a3b14e]" aria-hidden="true"></i>
                            <span class="font-medium text-slate-700 dark:text-slate-200">Settings</span>
                        </button>
                        <div class="sc-profile-divider" role="separator"></div>
                        <a href="logout.php" class="sc-profile-menu-item sc-profile-logout" role="menuitem">
                            <i class="fas fa-sign-out-alt text-rose-500 dark:text-rose-400" aria-hidden="true"></i>
                            <span class="text-slate-700 hover:text-rose-600 dark:text-slate-200 dark:hover:text-rose-400">Log Out</span>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>
</header>

<div id="mobileMenuPanel" class="mobile-nav-panel hidden border-b border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-white/10 dark:bg-[#111813]" aria-hidden="true">
    <nav class="flex flex-col gap-1" aria-label="Mobile Navigation">
        <a href="appointments.php" class="mobile-nav-link mobile-menu-item <?php echo isNavActive('appointments') ? 'active' : ''; ?>">Appointment</a>
        <a href="system-goals.php" class="mobile-nav-link mobile-menu-item <?php echo isNavActive('system-goals') ? 'active' : ''; ?>">System Goals</a>
        <a href="service-areas.php" class="mobile-nav-link mobile-menu-item <?php echo isNavActive('service-areas') ? 'active' : ''; ?>">Service Areas</a>
        <a href="surveillance.php" class="mobile-nav-link mobile-menu-item <?php echo isNavActive('surveillance') ? 'active' : ''; ?>">Surveillance</a>
        <a href="compliance.php" class="mobile-nav-link mobile-menu-item <?php echo isNavActive('compliance') ? 'active' : ''; ?>">Compliance</a>
    </nav>
</div>

<!-- Floating RHU Status Bar -->
<?php if (basename($_SERVER['PHP_SELF']) === 'landing.php'): ?>
<div id="floatingRhuBar" class="<?php echo basename($_SERVER['PHP_SELF']) === 'landing.php' ? 'landing-rhu-bar ' : ''; ?>fixed top-16 left-0 right-0 flex justify-center px-4 py-3 z-40 transition-all duration-300 ease-out translate-y-0">
    <span data-rhu-status class="rhu-status <?php echo $rhuStatus['open'] ? 'rhu-open' : 'rhu-closed'; ?> inline-flex items-center gap-2.5 rounded-full border <?php echo $rhuStatus['borderClass'] . ' ' . $rhuStatus['bgClass'] . ' ' . $rhuStatus['textClass']; ?> px-6 py-3 text-sm font-semibold shadow-lg backdrop-blur-sm">
        <span data-rhu-dot class="h-3 w-3 rounded-full <?php echo $rhuStatus['open'] ? 'animate-pulse' : ''; ?> <?php echo $rhuStatus['dotClass']; ?>"></span>
        <span data-rhu-message><?php echo $rhuStatus['message']; ?></span>
    </span>
</div>
<?php endif; ?>

<?php if (basename($_SERVER['PHP_SELF']) !== 'landing.php'): ?>
    <div id="settingsModal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-[60] items-center justify-center p-4 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="settingsModalTitle" onclick="if (event.target === this) closeSettingsModal()">
        <div class="w-full max-w-md overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl dark:border-white/10 dark:bg-[#111813]" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between border-b border-slate-200 p-5 dark:border-white/10">
                <h2 id="settingsModalTitle" class="text-lg font-bold text-slate-900 dark:text-white"><i class="fas fa-cog mr-2 text-[#454B1B]"></i>Settings</h2>
                <button type="button" onclick="closeSettingsModal()" aria-label="Close settings" class="text-xl text-slate-400 hover:text-slate-600 dark:text-slate-400 dark:hover:text-white"><i class="fas fa-times"></i></button>
            </div>
            <div class="space-y-5 p-5">
                <section>
                    <h3 class="mb-2 text-sm font-bold text-slate-900 dark:text-white"><i class="fas fa-bell mr-2 text-[#454B1B]"></i>Notifications</h3>
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-2 hover:bg-slate-100 dark:hover:bg-[#19251f]"><input type="checkbox" id="emailNotif" checked class="h-[18px] w-[18px] accent-[#454B1B]"><span class="text-sm text-slate-700 dark:text-slate-300">Enable Email Notifications</span></label>
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-2 hover:bg-slate-100 dark:hover:bg-[#19251f]"><input type="checkbox" id="smsNotif" checked class="h-[18px] w-[18px] accent-[#454B1B]"><span class="text-sm text-slate-700 dark:text-slate-300">Enable SMS Notifications</span></label>
                </section>
                <section>
                    <h3 class="mb-2 text-sm font-bold text-slate-900 dark:text-white"><i class="fas fa-lock mr-2 text-[#454B1B]"></i>Privacy</h3>
                    <label class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-2 hover:bg-slate-100 dark:hover:bg-[#19251f]"><input type="checkbox" id="profilePrivacy" class="h-[18px] w-[18px] accent-[#454B1B]"><span class="text-sm text-slate-700 dark:text-slate-300">Make Profile Private</span></label>
                </section>
                <button type="button" onclick="changePassword()" class="w-full rounded-lg bg-blue-600 px-4 py-2.5 font-semibold text-white shadow transition hover:bg-blue-700"><i class="fas fa-key mr-2"></i>Change Password</button>
            </div>
            <div class="flex gap-3 border-t border-slate-200 p-5 dark:border-white/10">
                <button type="button" onclick="closeSettingsModal()" class="flex-1 rounded-lg bg-slate-200 px-4 py-2.5 font-semibold text-slate-800 transition hover:bg-slate-300 dark:bg-slate-700/80 dark:text-slate-200 dark:hover:bg-slate-700">Close</button>
                <button type="button" onclick="saveSettings()" class="flex-1 rounded-lg bg-[#454B1B] px-4 py-2.5 font-semibold text-white shadow transition hover:bg-[#586022]"><i class="fas fa-save mr-2"></i>Save</button>
            </div>
        </div>
    </div>

    <script>
        let settingsOriginalState = {};
        let settingsSaveTriggered = false;

        function openSettingsModal() {
            const modal = document.getElementById('settingsModal');
            const profileMenu = document.querySelector('[data-profile-menu-panel]');
            settingsSaveTriggered = false;
            if (profileMenu) {
                profileMenu.setAttribute('aria-hidden', 'true');
                profileMenu.classList.remove('is-open');
            }
            settingsOriginalState = {
                emailNotif: document.getElementById('emailNotif')?.checked || false,
                smsNotif: document.getElementById('smsNotif')?.checked || false,
                profilePrivacy: document.getElementById('profilePrivacy')?.checked || false
            };
            if (modal) {
                modal.classList.remove('hidden');
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        }

        function closeSettingsModal() {
            if (!settingsSaveTriggered) restoreSettingsState();
            const modal = document.getElementById('settingsModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }

        function restoreSettingsState() {
            Object.entries(settingsOriginalState).forEach(([key, value]) => {
                const control = document.getElementById(key);
                if (control) control.checked = value;
            });
        }

        function saveSettings() {
            const settings = {
                emailNotif: document.getElementById('emailNotif')?.checked || false,
                smsNotif: document.getElementById('smsNotif')?.checked || false,
                profilePrivacy: document.getElementById('profilePrivacy')?.checked || false
            };
            Object.entries(settings).forEach(([key, value]) => localStorage.setItem(key, value ? 'enabled' : 'disabled'));
            settingsSaveTriggered = true;
            alert('Settings saved successfully!');
            closeSettingsModal();
        }

        function changePassword() {
            const currentPassword = prompt('Enter your current password:');
            if (!currentPassword) return;
            const newPassword = prompt('Enter your new password (min 6 characters):');
            if (!newPassword) return;
            if (newPassword.length < 6) return alert('Password must be at least 6 characters long');
            const confirmPassword = prompt('Confirm your new password:');
            if (!confirmPassword) return;
            if (newPassword !== confirmPassword) return alert('New passwords do not match');

            const button = document.querySelector('#settingsModal button[onclick="changePassword()"]');
            if (button) button.disabled = true;
            fetch('change_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ currentPassword, newPassword, confirmPassword })
            }).then(async response => {
                const data = await response.json();
                if (!response.ok || !data.success) throw new Error(data.error || 'Failed to change password');
                alert(data.message || 'Password changed successfully');
                closeSettingsModal();
            }).catch(error => alert(error.message)).finally(() => {
                if (button) button.disabled = false;
            });
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && document.getElementById('settingsModal')?.style.display !== 'none') closeSettingsModal();
        });
    </script>
<?php endif; ?>

<style>
html.dark body > header,
body.dark-mode > header,
html.dark .sc-pill-navbar,
body.dark-mode .sc-pill-navbar {
    background-color: #111827 !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}

html.dark .sc-pill-item,
html.dark .sc-profile-name,
html.dark .sc-profile-subtitle,
html.dark .sc-profile-menu-item,
html.dark .sc-profile-menu-name,
html.dark .sc-profile-menu-role,
body.dark-mode .sc-pill-item,
body.dark-mode .sc-profile-name,
body.dark-mode .sc-profile-subtitle,
body.dark-mode .sc-profile-menu-item,
body.dark-mode .sc-profile-menu-name,
body.dark-mode .sc-profile-menu-role {
    color: #cbd5e1 !important;
}

html.dark .sc-pill-item.active,
html.dark .sc-pill-item:hover,
html.dark .sc-icon-wrapper,
body.dark-mode .sc-pill-item.active,
body.dark-mode .sc-pill-item:hover,
body.dark-mode .sc-icon-wrapper {
    color: #a3b14e !important;
}

html.dark .sc-home-button,
body.dark-mode .sc-home-button {
    background-color: #1a251d !important;
    color: #dbe8d7 !important;
}

html.dark .sc-home-button:hover,
body.dark-mode .sc-home-button:hover {
    background-color: #293a2c !important;
    color: #ffffff !important;
}

html.dark .sc-profile-menu,
body.dark-mode .sc-profile-menu {
    background-color: #1e293b !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}

html.dark .sc-profile-menu-header,
html.dark .sc-profile-divider,
body.dark-mode .sc-profile-menu-header,
body.dark-mode .sc-profile-divider {
    border-color: rgba(255, 255, 255, 0.1) !important;
}

html.dark [data-rhu-status],
body.dark-mode [data-rhu-status] {
    background-color: #131c17 !important;
    border-color: rgba(69, 75, 27, 0.3) !important;
    color: #a3b14e !important;
}

[data-rhu-status].rhu-open {
    background-color: #ecfdf5 !important;
    border-color: #a7f3d0 !important;
    color: #047857 !important;
}

[data-rhu-status].rhu-open [data-rhu-dot] {
    background-color: #10b981 !important;
}

[data-rhu-status].rhu-closed {
    background-color: #f1f5f9 !important;
    border-color: #cbd5e1 !important;
    color: #475569 !important;
}

[data-rhu-status].rhu-closed [data-rhu-dot] {
    background-color: #94a3b8 !important;
}

html.dark [data-rhu-status].rhu-open,
body.dark-mode [data-rhu-status].rhu-open {
    background-color: rgba(69, 75, 27, 0.3) !important;
    border-color: rgba(69, 75, 27, 0.5) !important;
    color: #ffffff !important;
}

html.dark [data-rhu-status].rhu-open [data-rhu-dot],
body.dark-mode [data-rhu-status].rhu-open [data-rhu-dot] {
    background-color: #10b981 !important;
}

html.dark [data-rhu-status].rhu-closed,
body.dark-mode [data-rhu-status].rhu-closed {
    background-color: rgba(30, 41, 59, 0.85) !important;
    border-color: rgba(148, 163, 184, 0.35) !important;
    color: #ffffff !important;
}

html.dark [data-rhu-status].rhu-closed [data-rhu-dot],
body.dark-mode [data-rhu-status].rhu-closed [data-rhu-dot] {
    background-color: #94a3b8 !important;
}

/* Clean Borderless Container */
.sc-pill-navbar {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background-color: #ffffff;
    padding: 6px 12px;
    border-radius: 9999px;
    box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.06);
}

html.dark .sc-pill-navbar,
body.dark-mode .sc-pill-navbar {
    background-color: #0d1310;
}

/* Base Nav Item */
.sc-pill-item {
    position: relative;
    display: flex;
    flex-shrink: 0;
    align-items: center;
    gap: 8px;
    padding: 6px 8px;
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    text-decoration: none;
    white-space: nowrap;
    transition: color 0.2s ease;
}

/* Icon Container */
.sc-icon-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    color: #454B1B;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    z-index: 1;
}

.sc-icon-wrapper i {
    font-size: 14px;
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), color 0.2s ease;
}

/* Downward Hover Elevation & Bubble Effect */
.sc-pill-item:hover .sc-icon-wrapper {
    transform: translateY(8px);
    background-color: #454B1B;
    color: #ffffff;
    width: 32px;
    height: 32px;
    box-shadow: 0 6px 14px rgba(69, 75, 27, 0.35);
}

.sc-pill-item:hover .sc-icon-wrapper i {
    font-size: 13px;
}

.sc-pill-item:hover span {
    color: #454B1B;
}

/* Active State */
.sc-pill-item.active {
    color: #454B1B;
    font-weight: 700;
}

.sc-pill-item.active .sc-icon-wrapper {
    background-color: #454B1B;
    color: #ffffff;
}

.sc-pill-item.active .sc-icon-wrapper i {
    color: #ffffff;
}

.sc-profile {
    position: relative;
    z-index: 1001;
}

.sc-profile-trigger {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0;
    border: 0;
    background: transparent;
    color: inherit;
    cursor: pointer;
    text-align: right;
}

.sc-profile-trigger:focus-visible,
.sc-profile-menu-item:focus-visible {
    outline: 2px solid #454B1B;
    outline-offset: 3px;
}

.sc-profile-copy,
.sc-profile-menu-header > span:last-child {
    display: flex;
    flex-direction: column;
}

.sc-profile-copy {
    min-width: 0;
    max-width: 12rem;
    overflow: hidden;
}

.sc-profile-name {
    color: #454B1B;
    font-size: 14px;
    font-weight: 700;
    line-height: 1.25;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.sc-profile-subtitle,
.sc-profile-menu-role {
    color: #64748b;
    font-size: 12px;
    line-height: 1.4;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.sc-profile-avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    flex: 0 0 40px;
    border: 2px solid #ffffff;
    border-radius: 50%;
    background: #454B1B;
    color: #ffffff;
    font-size: 13px;
    font-weight: 700;
    box-shadow: 0 3px 10px rgba(69, 75, 27, 0.24);
}

.sc-profile-menu {
    position: absolute;
    top: 100%;
    right: 0;
    z-index: 1001;
    width: 260px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background-color: #ffffff;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transform: translateY(-6px);
    transition: opacity 0.18s ease, visibility 0.18s ease, transform 0.18s ease;
}

.sc-navbar-header {
    position: relative;
    z-index: 1000;
}

.sc-brand-scan {
    color: #1e293b !important;
}

html.dark .sc-brand-scan,
body.dark-mode .sc-brand-scan {
    color: #ffffff !important;
}

html.dark .sc-profile-menu,
body.dark-mode .sc-profile-menu {
    background: #111813;
    border-color: rgba(255, 255, 255, 0.1);
    box-shadow: 0 18px 36px rgba(0, 0, 0, 0.4);
}

.sc-profile-menu.is-open {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
    transform: translateY(0);
}

.sc-profile-menu-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

html.dark .sc-profile-menu-header,
body.dark-mode .sc-profile-menu-header {
    background: #0d1310;
    border-bottom-color: rgba(255, 255, 255, 0.1);
}

.sc-profile-avatar-small {
    width: 40px;
    height: 40px;
}

.sc-profile-menu-name {
    color: #1e293b;
    font-size: 14px;
    font-weight: 700;
    line-height: 1.35;
}

html.dark .sc-profile-menu-name,
body.dark-mode .sc-profile-menu-name {
    color: #ffffff;
}

.sc-profile-divider {
    height: 1px;
    background: #e2e8f0;
}

html.dark .sc-profile-divider,
body.dark-mode .sc-profile-divider {
    background: rgba(255, 255, 255, 0.1);
}

.sc-profile-menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    padding: 13px 16px;
    border: 0;
    background: transparent;
    color: #334155;
    font-size: 14px;
    text-align: left;
    text-decoration: none;
    cursor: pointer;
    transition: background 0.18s ease, color 0.18s ease;
}

html.dark .sc-profile-menu-item,
body.dark-mode .sc-profile-menu-item {
    color: #e2e8f0;
}

.sc-profile-menu-item:hover {
    background: #f8fafc;
    color: #454B1B;
}

html.dark .sc-profile-menu-item:hover,
body.dark-mode .sc-profile-menu-item:hover {
    background: #1a251d;
    color: #e2e8f0;
}

.sc-profile-menu-item i {
    width: 20px;
    color: #454B1B;
    text-align: center;
}

.sc-profile-menu-item.sc-profile-logout {
    color: #e53e3e;
}

.sc-profile-menu-item.sc-profile-logout i {
    color: #e53e3e;
}

.sc-profile-menu-item.sc-profile-logout:hover {
    background: #fff5f5;
    color: #c53030;
}

html.dark .sc-profile-menu-item.sc-profile-logout,
body.dark-mode .sc-profile-menu-item.sc-profile-logout {
    color: #e2e8f0;
}

html.dark .sc-profile-menu-item.sc-profile-logout:hover,
body.dark-mode .sc-profile-menu-item.sc-profile-logout:hover {
    background: rgba(244, 63, 94, 0.12);
    color: #fb7185;
}

html.dark .sc-profile-menu-item.sc-profile-logout i,
body.dark-mode .sc-profile-menu-item.sc-profile-logout i {
    color: #fb7185;
}

@media (max-width: 640px) {
    .sc-profile-subtitle {
        display: none;
    }

    .sc-profile-menu {
        right: -4px;
    }
}

@media (max-width: 1100px) and (min-width: 768px) {
    .sc-profile-copy {
        max-width: 8rem;
    }

    .sc-profile-subtitle {
        display: none;
    }
}

@media (max-width: 1023px) and (min-width: 768px) {
    header .sc-pill-navbar {
        display: none;
    }

    header .mobile-nav-toggle,
    header .mobile-nav-title {
        display: inline-flex;
    }
}

.mobile-nav-toggle,
.mobile-nav-title,
.mobile-nav-panel {
    display: none;
}

.mobile-nav-link {
    display: flex;
    min-height: 44px;
    position: relative;
    z-index: 60;
    pointer-events: auto !important;
    align-items: center;
    border-radius: 0.5rem;
    padding: 0.625rem 0.75rem;
    color: #475569;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    transition: background-color 0.2s ease, color 0.2s ease;
}

.nav-theme-toggle {
    display: inline-flex;
    color: #454b1b !important;
    background: transparent;
    transition: background-color 0.2s ease, color 0.2s ease;
}

.nav-theme-toggle:hover {
    background: rgba(69, 75, 27, 0.1);
    color: #303512 !important;
}

.nav-theme-toggle .fa-sun {
    color: #fbbf24 !important;
}

html.dark .nav-theme-toggle,
body.dark-mode .nav-theme-toggle {
    color: #a3b14e !important;
}

html.dark .nav-theme-toggle:hover,
body.dark-mode .nav-theme-toggle:hover {
    background: rgba(163, 177, 78, 0.14);
    color: #d9f99d !important;
}

.nav-link,
.mobile-menu-item {
    position: relative;
    z-index: 60;
    pointer-events: auto !important;
}

.mobile-nav-link:hover,
.mobile-nav-link.active {
    background-color: rgba(69, 75, 27, 0.1);
    color: #454b1b;
}

html.dark .mobile-nav-link,
body.dark-mode .mobile-nav-link {
    color: #cbd5e1;
}

body .mobile-nav-title {
    border: 1px solid rgba(69, 75, 27, 0.25);
    background-color: rgba(69, 75, 27, 0.1) !important;
    color: #454b1b !important;
}

html.dark .mobile-nav-title,
body.dark-mode .mobile-nav-title {
    border-color: rgba(163, 177, 78, 0.3);
    background-color: rgba(163, 177, 78, 0.14) !important;
    color: #a3b14e !important;
}

html.dark .mobile-nav-link:hover,
html.dark .mobile-nav-link.active,
body.dark-mode .mobile-nav-link:hover,
body.dark-mode .mobile-nav-link.active {
    background-color: rgba(163, 177, 78, 0.14);
    color: #a3b14e;
}

@media (max-width: 767px) {
    header > div {
        height: 3.5rem;
    }

    header .sc-pill-navbar {
        display: none;
    }

    header .mobile-nav-title,
    header .mobile-nav-toggle {
        display: inline-flex;
    }

    header .nav-theme-toggle {
        display: inline-flex;
        flex: 0 0 2.5rem;
        width: 2.5rem;
        height: 2.5rem;
        margin-left: 0.25rem;
    }

    header > div > div:last-child {
        gap: 0.25rem;
    }

    header > div > div:last-child > a:not(.sc-profile-trigger),
    header > div > div:last-child > .sc-profile {
        display: none;
    }

    .mobile-nav-panel.is-open {
        display: flex;
        flex-direction: column;
    }

        .mobile-nav-panel {
            position: fixed;
            top: 3.5rem;
            left: 0;
            right: auto;
            /* Keep the drawer beneath the header so it cannot cover the
               hamburger button when the browser restores a page snapshot. */
            z-index: 70;
            display: flex;
            width: 100vw;
            min-height: calc(100vh - 3.5rem);
            max-width: 100vw;
            max-height: calc(100vh - 3.5rem);
            overflow-y: auto;
            box-sizing: border-box;
            overscroll-behavior: contain;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transform: translateX(-104%);
            transition: transform 260ms ease, opacity 220ms ease, visibility 0s linear 260ms;
        }

        .mobile-nav-panel.is-open {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transform: translateX(0);
            transition-delay: 0s;
        }

        #floatingRhuBar {
            top: 3.5rem;
            width: 100%;
            box-sizing: border-box;
            padding: 0.5rem 0.75rem;
        }

        #floatingRhuBar > span {
            min-width: 0;
            max-width: 100%;
            text-align: center;
            white-space: normal;
            line-height: 1.35;
        }

        #floatingRhuBar [data-rhu-message] {
            min-width: 0;
            max-width: 100%;
            overflow-wrap: anywhere;
        }
}

/* Floating RHU Status Bar */
#floatingRhuBar {
    opacity: 1;
    transform: translateY(0);
    transition: all 300ms cubic-bezier(0.4, 0, 0.2, 1);
}
</style>

<script>
let lastScrollY = 0;
let isBarVisible = true;

document.addEventListener('DOMContentLoaded', () => {
    const mobileNavToggle = document.getElementById('mobileNavToggle');
    const mobileNavPanel = document.getElementById('mobileMenuPanel');
    const themeButtons = document.querySelectorAll('.nav-theme-toggle');
    const updateThemeButtonIcon = (button, isDark) => {
        const icon = button.querySelector('i');
        if (!icon) return;
        icon.outerHTML = isDark
            ? '<i class="fas fa-sun" aria-hidden="true" style="color: #fbbf24 !important;"></i>'
            : '<i class="fas fa-moon" aria-hidden="true"></i>';
    };

    themeButtons.forEach(themeButton => {
        themeButton.addEventListener('click', () => {
            const isDark = !document.documentElement.classList.contains('dark');
            localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            if (window.ScanCareTheme) window.ScanCareTheme.set(isDark ? 'dark' : 'light');
            else {
                document.documentElement.classList.toggle('dark', isDark);
                document.documentElement.classList.toggle('dark-mode', isDark);
                document.body.classList.toggle('dark-mode', isDark);
            }
            themeButtons.forEach(button => updateThemeButtonIcon(button, isDark));
        });
    });

    const savedTheme = localStorage.getItem('theme') || localStorage.getItem('scancare_theme');
    themeButtons.forEach(button => {
        const isDark = savedTheme === 'dark' || localStorage.getItem('darkMode') === 'enabled';
        updateThemeButtonIcon(button, isDark);
    });

    if (mobileNavToggle && mobileNavPanel) {
        const setMobileNavOpen = (isOpen) => {
            mobileNavPanel.classList.toggle('hidden', !isOpen);
            mobileNavPanel.classList.toggle('flex', isOpen);
            mobileNavPanel.classList.toggle('is-open', isOpen);
            mobileNavPanel.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
            mobileNavToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            mobileNavToggle.setAttribute('aria-label', isOpen ? 'Close navigation menu' : 'Open navigation menu');
        };

        mobileNavToggle.addEventListener('click', () => {
            setMobileNavOpen(!mobileNavPanel.classList.contains('is-open'));
        });

        mobileNavPanel.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => setMobileNavOpen(false));
        });

        document.addEventListener('click', (event) => {
            if (!mobileNavPanel.contains(event.target) && !mobileNavToggle.contains(event.target)) {
                setMobileNavOpen(false);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') setMobileNavOpen(false);
        });

        // A mobile browser can restore the DOM from its back/forward cache,
        // including the previous open drawer state. Always start a shown or
        // reloaded page with a closed drawer so the header remains usable.
        const closeRestoredMobileNav = () => setMobileNavOpen(false);
        window.addEventListener('pageshow', closeRestoredMobileNav);
        window.addEventListener('pagehide', closeRestoredMobileNav);
        closeRestoredMobileNav();
    }

    const profile = document.querySelector('[data-profile-menu]');
    const trigger = profile?.querySelector('[data-profile-trigger]');
    const menu = profile?.querySelector('[data-profile-menu-panel]');

    if (!profile || !trigger || !menu) return;

    const setMenuOpen = (isOpen) => {
        menu.classList.toggle('is-open', isOpen);
        trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        menu.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
    };

    trigger.addEventListener('click', () => {
        setMenuOpen(!menu.classList.contains('is-open'));
    });

    document.addEventListener('click', (event) => {
        if (!profile.contains(event.target)) setMenuOpen(false);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setMenuOpen(false);
            trigger.focus();
        }
    });
});

function updateRHUStatus() {
    const now = new Date();
    const hours = now.getHours();
    const dayOfWeek = now.getDay();
    
    const isWeekday = (dayOfWeek >= 1 && dayOfWeek <= 5);
    const isOpenHours = (hours >= 8 && hours < 17);
    const rhuIsOpen = isWeekday && isOpenHours;
    
    const statusSpan = document.querySelector('[data-rhu-status]');
    if (!statusSpan) return;
    
    let borderClass, bgClass, textClass, dotClass, message, shouldPulse;
    
    if (rhuIsOpen) {
        borderClass = 'border-emerald-200';
        bgClass = 'bg-emerald-50';
        textClass = 'text-emerald-700';
        dotClass = 'bg-emerald-500';
        message = 'RHU II is Now Open | will close at 5PM';
        shouldPulse = true;
    } else {
        borderClass = 'border-slate-300';
        bgClass = 'bg-slate-100';
        textClass = 'text-slate-600';
        dotClass = 'bg-slate-400';
        shouldPulse = false;
        
        if (isWeekday && hours < 8) {
            message = 'RHU II is Closed | Will resume today at 8am';
        } else if (isWeekday && hours >= 17) {
            message = 'RHU II is Closed | Will resume tomorrow at 8am';
        } else {
            message = 'RHU II is Closed | Will resume Monday at 8am';
        }
    }
    
    // Update all status spans
    document.querySelectorAll('[data-rhu-status]').forEach(span => {
        span.className = `rhu-status ${rhuIsOpen ? 'rhu-open' : 'rhu-closed'} inline-flex items-center gap-1.5 rounded-full border ${borderClass} ${bgClass} ${textClass} px-4 py-2 text-xs font-semibold ${span.id === 'floatingRhuBar' ? 'shadow-lg backdrop-blur-sm' : ''}`;
    });
    
    // Update dots
    document.querySelectorAll('[data-rhu-dot]').forEach(dot => {
        dot.className = `h-2.5 w-2.5 rounded-full ${dotClass} ${shouldPulse ? 'animate-pulse' : ''}`;
    });
    
    // Update messages
    document.querySelectorAll('[data-rhu-message]').forEach(msg => {
        msg.textContent = message;
    });
}

function handleFloatingBarScroll() {
    const currentScrollY = window.scrollY;
    const floatingBar = document.getElementById('floatingRhuBar');

    if (!floatingBar) return;

    // Keep the status visible at the top of the landing page. It then uses
    // the same smooth slide-up/down behavior as the rest of the site.
    if (currentScrollY === 0 && !isBarVisible) {
        isBarVisible = true;
        floatingBar.style.transform = 'translateY(0)';
        floatingBar.style.opacity = '1';
        floatingBar.style.pointerEvents = 'auto';
    }
    
    // Scrolling down - hide the bar
    if (currentScrollY > lastScrollY && isBarVisible) {
        isBarVisible = false;
        floatingBar.style.transform = 'translateY(-120%)';
        floatingBar.style.opacity = '0';
        floatingBar.style.pointerEvents = 'none';
    } 
    // Scrolling up - show the bar
    else if (currentScrollY < lastScrollY && !isBarVisible) {
        isBarVisible = true;
        floatingBar.style.transform = 'translateY(0)';
        floatingBar.style.opacity = '1';
        floatingBar.style.pointerEvents = 'auto';
    }
    
    lastScrollY = currentScrollY;
}

// Update on page load
document.addEventListener('DOMContentLoaded', updateRHUStatus);

// Update every minute
setInterval(updateRHUStatus, 60000);

// Handle scroll
window.addEventListener('scroll', handleFloatingBarScroll, { passive: true });

// Also update when tab becomes visible
document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
        updateRHUStatus();
    }
});
</script>
