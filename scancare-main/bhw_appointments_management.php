<?php


session_start();


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}


$user_role = strtolower($_SESSION['user_role'] ?? $_SESSION['worker_role'] ?? '');
if (!in_array($user_role, ['bhw', 'health_worker', 'worker', 'admin'])) {
    header("Location: dashboard.php");
    exit;
}


$bhw_id = $_SESSION['user_id'];
$bhw_name = $_SESSION['name'] ?? $_SESSION['full_name'] ?? 'BHW';

require_once 'db_config.php';

try {
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
            r.email as patient_email,
            hw.full_name as health_worker_name
        FROM appointment a
        LEFT JOIN resident r ON a.resident_id = r.resident_id
        LEFT JOIN health_worker hw ON a.health_worker_id = hw.worker_id
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $all_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    
    
    
    
    $todays_queue = [];        
    $followup_queue = [];      
    $history = [];             
    
    foreach ($all_appointments as $apt) {
        $status = strtolower($apt['status'] ?? '');
        
        if ($status === 'scheduled') {
            $todays_queue[] = $apt;
        } elseif ($status === 'follow_up') {
            $followup_queue[] = $apt;
        } elseif ($status === 'rendered' || $status === 'no_show') {
            $history[] = $apt;
        }
    }
    
    $total_appointments = count($all_appointments);
    $todays_queue_count = count($todays_queue);
    $followup_queue_count = count($followup_queue);
    $history_count = count($history);
    
    
    $status_counts = [
        'pending' => 0,
        'approved' => 0,
        'no_show' => 0,
        'completed' => 0,
        'cancelled' => 0
    ];
    
    foreach ($all_appointments as $apt) {
        $status = strtolower($apt['status'] ?? 'pending');
        if (isset($status_counts[$status])) {
            $status_counts[$status]++;
        }
    }
    
    $error_message = null;
    
} catch (PDOException $e) {
    $error_message = 'Unable to update the appointment.';
    $appointments = [];
    $total_appointments = 0;
    $status_counts = [
        'pending' => 0,
        'approved' => 0,
        'no_show' => 0,
        'completed' => 0,
        'cancelled' => 0
    ];
} catch (Exception $e) {
    $error_message = 'Unable to load appointments.';
    $appointments = [];
    $total_appointments = 0;
    $status_counts = [
        'pending' => 0,
        'approved' => 0,
        'no_show' => 0,
        'completed' => 0,
        'cancelled' => 0
    ];
}





function getStatusBadge($status) {
    $status = strtolower($status ?? 'pending');
    
    $badges = [
        'pending' => [
            'color' => 'bg-yellow-100 text-yellow-800',
            'icon' => '⏳',
            'label' => 'Pending'
        ],
        'approved' => [
            'color' => 'bg-blue-100 text-blue-800',
            'icon' => '✓',
            'label' => 'Approved'
        ],
        'no_show' => [
            'color' => 'bg-red-100 text-red-800',
            'icon' => '✕',
            'label' => 'No Show'
        ],
        '0' => [
            'color' => 'bg-red-100 text-red-800',
            'icon' => '✕',
            'label' => 'No Show'
        ],
        'completed' => [
            'color' => 'bg-green-100 text-green-800',
            'icon' => '★',
            'label' => 'Completed'
        ],
        'cancelled' => [
            'color' => 'bg-gray-100 text-gray-800',
            'icon' => '◉',
            'label' => 'Cancelled'
        ]
    ];
    
    $badge = $badges[$status] ?? $badges['pending'];
    
    return '
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ' . $badge['color'] . '">
            <span class="mr-1">' . $badge['icon'] . '</span>
            ' . $badge['label'] . '
        </span>
    ';
}





