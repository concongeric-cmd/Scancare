<?php
// New reusable header for BHW dashboard (v2)
// Expects: $worker_name (string), optional $profile_picture (URL)
$sessionUser = is_array($_SESSION['user'] ?? null) ? $_SESSION['user'] : [];
$userAvatar = $_SESSION['profile_picture']
    ?? $_SESSION['profile_pic']
    ?? $sessionUser['profile_picture']
    ?? $sessionUser['profile_pic']
    ?? $_SESSION['avatar']
    ?? $sessionUser['avatar']
    ?? (isset($profile_picture) && !empty($profile_picture) ? $profile_picture : null);
$rawAvatar = is_string($userAvatar) ? $userAvatar : '';
$cleanAvatar = ltrim(str_replace(['../', '..\\'], '', $rawAvatar), '/\\');
$uploadsPath = strpos(str_replace('\\', '/', $cleanAvatar), 'uploads/avatars/');
if ($uploadsPath !== false) {
    $cleanAvatar = substr(str_replace('\\', '/', $cleanAvatar), $uploadsPath);
}
$hasAvatar = !empty($cleanAvatar);
$documentRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__)) ?: '');
$projectRoot = str_replace('\\', '/', realpath(dirname(__DIR__)) ?: '');
$appBasePath = $documentRoot !== '' && str_starts_with($projectRoot, $documentRoot)
    ? substr($projectRoot, strlen($documentRoot))
    : '';
