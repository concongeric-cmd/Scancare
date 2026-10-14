<?php



if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}


$user_role = strtolower($_SESSION['user_role'] ?? $_SESSION['worker_role'] ?? '');
$allowed_roles = ['bhw', 'health_worker', 'worker', 'admin'];

if (!in_array($user_role, $allowed_roles)) {
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
            r.contact_number as patient_contact,
            r.email as patient_email,
            YEAR(a.appointment_date) as apt_year,
            MONTH(a.appointment_date) as apt_month,
            DAY(a.appointment_date) as apt_day
        FROM appointment a
        LEFT JOIN resident r ON a.resident_id = r.resident_id
        WHERE DATE(a.appointment_date) = CURDATE()
        ORDER BY a.appointment_time ASC, a.appointment_id ASC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    
    $status_counts = [
        'pending' => 0,
        'approved' => 0,
        'no_show' => 0,
        'completed' => 0,
        'cancelled' => 0
    ];
    
    foreach ($appointments as $apt) {
        $status = strtolower($apt['status'] ?? 'pending');
        if (isset($status_counts[$status])) {
            $status_counts[$status]++;
        }
    }
    
} catch (Exception $e) {
    error_log('Database error in today_appointments.php: ' . $e->getMessage());
    $appointments = [];
    $status_counts = [];
}






function getStatusBadge($status) {
    $status = strtolower(trim($status ?? ''));
    
    $badges = [
        'pending' => [
            'color' => 'bg-yellow-100',
            'text_color' => 'text-yellow-800',
            'icon' => '⏳',
            'label' => 'Pending'
        ],
        'approved' => [
            'color' => 'bg-blue-100',
            'text_color' => 'text-blue-800',
            'icon' => '✓',
            'label' => 'Approved'
        ],
        'no_show' => [
            'color' => 'bg-red-100',
            'text_color' => 'text-red-800',
            'icon' => '✕',
            'label' => 'No Show'
        ],
        'completed' => [
            'color' => 'bg-green-100',
            'text_color' => 'text-green-800',
            'icon' => '★',
            'label' => 'Completed'
        ],
        'cancelled' => [
            'color' => 'bg-gray-100',
            'text_color' => 'text-gray-800',
            'icon' => '◉',
            'label' => 'Cancelled'
        ]
    ];
    
    $badge = $badges[$status] ?? $badges['pending'];
    
    return sprintf(
        '<span class="px-3 py-1 rounded-full text-sm font-semibold %s %s">%s %s</span>',
        $badge['color'],
        $badge['text_color'],
        $badge['icon'],
        $badge['label']
    );
}


