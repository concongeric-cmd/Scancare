<?php


require_once 'db_config.php';

$diagnostics = [];

try {
    $pdo = getDatabaseConnection();
    
    if ($pdo === false) {
        die('Database connection failed.');
    }
    
    
    
    
    $stmt = $pdo->query("SHOW CREATE TABLE resident");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $create_table = $result['Create Table'];
    
    $has_auto_increment = strpos($create_table, 'AUTO_INCREMENT') !== false;
    $diagnostics['SQL_Migration_Needed'] = $has_auto_increment;
    
    if ($has_auto_increment) {
        $diagnostics['issue_1'] = '❌ AUTO_INCREMENT still exists on resident table';
        $diagnostics['fix_1'] = 'Run SQL migration: MIGRATION_REMOVE_AUTO_INCREMENT.sql';
    } else {
        $diagnostics['issue_1'] = '✅ AUTO_INCREMENT removed successfully';
    }
    
    
    
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM RESIDENT WHERE resident_id < 100000");
    $stmt->execute();
    $old_ids = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($old_ids > 0) {
        $diagnostics['issue_2'] = "❌ {$old_ids} residents still have old auto-increment IDs (< 100000)";
        $diagnostics['fix_2'] = 'Run batch migration: UPDATE_EXISTING_ACCOUNTS_TO_6DIGIT_IDS.php';
        
        
        $stmt = $pdo->prepare("SELECT resident_id, full_name, email FROM RESIDENT WHERE resident_id < 100000 ORDER BY resident_id");
        $stmt->execute();
        $old_residents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $diagnostics['old_residents'] = $old_residents;
    } else {
        $diagnostics['issue_2'] = '✅ No old auto-increment IDs found';
    }
    
    
    
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM RESIDENT WHERE resident_id >= 100000");
    $stmt->execute();
    $six_digit_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($six_digit_count > 0) {
        $diagnostics['issue_3'] = "✅ {$six_digit_count} residents have 6-digit IDs";
    } else {
        $diagnostics['issue_3'] = "❌ No 6-digit IDs found";
    }
    
    
    
    
    $stmt = $pdo->prepare("SELECT resident_id, COUNT(*) as cnt FROM RESIDENT GROUP BY resident_id HAVING COUNT(*) > 1");
    $stmt->execute();
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($duplicates) > 0) {
        $diagnostics['issue_4'] = "❌ Duplicate IDs found: " . json_encode($duplicates);
    } else {
        $diagnostics['issue_4'] = '✅ No duplicate IDs';
    }
    
    
    
    
    $stmt = $pdo->prepare("SELECT * FROM RESIDENT WHERE LOWER(full_name) LIKE '%francis%' OR LOWER(email) LIKE '%francis%'");
    $stmt->execute();
    $francis_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($francis_records) > 0) {
        $diagnostics['francis_found'] = true;
        $diagnostics['francis_data'] = $francis_records;
    } else {
        $diagnostics['francis_found'] = false;
        $diagnostics['francis_data'] = 'No accounts matching "Francis" found';
    }
    
    
    
    
    $stmt = $pdo->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS WHERE TABLE_NAME='appointment'");
    $fk_check = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $diagnostics['foreign_keys'] = count($fk_check) > 0 ? "✅ Foreign keys intact" : "⚠️ No foreign keys found";

    
    
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM RESIDENT");
    $stmt->execute();
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $diagnostics['total_residents'] = $total;
    
} catch (Exception $e) {
    $diagnostics['error'] = $e->getMessage();
}