$avatarUrl = rtrim($appBasePath, '/') . '/' . $cleanAvatar;
$avatarUrl = '/' . ltrim($avatarUrl, '/');
$display_name = isset($worker_name) ? htmlspecialchars(substr($worker_name, 0, 25)) : 'Health Worker';
?>
<nav id="bhwHeader" data-worker-id="<?= (int) ($_SESSION['worker_id'] ?? 0) ?>" class="fixed inset-x-0 top-0 z-[10000] h-20 w-full border-b border-slate-200 bg-white/95 shadow-sm backdrop-blur-md dark:border-slate-700 dark:bg-slate-900/95">
    <div class="mx-auto flex h-20 w-full max-w-7xl items-center justify-between gap-2 px-2 sm:gap-6 sm:px-6 lg:px-8">
        <div class="flex min-w-0 items-center gap-2 sm:gap-6">
            <button id="mobileNavToggleBtn" type="button" class="mobile-nav-toggle" aria-expanded="false" aria-controls="mobileSidebarNav" onclick="toggleMobileNav()">
                <span class="mobile-nav-icon">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
            </button>

            <div class="flex min-w-0 items-center gap-2 sm:gap-3 flex-shrink-0">
                <img src="images/SClogo.png" alt="ScanCare Logo" class="h-9 w-9 sm:h-12 sm:w-12 object-contain">
                <span class="font-heading text-lg sm:text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                    Scan<span class="text-[#3D4A1E]">Care</span>
                </span>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-2 sm:gap-4">
            <div class="profile-dropdown-container flex shrink-0 items-center gap-3">
                <div class="hidden md:flex flex-col text-left">
                    <div class="text-sm font-semibold text-[#3D4A1E] dark:text-emerald-300">
                        <?php echo $display_name; ?>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Health Worker • Barangay Gutad</p>
                </div>

                <button id="profileDropdownBtn" onclick="toggleProfileDropdown()" class="profile-dropdown-btn" aria-haspopup="true" aria-expanded="false">
                    <?php if ($hasAvatar): ?>
                        <img id="profileImageNavbar" src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8') ?>" class="w-10 h-10 rounded-full object-cover shrink-0 border border-slate-200 dark:border-slate-700 shadow-sm" onerror="console.error('Image failed to load at path:', this.src); this.style.display='none'; this.nextElementSibling.style.display='flex';" alt="Profile">
                        <div id="profileInitials" class="hidden w-10 h-10 rounded-full bg-[#454B1B] text-white font-bold items-center justify-center text-sm shadow-sm">HW</div>
                    <?php else: ?>
                        <!-- Always render the image node: crop uploads update it without a reload. -->
                        <img id="profileImageNavbar" src="" class="w-10 h-10 rounded-full object-cover shrink-0 border border-slate-200 dark:border-slate-700 shadow-sm" style="display:none" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" alt="Profile">
                        <div id="profileInitials" class="w-10 h-10 rounded-full bg-[#454B1B] text-white font-bold flex items-center justify-center text-sm shadow-sm">HW</div>
                    <?php endif; ?>
                </button>
            </div>

            <button type="button" id="dashboardThemeToggle" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-[#3D4A1E] transition hover:bg-[#3D4A1E]/10 dark:text-emerald-300 dark:hover:bg-emerald-300/15 sm:h-11 sm:w-11" aria-label="Toggle dark mode" title="Toggle dark mode">
                <i class="fas fa-moon" aria-hidden="true"></i>
            </button>

            <div id="profileDropdownMenu" class="profile-dropdown-menu" style="display: none;" role="menu" aria-hidden="true">
                <div class="profile-dropdown-header">
                    <?php if ($hasAvatar): ?>
                        <img id="dropdownProfileImage" src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Profile" class="dropdown-profile-img" style="display: block; object-fit: cover;" onerror="console.error('Image failed to load at path:', this.src); this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div id="dropdownInitials" class="dropdown-profile-initials" style="display:none">HW</div>
                    <?php else: ?>
                        <!-- The database loader also needs this node after a new login. -->
                        <img id="dropdownProfileImage" src="" alt="Profile" class="dropdown-profile-img" style="display:none; object-fit:cover;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div id="dropdownInitials" class="dropdown-profile-initials">HW</div>
                    <?php endif; ?>
                    <div>
                        <div class="dropdown-name"><?php echo $display_name; ?></div>
                        <div class="dropdown-role">Health Worker Account</div>
                    </div>
                </div>

                <div class="profile-dropdown-divider"></div>

                <a href="#" id="profileChangePhotoBtn" class="dropdown-menu-item" role="menuitem">
                    <i class="fas fa-camera"></i> Change Photo
                </a>
                <a href="javascript:void(0)" onclick="openSettingsModal()" class="dropdown-menu-item" role="menuitem">
                    <i class="fas fa-cog"></i> Settings
                </a>

                <div class="profile-dropdown-divider"></div>

                <a href="javascript:void(0)" onclick="openBHWLogoutConfirmModal()" class="dropdown-menu-item logout-item" role="menuitem">
                    <i class="fas fa-sign-out-alt"></i> Log Out
                </a>
            </div>

            <input type="file" id="profileUploadInput" name="profile_picture" class="profile-upload-input" accept=".jpg,.jpeg,.png,image/jpeg,image/png" style="display:none">
        </div>
    </div>
</nav>

<style>
    .bhw-cropper-modal { position: fixed; inset: 0; z-index: 20000; display: flex; align-items: center; justify-content: center; padding: 1rem; background: rgba(15, 23, 42, .72); }
    .bhw-cropper-card { width: min(600px, 100%); overflow: hidden; border-radius: 16px; background: #fff; box-shadow: 0 25px 50px rgba(0, 0, 0, .3); }
    .bhw-cropper-header, .bhw-cropper-footer { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: 1rem 1.25rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
    .bhw-cropper-header h3 { margin: 0; color: #454B1B; font-size: 1.125rem; font-weight: 700; }
    .bhw-cropper-close { border: 0; background: transparent; color: #64748b; cursor: pointer; font-size: 1.75rem; line-height: 1; }
    .bhw-cropper-body { display: grid; gap: 1rem; padding: 1.25rem; }
    .bhw-cropper-preview { display: grid; place-items: center; width: 100%; height: 350px; overflow: hidden; border: 2px dashed #cbd5e1; border-radius: 12px; background: #f1f5f9; }
    #bhwCropperCanvas { display: block; max-width: 100%; max-height: 100%; cursor: grab; }
    #bhwCropperCanvas:active { cursor: grabbing; }
    .bhw-cropper-controls { display: grid; gap: .5rem; }
    .bhw-cropper-controls label { color: #454B1B; font-size: .875rem; font-weight: 600; }
    .bhw-cropper-controls input { width: 100%; accent-color: #454B1B; }
    #bhwZoomValue { display: inline-block; width: fit-content; border-radius: 999px; padding: .25rem .75rem; background: #454B1B; color: #fff; font-size: .75rem; font-weight: 700; }
    .bhw-cropper-footer { justify-content: flex-end; border-top: 1px solid #e2e8f0; border-bottom: 0; }
    .bhw-cropper-footer button { border: 0; border-radius: 8px; padding: .625rem 1rem; font-weight: 700; cursor: pointer; }
    .bhw-cropper-cancel { background: #e2e8f0; color: #454B1B; }
    .bhw-cropper-save { background: #454B1B; color: #fff; }
</style>

<div id="bhwCropperModal" class="bhw-cropper-modal" style="display:none" role="dialog" aria-modal="true" aria-labelledby="bhwCropperTitle">
    <div class="bhw-cropper-card">
        <div class="bhw-cropper-header">
            <h3 id="bhwCropperTitle">Adjust Your Profile Picture</h3>
            <button type="button" class="bhw-cropper-close" id="bhwCropperClose" aria-label="Close">&times;</button>
        </div>
        <div class="bhw-cropper-body">
            <div class="bhw-cropper-preview"><canvas id="bhwCropperCanvas"></canvas></div>
            <div class="bhw-cropper-controls">
                <label for="bhwZoomSlider">Zoom</label>
                <input type="range" id="bhwZoomSlider" min="0.5" max="3" step="0.1" value="1">
                <span id="bhwZoomValue">100%</span>
                <small>Drag the image to position it inside the circle.</small>
            </div>
        </div>
        <div class="bhw-cropper-footer">
            <button type="button" class="bhw-cropper-cancel" id="bhwCropperCancel">Cancel</button>
            <button type="button" class="bhw-cropper-save" id="bhwCropperSave">Save &amp; Upload</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const changePhotoButton = document.getElementById('profileChangePhotoBtn');
    const profileUploadInput = document.getElementById('profileUploadInput');
    if (!changePhotoButton || !profileUploadInput) return;

    changePhotoButton.addEventListener('click', function (event) {
        event.preventDefault();
        profileUploadInput.click();
    });
    profileUploadInput.addEventListener('change', function (event) {
        if (typeof window.handleProfileUpload === 'function') {
            window.handleProfileUpload(event);
        }
    });

    const zoomSlider = document.getElementById('bhwZoomSlider');
    const zoomValue = document.getElementById('bhwZoomValue');
    const cropperCanvas = document.getElementById('bhwCropperCanvas');
    zoomSlider?.addEventListener('input', function () {
        if (window.bhwCropperState) window.bhwCropperState.zoom = Number(this.value);
        if (zoomValue) zoomValue.textContent = `${Math.round(Number(this.value) * 100)}%`;
        if (typeof window.drawBhwCropper === 'function') window.drawBhwCropper();
    });
    document.getElementById('bhwCropperClose')?.addEventListener('click', window.closeBhwCropper);
    document.getElementById('bhwCropperCancel')?.addEventListener('click', window.closeBhwCropper);
    document.getElementById('bhwCropperSave')?.addEventListener('click', window.confirmBhwCropper);

    if (cropperCanvas) {
        cropperCanvas.addEventListener('pointerdown', function (event) {
            if (!window.bhwCropperState) return;
            window.bhwCropperState.dragging = true;
            window.bhwCropperState.lastX = event.clientX;
            window.bhwCropperState.lastY = event.clientY;
            cropperCanvas.setPointerCapture(event.pointerId);
        });
        cropperCanvas.addEventListener('pointermove', function (event) {
            if (!window.bhwCropperState?.dragging) return;
            window.bhwCropperState.panX += event.clientX - window.bhwCropperState.lastX;
            window.bhwCropperState.panY += event.clientY - window.bhwCropperState.lastY;
            window.bhwCropperState.lastX = event.clientX;
            window.bhwCropperState.lastY = event.clientY;
            if (typeof window.drawBhwCropper === 'function') window.drawBhwCropper();
        });
        cropperCanvas.addEventListener('pointerup', function () {
            if (window.bhwCropperState) window.bhwCropperState.dragging = false;
        });
    }
});

// Ensure dropdown closes on Escape and update aria attributes
document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') {
        const dropdown = document.getElementById('profileDropdownMenu');
        const btn = document.getElementById('profileDropdownBtn');
        if (dropdown && btn) {
            dropdown.style.display = 'none';
            dropdown.setAttribute('aria-hidden','true');
            btn.classList.remove('active');
            btn.setAttribute('aria-expanded','false');
        }
    }
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const themeToggle = document.getElementById('dashboardThemeToggle');
    if (!themeToggle) return;

    const updateThemeIcon = () => {
        const isDark = document.documentElement.classList.contains('dark');
        themeToggle.innerHTML = isDark
            ? '<i class="fas fa-sun" aria-hidden="true" style="color: #fbbf24 !important;"></i>'
            : '<i class="fas fa-moon" aria-hidden="true"></i>';
    };

    themeToggle.addEventListener('click', () => {
        const isDark = !document.documentElement.classList.contains('dark');
        if (window.ScanCareTheme) {
            window.ScanCareTheme.set(isDark ? 'dark' : 'light');
        } else {
            document.documentElement.classList.toggle('dark', isDark);
            document.documentElement.classList.toggle('dark-mode', isDark);
            document.body.classList.toggle('dark-mode', isDark);
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            localStorage.setItem('scancare_theme', isDark ? 'dark' : 'light');
            localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
        }
        updateThemeIcon();
    });

    updateThemeIcon();
});
</script>
<script>
// Make header fixed and ensure page content is not hidden behind it
function applyHeaderPadding() {
    const header = document.getElementById('bhwHeader');
    if (!header) return;
    // Ensure fixed positioning (in case CSS overridden)
    header.style.position = 'fixed';
    header.style.top = '0';
    header.style.left = '0';
    header.style.right = '0';
    // Apply padding to body so content isn't covered
    const height = header.offsetHeight || 80;
    document.body.style.paddingTop = height + 'px';
}

window.addEventListener('DOMContentLoaded', applyHeaderPadding);
window.addEventListener('resize', applyHeaderPadding);
// Also run immediately
applyHeaderPadding();
</script>
