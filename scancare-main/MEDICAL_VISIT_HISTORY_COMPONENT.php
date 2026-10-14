<?php


?>

<!
     PART 1: PHP PDO DATA FETCHING LOGIC
     ============================================================================
     Place this code at the TOP of appointments.php (after session_start() and includes)
     ============================================================================ 

<?php









$medical_user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
$medical_history_records = [];
$medical_visit_stats = [
    'total_visits' => 0,
    'latest_visit_date' => 'N/A',
    'status' => 'Active'
];
$medical_history_error = null;


if (!$medical_user_id) {
    $medical_history_error = 'User not authenticated. Please log in to view medical history.';
}







if (!$medical_history_error) {
    try {
        
        
        
        
        $pdo = getDatabaseConnection();
        
        if ($pdo === false) {
            throw new Exception('Database connection failed. Please try again later.');
        }
        
        
        
        
        
        
        
        
        
        
        
        $query_summary = "
            SELECT 
                COUNT(*) AS total_visits,
                MAX(visit_date) AS latest_visit_date
            FROM medical_history
            WHERE user_id = :user_id
        ";
        
        $stmt_summary = $pdo->prepare($query_summary);
        $stmt_summary->bindParam(':user_id', $medical_user_id, PDO::PARAM_INT);
        $stmt_summary->execute();
        
        
        $summary_result = $stmt_summary->fetch(PDO::FETCH_ASSOC);
        
        
        if ($summary_result) {
            $medical_visit_stats['total_visits'] = intval($summary_result['total_visits']);
            $medical_visit_stats['latest_visit_date'] = $summary_result['latest_visit_date'] 
                ? date('M d, Y', strtotime($summary_result['latest_visit_date'])) 
                : 'N/A';
        }
        
        
        
        
        
        
        
        
        
        $query_records = "
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
            WHERE user_id = :user_id
            ORDER BY visit_date DESC, created_at DESC
            LIMIT 50
        ";
        
        $stmt_records = $pdo->prepare($query_records);
        $stmt_records->bindParam(':user_id', $medical_user_id, PDO::PARAM_INT);
        $stmt_records->execute();
        
        
        $medical_history_records = $stmt_records->fetchAll(PDO::FETCH_ASSOC);
        
        
        error_log("Medical History Query: User $medical_user_id - Records fetched: " . count($medical_history_records));
        
    } catch (PDOException $e) {
        
        
        
        
        
        
        
        
        error_log("Medical History PDO Error: " . $e->getMessage());
        $medical_history_error = 'Unable to retrieve medical history. Please try again later.';
        
    } catch (Exception $e) {
        
        
        
        
        
        
        error_log("Medical History Error: " . $e->getMessage());
        $medical_history_error = 'An unexpected error occurred. Please contact support.';
    }
}


function getMedicalVisitTypeBadgeClass($visit_type) {
    $badge_map = [
        'Routine Check-up' => [
            'bg' => 'bg-blue-100',
            'text' => 'text-blue-800',
            'border' => 'border-blue-300'
        ],
        'Consultation' => [
            'bg' => 'bg-orange-100',
            'text' => 'text-orange-800',
            'border' => 'border-orange-300'
        ],
        'Vaccination' => [
            'bg' => 'bg-green-100',
            'text' => 'text-green-800',
            'border' => 'border-green-300'
        ],
        'Follow-up' => [
            'bg' => 'bg-purple-100',
            'text' => 'text-purple-800',
            'border' => 'border-purple-300'
        ],
        'Emergency' => [
            'bg' => 'bg-red-100',
            'text' => 'text-red-800',
            'border' => 'border-red-300'
        ]
    ];
    
    
    return $badge_map[$visit_type] ?? [
        'bg' => 'bg-gray-100',
        'text' => 'text-gray-800',
        'border' => 'border-gray-300'
    ];
}

?>

<!
     PART 2: HTML / TAILWIND CSS FRONTEND MARKUP
     ============================================================================
     Place this code in the BODY of appointments.php where you want the
     Medical Visit History section to appear
     ============================================================================ 

