<?php


require_once 'db_config.php';


session_start();
$current_worker_id = $_SESSION['worker_id'] ?? null;




if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $appointment_id = intval($_POST['appointment_id'] ?? 0);
    
    if ($action === 'approve' && $appointment_id > 0) {
        try {
            $pdo = getDatabaseConnection();
            $stmt = $pdo->prepare("
                UPDATE appointment 
                SET status = 'Approved' 
                WHERE appointment_id = ?
            ");
            $stmt->execute([$appointment_id]);
            
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Appointment approved']);
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
    
    if ($action === 'no_show' && $appointment_id > 0) {
        try {
            $pdo = getDatabaseConnection();
            $stmt = $pdo->prepare("
                UPDATE appointment 
                SET status = 'No Show' 
                WHERE appointment_id = ?
            ");
            $stmt->execute([$appointment_id]);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Marked as No Show']);
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
}




function getAppointmentStatus($appointment_date, $appointment_time, $db_status) {
    $current_date = date('Y-m-d');
    $current_time = date('H:i:s');
    
    
    if ($db_status === 'Approved' && $appointment_date === $current_date) {
        
        $current_minutes = (int)date('H') * 60 + (int)date('i');
        $appointment_minutes = (int)substr($appointment_time, 0, 2) * 60 + (int)substr($appointment_time, 3, 2);
        
        
        if ($current_minutes >= $appointment_minutes && $current_minutes < ($appointment_minutes + 60)) {
            return 'Ongoing';
        }
    }
    
    return $db_status;
}




function getAppointments($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                a.appointment_id,
                a.resident_id,
                a.health_worker_id,
                a.appointment_date,
                a.appointment_time,
                a.purpose,
                a.status,
                r.full_name as resident_name,
                r.contact_number
            FROM appointment a
            LEFT JOIN resident r ON a.resident_id = r.resident_id
            WHERE a.status IN ('Scheduled', 'Approved', 'Ongoing')
            ORDER BY a.appointment_date ASC, a.appointment_time ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
    } catch (Exception $e) {
        error_log("Get appointments error: " . $e->getMessage());
        return [];
    }
}

$pdo = getDatabaseConnection();
$appointments = getAppointments($pdo);


foreach ($appointments as &$appt) {
    $appt['display_status'] = getAppointmentStatus($appt['appointment_date'], $appt['appointment_time'], $appt['status']);
}
unset($appt);
?>

<!
<div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
    
    <!
    <div class="px-6 py-4 border-b border-slate-200">
        <h3 class="text-lg font-bold text-slate-900">Manage Appointment Schedule</h3>
        <p class="text-sm text-slate-500 mt-1">View and manage resident appointment requests</p>
    </div>
    
    <!
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="px-6 py-3 text-left text-xs font-bold text-slate-700 uppercase">Resident Name</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-slate-700 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-slate-700 uppercase">Time</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-slate-700 uppercase">Purpose</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-slate-700 uppercase">Status</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-slate-700 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($appointments)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                            <i class="fas fa-calendar text-4xl text-slate-300 mb-3 block"></i>
                            <p class="font-medium">No appointments scheduled</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($appointments as $appt): ?>
                    <tr class="border-b border-slate-200 hover:bg-slate-50 transition">
                        <!
                        <td class="px-6 py-4 font-medium text-slate-900">
                            <?php echo htmlspecialchars($appt['resident_name']); ?>
                            <br><span class="text-xs text-slate-500"><?php echo htmlspecialchars($appt['contact_number']); ?></span>
                        </td>
                        
                        <!
                        <td class="px-6 py-4 text-slate-600">
                            <?php echo date('M d, Y', strtotime($appt['appointment_date'])); ?>
                        </td>
                        
                        <!
                        <td class="px-6 py-4 text-slate-600">
                            <?php echo date('h:i A', strtotime($appt['appointment_time'])); ?>
                        </td>
                        
                        <!
                        <td class="px-6 py-4 text-slate-600">
                            <?php echo htmlspecialchars($appt['purpose']); ?>
                        </td>
                        
                        <!
                        <td class="px-6 py-4 text-center">
                            <?php 
                            $status = $appt['display_status'];
                            if ($status === 'Scheduled'):
                                $badge_class = 'bg-blue-50 text-blue-700';
                                $dot_class = 'bg-blue-500';
                            elseif ($status === 'Approved'):
                                $badge_class = 'bg-emerald-50 text-emerald-700';
                                $dot_class = 'bg-emerald-500';
                            elseif ($status === 'Ongoing'):
                                $badge_class = 'bg-orange-50 text-orange-700 animate-pulse';
                                $dot_class = 'bg-orange-500';
                            elseif ($status === 'No Show'):
                                $badge_class = 'bg-red-50 text-red-700';
                                $dot_class = 'bg-red-500';
                            else:
                                $badge_class = 'bg-slate-50 text-slate-700';
                                $dot_class = 'bg-slate-500';
                            endif;
                            ?>
                            <span class="inline-flex items-center gap-2 px-3 py-1 <?php echo $badge_class; ?> text-xs font-bold rounded-full">
                                <span class="w-2 h-2 rounded-full <?php echo $dot_class; ?>"></span>
                                <?php echo $status; ?>
                            </span>
                        </td>
                        
                        <!
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <?php if ($appt['status'] === 'Scheduled'): ?>
                                    <button 
                                        onclick="approveAppointment(<?php echo $appt['appointment_id']; ?>)"
                                        class="px-3 py-1 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 rounded text-xs font-semibold transition"
                                    >
                                        <i class="fas fa-check mr-1"></i>Approve
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($appt['status'] !== 'No Show'): ?>
                                    <button 
                                        onclick="markNoShow(<?php echo $appt['appointment_id']; ?>)"
                                        class="px-3 py-1 bg-red-50 text-red-600 hover:bg-red-100 rounded text-xs font-semibold transition"
                                    >
                                        <i class="fas fa-times mr-1"></i>No Show
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!
<script>
function approveAppointment(appointmentId) {
    if (!confirm('Approve this appointment?')) return;
    
    const formData = new FormData();
    formData.append('action', 'approve');
    formData.append('appointment_id', appointmentId);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✓ Appointment approved');
            location.reload();
        } else {
            alert('✗ Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error: ' + error);
    });
}

function markNoShow(appointmentId) {
    if (!confirm('Mark this appointment as No Show?')) return;
    
    const formData = new FormData();
    formData.append('action', 'no_show');
    formData.append('appointment_id', appointmentId);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✓ Marked as No Show');
            location.reload();
        } else {
            alert('✗ Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error: ' + error);
    });
}
</script>

