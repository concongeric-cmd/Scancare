<?php



if (!isset($_SESSION['resident_id'])) {
    error_log("ERROR: Medical history component requires resident_id in session");
    echo '<div class="alert alert-error">⚠️ Session error - unable to load medical history</div>';
    exit;
}

$resident_id = $_SESSION['resident_id'];
$medical_records = [];
$total_visits = 0;
$latest_visit_date = null;
$error_message = null;

try {
    
    
    
    $pdo = getDatabaseConnection();
    
    if ($pdo === false) {
        throw new Exception('Database connection failed');
    }
    
    
    
    
    
    
    
    
    $fetch_query = "
        SELECT 
            history_id,
            user_id,
            visit_date,
            visit_type,
            bhw_name,
            diagnosis,
            treatment,
            notes,
            created_at
        FROM medical_history
        WHERE user_id = ?
        ORDER BY visit_date DESC
        LIMIT 100
    ";
    
    $stmt = $pdo->prepare($fetch_query);
    if (!$stmt) {
        throw new Exception("Query preparation failed: " . $pdo->errorInfo()[2]);
    }
    
    if (!$stmt->execute([$resident_id])) {
        throw new Exception("Query execution failed: " . implode(" ", $stmt->errorInfo()));
    }
    
    $medical_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_visits = count($medical_records);
    
    if ($total_visits > 0) {
        $latest_visit_date = $medical_records[0]['visit_date'];
    }
    
    error_log("Medical History Component: Resident {$resident_id} - Found {$total_visits} records");
    
} catch (Exception $e) {
    error_log("Medical history query error for resident {$resident_id}: " . $e->getMessage());
    $error_message = $e->getMessage();
}

?>

<!
     MEDICAL HISTORY SECTION - MAIN CONTAINER
     ===================================================================== 
