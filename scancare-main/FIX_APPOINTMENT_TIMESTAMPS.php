<?php
require_once 'db_config.php';
$pdo = getDatabaseConnection();

if (!$pdo) {
    die("Database connection failed");
}

echo "<h2>Fixing APPOINTMENT created_at timestamps</h2>";


$check_query = "SELECT COUNT(*) as count FROM APPOINTMENT WHERE created_at IS NULL OR TIME(created_at) = '00:00:00'";
$result = $pdo->query($check_query);
$row = $result->fetch(PDO::FETCH_ASSOC);
echo "<p>Appointments with NULL/00:00:00 created_at: " . $row['count'] . "</p>";


$update_query = "
    UPDATE APPOINTMENT 
    SET created_at = NOW() 
    WHERE created_at IS NULL OR TIME(created_at) = '00:00:00'
";

try {
    $stmt = $pdo->prepare($update_query);
    $stmt->execute();
    echo "<p>✓ Updated " . $stmt->rowCount() . " appointments with current timestamp</p>";
} catch (Exception $e) {
    echo "<p>✗ Error updating appointments: " . $e->getMessage() . "</p>";
}


echo "<h3>Sample Updated Appointments:</h3>";
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
    LIMIT 5
";

$result = $pdo->query($sample_query);
$appointments = $result->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Resident</th><th>Purpose</th><th>Appointment Date/Time</th><th>Created At</th></tr>";
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

echo "<p><strong>✅ Database has been fixed!</strong> Now go back to the admin panel and refresh the Activity Log.</p>";
?>