<div class="medical-visit-history-container py-6 px-4 sm:px-6 lg:px-8">
    
    <!
         HEADER SECTION: Title and Icon
         ==================================================================== 
    <div class="mb-6">
        <div class="flex items-center gap-3">
            <i class="fa-solid fa-history text-2xl" style="color: #3F4E24;"></i>
            <h2 class="text-2xl font-bold text-gray-900">Medical Visit History</h2>
        </div>
    </div>
    
    <!
         ERROR STATE: Display error message if database query failed
         ==================================================================== 
    <?php if ($medical_history_error): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-center gap-3">
                <i class="fa-solid fa-exclamation-circle text-red-600"></i>
                <p class="text-sm text-red-700">
                    <?php echo htmlspecialchars($medical_history_error); ?>
                </p>
            </div>
        </div>
    <?php endif; ?>
    
    <!
         SUMMARY STATISTICS SECTION: Top 3 Cards
         ====================================================================
         This section displays three key metrics:
         1. Total Visits - Count of all medical visits for this user
         2. Latest Visit - Date of the most recent visit (formatted as "Jan 01, 2026")
         3. Status - Current status (hardcoded as "Active" in this version)
         
         Each card uses Tailwind CSS for styling with a clean, modern aesthetic
         aligned with the ScanCare dark olive-green (#3F4E24) branding
         ==================================================================== 
    
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        
        <!
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Total Visits</p>
                    <p class="text-3xl font-bold text-gray-900">
                        <?php echo $medical_visit_stats['total_visits']; ?>
                    </p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <i class="fa-solid fa-stethoscope text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <!
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Latest Visit</p>
                    <p class="text-lg font-bold text-gray-900">
                        <?php echo $medical_visit_stats['latest_visit_date']; ?>
                    </p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <i class="fa-solid fa-calendar-check text-green-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <!
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Status</p>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-semibold"
                              style="background-color: rgba(63, 78, 36, 0.1); color: #3F4E24;">
                            <i class="fa-solid fa-circle text-xs"></i>
                            <?php echo $medical_visit_stats['status']; ?>
                        </span>
                    </div>
                </div>
                <div class="bg-yellow-100 rounded-full p-3">
                    <i class="fa-solid fa-circle-check text-yellow-600 text-xl"></i>
                </div>
            </div>
        </div>
        
    </div>
    
    <!
         MAIN CONTENT SECTION: Accordion List of Medical Records
         ==================================================================== 
    
    <?php if (!$medical_history_error && empty($medical_history_records)): ?>
        
        <!
             EMPTY STATE: No medical records exist for this user
             ================================================================ 
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border-2 border-dashed border-blue-200 rounded-lg p-8 text-center">
            <div class="mb-4">
                <i class="fa-solid fa-inbox text-4xl text-blue-300"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">No Medical History Yet</h3>
            <p class="text-gray-600 max-w-sm mx-auto">
                No medical history recorded yet. Once you visit the Rural Health Unit (RHU), 
                your medical records will appear here.
            </p>
            <button class="mt-6 px-6 py-2 rounded-lg font-medium text-white transition-all"
                    style="background-color: #3F4E24;"
                    onmouseover="this.style.opacity='0.9'"
                    onmouseout="this.style.opacity='1'">
                <i class="fa-solid fa-calendar-plus mr-2"></i>Schedule an Appointment
            </button>
        </div>
        
    <?php elseif (!$medical_history_error && !empty($medical_history_records)): ?>
        
        <!
             POPULATED STATE: Display accordion list of medical records
             ================================================================
             For each medical history record:
             1. Create an accordion item with a header showing preview info
             2. Collapsed state shows: Date, Visit Type badge, Diagnosis, BHW name
             3. Expanded state reveals: Full treatment details and notes
             4. JavaScript handles smooth expand/collapse animation
             ================================================================ 
        
        <div class="space-y-3">
            
            <?php foreach ($medical_history_records as $index => $record): 
                
                
                $accordion_id = 'medical_record_' . htmlspecialchars($record['history_id']);
                
                
                $badge_colors = getMedicalVisitTypeBadgeClass($record['visit_type']);
                
                
                $formatted_visit_date = date('M d, Y', strtotime($record['visit_date']));
                
            ?>
                
                <!
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-all">
                    
                    <!
                         ACCORDION HEADER: Clickable Section (Always Visible)
                         ======================================================== 
                    <button type="button"
                            class="w-full text-left p-4 sm:p-6 flex items-center justify-between gap-4 hover:bg-gray-50 transition-colors"
                            onclick="toggleMedicalAccordion('<?php echo $accordion_id; ?>')">
                        
                        <!
                        <div class="flex-1 min-w-0">
                            
                            <!
                            <div class="flex items-center gap-2 mb-2 flex-wrap">
                                <span class="text-sm font-medium text-gray-600">
                                    <i class="fa-solid fa-calendar mr-1"></i><?php echo $formatted_visit_date; ?>
                                </span>
                                <span class="<?php echo $badge_colors['bg'] . ' ' . $badge_colors['text']; ?> text-xs font-semibold px-2 py-1 rounded">
                                    <?php echo htmlspecialchars($record['visit_type']); ?>
                                </span>
                            </div>
                            
                            <!
                            <p class="font-bold text-gray-900 text-base mb-2 truncate">
                                <?php echo htmlspecialchars($record['diagnosis']); ?>
                            </p>
                            
                            <!
                            <p class="text-sm text-gray-600">
                                <i class="fa-solid fa-user-nurse mr-1" style="color: #3F4E24;"></i>
                                <?php echo htmlspecialchars($record['bhw_name']); ?>
                            </p>
                            
                        </div>
                        
                        <!
                        <div class="flex-shrink-0">
                            <i class="fa-solid fa-chevron-down text-gray-400 transition-transform duration-300"
                               id="chevron_<?php echo $accordion_id; ?>"
                               style="font-size: 16px; transform: rotate(0deg);"></i>
                        </div>
                        
                    </button>
                    
                    <!
                         ACCORDION CONTENT: Hidden Section (Expands on Click)
                         ========================================================
                         This section contains detailed information revealed
                         when the accordion is expanded:
                         - Full treatment protocol
                         - Follow-up notes and remarks
                         - Record metadata (ID, creation timestamp)
                         ======================================================== 
                    <div id="<?php echo $accordion_id; ?>"
                         class="accordion-content max-h-0 overflow-hidden transition-all duration-300 ease-in-out">
                        
                        <div class="px-4 sm:px-6 py-4 border-t border-gray-200 bg-gray-50 space-y-4">
                            
                            <!
                            <?php if (!empty($record['treatment'])): ?>
                                <div>
                                    <label class="text-xs font-bold text-gray-700 uppercase tracking-widest block mb-2">
                                        <i class="fa-solid fa-prescription-bottle-medical mr-2" style="color: #3F4E24;"></i>Treatment
                                    </label>
                                    <p class="text-sm text-gray-700 leading-relaxed">
                                        <?php echo nl2br(htmlspecialchars($record['treatment'])); ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                            
                            <!
                            <?php if (!empty($record['notes'])): ?>
                                <div>
                                    <label class="text-xs font-bold text-gray-700 uppercase tracking-widest block mb-2">
                                        <i class="fa-solid fa-clipboard-list mr-2" style="color: #3F4E24;"></i>Follow-up Notes
                                    </label>
                                    <p class="text-sm text-gray-700 leading-relaxed italic">
                                        "<?php echo nl2br(htmlspecialchars($record['notes'])); ?>"
                                    </p>
                                </div>
                            <?php endif; ?>
                            
                            <!
                            <div class="pt-3 border-t border-gray-300 flex justify-between text-xs text-gray-500">
                                <span>Record ID: <?php echo htmlspecialchars($record['history_id']); ?></span>
                                <?php if (!empty($record['created_at'])): ?>
                                    <span><?php echo date('M d, Y \a\t g:i A', strtotime($record['created_at'])); ?></span>
                                <?php endif; ?>
                            </div>
                            
                        </div>
                    </div>
                    
                </div>
                
            <?php endforeach; ?>
            
        </div>
        
    <?php endif; ?>
    
</div>

<!
     JAVASCRIPT: Accordion Toggle Functionality
     ============================================================================
     This script handles the smooth expand/collapse animation of the accordion
     items. When a user clicks on a medical record card header, this function:
     1. Calculates the content height dynamically
     2. Animates the expansion/collapse using CSS transitions
     3. Rotates the chevron icon to indicate open/closed state
     ============================================================================ 

<script>
    
    function toggleMedicalAccordion(recordId) {
        
        const contentDiv = document.getElementById(recordId);
        const chevronIcon = document.getElementById('chevron_' + recordId);
        
        
        if (!contentDiv) return;
        
        
        
        const isCurrentlyExpanded = contentDiv.style.maxHeight && 
                                    contentDiv.style.maxHeight !== '0px';
        
        if (isCurrentlyExpanded) {
            
            
            
            contentDiv.style.maxHeight = '0px';
            
            
            if (chevronIcon) {
                chevronIcon.style.transform = 'rotate(0deg)';
            }
            
        } else {
            
            
            
            
            
            contentDiv.style.maxHeight = contentDiv.scrollHeight + 'px';
            
            
            if (chevronIcon) {
                chevronIcon.style.transform = 'rotate(180deg)';
            }
        }
    }
    
    
    function expandAllMedicalRecords() {
        const allAccordions = document.querySelectorAll('[id^="medical_record_"]');
        
        allAccordions.forEach(accordion => {
            accordion.style.maxHeight = accordion.scrollHeight + 'px';
            
            
            const chevron = document.getElementById('chevron_' + accordion.id);
            if (chevron) {
                chevron.style.transform = 'rotate(180deg)';
            }
        });
    }
    
    
    function collapseAllMedicalRecords() {
        const allAccordions = document.querySelectorAll('[id^="medical_record_"]');
        
        allAccordions.forEach(accordion => {
            accordion.style.maxHeight = '0px';
            
            
            const chevron = document.getElementById('chevron_' + accordion.id);
            if (chevron) {
                chevron.style.transform = 'rotate(0deg)';
            }
        });
    }
</script>

<!
     END OF MEDICAL VISIT HISTORY COMPONENT
     ============================================================================ 

