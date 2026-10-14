<?php


require_once 'db_config.php';

echo "<div style='font-family: Arial; padding: 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; min-height: 100vh;'>";
echo "<div style='max-width: 1200px; margin: 0 auto;'>";

echo "<h1 style='font-size: 32px; margin-bottom: 30px;'>🔧 Appointment Scheduling Debug</h1>";

try {
    $pdo = getDatabaseConnection();
    
    if ($pdo === false) {
        throw new Exception("Database connection failed!");
    }
    
    
    
    
    echo "<div style='background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; color: #333;'>";
    echo "<h2 style='color: #667eea; margin-top: 0;'>✅ Check 1: Appointments Table Existence</h2>";
    
    try {
        $check_table = $pdo->query("SHOW TABLES LIKE 'appointments'");
        if ($check_table->rowCount() > 0) {
            echo "<p style='color: #4caf50; font-weight: bold;'>✅ appointments table EXISTS</p>";
        } else {
            echo "<p style='color: #f44336; font-weight: bold;'>❌ appointments table DOES NOT EXIST</p>";
            echo "<p style='margin-top: 10px;'>This is the problem! The table needs to be created.</p>";
            echo "<p><strong>SQL to create table:</strong></p>";
            echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto;'>" . htmlspecialchars("CREATE TABLE appointments (
    appointment_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    reason VARCHAR(255),
    status ENUM('scheduled', 'pending', 'completed') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;") . "</pre>";
        }
    } catch (Exception $e) {
        echo "<p style='color: #f44336;'>Error checking table: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    echo "</div>";
    
    
    
    
    echo "<div style='background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; color: #333;'>";
    echo "<h2 style='color: #667eea; margin-top: 0;'>✅ Check 2: Appointments Table Structure</h2>";
    
    try {
        $result = $pdo->query("DESCRIBE appointments");
        if ($result && $result->rowCount() > 0) {
            $columns = $result->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table style='width: 100%; border-collapse: collapse; margin-top: 15px;'>";
            echo "<tr style='background: #667eea; color: white;'>";
            echo "<th style='padding: 12px; text-align: left; border: 1px solid #ddd;'>Field</th>";
            echo "<th style='padding: 12px; text-align: left; border: 1px solid #ddd;'>Type</th>";
            echo "<th style='padding: 12px; text-align: left; border: 1px solid #ddd;'>Null</th>";
            echo "<th style='padding: 12px; text-align: left; border: 1px solid #ddd;'>Key</th>";
            echo "<th style='padding: 12px; text-align: left; border: 1px solid #ddd;'>Default</th>";
            echo "<th style='padding: 12px; text-align: left; border: 1px solid #ddd;'>Extra</th>";
            echo "</tr>";
            
            $required_fields = ['appointment_id', 'user_id', 'appointment_date', 'appointment_time', 'reason', 'status'];
            $found_fields = [];
            
            foreach ($columns as $col) {
                $field_name = $col['Field'];
                $found_fields[] = $field_name;
                
                echo "<tr style='border: 1px solid #ddd;'>";
                echo "<td style='padding: 12px;'><strong>" . htmlspecialchars($field_name) . "</strong></td>";
                echo "<td style='padding: 12px;'>" . htmlspecialchars($col['Type']) . "</td>";
                echo "<td style='padding: 12px;'>" . htmlspecialchars($col['Null']) . "</td>";
                echo "<td style='padding: 12px;'>" . htmlspecialchars($col['Key'] ?? '-') . "</td>";
                echo "<td style='padding: 12px;'>" . htmlspecialchars($col['Default'] ?? '-') . "</td>";
                echo "<td style='padding: 12px;'>" . htmlspecialchars($col['Extra'] ?? '-') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            echo "<h3 style='margin-top: 20px; color: #667eea;'>Required Fields Check:</h3>";
            foreach ($required_fields as $field) {
                if (in_array($field, $found_fields)) {
                    echo "<p style='color: #4caf50;'>✅ " . htmlspecialchars($field) . " exists</p>";
                } else {
                    echo "<p style='color: #f44336;'>❌ " . htmlspecialchars($field) . " MISSING</p>";
                }
            }
        } else {
            echo "<p style='color: #f44336;'>❌ Could not describe appointments table</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: #f44336;'>Error describing table: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    echo "</div>";
    
    
    
    
    echo "<div style='background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; color: #333;'>";
    echo "<h2 style='color: #667eea; margin-top: 0;'>✅ Check 3: Test INSERT Operation</h2>";
    
    try {
        
        $stmt = $pdo->query("SELECT user_id FROM users LIMIT 1");
        $user = $stmt->fetch();
        
        if ($user) {
            $test_user_id = $user['user_id'];
            
            echo "<p>Using test user_id: <strong>" . htmlspecialchars($test_user_id) . "</strong></p>";
            echo "<p style='background: #f0f0f0; padding: 10px; border-radius: 5px; margin-top: 10px;'>Attempting test INSERT...</p>";
            
            $test_insert = $pdo->prepare("
                INSERT INTO appointments 
                (user_id, appointment_date, appointment_time, reason, status) 
                VALUES (?, ?, ?, ?, 'scheduled')
            ");
            
            $tomorrow = date('Y-m-d', strtotime('+1 day'));
            $test_time = '14:30:00';
            $test_reason = 'Test Appointment';
            
            $result = $test_insert->execute([
                $test_user_id,
                $tomorrow,
                $test_time,
                $test_reason
            ]);
            
            if ($result) {
                $inserted_id = $pdo->lastInsertId();
                echo "<p style='color: #4caf50; font-weight: bold;'>✅ INSERT TEST SUCCESSFUL!</p>";
                echo "<p>Inserted appointment ID: <strong>" . htmlspecialchars($inserted_id) . "</strong></p>";
                
                
                $pdo->prepare("DELETE FROM appointments WHERE appointment_id = ?")->execute([$inserted_id]);
                echo "<p style='color: #ff9800; font-size: 12px;'>(Test record deleted)</p>";
            } else {
                echo "<p style='color: #f44336; font-weight: bold;'>❌ INSERT TEST FAILED</p>";
                echo "<p><strong>Error Info:</strong></p>";
                $error_info = $test_insert->errorInfo();
                echo "<pre style='background: #ffebee; padding: 10px; border-radius: 5px; overflow-x: auto;'>";
                echo "SQLSTATE: " . htmlspecialchars($error_info[0]) . "\n";
                echo "Error Code: " . htmlspecialchars($error_info[1]) . "\n";
                echo "Message: " . htmlspecialchars($error_info[2]) . "\n";
                echo "</pre>";
            }
        } else {
            echo "<p style='color: #f44336;'>No users found in database for testing</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: #f44336;'>Test error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    echo "</div>";
    
    
    
    
    echo "<div style='background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; color: #333;'>";
    echo "<h2 style='color: #667eea; margin-top: 0;'>✅ Check 4: Recent PHP Errors</h2>";
    
    $error_log = ini_get('error_log');
    if ($error_log && file_exists($error_log)) {
        $lines = file($error_log);
        $recent = array_slice($lines, -20);
        
        echo "<p>Error log location: <code>" . htmlspecialchars($error_log) . "</code></p>";
        echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; max-height: 300px;'>";
        foreach ($recent as $line) {
            echo htmlspecialchars($line);
        }
        echo "</pre>";
    } else {
        echo "<p>Error log not accessible or not found</p>";
    }
    echo "</div>";
    
    
    
    
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; color: #856404; border-left: 4px solid #ffc107;'>";
    echo "<h2 style='margin-top: 0; color: #856404;'>⚡ Recommendations</h2>";
    
    $check_table = $pdo->query("SHOW TABLES LIKE 'appointments'");
    if ($check_table->rowCount() === 0) {
        echo "<p><strong>1. Create the appointments table</strong> - Run the SQL provided in Check 1 above in phpMyAdmin.</p>";
    }
    
    echo "<p><strong>2. Clear browser cache</strong> - F5 or Ctrl+Shift+R to force refresh</p>";
    echo "<p><strong>3. Test the form again</strong> - Try submitting an appointment</p>";
    echo "<p><strong>4. If still failing</strong> - Check the error logs section above</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #ffebee; padding: 20px; border-radius: 8px; color: #c62828; border-left: 4px solid #f44336;'>";
    echo "<h2 style='margin-top: 0;'>❌ Database Connection Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</div></div>";

?>

<style>
    body {
        margin: 0;
        padding: 0;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    }
    
    code {
        background: #f5f5f5;
        padding: 2px 6px;
        border-radius: 3px;
        font-family: 'Courier New', monospace;
    }
    
    pre {
        font-family: 'Courier New', monospace;
        font-size: 13px;
        line-height: 1.5;
    }
</style>

