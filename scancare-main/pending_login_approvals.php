<?php
// ============================================================================
// PENDING LOGIN APPROVALS DASHBOARD
// Shows pending login requests from other devices for the current user
// ============================================================================

session_start();
require_once 'db_config.php';

// Redirect if not logged in
$isLoggedIn = !empty($_SESSION['resident_id']) || !empty($_SESSION['worker_id']) || !empty($_SESSION['admin_id']);
if (!$isLoggedIn) {
    header('Location: login.php');
    exit;
}

// Get current user info
function getCurrentUserFromSession() {
    if (!empty($_SESSION['resident_id'])) {
        return ['user_id' => $_SESSION['resident_id'], 'user_type' => $_SESSION['user_type'] ?? 'resident'];
    }
    if (!empty($_SESSION['worker_id'])) {
        return ['user_id' => $_SESSION['worker_id'], 'user_type' => $_SESSION['user_type'] ?? 'health_worker'];
    }
    if (!empty($_SESSION['admin_id'])) {
        return ['user_id' => $_SESSION['admin_id'], 'user_type' => $_SESSION['user_type'] ?? 'admin'];
    }
    if (!empty($_SESSION['user_id']) && !empty($_SESSION['user_type'])) {
        return ['user_id' => $_SESSION['user_id'], 'user_type' => $_SESSION['user_type']];
    }
    if (!empty($_SESSION['user_id']) && !empty($_SESSION['user_role'])) {
        return ['user_id' => $_SESSION['user_id'], 'user_type' => $_SESSION['user_role']];
    }
    return null;
}

$currentUser = getCurrentUserFromSession();
if (!$currentUser) {
    header('Location: login.php');
    exit;
}

$userId = $currentUser['user_id'];
$userType = $currentUser['user_type'];

try {
    $pdo = getDatabaseConnection();
    ensureUserLoginSessionsTable($pdo);
    
    // Get pending login requests
    $stmt = $pdo->prepare("
        SELECT id, temp_token, device_id, ip_address, created_at, 
               TIMESTAMPDIFF(SECOND, created_at, NOW()) as age_seconds
        FROM user_login_sessions 
        WHERE user_id = ? AND user_type = ? AND status = 'pending'
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId, $userType]);
    $pendingRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recently approved sessions
    $stmt = $pdo->prepare("
        SELECT id, device_id, ip_address, created_at, updated_at
        FROM user_login_sessions 
        WHERE user_id = ? AND user_type = ? AND status = 'active'
        ORDER BY updated_at DESC
        LIMIT 10
    ");
    $stmt->execute([$userId, $userType]);
    $activeSessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log('PENDING_APPROVALS_ERROR: ' . $e->getMessage());
    $pendingRequests = [];
    $activeSessions = [];
}

$resultStatus = $_GET['result'] ?? null;

function formatIPAddress($ip) {
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }
    return 'Unknown';
}

function extractDeviceInfo($userAgent) {
    if (empty($userAgent)) {
        return 'Unknown Device';
    }
    
    $ua = (string)$userAgent;
    
    // Mobile devices
    if (stripos($ua, 'Android') !== false) return 'Android Device';
    if (stripos($ua, 'iPhone') !== false) return 'iPhone';
    if (stripos($ua, 'iPad') !== false) return 'iPad';
    if (stripos($ua, 'Windows Phone') !== false) return 'Windows Phone';
    
    // Browsers/OS
    if (stripos($ua, 'Chrome') !== false) return 'Chrome Browser';
    if (stripos($ua, 'Firefox') !== false) return 'Firefox Browser';
    if (stripos($ua, 'Safari') !== false) return 'Safari Browser';
    if (stripos($ua, 'Edge') !== false) return 'Edge Browser';
    
    if (stripos($ua, 'Windows') !== false) return 'Windows PC';
    if (stripos($ua, 'Mac') !== false) return 'Mac';
    if (stripos($ua, 'Linux') !== false) return 'Linux';
    
    return substr($ua, 0, 50);
}

function formatTimeAgo($seconds) {
    if ($seconds < 60) return $seconds . 's ago';
    if ($seconds < 3600) return intval($seconds / 60) . 'm ago';
    if ($seconds < 86400) return intval($seconds / 3600) . 'h ago';
    return intval($seconds / 86400) . 'd ago';
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Approvals — ScanCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap');
        .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-body { font-family: 'Inter', sans-serif; }
        
        .pulse-glow {
            animation: pulse-glow 2s infinite;
        }
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(69, 75, 27, 0.3); }
            50% { box-shadow: 0 0 30px rgba(69, 75, 27, 0.6); }
        }
    </style>
