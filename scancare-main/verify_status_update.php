<?php
include 'db_config.php';
$pdo = getDatabaseConnection();

echo "=== APPOINTMENT #1 STATUS CHECK ===\n";
$result = $pdo->query("SELECT appointment_id, status, health_worker_id FROM appointment WHERE appointment_id = 1");
$row = $result->fetch(PDO::FETCH_ASSOC);
echo json_encode($row, JSON_PRETTY_PRINT);
?>
