<?php


require_once 'db_config.php';


set_time_limit(300); 


$migration_log = [];
$updated_count = 0;
$error_count = 0;
$mapping = []; 

try {
    $pdo = getDatabaseConnection();
    
    if ($pdo === false) {
        die('Database connection failed.');
    }
    
    
    
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $migration_log[] = "[STEP 1] Foreign key checks disabled";
    
    
    
    
    $stmt = $pdo->prepare("
        SELECT resident_id, full_name, email, created_at 
        FROM RESIDENT 
        WHERE resident_id < 100000
        ORDER BY resident_id ASC
    ");
    $stmt->execute();
    $existing_residents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $resident_count = count($existing_residents);
    $migration_log[] = "[STEP 2] Found {$resident_count} existing residents to migrate";
    
    if ($resident_count === 0) {
        $migration_log[] = "[INFO] No existing residents found. Migration skipped.";
        echo formatOutput($migration_log, 0, 0);
        exit;
    }
    
    
    
    
    $migration_log[] = "[STEP 3] Beginning ID generation and update process...";
    
    foreach ($existing_residents as $resident) {
        $old_id = $resident['resident_id'];
        $full_name = $resident['full_name'];
        $email = $resident['email'];
        
        try {
            
            $unique_new_id = null;
            $max_attempts = 50;
            $attempt = 0;
            
            while ($unique_new_id === null && $attempt < $max_attempts) {
                $candidate_id = mt_rand(100000, 999999);
                
                
                $check_stmt = $pdo->prepare("SELECT resident_id FROM RESIDENT WHERE resident_id = ?");
                $check_stmt->execute([$candidate_id]);
                
                
                if ($check_stmt->rowCount() === 0 && !in_array($candidate_id, $mapping)) {
                    $unique_new_id = $candidate_id;
                }
                
                $attempt++;
            }
            
            if ($unique_new_id === null) {
                throw new Exception("Unable to generate unique ID after {$max_attempts} attempts");
            }
            
            
            
            
            $update_resident = $pdo->prepare("
                UPDATE RESIDENT 
                SET resident_id = ? 
                WHERE resident_id = ?
            ");
            $update_resident->execute([$unique_new_id, $old_id]);
            
            
            $update_appointments = $pdo->prepare("
                UPDATE appointment 
                SET resident_id = ? 
                WHERE resident_id = ?
            ");
            $update_appointments->execute([$unique_new_id, $old_id]);
            
            
            try {
                $update_medical = $pdo->prepare("
                    UPDATE medical_history 
                    SET resident_id = ? 
                    WHERE resident_id = ?
                ");
                $update_medical->execute([$unique_new_id, $old_id]);
            } catch (Exception $e) {
                
            }
            
            
            $mapping[$old_id] = $unique_new_id;
            $updated_count++;
            
            $migration_log[] = "[UPDATED] ID {$old_id} → {$unique_new_id} | {$full_name} | {$email}";
            
        } catch (Exception $e) {
            $error_count++;
            $migration_log[] = "[ERROR] ID {$old_id} - " . $e->getMessage();
        }
    }
    
    
    
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    $migration_log[] = "[STEP 4] Foreign key checks re-enabled";
    
    
    
    
    $verify_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM RESIDENT WHERE resident_id >= 100000");
    $verify_stmt->execute();
    $verify_result = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    $six_digit_count = $verify_result['count'];
    
    $migration_log[] = "[VERIFICATION] Total residents with 6-digit IDs: {$six_digit_count}";
    
    
    
    
    $migration_log[] = "[COMPLETE] Migration finished. Updated: {$updated_count}, Errors: {$error_count}";
    
    
    echo formatOutput($migration_log, $updated_count, $error_count);
    
} catch (Exception $e) {
    $migration_log[] = "[FATAL ERROR] " . $e->getMessage();
    echo formatOutput($migration_log, 0, 1);
    exit;
}


function formatOutput($log_entries, $updated, $errors) {
    $html = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Batch Migration Report: 6-Digit Patient IDs</title>
    <link href='https:
    <style>
        .log-entry { padding: 8px; font-family: 'Courier New', monospace; font-size: 13px; line-height: 1.4; }
        .log-success { color: #059669; background: #f0fdf4; }
        .log-error { color: #dc2626; background: #fef2f2; }
        .log-info { color: #2563eb; background: #eff6ff; }
        .log-updated { color: #7c3aed; background: #faf5ff; }
        .log-complete { color: #16a34a; background: #f0fdf4; font-weight: bold; }
        .log-fatal { color: #991b1b; background: #fef2f2; font-weight: bold; }
    </style>
</head>
<body class='bg-gradient-to-br from-slate-50 to-slate-100 min-h-screen p-4'>
    <div class='max-w-4xl mx-auto'>
        <div class='bg-white rounded-lg shadow-lg p-6 mb-6'>
            <h1 class='text-3xl font-bold text-slate-800 mb-2'>🔄 Batch Migration Report</h1>
            <p class='text-slate-600 mb-4'>Updating all existing accounts to unique 6-digit patient IDs</p>
            
            <div class='grid grid-cols-3 gap-4 mb-6'>
                <div class='bg-green-50 border-2 border-green-200 rounded p-4'>
                    <p class='text-sm text-slate-600'>Updated Accounts</p>
                    <p class='text-3xl font-bold text-green-600'>" . $updated . "</p>
                </div>
                <div class='bg-red-50 border-2 border-red-200 rounded p-4'>
                    <p class='text-sm text-slate-600'>Errors</p>
                    <p class='text-3xl font-bold text-red-600'>" . $errors . "</p>
                </div>
                <div class='bg-blue-50 border-2 border-blue-200 rounded p-4'>
                    <p class='text-sm text-slate-600'>Status</p>
                    <p class='text-2xl font-bold text-blue-600'>" . ($errors === 0 ? "✅ SUCCESS" : "⚠️ REVIEW") . "</p>
                </div>
            </div>
        </div>
        
        <div class='bg-white rounded-lg shadow-lg p-6'>
            <h2 class='text-xl font-bold text-slate-800 mb-4'>📋 Migration Log</h2>
            <div class='border border-slate-200 rounded bg-slate-50 p-4 max-h-96 overflow-y-auto'>
";

    foreach ($log_entries as $entry) {
        $class = 'log-info';
        
        if (strpos($entry, '[UPDATED]') !== false) {
            $class = 'log-updated';
        } elseif (strpos($entry, '[ERROR]') !== false) {
            $class = 'log-error';
        } elseif (strpos($entry, '[COMPLETE]') !== false) {
            $class = 'log-complete';
        } elseif (strpos($entry, '[FATAL') !== false) {
            $class = 'log-fatal';
        } elseif (strpos($entry, '[STEP') !== false) {
            $class = 'log-info';
        } elseif (strpos($entry, '[VERIFICATION]') !== false) {
            $class = 'log-success';
        }
        
        $html .= "<div class='log-entry {$class}'>" . htmlspecialchars($entry) . "</div>\n";
    }

    $html .= "
            </div>
        </div>
        
        <div class='bg-amber-50 border-2 border-amber-200 rounded-lg p-4 mt-6'>
            <p class='text-sm text-slate-700'>
                <strong>⚠️ IMPORTANT:</strong> After verifying success above, delete this migration script (UPDATE_EXISTING_ACCOUNTS_TO_6DIGIT_IDS.php) 
                for security. Existing residents with IDs < 100000 are now converted to 6-digit IDs. New registrations will use the 6-digit system.
            </p>
        </div>
    </div>
</body>
</html>";
    
    return $html;
}
?>

