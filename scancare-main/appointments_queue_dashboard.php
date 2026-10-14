<?php



if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}


$user_role = strtolower($_SESSION['user_role'] ?? $_SESSION['worker_role'] ?? '');
if (!in_array($user_role, ['bhw', 'health_worker', 'worker', 'admin'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'] ?? 'Health Worker';




try {
    require_once __DIR__ . '/db_config.php';
    $pdo = getDatabaseConnection();
    
    
    $query = "
        SELECT 
            a.appointment_id,
            a.appointment_date,
            a.appointment_time,
            a.purpose,
            a.status,
            a.health_worker_id,
            r.resident_id,
            r.full_name as patient_name,
            r.contact_number,
            r.email
        FROM appointment a
        LEFT JOIN resident r ON a.resident_id = r.resident_id
        WHERE DATE(a.appointment_date) = CURDATE()
        ORDER BY a.appointment_time ASC, a.appointment_id ASC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    
    $status_counts = ['pending' => 0, 'approved' => 0, 'no_show' => 0, 'completed' => 0, 'cancelled' => 0];
    foreach ($appointments as $apt) {
        $s = strtolower($apt['status'] ?? 'pending');
        if (isset($status_counts[$s])) {
            $status_counts[$s]++;
        }
    }
    
} catch (Exception $e) {
    $appointments = [];
    $status_counts = [];
    $error_message = 'Database connection error.';
}





function getStatusBadge($status) {
    $status = strtolower(trim($status ?? 'pending'));
    
    $badges = [
        'pending' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'icon' => '⏳', 'label' => 'Pending'],
        'approved' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'icon' => '✓', 'label' => 'Approved'],
        'no_show' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'icon' => '✕', 'label' => 'No Show'],
        'completed' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'icon' => '★', 'label' => 'Completed'],
        'cancelled' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'icon' => '◉', 'label' => 'Cancelled'],
    ];
    
    $b = $badges[$status] ?? $badges['pending'];
    return sprintf(
        '<span class="px-3 py-1 rounded-full text-sm font-semibold %s %s whitespace-nowrap">%s %s</span>',
        $b['bg'], $b['text'], $b['icon'], $b['label']
    );
}

