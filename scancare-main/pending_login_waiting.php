<?php
// ============================================================================
// PENDING LOGIN APPROVAL WAITING PAGE
// Shown to new device attempting to login while waiting for approval from existing device
// ============================================================================

session_start();
require_once 'db_config.php';

// Get the temp token from session or fallback from query
$tempToken = $_SESSION['pending_login_temp_token'] ?? null;
$userName = $_SESSION['pending_login_user_name'] ?? 'User';
$userEmail = $_SESSION['pending_login_user_email'] ?? '';

if (!$tempToken && !empty($_GET['temp_token'])) {
    $tempToken = trim($_GET['temp_token']);
}

if (!$tempToken) {
    header('Location: login.php');
    exit;
}

// Check approval status via AJAX endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_status'])) {
    header('Content-Type: application/json');
    
    try {
        $pdo = getDatabaseConnection();
        
        // Debug: Log the request
        error_log('CHECK_STATUS: Looking for token: ' . substr($tempToken, 0, 16) . '... (length: ' . strlen($tempToken) . ')');
        
        $stmt = $pdo->prepare("
            SELECT id, status, user_id, user_type, created_at 
            FROM user_login_sessions 
            WHERE temp_token = ? LIMIT 1
        ");
        $stmt->execute([$tempToken]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            error_log('CHECK_STATUS: Found record - Status: ' . $result['status'] . ', ID: ' . $result['id']);
            echo json_encode([
                'status' => $result['status'],
                'token' => substr($tempToken, 0, 16) . '...',
                'record_id' => $result['id'],
                'debug' => true
            ]);
        } else {
            error_log('CHECK_STATUS: No record found for token');
            
            // Try to find any pending records for debugging
            $debugStmt = $pdo->prepare("
                SELECT temp_token, status 
                FROM user_login_sessions 
                WHERE temp_token LIKE ? 
                LIMIT 5
            ");
            $debugStmt->execute([substr($tempToken, 0, 20) . '%']);
            $debugResults = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
            error_log('DEBUG: Similar tokens found: ' . count($debugResults));
            
            echo json_encode(['status' => 'not_found']);
        }
    } catch (Exception $e) {
        error_log('CHECK_STATUS_ERROR: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approving Login — ScanCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap');
        .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-body { font-family: 'Inter', sans-serif; }
        
        .spinner {
            border: 3px solid rgba(69, 75, 27, 0.1);
            border-radius: 50%;
            border-top: 3px solid #454B1B;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .pulse-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #454B1B;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .slide-in {
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="bg-gradient-to-b from-[#454B1B] to-slate-900 font-body h-screen flex items-center justify-center">
    <div class="w-full max-w-md px-6">
        <!-- Main Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-8 slide-in">
            
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="flex justify-center mb-6">
                    <div class="bg-gradient-to-br from-[#454B1B] to-green-700 rounded-full p-4">
                        <i class="fas fa-lock text-white text-4xl"></i>
                    </div>
                </div>
                <h1 class="font-heading font-bold text-2xl text-slate-900 mb-2">
                    Confirm Your Login
                </h1>
                <p class="text-slate-600 text-sm">
                    A confirmation request has been sent to your active device
                </p>
            </div>

            <!-- Status Card -->
            <div class="bg-slate-50 border-2 border-blue-200 rounded-xl p-6 mb-8">
                <div class="flex items-center justify-center gap-4 mb-4">
                    <div class="spinner"></div>
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Waiting for Approval</p>
                        <p class="text-xs text-slate-500">Checking your device...</p>
                    </div>
                </div>
                
                <div class="border-t border-slate-200 pt-4">
                    <p class="text-xs text-slate-600 mb-3">
                        <i class="fas fa-user-circle mr-2 text-[#454B1B]"></i>
                        <strong>Account:</strong> <?php echo htmlspecialchars($userName); ?>
                    </p>
                    <?php if (!empty($userEmail)): ?>
                    <p class="text-xs text-slate-600">
                        <i class="fas fa-envelope mr-2 text-[#454B1B]"></i>
                        <strong>Email:</strong> <?php echo htmlspecialchars($userEmail); ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Instructions -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <p class="text-xs text-blue-900">
                    <i class="fas fa-info-circle mr-2 text-blue-600"></i>
                    <strong>What to do:</strong> Check your currently logged-in device. You'll see a confirmation prompt there.
                </p>
            </div>

            <!-- Help Section -->
            <div class="text-center mb-6">
                <p class="text-xs text-slate-600 mb-3">Approval code:</p>
                <p class="font-mono text-sm font-bold text-[#454B1B] bg-slate-100 px-3 py-2 rounded-lg tracking-widest">
                    <?php echo strtoupper(substr($tempToken, 0, 8)); ?>...
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-3">
                <button id="retryBtn" class="w-full bg-[#454B1B] hover:bg-[#3a3f16] text-white font-semibold py-3 px-4 rounded-lg transition-all duration-200 flex items-center justify-center gap-2">
                    <i class="fas fa-redo"></i> <span id="retryText">Check Status</span>
                </button>
                
                <a href="login.php?pending_rejected=1" class="block w-full bg-slate-200 hover:bg-slate-300 text-slate-900 font-semibold py-3 px-4 rounded-lg transition-all duration-200 text-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Login
                </a>
            </div>

            <!-- Timeout Warning -->
            <p class="text-center text-xs text-slate-500 mt-6">
                <i class="fas fa-clock"></i> This request will expire in 10 minutes
            </p>
        </div>

        <!-- Security Info Footer -->
        <div class="text-center mt-8">
            <p class="text-white text-xs opacity-75">
                <i class="fas fa-shield-alt mr-1"></i>
                Your login is protected with device verification
            </p>
        </div>
    </div>

    <script>
        const tempToken = '<?php echo htmlspecialchars($tempToken); ?>';
        const retryBtn = document.getElementById('retryBtn');
        const retryText = document.getElementById('retryText');
        let isChecking = false;
        let checkInterval = null;
        let checkCount = 0;
        const MAX_CHECKS = 300; // 10 minutes * 30 checks per minute (2 second intervals)

        // Auto-check every 2 seconds for faster response
        function autoCheck() {
            if (!isChecking && checkCount < MAX_CHECKS) {
                checkApprovalStatus();
            }
        }

        function checkApprovalStatus() {
            if (isChecking || checkCount >= MAX_CHECKS) return;
            
            isChecking = true;
            retryBtn.disabled = true;
            retryBtn.classList.add('opacity-50');
            retryText.textContent = 'Checking...';

            fetch('pending_login_waiting.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                credentials: 'same-origin', // IMPORTANT: Include cookies for session persistence
                body: 'check_status=1'
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                checkCount++;
                console.log('Status check #' + checkCount + ':', data);

                if (data.status === 'active') {
                    // Login approved! Redirect to dashboard
                    console.log('✅ Login approved! Redirecting...');
                    showSuccess('Login approved! Redirecting to dashboard...');
                    clearInterval(checkInterval);
                    isChecking = false;
                    
                    setTimeout(() => {
                        // Complete the login and redirect to dashboard
                        // Include the temp token as a fallback query param so completion works
                        // even if the session cookie was lost (eg. incognito)
                        window.location.href = 'login.php?complete_pending=1&temp_token=' + encodeURIComponent(tempToken);
                    }, 1000);
                } else if (data.status === 'rejected') {
                    // Login denied - show error and redirect
                    console.log('❌ Login rejected!');
                    showLoginDenied('Login request was denied. Your login attempt has been blocked.');
                    clearInterval(checkInterval);
                    isChecking = false;
                } else if (data.status === 'pending') {
                    // Still waiting
                    console.log('⏳ Still waiting for approval...');
                    isChecking = false;
                    retryBtn.disabled = false;
                    retryBtn.classList.remove('opacity-50');
                    retryText.textContent = 'Check Status';
                } else {
                    // Timeout - session expired
                    if (checkCount >= MAX_CHECKS) {
                        console.log('⏱️ Request expired');
                        showError('Request expired. Please login again.');
                        clearInterval(checkInterval);
                    }
                    isChecking = false;
                    retryBtn.disabled = false;
                    retryBtn.classList.remove('opacity-50');
                    retryText.textContent = 'Check Status';
                }
            })
            .catch(error => {
                console.error('❌ Check error:', error);
                isChecking = false;
                retryBtn.disabled = false;
                retryBtn.classList.remove('opacity-50');
                retryText.textContent = 'Check Status';
            });
        }

        function showSuccess(message) {
            retryBtn.innerHTML = '<i class="fas fa-check-circle"></i> ' + message;
            retryBtn.classList.remove('bg-[#454B1B]', 'hover:bg-[#3a3f16]');
            retryBtn.classList.add('bg-green-600');
            retryBtn.disabled = true;
        }

        function showLoginDenied(message) {
            // Change the entire page to show denial message
            document.body.innerHTML = `
                <div class="w-full max-w-md px-6">
                    <div class="bg-white rounded-2xl shadow-2xl p-8 slide-in">
                        <div class="text-center mb-8">
                            <div class="flex justify-center mb-6">
                                <div class="bg-gradient-to-br from-red-600 to-red-700 rounded-full p-4">
                                    <i class="fas fa-lock text-white text-4xl"></i>
                                </div>
                            </div>
                            <h1 class="font-heading font-bold text-2xl text-slate-900 mb-2">
                                Login Denied
                            </h1>
                            <p class="text-slate-600 text-sm">
                                Your login attempt has been rejected
                            </p>
                        </div>

                        <div class="bg-red-50 border-2 border-red-200 rounded-xl p-6 mb-8">
                            <p class="text-red-900 text-sm font-semibold mb-4">
                                <i class="fas fa-exclamation-circle mr-2"></i>
                                ${message}
                            </p>
                            <p class="text-red-700 text-xs">
                                The owner of this account has blocked your login attempt. 
                                If you think this is a mistake, please contact the account owner.
                            </p>
                        </div>

                        <a href="login.php" class="block w-full bg-[#454B1B] hover:bg-[#3a3f16] text-white font-semibold py-3 px-4 rounded-lg transition-all duration-200 text-center mb-3">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Login
                        </a>

                        <p class="text-center text-xs text-slate-500">
                            <i class="fas fa-shield-alt mr-1"></i>
                            Your account security is our priority
                        </p>
                    </div>
                </div>
            `;
        }

        function showError(message) {
            alert(message);
            window.location.href = 'login.php';
        }

        // Manual check button
        retryBtn.addEventListener('click', checkApprovalStatus);

        // Start auto-checking immediately with 2-second interval for faster responsiveness
        checkApprovalStatus();
        checkInterval = setInterval(autoCheck, 2000); // Check every 2 seconds for instant feedback

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            clearInterval(checkInterval);
        });
    </script>
</body>
</html>