function formatDateTime($date, $time) {
    if (!$date || !$time) return 'N/A';
    
    try {
        $datetime = DateTime::createFromFormat('Y-m-d H:i:s', $date . ' ' . $time);
        if ($datetime) {
            return $datetime->format('M d, Y • h:i A');
        }
    } catch (Exception $e) {}
    
    return htmlspecialchars($date . ' ' . $time);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments Management - ScanCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">

<!
<!
<!
<nav class="bg-white shadow-sm">
    <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Appointments Management</h1>
            <p class="text-sm text-gray-600 mt-1">Logged in as: <strong><?php echo htmlspecialchars($bhw_name); ?></strong></p>
        </div>
        <a href="dashboard_bhw.php" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">
            ← Back to Dashboard
        </a>
    </div>
</nav>

<!
<!
<!
<div class="max-w-7xl mx-auto px-4 py-8">
    
    <!
    <?php if (!empty($error_message)): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3">
            <i class="fas fa-exclamation-circle text-red-600 text-lg mt-0.5"></i>
            <div>
                <h3 class="font-semibold text-red-900">Error Loading Appointments</h3>
                <p class="text-sm text-red-800"><?php echo htmlspecialchars($error_message); ?></p>
            </div>
        </div>
    <?php endif; ?>
    
    <!
    <!
    <!
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
        
        <!
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Appointments</p>
                    <p class="text-3xl font-bold text-gray-900"><?php echo $total_appointments; ?></p>
                </div>
                <i class="fas fa-calendar-check text-4xl text-blue-200"></i>
            </div>
        </div>
        
        <!
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Pending</p>
                    <p class="text-3xl font-bold text-yellow-600"><?php echo $status_counts['pending']; ?></p>
                </div>
                <i class="fas fa-hourglass-half text-4xl text-yellow-200"></i>
            </div>
        </div>
        
        <!
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Approved</p>
                    <p class="text-3xl font-bold text-blue-600"><?php echo $status_counts['approved']; ?></p>
                </div>
                <i class="fas fa-check-circle text-4xl text-blue-200"></i>
            </div>
        </div>
        
        <!
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">No Show</p>
                    <p class="text-3xl font-bold text-red-600"><?php echo $status_counts['no_show']; ?></p>
                </div>
                <i class="fas fa-times-circle text-4xl text-red-200"></i>
            </div>
        </div>
        
        <!
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Completed</p>
                    <p class="text-3xl font-bold text-green-600"><?php echo $status_counts['completed']; ?></p>
                </div>
                <i class="fas fa-star text-4xl text-green-200"></i>
            </div>
        </div>
        
    </div>
    
    <!
    <!
    <!
    <div class="bg-white rounded-lg shadow overflow-hidden">
        
        <!
        <div class="border-b border-gray-200 bg-gray-50">
            <div class="px-6 flex gap-0">
                <button class="tab-button active px-6 py-4 font-semibold text-gray-900 border-b-2 border-blue-600 hover:bg-gray-100 transition" data-tab="todays-queue">
                    <i class="fas fa-clock mr-2"></i>Today's Queue <span class="ml-2 bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full text-xs font-bold"><?php echo $todays_queue_count; ?></span>
                </button>
                <button class="tab-button px-6 py-4 font-semibold text-gray-600 border-b-2 border-transparent hover:bg-gray-100 transition" data-tab="followup">
                    <i class="fas fa-calendar-plus mr-2"></i>Follow-up Check-up <span class="ml-2 bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded-full text-xs font-bold"><?php echo $followup_queue_count; ?></span>
                </button>
                <button class="tab-button px-6 py-4 font-semibold text-gray-600 border-b-2 border-transparent hover:bg-gray-100 transition" data-tab="history">
                    <i class="fas fa-history mr-2"></i>Appointment History <span class="ml-2 bg-green-100 text-green-800 px-2 py-0.5 rounded-full text-xs font-bold"><?php echo $history_count; ?></span>
                </button>
                <button class="tab-button px-6 py-4 font-semibold text-gray-600 border-b-2 border-transparent hover:bg-gray-100 transition" data-tab="manual-entry">
                    <i class="fas fa-keyboard mr-2"></i>Manual Entry
                </button>
            </div>
        </div>
        
        <!
        
        <!
        <div id="todays-queue-content" class="tab-content active">
            <?php if (count($todays_queue) > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Date & Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Patient Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Purpose</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">BHW</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($todays_queue as $apt): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                
                                <!
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div class="flex items-center">
                                        <i class="fas fa-calendar text-blue-500 mr-2"></i>
                                        <?php echo formatDateTime($apt['appointment_date'], $apt['appointment_time']); ?>
                                    </div>
                                </td>
                                
                                <!
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($apt['patient_name'] ?? 'Unknown Patient'); ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        ID: <?php echo htmlspecialchars($apt['resident_id']); ?>
                                    </div>
                                </td>
                                
                                <!
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($apt['contact_number'] ?? 'N/A'); ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo htmlspecialchars($apt['patient_email'] ?? 'N/A'); ?>
                                    </div>
                                </td>
                                
                                <!
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo htmlspecialchars(substr($apt['purpose'], 0, 50)); ?>
                                    <?php if (strlen($apt['purpose']) > 50): ?>
                                        <span class="text-gray-500">...</span>
                                    <?php endif; ?>
                                </td>
                                
                                <!
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php echo getStatusBadge($apt['status']); ?>
                                </td>
                                
                                <!
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($apt['health_worker_name'] ?? 'Unassigned'); ?>
                                </td>
                                
                                <!
                                <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2 flex flex-wrap gap-2">
                                    <!
                                    <button 
                                        type="button"
                                        onclick="updateAppointmentStatus(<?php echo $apt['appointment_id']; ?>, 'rendered', '<?php echo htmlspecialchars($apt['patient_name']); ?>')"
                                        class="px-3 py-1 bg-green-500 text-white text-xs font-medium rounded hover:bg-green-600 transition-colors"
                                        title="Mark as Completed">
                                        <i class="fas fa-check mr-1"></i> Completed
                                    </button>
                                    
                                    <!
                                    <button 
                                        type="button"
                                        onclick="updateAppointmentStatus(<?php echo $apt['appointment_id']; ?>, 'no_show', '<?php echo htmlspecialchars($apt['patient_name']); ?>')"
                                        class="px-3 py-1 bg-red-500 text-white text-xs font-medium rounded hover:bg-red-600 transition-colors"
                                        title="Mark as No Show">
                                        <i class="fas fa-times mr-1"></i> No Show
                                    </button>
                                    
                                    <!
                                    <button 
                                        type="button"
                                        onclick="updateAppointmentStatus(<?php echo $apt['appointment_id']; ?>, 'follow_up', '<?php echo htmlspecialchars($apt['patient_name']); ?>')"
                                        class="px-3 py-1 bg-yellow-400 text-gray-900 text-xs font-medium rounded hover:bg-yellow-500 transition-colors"
                                        title="Schedule Follow-up">
                                        <i class="fas fa-plus mr-1"></i> Follow-up
                                    </button>
                                </td>
                                
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="px-6 py-12 text-center">
                    <i class="fas fa-inbox text-4xl text-gray-300 mb-4 block"></i>
                    <p class="text-lg text-gray-600">No scheduled appointments for today.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!
        <div id="followup-content" class="tab-content hidden">
            <?php if (count($followup_queue) > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Date & Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Patient Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Purpose</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Follow-up Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Follow-up Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($followup_queue as $apt): ?>
                            <tr class="hover:bg-gray-50 transition-colors" data-appointment-id="<?php echo $apt['appointment_id']; ?>">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div class="flex items-center">
                                        <i class="fas fa-calendar text-blue-500 mr-2"></i>
                                        <?php echo formatDateTime($apt['appointment_date'], $apt['appointment_time']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($apt['patient_name'] ?? 'Unknown Patient'); ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        ID: <?php echo htmlspecialchars($apt['resident_id']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($apt['contact_number'] ?? 'N/A'); ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo htmlspecialchars($apt['patient_email'] ?? 'N/A'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo htmlspecialchars(substr($apt['purpose'], 0, 40)); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php echo getStatusBadge($apt['status']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="date" class="followup-date" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" class="px-2 py-1 border border-gray-300 rounded text-sm">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <select class="followup-time px-2 py-1 border border-gray-300 rounded text-sm">
                                        <option value="09:00">9:00 AM</option>
                                        <option value="10:00">10:00 AM</option>
                                        <option value="11:00">11:00 AM</option>
                                        <option value="14:00">2:00 PM</option>
                                        <option value="15:00">3:00 PM</option>
                                        <option value="16:00">4:00 PM</option>
                                    </select>
                                    <button onclick="saveFollowupSchedule(<?php echo $apt['appointment_id']; ?>, '<?php echo htmlspecialchars($apt['patient_name']); ?>')" class="ml-2 px-2 py-1 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700">
                                        Save
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="px-6 py-12 text-center">
                    <i class="fas fa-inbox text-4xl text-gray-300 mb-4 block"></i>
                    <p class="text-lg text-gray-600">No follow-up appointments scheduled.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!
        <div id="history-content" class="tab-content hidden">
            <?php if (count($history) > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Date & Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Patient Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Purpose</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">BHW</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($history as $apt): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div class="flex items-center">
                                        <i class="fas fa-calendar text-blue-500 mr-2"></i>
                                        <?php echo formatDateTime($apt['appointment_date'], $apt['appointment_time']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($apt['patient_name'] ?? 'Unknown Patient'); ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        ID: <?php echo htmlspecialchars($apt['resident_id']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($apt['contact_number'] ?? 'N/A'); ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo htmlspecialchars($apt['patient_email'] ?? 'N/A'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo htmlspecialchars(substr($apt['purpose'], 0, 50)); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php echo getStatusBadge($apt['status']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($apt['health_worker_name'] ?? 'Unassigned'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="px-6 py-12 text-center">
                    <i class="fas fa-inbox text-4xl text-gray-300 mb-4 block"></i>
                    <p class="text-lg text-gray-600">No appointment history yet.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!
        <div id="manual-entry-content" class="tab-content hidden">
            <div class="p-6">
                <div class="max-w-2xl">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Search Patient by 6-Digit ID</h3>
                    
                    <!
                    <div class="flex gap-2 mb-6">
                        <input 
                            type="text" 
                            id="patientIdInput" 
                            placeholder="Enter 6-digit Patient ID" 
                            maxlength="6"
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600"
                            onkeyup="searchPatientById(this.value)">
                        <button onclick="searchPatientById(document.getElementById('patientIdInput').value)" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                            <i class="fas fa-search mr-2"></i>Search
                        </button>
                    </div>
                    
                    <!
                    <div id="patientProfileContainer" class="hidden">
                        <div class="bg-gradient-to-r from-blue-50 to-blue-100 p-6 rounded-lg border border-blue-200">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">Patient Profile</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs text-gray-600 uppercase tracking-wide">Full Name</p>
                                    <p class="text-lg font-bold text-gray-900" id="patientFullName">-</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-600 uppercase tracking-wide">Patient ID</p>
                                    <p class="text-lg font-bold text-blue-600" id="patientID">-</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-600 uppercase tracking-wide">Contact Number</p>
                                    <p class="text-base text-gray-900" id="patientContact">-</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-600 uppercase tracking-wide">Email Address</p>
                                    <p class="text-base text-gray-900" id="patientEmail">-</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!
                    <div id="noResultsMessage" class="hidden bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                        <i class="fas fa-search text-4xl text-yellow-300 mb-2 block"></i>
                        <p class="text-gray-700">No patient found with that ID.</p>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
    
</div>

<!
<!
<!

<!
<div id="noShowModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full mx-4">
        <div class="p-6">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                <i class="fas fa-exclamation text-red-600 text-xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 text-center mb-2">
                Mark as No Show?
            </h3>
            <p class="text-gray-600 text-center mb-4">
                Are you sure you want to mark <strong id="patientNameDisplay"></strong> as a no show?
                This action will be recorded in the system.
            </p>
            <div class="flex gap-3">
                <button 
                    type="button"
                    onclick="closeNoShowModal()"
                    class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium">
                    Cancel
                </button>
                <button 
                    type="button"
                    id="confirmNoShowBtn"
                    onclick="confirmNoShow()"
                    class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium">
                    Yes, Mark No Show
                </button>
            </div>
        </div>
    </div>
</div>

<!
<div id="successMessage" class="fixed top-4 right-4 bg-green-50 border border-green-200 rounded-lg px-6 py-4 hidden z-50 max-w-md">
    <div class="flex items-start gap-3">
        <i class="fas fa-check-circle text-green-600 text-lg mt-0.5"></i>
        <div>
            <h3 class="font-semibold text-green-900">Success!</h3>
            <p class="text-sm text-green-800" id="successText"></p>
        </div>
        <button onclick="document.getElementById('successMessage').classList.add('hidden')" class="text-green-400 hover:text-green-600">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

<!
<div id="errorMessage" class="fixed top-4 right-4 bg-red-50 border border-red-200 rounded-lg px-6 py-4 hidden z-50 max-w-md">
    <div class="flex items-start gap-3">
        <i class="fas fa-exclamation-circle text-red-600 text-lg mt-0.5"></i>
        <div>
            <h3 class="font-semibold text-red-900">Error</h3>
            <p class="text-sm text-red-800" id="errorText"></p>
        </div>
        <button onclick="document.getElementById('errorMessage').classList.add('hidden')" class="text-red-400 hover:text-red-600">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

<!
<!
<!

<script>





document.querySelectorAll('.tab-button').forEach(button => {
    button.addEventListener('click', function() {
        const tabName = this.getAttribute('data-tab');
        
        
        document.querySelectorAll('.tab-button').forEach(b => {
            b.classList.remove('active', 'border-b-2', 'border-blue-600', 'text-gray-900');
            b.classList.add('border-b-2', 'border-transparent', 'text-gray-600');
        });
        
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
            content.classList.remove('active');
        });
        
        
        this.classList.add('active', 'border-b-2', 'border-blue-600', 'text-gray-900');
        this.classList.remove('border-b-2', 'border-transparent', 'text-gray-600');
        
        
        const contentId = tabName + '-content';
        const content = document.getElementById(contentId);
        if (content) {
            content.classList.remove('hidden');
            content.classList.add('active');
        }
    });
});





function updateAppointmentStatus(appointmentId, status, patientName) {
    const statusLabels = {
        'rendered': 'Completed - Consultation Complete',
        'no_show': 'No Show - Patient Did Not Attend',
        'follow_up': 'Follow-up - Scheduled for Later'
    };
    
    const displayLabel = statusLabels[status] || status;
    
    if (!confirm(`Mark appointment as "${displayLabel}"?`)) {
        return;
    }
    
    const button = event.target.closest('button');
    if (!button) return;
    
    button.disabled = true;
    const originalHTML = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    
    fetch('update_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'appointment_id=' + appointmentId + '&status=' + status
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess(patientName + ' - ' + displayLabel);
            setTimeout(() => { location.reload(); }, 1500);
        } else {
            throw new Error(data.message || 'Failed to update status');
        }
    })
    .catch(error => {
        button.disabled = false;
        button.innerHTML = originalHTML;
        showError('Error: ' + error.message);
    });
}





function saveFollowupSchedule(appointmentId, patientName) {
    const row = document.querySelector(`tr[data-appointment-id="${appointmentId}"]`);
    if (!row) return;
    
    const dateInput = row.querySelector('.followup-date');
    const timeInput = row.querySelector('.followup-time');
    
    if (!dateInput || !timeInput) {
        showError('Could not find date/time fields');
        return;
    }
    
    const followupDate = dateInput.value;
    const followupTime = timeInput.value;
    
    if (!followupDate || !followupTime) {
        showError('Please select both date and time');
        return;
    }
    
    const button = event.target;
    button.disabled = true;
    const originalText = button.textContent;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    
    fetch('update_followup_schedule.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'appointment_id=' + appointmentId + '&followup_date=' + followupDate + '&followup_time=' + followupTime
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess(patientName + ' - Follow-up scheduled for ' + followupDate);
            setTimeout(() => { location.reload(); }, 1500);
        } else {
            throw new Error(data.message || 'Failed to save follow-up schedule');
        }
    })
    .catch(error => {
        button.disabled = false;
        button.textContent = originalText;
        showError('Error: ' + error.message);
    });
}





function searchPatientById(patientId) {
    const profileContainer = document.getElementById('patientProfileContainer');
    const noResultsMessage = document.getElementById('noResultsMessage');
    
    if (!patientId || patientId.trim().length === 0) {
        profileContainer.classList.add('hidden');
        noResultsMessage.classList.add('hidden');
        return;
    }
    
    if (patientId.length !== 6 || isNaN(patientId)) {
        profileContainer.classList.add('hidden');
        noResultsMessage.classList.add('hidden');
        return;
    }
    
    fetch('search_patient.php?resident_id=' + encodeURIComponent(patientId))
        .then(response => response.json())
        .then(data => {
            if (data.success && data.patient) {
                document.getElementById('patientFullName').textContent = data.patient.full_name || '-';
                document.getElementById('patientID').textContent = data.patient.resident_id || '-';
                document.getElementById('patientContact').textContent = data.patient.contact_number || '-';
                document.getElementById('patientEmail').textContent = data.patient.email || '-';
                
                profileContainer.classList.remove('hidden');
                noResultsMessage.classList.add('hidden');
            } else {
                profileContainer.classList.add('hidden');
                noResultsMessage.classList.remove('hidden');
            }
        })
        .catch(error => {
            console.error('Search error:', error);
            profileContainer.classList.add('hidden');
            noResultsMessage.classList.remove('hidden');
        });
}





function showSuccess(message) {
    const div = document.createElement('div');
    div.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2';
    div.innerHTML = '<i class="fas fa-check-circle"></i> ' + message;
    document.body.appendChild(div);
    
    setTimeout(() => div.remove(), 3000);
}

function showError(message) {
    const div = document.createElement('div');
    div.className = 'fixed bottom-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2';
    div.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + message;
    document.body.appendChild(div);
    
    setTimeout(() => div.remove(), 4000);
}

</script>

</body>
</html>

