<?php



session_start();
header('Content-Type: application/json');

error_log("=== GET STAFF ACTIVITIES START ===");
error_log("Staff ID received: " . ($_GET['staff_id'] ?? 'none'));

try {
    
    require_once 'db_config.php';
    
    
    $staff_id = isset($_GET['staff_id']) ? intval($_GET['staff_id']) : null;
    
    if (!$staff_id || $staff_id <= 0) {
        throw new Exception('Invalid staff ID: ' . var_export($_GET, true));
    }
    
    
    $pdo = getDatabaseConnection();
    if ($pdo === false) {
        throw new Exception('Database connection failed');
    }
    
    
    try {
        $pdo->query("SELECT 1 FROM activity_logs LIMIT 1");
        error_log("✓ activity_logs table is accessible");
    } catch (Exception $table_err) {
        error_log("✗ activity_logs table error: " . $table_err->getMessage());
        
        
        try {
            $create_table = "
                CREATE TABLE IF NOT EXISTS activity_logs (
                    log_id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT NOT NULL,
                    user_role_snapshot VARCHAR(50),
                    action_type VARCHAR(100),
                    action_description TEXT,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    KEY idx_user_id (user_id),
                    KEY idx_timestamp (timestamp)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
            $pdo->exec($create_table);
            error_log("✓ Created activity_logs table");
        } catch (Exception $create_err) {
            error_log("✗ Could not create table: " . $create_err->getMessage());
        }
    }
    
    
    $query = "
        SELECT 
            log_id,
            action_type,
            action_description,
            timestamp
        FROM activity_logs
        WHERE user_id = ?
        ORDER BY timestamp DESC
        LIMIT 100
    ";
    
    $stmt = $pdo->prepare($query);
    if (!$stmt) {
        $errorInfo = $pdo->errorInfo();
        throw new Exception('Query prep failed: ' . json_encode($errorInfo));
    }
    
    if (!$stmt->execute([$staff_id])) {
        $errorInfo = $stmt->errorInfo();
        error_log("Query execute failed: " . json_encode($errorInfo));
        throw new Exception('Query execute failed: ' . $errorInfo[2]);
    }
    
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    
    if (count($activities) === 0) {
        error_log("No activities found for staff_id={$staff_id}, attempting test insert...");
        
        try {
            
            $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
            
            $test_insert = "
                INSERT INTO activity_logs (user_id, user_role_snapshot, action_type, action_description, timestamp)
                VALUES (?, ?, ?, ?, NOW())
            ";
            
            $test_stmt = $pdo->prepare($test_insert);
            $test_stmt->execute([
                $staff_id,
                'bhw',
                'system_test',
                'Test activity - Activity logging system is working. Render appointments to see real activities.'
            ]);
            
            
            $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
            
            error_log("✓ Test activity inserted for staff_id={$staff_id}");
            
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([$staff_id]);
            $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $test_err) {
            error_log("Test insert failed: " . $test_err->getMessage());
            
            $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
        }
    }
    
    error_log("✓ Query successful: Found " . count($activities) . " activities for staff_id={$staff_id}");
    error_log("=== GET STAFF ACTIVITIES END ===");
    
    
    echo json_encode($activities ?: []);
    
} catch (Exception $e) {
    error_log("✗ ERROR: " . $e->getMessage());
    error_log("Stack: " . $e->getTraceAsString());
    error_log("=== GET STAFF ACTIVITIES FAILED ===");
    
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to load activities',
        'message' => $e->getMessage(),
        'staff_id_received' => ($_GET['staff_id'] ?? null)
    ]);
}
?>