function formatTime($time) {
    return date('h:i A', strtotime($time));
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments Queue | ScanCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { 
        body { background-color: #f8f9fa; }
        .header { background: linear-gradient(135deg, var(
        .fade-out { animation: fadeOut 0.4s ease-out forwards; }
        @keyframes fadeOut {
            from { opacity: 1; transform: translateX(0); }
            to { opacity: 0; transform: translateX(-100px); }
        }
        .notification {
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<!
<div class="header text-white p-6 shadow-lg">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold">📋 Appointments Queue</h1>
                <p class="text-gray-200">Today's Schedule - Real-time Management</p>
            </div>
            <div class="text-right">
                <p class="text-lg font-semibold"><?php echo htmlspecialchars($user_name); ?></p>
                <p class="text-sm text-gray-300"><?php echo date('F d, Y'); ?></p>
            </div>
        </div>
    </div>
</div>

<!
<div id="notificationContainer" class="fixed top-6 right-6 z-50 space-y-2"></div>

<!
<div class="max-w-7xl mx-auto p-6">
    
    <!
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
            <p class="text-gray-600 text-sm font-semibold">Total</p>
            <p class="text-3xl font-bold text-blue-600 mt-2"><?php echo count($appointments); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-yellow-500">
            <p class="text-gray-600 text-sm font-semibold">⏳ Pending</p>
            <p class="text-3xl font-bold text-yellow-600 mt-2"><?php echo $status_counts['pending'] ?? 0; ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-600">
            <p class="text-gray-600 text-sm font-semibold">✓ Approved</p>
            <p class="text-3xl font-bold text-blue-700 mt-2"><?php echo $status_counts['approved'] ?? 0; ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
            <p class="text-gray-600 text-sm font-semibold">✕ No Show</p>
            <p class="text-3xl font-bold text-red-600 mt-2"><?php echo $status_counts['no_show'] ?? 0; ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
            <p class="text-gray-600 text-sm font-semibold">★ Completed</p>
            <p class="text-3xl font-bold text-green-600 mt-2"><?php echo $status_counts['completed'] ?? 0; ?></p>
        </div>
    </div>

    <!
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-2xl font-bold text-gray-800">Today's Queue</h2>
            <p class="text-gray-600 text-sm mt-1">Click "Mark No Show" to remove from queue</p>
        </div>

        <?php if (count($appointments) > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Time</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Patient</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Contact</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Purpose</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Action</th>
                        </tr>
                    </thead>
                    <tbody id="appointmentTableBody">
                        <?php foreach ($appointments as $apt): 
                            $status_lower = strtolower($apt['status'] ?? 'pending');
                            $is_no_show = $status_lower === 'no_show';
                            $row_id = 'apt-row-' . $apt['appointment_id'];
                        ?>
                            <tr class="border-b border-gray-200 hover:bg-gray-50 transition-colors" id="<?php echo htmlspecialchars($row_id); ?>">
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900">
                                    <?php echo htmlspecialchars(formatTime($apt['appointment_time'])); ?>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($apt['patient_name'] ?? 'Unknown'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <?php echo htmlspecialchars($apt['contact_number'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate" title="<?php echo htmlspecialchars($apt['purpose'] ?? ''); ?>">
                                    <?php echo htmlspecialchars(substr($apt['purpose'] ?? '', 0, 40)); ?>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <?php echo getStatusBadge($apt['status']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <?php if (!$is_no_show): ?>
                                        <button 
                                            onclick="markNoShow(<?php echo intval($apt['appointment_id']); ?>, '<?php echo htmlspecialchars(addslashes($apt['patient_name'] ?? 'Patient')); ?>')"
                                            class="inline-flex items-center gap-2 px-4 py-2 bg-red-500 hover:bg-red-600 text-white font-semibold rounded-lg transition-colors"
                                            title="Mark as No Show"
                                        >
                                            <i class="fas fa-times"></i> No Show
                                        </button>
                                    <?php else: ?>
                                        <span class="text-gray-500 text-sm">✓ Already Marked</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-12 text-center">
                <p class="text-gray-500 text-lg">📭 No appointments scheduled for today</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!
<div id="confirmModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="p-6">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
            <h3 class="mt-4 text-lg font-bold text-center text-gray-900">Mark as No Show?</h3>
            <p class="mt-4 text-center text-gray-600">
                Are you sure you want to mark <strong id="confirmPatientName"></strong> as a no show?
            </p>
        </div>
        <div class="bg-gray-50 px-6 py-4 flex gap-3 justify-end border-t border-gray-200">
            <button onclick="closeModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg font-semibold transition-colors">
                Cancel
            </button>
            <button id="confirmBtn" onclick="confirmAction()" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg font-semibold transition-colors">
                Yes, Mark No Show
            </button>
        </div>
    </div>
</div>

<!
<script>
let currentAppointmentId = null;
let currentPatientName = null;


function markNoShow(appointmentId, patientName) {
    currentAppointmentId = appointmentId;
    currentPatientName = patientName;
    document.getElementById('confirmPatientName').textContent = patientName;
    document.getElementById('confirmModal').classList.remove('hidden');
}


function closeModal() {
    document.getElementById('confirmModal').classList.add('hidden');
    currentAppointmentId = null;
    currentPatientName = null;
}


function confirmAction() {
    if (!currentAppointmentId) {
        showNotification('Error: Missing appointment ID', 'error');
        return;
    }

    const btn = document.getElementById('confirmBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';

    
    fetch('update_appointment_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'appointment_id=' + encodeURIComponent(currentAppointmentId) + '&status=no_show'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            
            const rowElement = document.getElementById('apt-row-' + currentAppointmentId);
            if (rowElement) {
                rowElement.classList.add('fade-out');
                setTimeout(() => {
                    rowElement.remove();
                    
                    setTimeout(() => {
                        location.reload();
                    }, 300);
                }, 400);
            }

            
            showNotification(
                currentPatientName + ' marked as No Show by ' + (data.updated_by || 'BHW'),
                'success'
            );

            closeModal();
        } else {
            throw new Error(data.message || 'Unknown error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Failed: ' + error.message, 'error');
        btn.disabled = false;
        btn.innerHTML = 'Yes, Mark No Show';
    });
}


function showNotification(message, type = 'info') {
    const container = document.getElementById('notificationContainer');
    
    const styles = {
        success: 'bg-green-50 border-green-200 text-green-800',
        error: 'bg-red-50 border-red-200 text-red-800',
        info: 'bg-blue-50 border-blue-200 text-blue-800'
    };

    const notif = document.createElement('div');
    notif.className = `notification border rounded-lg p-4 flex items-center gap-3 font-semibold max-w-md ${styles[type]}`;
    notif.innerHTML = `
        <span>${type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ'}</span>
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" class="ml-auto text-lg cursor-pointer">×</button>
    `;
    
    container.appendChild(notif);
    
    
    setTimeout(() => {
        if (notif.parentElement) {
            notif.style.opacity = '0';
            notif.style.transform = 'translateY(-20px)';
            notif.style.transition = 'all 0.3s ease';
            setTimeout(() => notif.remove(), 300);
        }
    }, 5000);
}


document.getElementById('confirmModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});


document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});
</script>

</body>
</html>

