<?php
require_once 'db_config.php';

try {
    $pdo = getDatabaseConnection();
    
    echo "<h2>🔍 CHECKING FOR: barrerafrancisgeri17@gmail.com</h2>";
    
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(['barrerafrancisgeri17@gmail.com']);
    
    echo "<h3>In USERS table?</h3>";
    if ($stmt->rowCount() > 0) {
        echo "✓ YES - User exists:<br>";
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<pre>" . print_r($row, true) . "</pre>";
    } else {
        echo "✗ NO - User DOES NOT EXIST in users table";
    }
    
    
    echo "<h3>In APPOINTMENTS table?</h3>";
    $stmt2 = $pdo->query("SELECT * FROM appointments WHERE patient_email = 'barrerafrancisgeri17@gmail.com' OR patient_name LIKE '%barrera%'");
    
    if ($stmt2->rowCount() > 0) {
        echo "✗ PROBLEM FOUND - Has " . $stmt2->rowCount() . " appointment(s) but NO user account!<br><br>";
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Patient Email</th><th>Patient Name</th><th>Service</th><th>Date</th><th>Status</th></tr>";
        while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['appointment_id'] . "</td>";
            echo "<td>" . $row['patient_email'] . "</td>";
            echo "<td>" . $row['patient_name'] . "</td>";
            echo "<td>" . $row['service_type'] . "</td>";
            echo "<td>" . $row['appointment_date'] . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "✓ No appointments found";
    }
    
    
    echo "<h3>ALL APPOINTMENTS IN DATABASE:</h3>";
    $stmt3 = $pdo->query("SELECT * FROM appointments");
    echo "Total: " . $stmt3->rowCount() . " appointments<br><br>";
    
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Patient Email</th><th>Patient Name</th><th>Service</th><th>Date</th></tr>";
    while ($row = $stmt3->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['appointment_id'] . "</td>";
        echo "<td>" . $row['patient_email'] . "</td>";
        echo "<td>" . $row['patient_name'] . "</td>";
        echo "<td>" . $row['service_type'] . "</td>";
        echo "<td>" . $row['appointment_date'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>

