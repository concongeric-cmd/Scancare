<?php


session_start();
require_once 'db_config.php';


$resident_id = $_SESSION['user_id'] ?? 9;

echo "<h1>🔍 Dashboard Appointment Debug Report</h1>";
echo "<p><strong>Resident ID:</strong> $resident_id</p>";
echo "<hr>";

try {
    $pdo = getDatabaseConnection();
    
    
    
    
    echo "<h2>1️⃣ Dashboard Query Results (Live Fetch)</h2>";
    $fetch_upcoming = "
        SELECT 
            appointment_id,
            appointment_date,
            appointment_time,
            reason,
            status,
            user_id
        FROM appointments
        WHERE user_id = ? 
        AND appointment_date >= CURDATE()
        AND status IN ('scheduled', 'pending')
        ORDER BY appointment_date ASC, appointment_time ASC
        LIMIT 5
    ";
    
    $stmt = $pdo->prepare($fetch_upcoming);
    $stmt->execute([$resident_id]);
    $upcoming_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Total rows returned:</strong> " . count($upcoming_appointments) . "</p>";
    
    if (!empty($upcoming_appointments)) {
        echo "<table border='1' cellpadding='10' style='margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Date</th><th>Time</th><th>Reason</th><th>Status</th></tr>";
        foreach ($upcoming_appointments as $index => $apt) {
            echo "<tr>";
            echo "<td>" . $apt['appointment_id'] . "</td>";
            echo "<td>" . $apt['user_id'] . "</td>";
            echo "<td>" . $apt['appointment_date'] . "</td>";
            echo "<td>" . $apt['appointment_time'] . "</td>";
            echo "<td>" . $apt['reason'] . "</td>";
            echo "<td>" . $apt['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: green;'>✓ No appointments returned (this is correct if expecting empty)</p>";
    }
    
    echo "<hr>";
    
    
    
    
    echo "<h2>2️⃣ All Appointments for User $resident_id (No Date Filter)</h2>";
    $stmt2 = $pdo->prepare("
        SELECT 
            appointment_id,
            user_id,
            appointment_date,
            appointment_time,
            reason,
            status
        FROM appointments
        WHERE user_id = ?
        ORDER BY appointment_date DESC
    ");
    $stmt2->execute([$resident_id]);
    $all_appointments = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Total appointments (all statuses/dates):</strong> " . count($all_appointments) . "</p>";
    
    if (!empty($all_appointments)) {
        echo "<table border='1' cellpadding='10' style='margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Date</th><th>Time</th><th>Reason</th><th>Status</th><th>Future?</th></tr>";
        foreach ($all_appointments as $apt) {
            $is_future = strtotime($apt['appointment_date']) >= strtotime(date('Y-m-d')) ? '✓ YES' : '✗ NO';
            echo "<tr>";
            echo "<td>" . $apt['appointment_id'] . "</td>";
            echo "<td>" . $apt['user_id'] . "</td>";
            echo "<td>" . $apt['appointment_date'] . "</td>";
            echo "<td>" . $apt['appointment_time'] . "</td>";
            echo "<td>" . $apt['reason'] . "</td>";
            echo "<td>" . $apt['status'] . "</td>";
            echo "<td>" . $is_future . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<hr>";
    
    
    
    
    echo "<h2>3️⃣ Database Statistics</h2>";
    $stmt3 = $pdo->query("SELECT COUNT(*) as total FROM appointments");
    $total_count = $stmt3->fetch()['total'];
    echo "<p><strong>Total appointments in entire database:</strong> " . $total_count . "</p>";
    
    
    
    
    echo "<h2>4️⃣ Duplicate Check</h2>";
    $stmt4 = $pdo->query("
        SELECT 
            appointment_id,
            COUNT(*) as count
        FROM appointments
        GROUP BY appointment_id
        HAVING count > 1
    ");
    $duplicates = $stmt4->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($duplicates)) {
        echo "<p style='color: red;'><strong>⚠️ DUPLICATE FOUND:</strong></p>";
        foreach ($duplicates as $dup) {
            echo "Appointment ID " . $dup['appointment_id'] . " appears " . $dup['count'] . " times<br>";
        }
    } else {
        echo "<p style='color: green;'>✓ No duplicate appointment IDs</p>";
    }
    
    echo "<hr>";
    
    
    
    
    echo "<h2>5️⃣ Raw Database Dump (User $resident_id)</h2>";
    $stmt5 = $pdo->prepare("SELECT * FROM appointments WHERE user_id = ?");
    $stmt5->execute([$resident_id]);
    $raw_data = $stmt5->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($raw_data)) {
        echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto;'>";
        echo htmlspecialchars(json_encode($raw_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo "</pre>";
    }
    
    echo "<hr>";
    echo "<p><strong>Debug completed at:</strong> " . date('Y-m-d H:i:s') . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
        background-color: #f9f9f9;
    }
    h1, h2 {
        color: #333;
    }
    table {
        border-collapse: collapse;
        width: 100%;
        background: white;
    }
    th {
        background-color: #454B1B;
        color: white;
    }
    td {
        border: 1px solid #ddd;
    }
    hr {
        margin: 20px 0;
        border: none;
        border-top: 2px solid #454B1B;
    }
</style>