function formatDateTime($date, $time) {
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dt) return 'Invalid Date';
    
    $formatted_date = $dt->format('M d, Y');
    $formatted_time = date('h:i A', strtotime($time));
    
    return $formatted_date . ' • ' . $formatted_time;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Today's Appointments | ScanCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .header {
            background: linear-gradient(135deg, var(
        }
        
        .fade-out {
            animation: fadeOut 0.5s ease-out forwards;
        }
        
        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(100px);
            }
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .notification {
            animation: slideDown 0.3s ease-out;
        }
        
        .appointment-row {
            transition: all 0.3s ease;
        }
        
        .appointment-row:hover {
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>
    <!
         HEADER
         ==================================================================== 
    <div class="header text-white p-6 shadow-lg">
        <div class="max-w-7xl mx-auto">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">📅 Today's Appointments</h1>
                    <p class="text-gray-200 mt-1">Queue Management System</p>
                </div>
                <div class="text-right">
                    <p class="text-lg">Welcome, <strong><?php echo htmlspecialchars($user_name); ?></strong></p>
                    <p class="text-sm text-gray-200"><?php echo date('l, F d, Y'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!
         MAIN CONTENT
         ==================================================================== 
    <div class="max-w-7xl mx-auto p-6">
        
        <!
             NOTIFICATIONS
             ================================================================ 
        <div id="notificationContainer" class="mb-6"></div>

        <!
             SUMMARY CARDS
             ================================================================ 
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <!
            <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
                <p class="text-gray-600 text-sm font-semibold">Total Today</p>
                <p class="text-3xl font-bold text-blue-600 mt-2">
                    <?php echo count($appointments); ?>
                </p>
            </div>

            <!
            <div class="bg-white rounded-lg shadow p-4 border-l-4 border-yellow-500">
                <p class="text-gray-600 text-sm font-semibold">⏳ Pending</p>
                <p class="text-3xl font-bold text-yellow-600 mt-2">
                    <?php echo $status_counts['pending'] ?? 0; ?>
                </p>
            </div>

            <!
            <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
                <p class="text-gray-600 text-sm font-semibold">✓ Approved</p>
                <p class="text-3xl font-bold text-green-600 mt-2">
                    <?php echo $status_counts['approved'] ?? 0; ?>
                </p>
            </div>

            <!
            <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
                <p class="text-gray-600 text-sm font-semibold">✕ No Show</p>
                <p class="text-3xl font-bold text-red-600 mt-2">
                    <?php echo $status_counts['no_show'] ?? 0; ?>
                </p>
            </div>

            <!
            <div class="bg-white rounded-lg shadow p-4 border-l-4 border-purple-500">
                <p class="text-gray-600 text-sm font-semibold">★ Completed</p>
                <p class="text-3xl font-bold text-purple-600 mt-2">
                    <?php echo $status_counts['completed'] ?? 0; ?>
                </p>
            </div>
        </div>

        <!
             APPOINTMENTS TABLE
             ================================================================ 
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-800">Appointments Queue</h2>
                <p class="text-gray-600 text-sm mt-1">Click "Mark No Show" to update appointment status</p>
            </div>

            <?php if (count($appointments) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Time</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Patient Name</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Contact</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Purpose</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $apt): 
                                $status_lower = strtolower($apt['status'] ?? 'pending');
                                $row_id = 'row-' . $apt['appointment_id'];
                                $is_no_show = $status_lower === 'no_show';
                            ?>
                                <tr class="appointment-row border-b border-gray-200 hover:bg-gray-50" id="<?php echo htmlspecialchars($row_id); ?>">
                                    <!
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-900">
                                        <?php echo htmlspecialchars($apt['appointment_time']); ?>
                                    </td>

                                    <!
                                    <td class="px-6 py-4 text-sm text-gray-900 font-medium">
                                        <?php echo htmlspecialchars($apt['patient_name'] ?? 'Unknown Patient'); ?>
                                    </td>

                                    <!
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?php echo htmlspecialchars($apt['patient_contact'] ?? 'N/A'); ?>
                                    </td>

                                    <!
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <span class="truncate" title="<?php echo htmlspecialchars($apt['purpose'] ?? 'No purpose'); ?>">
                                            <?php echo htmlspecialchars(substr($apt['purpose'] ?? '', 0, 30)); ?>
                                        </span>
                                    </td>

                                    <!
                                    <td class="px-6 py-4 text-sm">
                                        <?php echo getStatusBadge($apt['status']); ?>
                                    </td>

                                    <!
                                    <td class="px-6 py-4 text-sm">
                                        <?php if (!$is_no_show): ?>
                                            <button 
                                                onclick="markNoShow(<?php echo intval($apt['appointment_id']); ?>, '<?php echo htmlspecialchars(addslashes($apt['patient_name'] ?? 'Patient')); ?>')"
                                                class="inline-flex items-center px-4 py-2 bg-red-500 text-white rounded-lg font-semibold hover:bg-red-600 transition-colors duration-200"
                                                title="Mark this appointment as No Show"
                                            >
                                                <i class="fas fa-times mr-2"></i>
                                                Mark No Show
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
                <div class="p-6 text-center">
                    <p class="text-gray-500 text-lg">📭 No appointments scheduled for today</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!
         CONFIRMATION MODAL
         ==================================================================== 
    <div id="confirmModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full">
                    <i class="fas fa-exclamation text-red-600 text-xl"></i>
                </div>
                <h3 class="mt-4 text-lg font-bold text-center text-gray-900">Confirm No Show</h3>
                <p class="mt-4 text-center text-gray-600">
                    Are you sure you want to mark <strong id="confirmPatientName"></strong> as a no show?
                </p>
                <p class="mt-2 text-center text-sm text-gray-500">
                    This action will update the appointment status and cannot be easily undone.
                </p>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex gap-3 justify-end border-t border-gray-200">
                <button 
                    onclick="closeConfirmModal()"
                    class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg font-semibold hover:bg-gray-300 transition-colors"
                >
                    Cancel
                </button>
                <button 
                    id="confirmButton"
                    onclick="confirmNoShow()"
                    class="px-4 py-2 bg-red-500 text-white rounded-lg font-semibold hover:bg-red-600 transition-colors"
                >
                    Yes, Mark No Show
                </button>
            </div>
        </div>
    </div>

    <!
         JAVASCRIPT - AJAX HANDLER & DOM MANIPULATION
         ==================================================================== 
    <script>
        let pendingAppointmentId = null;
        let pendingPatientName = null;

        
        function markNoShow(appointmentId, patientName) {
            pendingAppointmentId = appointmentId;
            pendingPatientName = patientName;
            
            document.getElementById('confirmPatientName').textContent = patientName;
            document.getElementById('confirmModal').classList.remove('hidden');
        }

        
        function closeConfirmModal() {
            document.getElementById('confirmModal').classList.add('hidden');
            pendingAppointmentId = null;
            pendingPatientName = null;
        }

        
        function confirmNoShow() {
            if (!pendingAppointmentId || !pendingPatientName) {
                showNotification('Error: Missing appointment data', 'error');
                return;
            }

            const appointmentId = pendingAppointmentId;
            const patientName = pendingPatientName;
            
            
            const confirmBtn = document.getElementById('confirmButton');
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';

            
            fetch('update_appointment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'appointment_id=' + encodeURIComponent(appointmentId) + '&status=no_show'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error, status = ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    
                    const rowElement = document.getElementById('row-' + appointmentId);
                    if (rowElement) {
                        rowElement.classList.add('fade-out');
                        setTimeout(() => {
                            rowElement.remove();
                            
                            setTimeout(() => {
                                location.reload();
                            }, 500);
                        }, 500);
                    }
                    
                    
                    showNotification(
                        patientName + ' marked as No Show by ' + data.updated_by,
                        'success'
                    );
                    
                    
                    closeConfirmModal();
                } else {
                    throw new Error(data.message || 'Unknown error occurred');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification(
                    'Error marking appointment: ' + error.message,
                    'error'
                );
                
                
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = 'Yes, Mark No Show';
            });
        }

        
        function showNotification(message, type = 'info') {
            const container = document.getElementById('notificationContainer');
            
            const bgColor = type === 'success' ? 'bg-green-50' : type === 'error' ? 'bg-red-50' : 'bg-blue-50';
            const borderColor = type === 'success' ? 'border-green-200' : type === 'error' ? 'border-red-200' : 'border-blue-200';
            const textColor = type === 'success' ? 'text-green-800' : type === 'error' ? 'text-red-800' : 'text-blue-800';
            const icon = type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ';
            
            const notif = document.createElement('div');
            notif.className = `notification ${bgColor} border ${borderColor} rounded-lg p-4 flex items-center gap-3 ${textColor} font-semibold`;
            notif.innerHTML = `
                <span class="text-xl">${icon}</span>
                <span>${message}</span>
                <button onclick="this.parentElement.remove()" class="ml-auto text-lg cursor-pointer">×</button>
            `;
            
            container.appendChild(notif);
            
            
            setTimeout(() => {
                notif.style.opacity = '0';
                notif.style.transform = 'translateY(-20px)';
                notif.style.transition = 'all 0.3s ease';
                setTimeout(() => notif.remove(), 300);
            }, 5000);
        }

        
        document.getElementById('confirmModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeConfirmModal();
            }
        });
    </script>
</body>
</html>

