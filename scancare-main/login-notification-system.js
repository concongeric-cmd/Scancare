// ============================================================================
// LOGIN APPROVAL NOTIFICATION SYSTEM
// Real-time notifications for pending login requests on logged-in devices
// Include this in your dashboard pages
// ============================================================================

(function() {
    'use strict';

    if (window.__loginApprovalNotificationSystemInitialized) {
        return;
    }
    window.__loginApprovalNotificationSystemInitialized = true;

    // Configuration
    const CONFIG = {
        pollInterval: 3000,          // Check every 3 seconds without overloading the API
        showNotification: true,       // Show notification sound
        autoHideModal: false,        // Don't auto-close (user must dismiss)
        maxRetries: 3
    };

    // Track shown notifications to prevent loop
    let shownNotifications = new Set();
    let pollInterval = null;
    let isCheckingPending = false;

    // Modal HTML
    const MODAL_HTML = `
    <div id="loginApprovalNotification" class="login-approval-modal fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center" style="display:none;" aria-hidden="true">
        <div class="bg-white/95 rounded-2xl shadow-2xl max-w-lg w-full p-0 overflow-hidden animate-slideIn" style="border:1px solid rgba(69,75,27,0.08)">
            <div style="background:linear-gradient(90deg,#3f4720 0%, #516032 100%); padding:18px 20px; display:flex; align-items:center; justify-content:space-between; gap:12px">
                <div style="display:flex; align-items:center; gap:12px">
                    <div style="width:56px; height:56px; border-radius:12px; background:rgba(255,255,255,0.08); display:flex; align-items:center; justify-content:center; box-shadow: 0 6px 18px rgba(10,10,10,0.12)">
                        <i class="fas fa-shield-alt text-white" style="font-size:22px"></i>
                    </div>
                    <div style="color: white;">
                        <div style="font-weight:700; font-size:18px;">New Login Attempt Detected</div>
                        <div style="font-size:13px; opacity:0.9; margin-top:2px">Protect your account — approve or deny</div>
                    </div>
                </div>
                <button id="closeNotification" aria-label="Close login request" style="background:transparent; border:none; color:rgba(255,255,255,0.9); font-size:18px">×</button>
            </div>

            <div style="padding:18px 20px; display:flex; gap:18px; align-items:flex-start;">
                <div style="flex:1; min-width:0">
                    <div id="notificationDetails" style="background:#F6F7F2; border-radius:12px; padding:14px;">
                        <div style="display:flex; gap:12px; align-items:center; margin-bottom:10px">
                            <div style="width:44px; height:44px; border-radius:8px; background:linear-gradient(180deg,#fff,#f3f4ed); display:flex; align-items:center; justify-content:center; color:#2b2b2b; box-shadow: 0 6px 14px rgba(10,10,10,0.06)">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <div style="min-width:0">
                                <div style="font-size:11px; color:#6b6b6b; font-weight:600">DEVICE</div>
                                <div id="notifDevice" style="font-size:14px; font-weight:700; color:#222; white-space:nowrap; overflow:hidden; text-overflow:ellipsis">Unknown Device</div>
                            </div>
                        </div>

                        <div style="display:flex; gap:12px; align-items:center; margin-bottom:10px">
                            <div style="width:44px; height:44px; border-radius:8px; background:#fff; display:flex; align-items:center; justify-content:center; color:#2b2b2b; box-shadow: 0 6px 14px rgba(10,10,10,0.04)">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div>
                                <div style="font-size:11px; color:#6b6b6b; font-weight:600">IP ADDRESS</div>
                                <div id="notifIP" style="font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Monospace; font-size:13px; font-weight:700; color:#222">Unknown</div>
                            </div>
                        </div>

                        <div style="display:flex; gap:12px; align-items:center">
                            <div style="width:44px; height:44px; border-radius:8px; background:#fff; display:flex; align-items:center; justify-content:center; color:#2b2b2b; box-shadow: 0 6px 14px rgba(10,10,10,0.04)">
                                <i class="fas fa-key"></i>
                            </div>
                            <div>
                                <div style="font-size:11px; color:#6b6b6b; font-weight:600">VERIFICATION CODE</div>
                                <div id="notifCode" style="font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Monospace; font-size:16px; font-weight:800; letter-spacing:2px; color:#222">---</div>
                            </div>
                        </div>

                        <div style="display:flex; gap:12px; align-items:center; margin-top:10px">
                            <div style="width:44px; height:44px; border-radius:8px; background:#fff; display:flex; align-items:center; justify-content:center; color:#2b2b2b; box-shadow: 0 6px 14px rgba(10,10,10,0.04)">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <div style="font-size:11px; color:#6b6b6b; font-weight:600">REQUESTED</div>
                                <div id="notifTime" style="font-size:13px; font-weight:700; color:#222">Unknown</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="width:220px; display:flex; flex-direction:column; gap:12px">
                    <div style="background:linear-gradient(180deg, rgba(69,75,27,0.06), rgba(69,75,27,0.03)); border-radius:10px; padding:12px; border:1px solid rgba(69,75,27,0.06);">
                        <div style="font-size:12px; color:#42502a; font-weight:700; margin-bottom:6px">Is this you?</div>
                        <div style="font-size:13px; color:#56643a">Confirm if you were the one trying to sign in from another device.</div>
                    </div>

                    <button id="approveBtn" style="background:linear-gradient(180deg,#2f8a39,#1f7a2a); border:none; color:white; font-weight:800; padding:12px; border-radius:10px; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px">
                        <i class="fas fa-check-circle" style="font-size:16px"></i> Approve Login
                    </button>

                    <button id="denyBtn" style="background:transparent; border:1px solid rgba(220,38,38,0.12); color:#b93636; font-weight:700; padding:10px; border-radius:10px; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px">
                        <i class="fas fa-times-circle" style="font-size:16px"></i> Reject Login
                    </button>

                    <div style="font-size:12px; color:#7a7a7a; text-align:center; margin-top:6px">Your account is protected</div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-slideIn { animation: slideIn 0.26s cubic-bezier(.2,.9,.2,1); }
        #loginApprovalNotification button[disabled] { opacity: 0.6; cursor: not-allowed; }
        .login-approval-modal {
            z-index: 10000 !important;
            box-sizing: border-box;
            padding: 76px 16px 20px !important;
            color-scheme: light;
        }
        .login-approval-modal > div {
            background: #ffffff !important;
            color: #1f2937 !important;
            max-height: calc(100dvh - 96px);
            overflow: hidden;
        }
        .login-approval-modal > div > div:nth-child(2) {
            min-height: 0;
            overflow-y: auto;
        }
        .login-approval-modal > div > div:first-child {
            padding: 12px 16px !important;
        }
        .login-approval-modal > div > div:first-child > div:first-child > div:first-child {
            width: 44px !important;
            height: 44px !important;
        }
        .login-approval-modal > div > div:first-child > div:first-child > div:last-child > div:first-child {
            font-size: 16px !important;
        }
        .login-approval-modal > div > div:first-child > div:first-child > div:last-child > div:last-child {
            font-size: 12px !important;
        }
        .login-approval-modal > div > div:nth-child(2) {
            padding: 12px 16px !important;
            gap: 12px !important;
        }
        .login-approval-modal #notificationDetails {
            padding: 10px !important;
        }
        .login-approval-modal #notificationDetails [style*="width:44px"] {
            width: 36px !important;
            height: 36px !important;
        }
        .login-approval-modal [style*="width:220px"] > div:first-child {
            padding: 10px !important;
        }
        .login-approval-modal #approveBtn,
        .login-approval-modal #denyBtn {
            padding: 10px !important;
        }
        @media (max-width:640px) {
            #loginApprovalNotification { align-items: flex-start; overflow: hidden; padding: 72px 12px 12px !important; }
            #loginApprovalNotification > div { max-width: 100%; margin: 0; max-height: calc(100dvh - 84px); }
            #loginApprovalNotification > div > div:first-child { order: 0; display: flex !important; }
            #loginApprovalNotification > div > div:nth-child(2) { display: block !important; padding: 12px !important; }
            #loginApprovalNotification [style*="width:220px"] { width: auto !important; margin-top: 14px; display: grid !important; grid-template-columns: 1fr 1fr; gap: 10px !important; }
            #loginApprovalNotification [style*="width:220px"] > div:first-child,
            #loginApprovalNotification [style*="width:220px"] > div:last-child { grid-column: 1 / -1; }
            #loginApprovalNotification [style*="white-space:nowrap"] { white-space: normal !important; overflow: visible !important; text-overflow: clip !important; overflow-wrap: anywhere; }
            #loginApprovalNotification #approveBtn,
            #loginApprovalNotification #denyBtn { width: 100%; min-height: 44px; }
        }
    </style>
    `;

    // Initialize notification system
    function init() {
        // Inject modal into DOM
        document.body.insertAdjacentHTML('beforeend', MODAL_HTML);

        const modal = document.getElementById('loginApprovalNotification');
        if (modal) {
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            modal.classList.add('hidden');
        }

        const closeBtn = document.getElementById('closeNotification');
        const approveBtn = document.getElementById('approveBtn');
        const denyBtn = document.getElementById('denyBtn');

        if (!closeBtn || !approveBtn || !denyBtn) {
            console.error('[LoginApproval] Notification modal controls are missing');
            return;
        }

        // Setup event listeners
        closeBtn.addEventListener('click', () => hideNotification());
        approveBtn.addEventListener('click', () => handleApproval(true));
        denyBtn.addEventListener('click', () => handleApproval(false));

        // Start polling
        console.info('[LoginApproval] Notification system initialized');
        pollForPendingLogins();
    }

    // Poll for pending login requests
    function pollForPendingLogins() {
        // Clear any existing interval
        if (pollInterval) clearInterval(pollInterval);
        
        async function checkPendingLogins() {
            if (!navigator.onLine) {
                clearPollingError();
                return;
            }

            const modal = document.getElementById('loginApprovalNotification');
            
            // Skip polling if modal is already visible (notification already shown)
            if (modal && !modal.classList.contains('hidden')) {
                return;
            }
            
            if (isCheckingPending) {
                return;
            }
            isCheckingPending = true;

            const url = 'get_pending_logins.php?ts=' + Date.now();
            console.debug('[LoginApproval] polling pending login API:', url);
            try {
                const response = await fetch(url, {
                    credentials: 'same-origin' // IMPORTANT: Include session cookies
                });
                if (!response.ok) {
                    if (response.status === 503 || response.statusText.toLowerCase().includes('offline')) {
                        clearPollingError();
                        return;
                    }
                    console.warn('[LoginApproval] pending logins request failed:', response.status, response.statusText);
                    showPollingError();
                    return;
                }

                // Defensive JSON parsing: servers sometimes return HTML on errors
                let data;
                const ctype = response.headers.get('content-type') || '';
                if (ctype.indexOf('application/json') !== -1) {
                    try {
                        data = await response.json();
                    } catch (err) {
                        console.error('[LoginApproval] Failed to parse JSON response', err);
                        const text = await response.text();
                        console.debug('[LoginApproval] Response text:', text);
                        showPollingError();
                        return;
                    }
                } else {
                    // Not JSON — read text for debugging and bail out
                    const text = await response.text();
                    console.warn('[LoginApproval] Unexpected response content-type:', ctype);
                    console.debug('[LoginApproval] Response text:', text.substring(0, 200));
                    showPollingError();
                    return;
                }
                console.debug('[LoginApproval] pending logins response', data);
                if (!data) {
                    return;
                }

                if (!data.success) {
                    console.warn('[LoginApproval] no active user session or no pending requests', data);
                    return;
                }

                if (data.pending && data.pending.length > 0) {
                    const request = data.pending[0];
                    const tokenHash = request.temp_token;
                    
                    // Only show if we haven't shown this notification already
                    if (!shownNotifications.has(tokenHash)) {
                        shownNotifications.add(tokenHash);
                        showNotification(request);
                    }
                }
                clearPollingError();
            } catch (error) {
                console.error('[LoginApproval] Error polling for logins:', error);
                showPollingError();
            } finally {
                isCheckingPending = false;
            }
        }

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                checkPendingLogins();
            }
        });

        window.addEventListener('focus', () => {
            checkPendingLogins();
        });

        window.addEventListener('online', () => {
            checkPendingLogins();
        });

        checkPendingLogins();
        pollInterval = setInterval(checkPendingLogins, CONFIG.pollInterval);
    }

    // Show a small indicator in console/UI when polling fails repeatedly
    let pollingErrorCount = 0;
    function showPollingError() {
        pollingErrorCount++;
        if (pollingErrorCount === 1) {
            console.warn('[LoginApproval] Polling errors detected — will retry in background');
        }
        const badgeId = 'loginApprovalPollingErrorBadge';
        if (!document.getElementById(badgeId)) {
            const el = document.createElement('div');
            el.id = badgeId;
            el.style.position = 'fixed';
            el.style.bottom = '14px';
            el.style.right = '14px';
            el.style.padding = '8px 12px';
            el.style.background = 'rgba(220,38,38,0.9)';
            el.style.color = 'white';
            el.style.borderRadius = '10px';
            el.style.zIndex = 99999;
            el.style.fontSize = '13px';
            el.textContent = 'Login approval: connection issues';
            document.body.appendChild(el);
        }
    }

    function clearPollingError() {
        pollingErrorCount = 0;
        const badge = document.getElementById('loginApprovalPollingErrorBadge');
        if (badge) badge.remove();
    }

    // Show notification modal
    function showNotification(request) {
        // Extract device info from User-Agent
        const deviceInfo = extractDeviceInfo(request.device_id);
        const verificationCode = request.temp_token.substring(0, 8).toUpperCase();

        // Update modal content
        document.getElementById('notifDevice').textContent = deviceInfo;
        document.getElementById('notifIP').textContent = request.ip_address || 'Unknown';
        document.getElementById('notifCode').textContent = verificationCode + '...';
        document.getElementById('notifTime').textContent = request.created_at ? new Date(request.created_at.replace(' ', 'T')).toLocaleString() : 'Unknown';

        // Store temp_token for approval/rejection
        document.getElementById('loginApprovalNotification').dataset.tempToken = request.temp_token;

        // Show modal
        const modal = document.getElementById('loginApprovalNotification');
        if (modal) {
            modal.style.display = 'flex';
            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
        }

        // Play notification sound (optional)
        playNotificationSound();
    }

    // Hide notification modal
    function hideNotification() {
        const modal = document.getElementById('loginApprovalNotification');
        if (modal) {
            modal.style.display = 'none';
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
        }
        // Don't clear from shownNotifications here - keep it shown to prevent duplicate handling
    }

    // Handle approval or rejection
    function handleApproval(approved) {
        const modal = document.getElementById('loginApprovalNotification');
        const tempToken = modal.dataset.tempToken;
        const approveBtn = document.getElementById('approveBtn');
        const denyBtn = document.getElementById('denyBtn');

        if (!tempToken) {
            console.error('❌ No temp token found');
            hideNotification();
            return;
        }

        console.log((approved ? '✅ APPROVING' : '❌ DENYING') + ' login with token: ' + tempToken.substring(0, 16) + '...');

        // Disable buttons during submission
        approveBtn.disabled = true;
        denyBtn.disabled = true;
        approveBtn.classList.add('opacity-50', 'cursor-not-allowed');
        denyBtn.classList.add('opacity-50', 'cursor-not-allowed');

        // Send approval/rejection request
        const formData = new FormData();
        formData.append('action', approved ? 'approve' : 'reject');
        formData.append('temp_token', tempToken);

        fetch('approve_login_request.php', {
            method: 'POST',
            credentials: 'same-origin', // IMPORTANT: Include session cookies
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Response:', data);
            if (data.success) {
                // Show success message
                showSuccessMessage(approved);
                
                // Remove from tracking and hide modal after a delay
                shownNotifications.delete(tempToken);
                
                setTimeout(() => {
                    hideNotification();
                    // Clear modal content for next request
                    clearModalContent();
                    const actionPanel = document.getElementById('approveBtn').parentElement;
                    actionPanel.style.display = '';
                    // Re-enable buttons
                    approveBtn.disabled = false;
                    denyBtn.disabled = false;
                    approveBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    denyBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }, 2000);
            } else {
                console.error('❌ Error response:', data.message);
                alert('Error: ' + (data.message || 'Failed to process request'));
                approveBtn.disabled = false;
                denyBtn.disabled = false;
                approveBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                denyBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        })
        .catch(error => {
            console.error('❌ Error:', error);
            alert('Error processing request');
            approveBtn.disabled = false;
            denyBtn.disabled = false;
            approveBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            denyBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        });
    }

    // Clear modal content for next request
    function clearModalContent() {
        document.getElementById('notifDevice').textContent = 'Unknown Device';
        document.getElementById('notifIP').textContent = 'Unknown';
        document.getElementById('notifCode').textContent = '---';
        document.getElementById('notifTime').textContent = 'Unknown';
        
        // Remove any success messages
        const successMessages = document.querySelectorAll('.bg-green-50, .bg-red-50');
        successMessages.forEach(msg => msg.remove());
    }

    // Show success message
    function showSuccessMessage(approved) {
        const details = document.getElementById('notificationDetails').parentElement;
        const message = approved ? 
            '<i class="fas fa-check-circle text-green-600 mr-2"></i>Login approved! Device is now logged in.' :
            '<i class="fas fa-ban text-red-600 mr-2"></i>Login denied! Device has been blocked.';
        
        const alertDiv = document.createElement('div');
        alertDiv.className = approved ? 
            'bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm mb-4' :
            'bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm mb-4';
        alertDiv.style.flexBasis = '100%';
        alertDiv.style.width = '100%';
        alertDiv.style.order = '-1';
        alertDiv.style.backgroundColor = approved ? '#f0fdf4' : '#fef2f2';
        alertDiv.style.borderColor = approved ? '#bbf7d0' : '#fecaca';
        alertDiv.style.color = approved ? '#15803d' : '#b91c1c';
        alertDiv.innerHTML = message;

        if (approved) {
            const actionPanel = document.getElementById('approveBtn').parentElement;
            actionPanel.style.display = 'none';
        }
        
        const content = details.parentElement.parentElement;
        content.insertAdjacentElement('afterbegin', alertDiv);
    }

    // Extract device info from User-Agent
    function extractDeviceInfo(userAgent) {
        if (!userAgent) return 'Unknown Device';
        
        const ua = userAgent.substring(0, 255);
        
        // Mobile devices
        if (ua.indexOf('Android') !== -1) return 'Android Device';
        if (ua.indexOf('iPhone') !== -1) return 'iPhone';
        if (ua.indexOf('iPad') !== -1) return 'iPad';
        if (ua.indexOf('Windows Phone') !== -1) return 'Windows Phone';
        
        // Browsers
        if (ua.indexOf('Chrome') !== -1) return 'Chrome Browser';
        if (ua.indexOf('Firefox') !== -1) return 'Firefox Browser';
        if (ua.indexOf('Safari') !== -1) return 'Safari Browser';
        if (ua.indexOf('Edge') !== -1) return 'Edge Browser';
        
        // OS
        if (ua.indexOf('Windows') !== -1) return 'Windows PC';
        if (ua.indexOf('Mac') !== -1) return 'Mac';
        if (ua.indexOf('Linux') !== -1) return 'Linux';
        
        return ua.substring(0, 30) + '...';
    }

    // Play notification sound (optional)
    function playNotificationSound() {
        if (!CONFIG.showNotification) return;
        
        try {
            // Create a simple beep sound
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.value = 800;
            oscillator.type = 'sine';
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.5);
        } catch (e) {
            // Audio context not available, silently fail
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
