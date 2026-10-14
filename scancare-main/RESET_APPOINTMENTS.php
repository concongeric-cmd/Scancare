<?php


session_start();
require_once 'db_config.php';


if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$pdo = getDatabaseConnection();
$message = '';
$status = 'error';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'clear_all') {
            
            $stmt = $pdo->prepare("DELETE FROM appointments");
            $stmt->execute();
            $count = $pdo->exec("SELECT COUNT(*) FROM appointments");
            $message = "✅ All appointments cleared. System reset complete.";
            $status = 'success';
        }
        elseif ($action === 'create_test_data') {
            
            $pdo->exec("DELETE FROM appointments");
            
            
            $residents_stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_role = 'resident' LIMIT 10");
            $residents_stmt->execute();
            $residents = $residents_stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $bhws_stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_role = 'bhw' LIMIT 5");
            $bhws_stmt->execute();
            $bhws = $bhws_stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $services_stmt = $pdo->prepare("SELECT service_id FROM services LIMIT 8");
            $services_stmt->execute();
            $services = $services_stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($residents) || empty($services)) {
                throw new Exception("Not enough test data: residents or services missing");
            }
            
            $statuses = ['scheduled', 'completed', 'canceled'];
            $created_count = 0;
            
            
            for ($i = 0; $i < 20; $i++) {
                $resident = $residents[array_rand($residents)];
                $bhw = !empty($bhws) && rand(0, 1) ? $bhws[array_rand($bhws)] : null;
                $service = $services[array_rand($services)];
                $status_type = $statuses[array_rand($statuses)];
                
                
                $days_offset = rand(-30, 15);
                $appointment_date = date('Y-m-d', strtotime($days_offset . ' days'));
                $appointment_time = sprintf("%02d:%02d", rand(8, 17), rand(0, 59));
                
                $created_at = date('Y-m-d H:i:s', strtotime($days_offset . ' days - ' . rand(0, 7) . ' days'));
                
                $insert_stmt = $pdo->prepare("
                    INSERT INTO appointments (user_id, bhw_id, service_id, appointment_date, appointment_time, status, reason, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $insert_stmt->execute([
                    $resident,
                    $bhw,
                    $service,
                    $appointment_date,
                    $appointment_time,
                    $status_type,
                    'Test appointment - ' . ($status_type === 'scheduled' ? 'Pending review' : ($status_type === 'completed' ? 'Follow-up needed' : 'Rescheduled')),
                    $created_at
                ]);
                
                $created_count++;
            }
            
            $message = "✅ System reset complete! Created $created_count test appointments with varied statuses.";
            $status = 'success';
        }
        
    } catch (Exception $e) {
        $message = "❌ Error: " . $e->getMessage();
        $status = 'error';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Appointments — ScanCare</title>
    <script src="https:
    <link rel="stylesheet" href="https:
    <style>
        @import url('https:
        .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#F8FAF6] font-[Inter]">
    <div class="min-h-screen flex items-center justify-center px-4 py-8">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-lg p-8 max-w-md w-full">
            <div class="flex items-center gap-3 mb-6">
                <i class="fa-solid fa-rotate-left text-2xl" style="color: #454B1B;"></i>
                <h1 class="font-heading text-2xl font-bold">Reset Appointments</h1>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="mb-6 p-4 rounded-lg border <?php echo $status === 'success' ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'; ?>">
                    <p class="<?php echo $status === 'success' ? 'text-green-800' : 'text-red-800'; ?>">
                        <?php echo $message; ?>
                    </p>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-4">
                <button type="submit" name="action" value="clear_all" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 rounded-lg transition-colors">
                    <i class="fa-solid fa-trash mr-2"></i> Clear All Appointments
                </button>
                
                <button type="submit" name="action" value="create_test_data" class="w-full bg-[#454B1B] hover:bg-[#2a2d0f] text-white font-semibold py-3 rounded-lg transition-colors">
                    <i class="fa-solid fa-flask mr-2"></i> Reset & Load Test Data
                </button>
            </form>
            
            <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200 text-sm text-blue-900">
                <p><strong>Info:</strong></p>
                <ul class="mt-2 space-y-1 text-xs">
                    <li>• <strong>Clear All:</strong> Deletes all appointments</li>
                    <li>• <strong>Reset & Test:</strong> Clears all, then creates 20 test appointments with varied statuses (scheduled, completed, canceled)</li>
                </ul>
            </div>
            
            <div class="mt-6">
                <a href="admin_dashboard.php" class="text-center block text-slate-600 hover:text-[#454B1B] font-medium text-sm">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Back to Admin Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>