<section class="mt-12 mb-12">
    
    <!
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-3">
            <i class="fas fa-file-medical text-emerald-600"></i>
            Medical Visit History
        </h2>
        <p class="text-slate-600 dark:text-slate-400">Your complete medical records from health worker visits</p>
    </div>

    <!
    <div class="grid grid-cols-2 gap-4 mb-8 md:grid-cols-4">
        <!
        <div class="bg-white dark:bg-slate-800 rounded-lg p-4 border border-slate-200 dark:border-slate-700 shadow-sm">
            <p class="text-slate-600 dark:text-slate-400 text-sm font-semibold uppercase tracking-wide">Total Visits</p>
            <p class="text-3xl font-bold text-emerald-600 mt-2"><?php echo $total_visits; ?></p>
        </div>

        <!
        <div class="bg-white dark:bg-slate-800 rounded-lg p-4 border border-slate-200 dark:border-slate-700 shadow-sm">
            <p class="text-slate-600 dark:text-slate-400 text-sm font-semibold uppercase tracking-wide">Latest Visit</p>
            <p class="text-lg font-semibold text-slate-900 dark:text-white mt-2">
                <?php 
                if ($latest_visit_date) {
                    echo date('M d, Y', strtotime($latest_visit_date));
                } else {
                    echo 'No visits yet';
                }
                ?>
            </p>
        </div>

        <!
        <div class="bg-white dark:bg-slate-800 rounded-lg p-4 border border-slate-200 dark:border-slate-700 shadow-sm">
            <p class="text-slate-600 dark:text-slate-400 text-sm font-semibold uppercase tracking-wide">Last Record Status</p>
            <p class="text-lg font-semibold mt-2">
                <span class="inline-flex items-center gap-1 px-2 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded-full text-xs font-bold">
                    <i class="fas fa-check-circle"></i> Active
                </span>
            </p>
        </div>

        <!
        <div class="bg-white dark:bg-slate-800 rounded-lg p-4 border border-slate-200 dark:border-slate-700 shadow-sm">
            <p class="text-slate-600 dark:text-slate-400 text-sm font-semibold uppercase tracking-wide">Record Count</p>
            <p class="text-3xl font-bold text-blue-600 mt-2"><?php echo $total_visits; ?></p>
        </div>
    </div>

    <!
         ERROR STATE - Database or query error
         =================================================================== 
    <?php if (!empty($error_message)): ?>
        <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 rounded-lg p-6 mb-8">
            <div class="flex items-start gap-3">
                <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400 text-xl mt-1"></i>
                <div>
                    <h3 class="font-semibold text-red-900 dark:text-red-200">Unable to Load Medical History</h3>
                    <p class="text-red-700 dark:text-red-300 text-sm mt-1"><?php echo htmlspecialchars($error_message); ?></p>
                    <p class="text-red-600 dark:text-red-400 text-xs mt-2">Please contact your health worker or system administrator for assistance.</p>
                </div>
            </div>
        </div>

    <!
         EMPTY STATE - No medical records yet
         =================================================================== 
    <?php elseif ($total_visits === 0): ?>
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-slate-800 dark:to-slate-700 rounded-xl border-2 border-dashed border-blue-300 dark:border-slate-600 p-12 text-center">
            
            <!
            <div class="flex justify-center mb-4">
                <div class="bg-blue-100 dark:bg-slate-600 rounded-full p-6">
                    <i class="fas fa-folder-open text-4xl text-blue-600 dark:text-blue-300"></i>
                </div>
            </div>
            
            <!
            <h3 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">
                No Medical History Yet
            </h3>
            <p class="text-slate-600 dark:text-slate-300 max-w-md mx-auto mb-6 text-sm">
                Once you visit your health worker and they approve your appointment, your medical records will appear here. 
                All visits, diagnoses, and treatment information will be documented.
            </p>
            
            <!
            <div class="flex justify-center gap-3 flex-wrap">
                <a href="appointments.php" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 px-6 rounded-lg transition">
                    <i class="fas fa-calendar-check"></i>
                    Schedule Appointment
                </a>
                <a href="dashboard.php" class="inline-flex items-center gap-2 bg-slate-300 hover:bg-slate-400 text-slate-900 font-semibold py-2 px-6 rounded-lg transition">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>

    <!
         MEDICAL RECORDS LIST - Show all visits
         =================================================================== 
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($medical_records as $index => $record): ?>
                <!
                <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6 hover:shadow-lg hover:border-slate-300 dark:hover:border-slate-600 transition-all">
                    
                    <!
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 pb-4 border-b border-slate-200 dark:border-slate-700">
                        <div class="flex-1">
                            <!
                            <p class="text-sm font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wide mb-1">
                                <i class="fas fa-calendar-alt mr-2"></i>
                                <?php echo date('F d, Y', strtotime($record['visit_date'])); ?>
                            </p>
                            
                            <!
                            <h4 class="text-lg font-bold text-slate-900 dark:text-white">
                                <i class="fas fa-user-md mr-2 text-blue-600"></i>
                                <?php echo htmlspecialchars($record['bhw_name'] ?? 'Health Worker'); ?>
                            </h4>
                        </div>
                        
                        <!
                        <div class="mt-4 md:mt-0 text-right">
                            <span class="inline-flex items-center gap-2 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-xs font-bold px-3 py-1 rounded-full">
                                <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                                Completed
                            </span>
                        </div>
                    </div>
                    
                    <!
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        
                        <!
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                            <p class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                                <i class="fas fa-stethoscope mr-2 text-blue-600"></i>Diagnosis
                            </p>
                            <p class="text-slate-900 dark:text-white font-medium leading-relaxed">
                                <?php 
                                if (!empty($record['diagnosis'])) {
                                    echo htmlspecialchars($record['diagnosis']);
                                } else {
                                    echo '<span class="text-slate-500 italic">Not recorded yet</span>';
                                }
                                ?>
                            </p>
                        </div>
                        
                        <!
                        <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-4">
                            <p class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                                <i class="fas fa-pills mr-2 text-emerald-600"></i>Treatment Plan
                            </p>
                            <p class="text-slate-900 dark:text-white font-medium leading-relaxed">
                                <?php 
                                if (!empty($record['treatment'])) {
                                    echo htmlspecialchars($record['treatment']);
                                } else {
                                    echo '<span class="text-slate-500 italic">No specific treatment prescribed</span>';
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                    
                    <!
                    <?php if (!empty($record['notes'])): ?>
                    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-4 border border-slate-200 dark:border-slate-600">
                        <p class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                            <i class="fas fa-sticky-note mr-2"></i>Additional Notes
                        </p>
                        <p class="text-slate-700 dark:text-slate-300 text-sm leading-relaxed whitespace-pre-wrap">
                            <?php echo htmlspecialchars($record['notes']); ?>
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <!
                    <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                        <span>
                            <i class="fas fa-hashtag mr-1"></i>Record ID: <?php echo htmlspecialchars($record['history_id']); ?>
                        </span>
                        <span>
                            <i class="fas fa-clock mr-1"></i>Recorded: <?php echo date('M d, Y g:i A', strtotime($record['created_at'])); ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!
        <div class="mt-8 p-4 bg-slate-50 dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 text-center text-sm text-slate-600 dark:text-slate-400">
            <p>
                <i class="fas fa-info-circle mr-2"></i>
                Showing <?php echo $total_visits; ?> medical visit record<?php echo ($total_visits === 1) ? '' : 's'; ?> 
                | Latest visit: <?php echo date('M d, Y', strtotime($latest_visit_date)); ?>
            </p>
        </div>

    <?php endif; ?>

</section>

<style>
    
    .medical-history-section {
        animation: fadeInUp 0.3s ease-out;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    
    @media (max-width: 768px) {
        .medical-history-section .record-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

