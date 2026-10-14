<?php


require_once 'db_config.php';


session_start();
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['worker_id'])) {
    die('❌ Unauthorized access. Admin or BHW login required.');
}

echo "<h2>📋 Appointment History Recovery Tool</h2>";
echo "<p>This tool helps restore historical appointment records.</p>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'check_backups') {
        try {
            $pdo = getDatabaseConnection();
            
            
            $check_backup = $pdo->prepare("
                SELECT TABLE_NAME 
                FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME IN ('appointment_backup', 'appointment_old', 'appointment_archive')
            ");
            $check_backup->execute();
            $backup_tables = $check_backup->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($backup_tables)) {
                echo "<div style='background-color: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
                echo "<h3>✅ Backup Tables Found:</h3>";
                echo "<ul>";
                foreach ($backup_tables as $table) {
                    $count_stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM {$table}");
                    $count_stmt->execute();
                    $count = $count_stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;
                    echo "<li><strong>{$table}</strong> - {$count} records</li>";
                }
                echo "</ul>";
                echo "<form method='POST' style='margin-top: 10px;'>";
                echo "<input type='hidden' name='action' value='restore_from_backup'>";
                echo "<select name='backup_table' required>";
                echo "<option value=''>
                foreach ($backup_tables as $table) {
                    echo "<option value='{$table}'>{$table}</option>";
                }
                echo "</select>";
                echo "<button type='submit' style='padding: 8px 15px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;'>🔄 Restore from Backup</button>";
                echo "</form>";
                echo "</div>";
            } else {
                echo "<div style='background-color: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
                echo "<h3>⚠️ No Backup Tables Found</h3>";
                echo "<p>No backup tables detected in the database.</p>";
                echo "<p><strong>Options:</strong></p>";
                echo "<ul>";
                echo "<li>Check if database backups exist on the server</li>";
                echo "<li>Contact system administrator for backup restoration</li>";
                echo "<li>Review database transaction logs if available</li>";
                echo "</ul>";
                echo "</div>";
            }
        } catch (Exception $e) {
            echo "<div style='background-color: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h3>❌ Error:</h3>";
            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
    }
    
    if ($action === 'restore_from_backup') {
        $backup_table = $_POST['backup_table'] ?? '';
        
        if (empty($backup_table)) {
            echo "<div style='background-color: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h3>❌ Error:</h3>";
            echo "<p>Please select a backup table.</p>";
            echo "</div>";
        } else {
            try {
                $pdo = getDatabaseConnection();
                
                
                $restore_sql = "
                    INSERT INTO appointment 
                    (appointment_id, resident_id, health_worker_id, appointment_date, appointment_time, purpose, status, created_at, updated_at)
                    SELECT appointment_id, resident_id, health_worker_id, appointment_date, appointment_time, purpose, status, created_at, updated_at
                    FROM {$backup_table}
                    WHERE status = 'completed'
                    ON DUPLICATE KEY UPDATE status = VALUES(status)
                ";
                
                $pdo->exec($restore_sql);
                
                echo "<div style='background-color: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
                echo "<h3>✅ Restoration Complete</h3>";
                echo "<p>Completed appointments have been restored from backup.</p>";
                echo "<p><a href='dashboard_bhw.php?module=patient-profile' style='color: #0c5460;'>← Back to Dashboard</a></p>";
                echo "</div>";
                
                error_log("✓ Appointment history restored from {$backup_table}");
            } catch (Exception $e) {
                echo "<div style='background-color: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
                echo "<h3>❌ Restoration Error:</h3>";
                echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
                echo "</div>";
                error_log("ERROR restoring appointments: " . $e->getMessage());
            }
        }
    }
} else {
    
    echo "<form method='POST'>";
    echo "<input type='hidden' name='action' value='check_backups'>";
    echo "<button type='submit' style='padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;'>🔍 Check for Backup Tables</button>";
    echo "</form>";
}
?>
<hr>
<p style='font-size: 12px; color: #666;'>
    <strong>Note:</strong> This tool only restores 'completed' appointments to preserve historical records.
    Future appointments with 'pending' or 'scheduled' status are not restored.
</p>

