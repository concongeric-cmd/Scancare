<?php


require_once 'db_config.php';

$message = '';
$error = '';
$pending_appointments = [];

try {
    $pdo = getDatabaseConnection();
    
    if ($pdo === false) {
        $error = 'Database connection failed';
    } else {
        
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            
            if ($_POST['action'] === 'complete_appointment') {
                $appointment_id = intval($_POST['appointment_id'] ?? 0);
                $bhw_name = trim($_POST['bhw_name'] ?? '');
                $notes = trim($_POST['notes'] ?? '');
                
                if ($appointment_id <= 0 || empty($bhw_name)) {
                    $error = 'Invalid appointment ID or missing BHW name';
                } else {
                    
                    $ch = curl_init();
                    curl_setopt_array($ch, [
                        CURLOPT_URL => appUrl('appointment_workflow.php'),
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => http_build_query([
                            'action' => 'complete_appointment',
                            'appointment_id' => $appointment_id,
                            'bhw_name' => $bhw_name,
                            'notes' => $notes
                        ])
                    ]);
                    
                    $response = curl_exec($ch);
                    curl_close($ch);
                    
                    $result = json_decode($response, true);
                    
                    if ($result['success']) {
                        $message = '✅ ' . $result['message'];
                    } else {
                        $error = '❌ ' . $result['message'];
                    }
                }
            }
        }
        
        
        $stmt = $pdo->prepare("
            SELECT 
                a.appointment_id,
                a.user_id,
                a.appointment_date,
                a.appointment_time,
                a.reason,
                a.status,
                u.first_name,
                u.last_name,
                u.email
            FROM appointments a
            LEFT JOIN users u ON a.user_id = u.user_id
            WHERE a.status IN ('scheduled', 'pending')
            ORDER BY a.appointment_date ASC, a.appointment_time ASC
        ");
        $stmt->execute();
        $pending_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $error = 'Unable to load appointments.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Manager - ScanCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!
        <div class="bg-white border-b border-gray-200 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-gray-900">
                        <i class="fa-solid fa-calendar-check mr-2" style="color: #454B1B;"></i>
                        Appointment Manager
                    </h1>
                    <a href="dashboard.php" class="text-sm text-blue-600 hover:text-blue-800">
                        <i class="fa-solid fa-arrow-left mr-1"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            
            <!
            <?php if (!empty($message)): ?>
            <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-4 rounded-lg">
                <i class="fa-solid fa-check-circle mr-2"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-4 rounded-lg">
                <i class="fa-solid fa-exclamation-circle mr-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <!
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Pending Appointments (<?php echo count($pending_appointments); ?>)
                    </h2>
                </div>

                <?php if (empty($pending_appointments)): ?>
                    <div class="px-6 py-12 text-center">
                        <i class="fa-solid fa-calendar-xmark text-4xl text-gray-300 mb-4 block"></i>
                        <p class="text-gray-600">No pending appointments</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Date & Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Resident Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Reason</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($pending_appointments as $apt): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <strong><?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?></strong>
                                        <br>
                                        <span class="text-gray-600"><?php echo date('g:i A', strtotime($apt['appointment_time'])); ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($apt['first_name'] . ' ' . $apt['last_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?php echo htmlspecialchars($apt['email']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($apt['reason']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="inline-block px-3 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">
                                            <?php echo strtoupper($apt['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <button 
                                            type="button"
                                            onclick="completeAppointment(<?php echo $apt['appointment_id']; ?>)"
                                            class="text-blue-600 hover:text-blue-800 font-medium"
                                        >
                                            Complete <i class="fa-solid fa-chevron-right ml-1"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!
    <div id="completionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-md w-full p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">
                <i class="fa-solid fa-check-circle mr-2" style="color: #454B1B;"></i>
                Complete Appointment
            </h2>

            <form method="POST" id="completionForm">
                <input type="hidden" name="action" value="complete_appointment">
                <input type="hidden" name="appointment_id" id="appointmentId" value="">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">BHW/Staff Name</label>
                    <input 
                        type="text" 
                        name="bhw_name" 
                        required
                        placeholder="e.g., Dr. Maria Santos"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B]"
                    >
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Visit Notes</label>
                    <textarea 
                        name="notes" 
                        rows="4"
                        placeholder="e.g., Vital signs normal. No concerns noted. Follow-up in 3 months."
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B]"
                    ></textarea>
                </div>

                <div class="flex gap-3">
                    <button 
                        type="button"
                        onclick="closeModal()"
                        class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit"
                        style="background-color: #454B1B;"
                        class="flex-1 px-4 py-2 text-white rounded-lg font-medium hover:opacity-90"
                    >
                        <i class="fa-solid fa-check mr-2"></i> Complete Appointment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function completeAppointment(appointmentId) {
            document.getElementById('appointmentId').value = appointmentId;
            document.getElementById('completionModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('completionModal').classList.add('hidden');
            document.getElementById('completionForm').reset();
        }

        
        document.getElementById('completionModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        
        document.getElementById('completionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const appointmentId = document.getElementById('appointmentId').value;
            const bhwName = document.querySelector('input[name="bhw_name"]').value;
            const notes = document.querySelector('textarea[name="notes"]').value;

            if (!bhwName) {
                alert('Please enter BHW/Staff name');
                return;
            }

            
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Processing...';
            btn.disabled = true;

            
            fetch(<?php echo json_encode(appPath('appointment_workflow.php')); ?>, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'complete_appointment',
                    appointment_id: appointmentId,
                    bhw_name: bhwName,
                    notes: notes
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Appointment completed! Transferred to medical history.');
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            })
            .catch(err => {
                alert('❌ Error: ' + err.message);
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        });
    </script>
</body>
</html>

