<?php

session_start();

require_once 'auth_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resident_id'])) {
    $resident_id = $_POST['resident_id'];
    
    echo "<h1>DEBUG: Checking Appointments for Resident ID: $resident_id</h1>";
    
    try {
        
        $check_resident = $pdo->prepare("SELECT resident_id, full_name FROM RESIDENT WHERE resident_id = ?");
        $check_resident->execute([$resident_id]);
        $resident = $check_resident->fetch(PDO::FETCH_ASSOC);
        
        if ($resident) {
            echo "<p><strong>✓ Resident Found:</strong> {$resident['full_name']} (ID: {$resident['resident_id']})</p>";
        } else {
            echo "<p><strong>✗ Resident NOT Found</strong> with ID: $resident_id</p>";
        }
        
        
        echo "\n<h2>All Appointments for Resident $resident_id:</h2>";
        $appt_query = $pdo->prepare("
            SELECT 
                appointment_id,
                resident_id,
                appointment_date,
                appointment_time,
                purpose,
                status,
                created_at,
                DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as created_at_formatted
            FROM APPOINTMENT
            WHERE resident_id = ?
            ORDER BY created_at DESC
        ");
        $appt_query->execute([$resident_id]);
        $appointments = $appt_query->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Total Appointments Found:</strong> " . count($appointments) . "</p>";
        
        if (count($appointments) > 0) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Date</th><th>Time</th><th>Purpose</th><th>Status</th><th>Created At</th></tr>";
            foreach ($appointments as $appt) {
                echo "<tr>";
                echo "<td>{$appt['appointment_id']}</td>";
                echo "<td>{$appt['appointment_date']}</td>";
                echo "<td>{$appt['appointment_time']}</td>";
                echo "<td>{$appt['purpose']}</td>";
                echo "<td>{$appt['status']}</td>";
                echo "<td>{$appt['created_at_formatted']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        
        echo "\n<h2>Activity Logs for Resident $resident_id:</h2>";
        $log_query = $pdo->prepare("
            SELECT log_id, action_type, timestamp
            FROM activity_logs
            WHERE user_id = ?
            ORDER BY timestamp DESC
            LIMIT 20
        ");
        $log_query->execute([$resident_id]);
        $logs = $log_query->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Total Logs Found:</strong> " . count($logs) . "</p>";
        
        if (count($logs) > 0) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Action</th><th>Timestamp</th></tr>";
            foreach ($logs as $log) {
                echo "<tr>";
                echo "<td>{$log['log_id']}</td>";
                echo "<td>{$log['action_type']}</td>";
                echo "<td>{$log['timestamp']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        
        echo "\n<h2>What get_resident_activities.php Returns:</h2>";
        $api_response = file_get_contents("http:
        echo "<pre>" . htmlspecialchars($api_response) . "</pre>";
        
    } catch (PDOException $e) {
        echo "<p><strong>Database Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    
    ?>
    <form method="POST">
        <label>Resident ID: <input type="number" name="resident_id" required></label>
        <button type="submit">Check</button>
    </form>
    <?php
}
?>