</head>
<body class="bg-gradient-to-b from-slate-50 to-slate-100 font-body">
    <!-- Header -->
    <div class="bg-white border-b border-slate-200 sticky top-0 z-40">
        <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="dashboard.php" class="font-heading font-bold text-2xl text-[#454B1B] flex items-center gap-2">
                <i class="fas fa-shield-check text-green-600"></i> Login Approvals
            </a>
            <a href="dashboard.php" class="px-4 py-2 text-slate-600 hover:text-[#454B1B] transition-colors">
                ← Back to Dashboard
            </a>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <?php if (!empty($resultStatus)): ?>
            <?php if ($resultStatus === 'approved'): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg p-4 mb-6">
                    <strong>✓ Approved:</strong> The login request was approved and the other device is now active.
                </div>
            <?php elseif ($resultStatus === 'rejected'): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 mb-6">
                    <strong>✖ Rejected:</strong> The login request was denied and access was blocked.
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <!-- Pending Requests Section -->
        <div class="mb-8">
            <h2 class="font-heading font-bold text-2xl text-slate-900 mb-6 flex items-center gap-3">
                <i class="fas fa-clock text-orange-500"></i> Pending Requests
            </h2>

            <?php if (empty($pendingRequests)): ?>
                <div class="bg-white rounded-lg border border-slate-200 p-8 text-center">
                    <i class="fas fa-check-circle text-green-500 text-4xl mb-4 block"></i>
                    <p class="text-slate-600">No pending login requests. Your account is secure.</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($pendingRequests as $request): ?>
                        <div class="bg-white rounded-lg border border-orange-200 p-6 pulse-glow">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <p class="font-heading font-semibold text-lg text-slate-900 flex items-center gap-2">
                                        <i class="fas fa-exclamation-triangle text-orange-500"></i>
                                        New Login Attempt
                                    </p>
                                    <p class="text-sm text-slate-500 mt-1">
                                        <i class="fas fa-clock"></i> <?php echo formatTimeAgo($request['age_seconds']); ?>
                                    </p>
                                </div>
                                <span class="inline-block px-3 py-1 bg-orange-100 text-orange-700 text-xs font-semibold rounded-full">
                                    PENDING
                                </span>
                            </div>

                            <div class="bg-slate-50 rounded-lg p-4 mb-4 space-y-3">
                                <div class="flex items-start gap-3">
                                    <i class="fas fa-mobile-alt text-slate-400 mt-1 w-5 flex-shrink-0"></i>
                                    <div>
                                        <p class="text-xs text-slate-500">Device</p>
                                        <p class="text-sm font-medium text-slate-900"><?php echo htmlspecialchars(extractDeviceInfo($request['device_id'])); ?></p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <i class="fas fa-network-wired text-slate-400 mt-1 w-5 flex-shrink-0"></i>
                                    <div>
                                        <p class="text-xs text-slate-500">IP Address</p>
                                        <p class="text-sm font-medium text-slate-900 font-mono"><?php echo htmlspecialchars(formatIPAddress($request['ip_address'])); ?></p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <i class="fas fa-lock text-slate-400 mt-1 w-5 flex-shrink-0"></i>
                                    <div>
                                        <p class="text-xs text-slate-500">Verification Code</p>
                                        <p class="text-sm font-medium text-slate-900 font-mono tracking-widest"><?php echo strtoupper(substr($request['temp_token'], 0, 8)) . '...'; ?></p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-3">
                                <form method="POST" action="approve_login_request.php" class="flex-1">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="temp_token" value="<?php echo htmlspecialchars($request['temp_token']); ?>">
                                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center gap-2">
                                        <i class="fas fa-check"></i> Approve Login
                                    </button>
                                </form>
                                <form method="POST" action="approve_login_request.php" class="flex-1">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="temp_token" value="<?php echo htmlspecialchars($request['temp_token']); ?>">
                                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center gap-2">
                                        <i class="fas fa-times"></i> Deny Access
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Active Sessions -->
        <div>
            <h2 class="font-heading font-bold text-2xl text-slate-900 mb-6 flex items-center gap-3">
                <i class="fas fa-check-circle text-green-500"></i> Active Sessions
            </h2>

            <?php if (empty($activeSessions)): ?>
                <div class="bg-white rounded-lg border border-slate-200 p-8 text-center">
                    <i class="fas fa-info-circle text-slate-400 text-4xl mb-4 block"></i>
                    <p class="text-slate-600">No active sessions recorded.</p>
                </div>
            <?php else: ?>
                <div class="grid gap-4">
                    <?php foreach ($activeSessions as $session): ?>
                        <div class="bg-white rounded-lg border border-green-100 p-4">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-slate-900">
                                        <i class="fas fa-desktop text-green-600 mr-2"></i><?php echo htmlspecialchars(extractDeviceInfo($session['device_id'])); ?>
                                    </p>
                                    <p class="text-xs text-slate-500 mt-1">
                                        <i class="fas fa-network-wired mr-1"></i><?php echo htmlspecialchars(formatIPAddress($session['ip_address'])); ?>
                                    </p>
                                </div>
                                <span class="inline-block px-2 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded">
                                    ACTIVE
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
