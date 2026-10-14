<?php


require_once 'db_config.php';


session_start();
$resident_id = $_SESSION['resident_id'] ?? null;

if (!$resident_id) {
    echo 'Not authenticated as resident.';
    exit;
}




function getMedicalVisitHistory($pdo, $resident_id) {
    try {
        
        $stmt = $pdo->prepare("
            SELECT 
                'appointment' as record_type,
                a.appointment_id as record_id,
                a.appointment_date as visit_date,
                a.appointment_time as visit_time,
                a.purpose as diagnosis,
                a.status as notes,
                hw.full_name as health_worker_name,
                NULL as treatment
            FROM appointment a
            LEFT JOIN health_worker hw ON a.health_worker_id = hw.worker_id
            WHERE a.resident_id = ? AND a.status IN ('Approved', 'Completed')
            
            UNION ALL
            
            SELECT 
                'consultation' as record_type,
                pr.record_id,
                pr.visit_date,
                NULL as visit_time,
                pr.diagnosis,
                pr.notes,
                hw.full_name as health_worker_name,
                pr.treatment
            FROM patient_record pr
            LEFT JOIN health_worker hw ON pr.health_worker_id = hw.worker_id
            WHERE pr.resident_id = ?
            
            ORDER BY visit_date DESC, visit_time DESC
        ");
        
        $stmt->execute([$resident_id, $resident_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
    } catch (Exception $e) {
        error_log("Get medical history error: " . $e->getMessage());
        return [];
    }
}

$pdo = getDatabaseConnection();
$medical_history = getMedicalVisitHistory($pdo, $resident_id);
?>

<!
<div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
    
    <!
    <div class="mb-6">
        <h3 class="text-lg font-bold text-slate-900">Medical Visit History</h3>
        <p class="text-sm text-slate-500 mt-1">Your appointments and consultation records</p>
    </div>
    
    <!
    <?php if (empty($medical_history)): ?>
        <!
        <div class="py-12 text-center">
            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-calendar-times text-2xl text-slate-400"></i>
            </div>
            <h4 class="text-base font-semibold text-slate-700 mb-2">No Medical History Yet</h4>
            <p class="text-slate-500 max-w-sm mx-auto">
                Once you visit the Rural Health Unit (RHU), your medical records will appear here. Your health worker will log your appointments and consultations.
            </p>
        </div>
    <?php else: ?>
        <!
        <div class="space-y-4">
            <?php foreach ($medical_history as $record): ?>
            <div class="border-l-4 border-emerald-500 bg-slate-50 p-4 rounded-r-lg hover:bg-slate-100 transition">
                
                <!
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <?php if ($record['record_type'] === 'appointment'): ?>
                                <span class="text-xs font-bold bg-blue-100 text-blue-700 px-2 py-1 rounded">
                                    <i class="fas fa-calendar-check mr-1"></i>Appointment
                                </span>
                            <?php else: ?>
                                <span class="text-xs font-bold bg-emerald-100 text-emerald-700 px-2 py-1 rounded">
                                    <i class="fas fa-stethoscope mr-1"></i>Consultation
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <!
                        <p class="text-sm text-slate-600 mt-2">
                            <i class="fas fa-calendar mr-2 text-slate-400"></i>
                            <strong><?php echo date('F d, Y', strtotime($record['visit_date'])); ?></strong>
                            <?php if ($record['visit_time']): ?>
                                at <?php echo date('h:i A', strtotime($record['visit_time'])); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <!
                    <div>
                        <?php 
                        $status = $record['notes'] ?? $record['status'] ?? 'Completed';
                        if ($status === 'Approved'):
                            $status_class = 'bg-emerald-50 text-emerald-700';
                            $icon = 'fa-check-circle';
                        elseif ($status === 'Completed'):
                            $status_class = 'bg-emerald-50 text-emerald-700';
                            $icon = 'fa-check-circle';
                        elseif ($status === 'No Show'):
                            $status_class = 'bg-red-50 text-red-700';
                            $icon = 'fa-times-circle';
                        else:
                            $status_class = 'bg-blue-50 text-blue-700';
                            $icon = 'fa-clock';
                        endif;
                        ?>
                        <span class="inline-flex items-center gap-1 px-3 py-1 <?php echo $status_class; ?> text-xs font-bold rounded-full">
                            <i class="fas <?php echo $icon; ?>"></i> <?php echo $status; ?>
                        </span>
                    </div>
                </div>
                
                <!
                <p class="text-sm text-slate-600 mb-3">
                    <i class="fas fa-user-md mr-2 text-slate-400"></i>
                    <strong><?php echo htmlspecialchars($record['health_worker_name'] ?? 'RHU Staff'); ?></strong>
                </p>
                
                <!
                <?php if ($record['diagnosis']): ?>
                <div class="mb-2">
                    <p class="text-xs font-semibold text-slate-600 mb-1">DIAGNOSIS / PURPOSE:</p>
                    <p class="text-sm text-slate-700 bg-white p-2 rounded border border-slate-200">
                        <?php echo htmlspecialchars($record['diagnosis']); ?>
                    </p>
                </div>
                <?php endif; ?>
                
                <!
                <?php if ($record['treatment']): ?>
                <div>
                    <p class="text-xs font-semibold text-slate-600 mb-1">TREATMENT PLAN:</p>
                    <p class="text-sm text-slate-700 bg-white p-2 rounded border border-slate-200">
                        <?php echo htmlspecialchars($record['treatment']); ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

