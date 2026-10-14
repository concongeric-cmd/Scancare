<?php

require_once 'db_config.php';
$pdo = getDatabaseConnection();

if (!$pdo) {
    die("Database connection failed");
}

echo "<h2>APPOINTMENT Table Structure:</h2>";
$result = $pdo->query("DESCRIBE APPOINTMENT");
$columns = $result->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
foreach ($columns as $col) {
    echo "<tr>";
    echo "<td>" . $col['Field'] . "</td>";
    echo "<td>" . $col['Type'] . "</td>";
    echo "<td>" . $col['Null'] . "</td>";
    echo "<td>" . $col['Key'] . "</td>";
    echo "<td>" . $col['Default'] . "</td>";
    echo "<td>" . $col['Extra'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>Sample Appointment Records:</h2>";
$sample = $pdo->query("SELECT appointment_id, resident_id, appointment_date, appointment_time, created_at FROM APPOINTMENT LIMIT 5");
$records = $sample->fetchAll(PDO::FETCH_ASSOC);

echo "<pre>";
print_r($records);
echo "</pre>";
?>