?>
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>6-Digit ID Migration Diagnostic Report</title>
    <link href='https:
    <style>
        .status-box { padding: 12px; border-radius: 6px; margin-bottom: 8px; font-family: 'Courier New', monospace; }
        .status-success { background: #dcfce7; border-left: 4px solid #16a34a; color: #15803d; }
        .status-error { background: #fee2e2; border-left: 4px solid #dc2626; color: #991b1b; }
        .status-warning { background: #fef3c7; border-left: 4px solid #f59e0b; color: #92400e; }
    </style>
</head>
<body class='bg-slate-100 p-6'>
    <div class='max-w-4xl mx-auto'>
        <div class='bg-white rounded-lg shadow-lg p-6 mb-6'>
            <h1 class='text-3xl font-bold text-slate-800 mb-2'>🔍 6-Digit ID Migration Diagnostic</h1>
            <p class='text-slate-600'>Database status analysis and next steps</p>
        </div>
        
        <div class='bg-white rounded-lg shadow-lg p-6 mb-6'>
            <h2 class='text-xl font-bold text-slate-800 mb-4'>📊 Database Status</h2>
            
            <!
            <div class='mb-6'>
                <label class='block text-sm font-semibold text-slate-700 mb-2'>Total Residents:</label>
                <div class='text-2xl font-bold text-blue-600'><?php echo $diagnostics['total_residents']; ?> accounts</div>
            </div>
            
            <!
            <div class='mb-4'>
                <label class='block text-sm font-semibold text-slate-700 mb-2'>1. SQL Migration Status:</label>
                <div class='status-box <?php echo $diagnostics['SQL_Migration_Needed'] ? 'status-error' : 'status-success'; ?>'>
                    <?php echo $diagnostics['issue_1']; ?>
                </div>
                <?php if ($diagnostics['SQL_Migration_Needed']): ?>
                    <div class='mt-2 p-3 bg-amber-50 border border-amber-200 rounded text-sm text-amber-900'>
                        <strong>Action Required:</strong> <?php echo $diagnostics['fix_1']; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!
            <div class='mb-4'>
                <label class='block text-sm font-semibold text-slate-700 mb-2'>2. Old Account IDs (< 100000):</label>
                <div class='status-box <?php echo ($old_ids > 0) ? 'status-error' : 'status-success'; ?>'>
                    <?php echo $diagnostics['issue_2']; ?>
                </div>
                <?php if ($old_ids > 0): ?>
                    <div class='mt-2 p-3 bg-amber-50 border border-amber-200 rounded text-sm text-amber-900'>
                        <strong>Action Required:</strong> <?php echo $diagnostics['fix_2']; ?>
                        <br><br>
                        <strong>Residents to update:</strong>
                        <table class='w-full mt-2 text-xs'>
                            <thead>
                                <tr class='border-b'>
                                    <th class='text-left'>ID</th>
                                    <th class='text-left'>Name</th>
                                    <th class='text-left'>Email</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($diagnostics['old_residents'] as $resident): ?>
                                <tr class='border-b'>
                                    <td><?php echo $resident['resident_id']; ?></td>
                                    <td><?php echo htmlspecialchars($resident['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($resident['email']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!
            <div class='mb-4'>
                <label class='block text-sm font-semibold text-slate-700 mb-2'>3. New 6-Digit Accounts (>= 100000):</label>
                <div class='status-box <?php echo ($six_digit_count > 0) ? 'status-success' : 'status-error'; ?>'>
                    <?php echo $diagnostics['issue_3']; ?>
                </div>
            </div>
            
            <!
            <div class='mb-4'>
                <label class='block text-sm font-semibold text-slate-700 mb-2'>4. Duplicate Check:</label>
                <div class='status-box <?php echo (count($duplicates) === 0) ? 'status-success' : 'status-error'; ?>'>
                    <?php echo $diagnostics['issue_4']; ?>
                </div>
            </div>
            
            <!
            <div class='mb-4'>
                <label class='block text-sm font-semibold text-slate-700 mb-2'>5. Francis Account Status:</label>
                <?php if ($diagnostics['francis_found']): ?>
                    <div class='status-box status-error'>
                        ❌ Found <?php echo count($diagnostics['francis_data']); ?> account(s) matching "Francis"
                    </div>
                    <table class='w-full text-sm mt-2 border border-slate-200 rounded'>
                        <thead class='bg-slate-100'>
                            <tr>
                                <th class='p-2 text-left'>resident_id</th>
                                <th class='p-2 text-left'>full_name</th>
                                <th class='p-2 text-left'>email</th>
                                <th class='p-2 text-left'>created_at</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($diagnostics['francis_data'] as $francis): ?>
                            <tr class='border-t'>
                                <td class='p-2 font-mono font-bold text-red-600'><?php echo $francis['resident_id']; ?></td>
                                <td class='p-2'><?php echo htmlspecialchars($francis['full_name']); ?></td>
                                <td class='p-2'><?php echo htmlspecialchars($francis['email'] ?? 'N/A'); ?></td>
                                <td class='p-2 text-xs text-slate-600'><?php echo $francis['created_at'] ?? 'N/A'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class='mt-3 p-3 bg-red-50 border border-red-200 rounded text-sm text-red-900'>
                        <strong>⚠️ Francis still has old ID:</strong> Batch migration script needs to be executed.
                    </div>
                <?php else: ?>
                    <div class='status-box status-success'>
                        ✅ No "Francis" accounts found in database
                    </div>
                <?php endif; ?>
            </div>
            
            <!
            <div class='mb-4'>
                <label class='block text-sm font-semibold text-slate-700 mb-2'>6. Foreign Key Constraints:</label>
                <div class='status-box <?php echo (strpos($diagnostics['foreign_keys'], '✅') !== false) ? 'status-success' : 'status-warning'; ?>'>
                    <?php echo $diagnostics['foreign_keys']; ?>
                </div>
            </div>
        </div>
        
        <!
        <div class='bg-white rounded-lg shadow-lg p-6 mb-6'>
            <h2 class='text-xl font-bold text-slate-800 mb-4'>📋 What to Do Next</h2>
            
            <?php if ($diagnostics['SQL_Migration_Needed']): ?>
            <div class='mb-4 p-4 bg-red-50 border-l-4 border-red-500 rounded'>
                <h3 class='font-bold text-red-900 mb-2'>⚠️ STEP 1: Run SQL Migration (REQUIRED FIRST)</h3>
                <p class='text-sm text-red-800 mb-2'>Execute in phpMyAdmin:</p>
                <div class='bg-white p-2 rounded font-mono text-xs mb-2 overflow-x-auto border border-red-200'>
SET FOREIGN_KEY_CHECKS = 0;<br>
ALTER TABLE `resident` MODIFY COLUMN `resident_id` INT NOT NULL;<br>
SET FOREIGN_KEY_CHECKS = 1;
                </div>
                <p class='text-xs text-red-700'><strong>File:</strong> MIGRATION_REMOVE_AUTO_INCREMENT.sql</p>
            </div>
            <?php endif; ?>
            
            <?php if ($old_ids > 0): ?>
            <div class='mb-4 p-4 bg-orange-50 border-l-4 border-orange-500 rounded'>
                <h3 class='font-bold text-orange-900 mb-2'>⚠️ STEP 2: Run Batch Migration Script</h3>
                <p class='text-sm text-orange-800 mb-2'>Open in browser:</p>
                <div class='bg-white p-2 rounded font-mono text-xs mb-2 border border-orange-200'>
http:
                </div>
                <p class='text-xs text-orange-700'><strong>This will convert <?php echo $old_ids; ?> accounts to 6-digit IDs</strong></p>
                <p class='text-xs text-orange-700 mt-2'><strong>File:</strong> UPDATE_EXISTING_ACCOUNTS_TO_6DIGIT_IDS.php</p>
            </div>
            <?php endif; ?>
            
            <?php if ($old_ids === 0 && !$diagnostics['SQL_Migration_Needed']): ?>
            <div class='p-4 bg-green-50 border-l-4 border-green-500 rounded'>
                <h3 class='font-bold text-green-900 mb-2'>✅ All migrations complete!</h3>
                <p class='text-sm text-green-800'>The system is ready. New registrations will use 6-digit IDs automatically.</p>
            </div>
            <?php endif; ?>
        </div>
        
        <div class='bg-blue-50 border border-blue-200 rounded-lg p-4'>
            <p class='text-sm text-blue-900'>
                <strong>💡 Tip:</strong> After completing all steps, refresh this page to verify the status has updated.
            </p>
        </div>
    </div>
</body>
</html>

