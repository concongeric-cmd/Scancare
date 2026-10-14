<?php



session_start();
header('Content-Type: application/json');

error_log("=== GET RESIDENT ACTIVITIES START ===");
error_log("Resident ID received: " . ($_GET['resident_id'] ?? 'none'));

try {
    
    require_once 'db_config.php';
    
    
    $resident_id = isset($_GET['resident_id']) ? intval($_GET['resident_id']) : null;
    
    if (!$resident_id || $resident_id <= 0) {
        throw new Exception('Invalid resident ID: ' . var_export($_GET, true));
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
    
    
    
    $activities = [];
    
    error_log("DEBUG: Querying appointments for resident_id={$resident_id}");
    
    try {
        $appointment_query = "
            SELECT 
                appointment_id,
                'scheduled_appointment' as action_type,
                DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as action_timestamp,
                appointment_date,
                appointment_time,
                purpose,
                status,
                '' as action_description
            FROM APPOINTMENT
            WHERE resident_id = ?
            ORDER BY created_at DESC, appointment_date DESC, appointment_time DESC
            LIMIT 50
        ";
        
        $appt_stmt = $pdo->prepare($appointment_query);
        if ($appt_stmt) {
            if ($appt_stmt->execute([$resident_id])) {
                $appointments = $appt_stmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("DEBUG: Query returned " . count($appointments) . " appointments");
                
                if (count($appointments) > 0) {
                    error_log("✓ Found " . count($appointments) . " appointments for resident_id={$resident_id}");
                    error_log("DEBUG: First appointment: " . json_encode($appointments[0]));
                    $activities = $appointments;
                } else {
                    error_log("No appointments found for resident_id={$resident_id}");
                    
                    $check_stmt = $pdo->query("SELECT DISTINCT resident_id FROM APPOINTMENT LIMIT 5");
                    $resident_ids = $check_stmt->fetchAll(PDO::FETCH_COLUMN);
                    error_log("DEBUG: Resident IDs in APPOINTMENT table: " . json_encode($resident_ids));
                }
            } else {
                error_log("Execute failed: " . json_encode($appt_stmt->errorInfo()));
            }
        } else {
            error_log("Prepare failed: " . json_encode($pdo->errorInfo()));
        }
    } catch (Exception $appt_err) {
        error_log("Appointment history lookup failed: " . $appt_err->getMessage());
    }
    
    
    if (count($activities) === 0) {
        error_log("No appointments found, trying activity_logs table for resident_id={$resident_id}");
        
        try {
            $activity_query = "
                SELECT 
                    log_id,
                    action_type,
                    action_description,
                    timestamp as action_timestamp
                FROM activity_logs
                WHERE user_id = ?
                ORDER BY timestamp DESC
                LIMIT 100
            ";
            
            $log_stmt = $pdo->prepare($activity_query);
            if ($log_stmt && $log_stmt->execute([$resident_id])) {
                $activities = $log_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($activities) > 0) {
                    error_log("✓ Found " . count($activities) . " activity log entries for resident_id={$resident_id}");
                } else {
                    error_log("No activity logs found for resident_id={$resident_id}");
                }
            }
        } catch (Exception $log_err) {
            error_log("Activity logs lookup failed: " . $log_err->getMessage());
        }
    }
    
    
    if (count($activities) === 0) {
        error_log("No activities found for resident_id={$resident_id}, attempting test insert...");
        
        try {
            
            $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
            
            $test_insert = "
                INSERT INTO activity_logs (user_id, user_role_snapshot, action_type, action_description, timestamp)
                VALUES (?, ?, ?, ?, NOW())
            ";
            
            $test_stmt = $pdo->prepare($test_insert);
            $test_stmt->execute([
                $resident_id,
                'resident',
                'system_info',
                'Resident registered. Activities will appear here when appointments are scheduled or updated.'
            ]);
            
            
            $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
            
            error_log("✓ Test activity inserted for resident_id={$resident_id}");
            
            
            $log_query = "
                SELECT 
                    log_id,
                    action_type,
                    action_description,
                    timestamp as action_timestamp
                FROM activity_logs
                WHERE user_id = ?
                ORDER BY timestamp DESC
                LIMIT 100
            ";
            $stmt = $pdo->prepare($log_query);
            $stmt->execute([$resident_id]);
            $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $test_err) {
            error_log("Test insert failed: " . $test_err->getMessage());
            
            $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
        }
    }
    
    error_log("✓ Query successful: Found " . count($activities) . " activities for resident_id={$resident_id}");
    error_log("=== GET RESIDENT ACTIVITIES END ===");
    
    
    echo json_encode($activities ?: []);
    
} catch (Exception $e) {
    error_log("✗ ERROR: " . $e->getMessage());
    error_log("Stack: " . $e->getTraceAsString());
    error_log("=== GET RESIDENT ACTIVITIES FAILED ===");
    
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to load resident activities',
        'message' => $e->getMessage(),
        'resident_id_received' => ($_GET['resident_id'] ?? null)
    ]);
}
?>

