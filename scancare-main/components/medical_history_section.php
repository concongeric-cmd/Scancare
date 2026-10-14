<?php



if (!isset($_SESSION['resident_id'])) {
    die('ERROR: Medical history component requires resident_id in session.');
}

try {
    $pdo = getDatabaseConnection();
    
    if ($pdo === false) {
        throw new Exception('Database connection failed');
    }
    
    $resident_id = $_SESSION['resident_id'];
    
    
    
    
    
    
    
    
    $query = "
        SELECT 
            pr.record_id,
            pr.visit_date,
            pr.diagnosis,
            pr.treatment,
            pr.notes,
            hw.full_name AS health_worker_name,
            hw.worker_id
        FROM patient_record pr
        LEFT JOIN health_worker hw ON pr.health_worker_id = hw.worker_id
        WHERE pr.resident_id = ?
        ORDER BY pr.visit_date DESC
        LIMIT 50
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$resident_id]);
    $medical_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_visits = count($medical_records);
    $latest_visit = ($total_visits > 0) ? $medical_records[0]['visit_date'] : null;
    
} catch (Exception $e) {
    error_log("Medical history query error: " . $e->getMessage());
    $medical_records = [];
    $total_visits = 0;
    $latest_visit = null;
}

?>

<!
     MEDICAL HISTORY SECTION - MAIN CONTAINER
     ===================================================================== 
<section class="mt-12">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">
            <i class="fas fa-file-medical mr-3 text-emerald-600"></i>Medical History
        </h2>
        <p class="text-slate-600 dark:text-slate-400">Your medical records from Rural Health Unit visits</p>
    </div>

    <!
    <div class="grid grid-cols-2 gap-4 mb-8 md:grid-cols-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg p-4 border border-slate-200 dark:border-slate-700">
            <p class="text-slate-600 dark:text-slate-400 text-sm font-medium">Total Visits</p>
            <p class="text-3xl font-bold text-emerald-600 mt-1"><?php echo $total_visits; ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg p-4 border border-slate-200 dark:border-slate-700">
            <p class="text-slate-600 dark:text-slate-400 text-sm font-medium">Latest Visit</p>
            <p class="text-lg font-semibold text-slate-900 dark:text-white mt-1">
                <?php echo ($latest_visit) ? date('M d, Y', strtotime($latest_visit)) : 'N/A'; ?>
            </p>
        </div>
    </div>

    <!
         CONDITION A: EMPTY STATE (No medical records)
         =================================================================== 
    <?php if ($total_visits === 0): ?>
        
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-slate-800 dark:to-slate-700 rounded-xl border-2 border-dashed border-blue-300 dark:border-slate-600 p-12 text-center">
            
            <!
            <div class="flex justify-center mb-4">
                <div class="bg-blue-100 dark:bg-slate-600 rounded-full p-4">
                    <i class="fas fa-folder-open text-3xl text-blue-600 dark:text-blue-300"></i>
                </div>
            </div>
            
            <!
            <h3 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">
                No Medical History Yet
            </h3>
            <p class="text-slate-600 dark:text-slate-300 max-w-md mx-auto mb-6">
                Once you visit the Rural Health Unit (RHU), your medical records will appear here. 
                Your health worker will document your consultation and create your medical record.
            </p>
            
            <!
            <a href="appointments.php" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2 px-6 rounded-lg transition">
                <i class="fas fa-calendar-check"></i>Schedule an Appointment
            </a>
        </div>

    <!
         CONDITION B: DISPLAY MEDICAL RECORDS (Has visit history)
         =================================================================== 
    <?php else: ?>
        
        <div class="space-y-4">
            <?php foreach ($medical_records as $record): ?>
                
                <!
                <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6 hover:shadow-lg transition">
                    
                    <!
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-sm font-semibold text-emerald-600 uppercase tracking-wide">
                                <i class="fas fa-calendar-alt mr-2"></i><?php echo date('F d, Y', strtotime($record['visit_date'])); ?>
                            </p>
                            <h4 class="text-lg font-bold text-slate-900 dark:text-white mt-1">
                                <?php echo htmlspecialchars($record['health_worker_name'] ?? 'Health Worker'); ?>
                            </h4>
                        </div>
                        <span class="bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-xs font-semibold px-3 py-1 rounded-full">
                            Completed
                        </span>
                    </div>

                    <!
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        
                        <!
                        <div>
                            <p class="text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wide">Diagnosis</p>
                            <p class="text-slate-900 dark:text-white font-medium mt-1">
                                <?php echo htmlspecialchars($record['diagnosis'] ?? 'No diagnosis recorded'); ?>
                            </p>
                        </div>

                        <!
                        <div>
                            <p class="text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wide">Treatment</p>
                            <p class="text-slate-900 dark:text-white font-medium mt-1">
                                <?php echo htmlspecialchars($record['treatment'] ?? 'No treatment recorded'); ?>
                            </p>
                        </div>
                    </div>

                    <!
                    <?php if (!empty($record['notes'])): ?>
                        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-3">
                            <p class="text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wide mb-2">Additional Notes</p>
                            <p class="text-slate-700 dark:text-slate-300 text-sm">
                                <?php echo htmlspecialchars($record['notes']); ?>
                            </p>
                        </div>
                    <?php endif; ?>

                </div>

            <?php endforeach; ?>
        </div>

    <?php endif; ?>

</section>

<!
     OPTIONAL: MEDICAL HISTORY STYLES (if not using global Tailwind)
     ===================================================================== 
<style>
    @media (prefers-color-scheme: dark) {
        .dark\:text-white {
            color: #ffffff;
        }
        .dark\:bg-slate-800 {
            background-color: #1e293b;
        }
    }
</style>

