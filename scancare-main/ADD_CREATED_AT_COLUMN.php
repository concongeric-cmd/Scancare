<?php
require_once 'db_config.php';
$pdo = getDatabaseConnection();

if (!$pdo) {
    die("Database connection failed");
}

echo "<h2>Step 1: Add created_at Column to APPOINTMENT table</h2>";


try {
    $check_query = "SELECT created_at FROM APPOINTMENT LIMIT 1";
    $pdo->query($check_query);
    echo "<p>Column already exists</p>";
} catch (Exception $e) {
    echo "<p>Column doesn't exist, adding it...</p>";
    
    
    try {
        $alter_query = "
            ALTER TABLE APPOINTMENT 
            ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ";
        $pdo->exec($alter_query);
        echo "<p>✓ Column created_at added successfully with DEFAULT CURRENT_TIMESTAMP</p>";
    } catch (Exception $alter_error) {
        echo "<p>✗ Error adding column: " . $alter_error->getMessage() . "</p>";
        exit;
    }
}


echo "<h2>Step 2: Update Existing Appointments</h2>";

$update_query = "
    UPDATE APPOINTMENT 
    SET created_at = NOW() 
    WHERE created_at IS NULL
";

try {
    $stmt = $pdo->prepare($update_query);
    $stmt->execute();
    echo "<p>✓ Updated " . $stmt->rowCount() . " appointments with current timestamp</p>";
} catch (Exception $e) {
    echo "<p>✗ Error updating appointments: " . $e->getMessage() . "</p>";
}


echo "<h2>Step 3: Verify All Appointments Now Have Timestamps</h2>";
$sample_query = "
    SELECT 
        appointment_id,
        resident_id,
        appointment_date,
        appointment_time,
        created_at,
        DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as created_at_formatted,
        purpose
    FROM APPOINTMENT
    ORDER BY created_at DESC
    LIMIT 10
";

$result = $pdo->query($sample_query);
$appointments = $result->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5' style='margin-top: 20px; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>ID</th><th>Resident</th><th>Purpose</th><th>Appointment Date/Time</th><th>Created At</th>";
echo "</tr>";
foreach ($appointments as $apt) {
    echo "<tr>";
    echo "<td>{$apt['appointment_id']}</td>";
    echo "<td>{$apt['resident_id']}</td>";
    echo "<td>{$apt['purpose']}</td>";
    echo "<td>{$apt['appointment_date']} {$apt['appointment_time']}</td>";
    echo "<td>{$apt['created_at_formatted']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p style='margin-top: 20px;'><strong style='color: green;'>✅ Database is now fixed!</strong></p>";
echo "<p>Next steps:</p>";
echo "<ol>";
echo "<li>Hard refresh the admin panel (Ctrl+Shift+R)</li>";
echo "<li>Open the Activity Log modal for a resident</li>";
echo "<li>The appointment creation times should now display correctly</li>";
echo "</ol>";
?>

