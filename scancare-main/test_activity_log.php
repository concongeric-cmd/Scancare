<?php
require_once 'db_config.php';

$pdo = getDatabaseConnection();

echo "=== CHECKING APPOINTMENT TABLE ===\n";
$stmt = $pdo->query("SELECT * FROM APPOINTMENT LIMIT 3");
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Found " . count($appointments) . " appointments\n";
var_dump($appointments);

echo "\n=== CHECKING RESIDENT 1 APPOINTMENTS ===\n";
$stmt = $pdo->prepare("
    SELECT 
        appointment_id,
        'scheduled_appointment' as action_type,
        DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as action_timestamp,
        appointment_date,
        appointment_time,
        purpose,
        status
    FROM APPOINTMENT
    WHERE resident_id = ?
    ORDER BY created_at DESC, appointment_date DESC, appointment_time DESC
    LIMIT 5
");
$stmt->execute([1]);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Found " . count($result) . " appointments for resident_id=1\n";
var_dump($result);

echo "\n=== JSON OUTPUT (what JavaScript sees) ===\n";
echo json_encode($result, JSON_PRETTY_PRINT);
?>
