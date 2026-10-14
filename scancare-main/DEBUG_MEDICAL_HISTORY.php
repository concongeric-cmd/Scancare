<?php
require 'db_config.php';
$pdo = getDatabaseConnection();


$result = $pdo->query('SELECT * FROM medical_history ORDER BY history_id DESC LIMIT 10');
$records = $result->fetchAll(PDO::FETCH_ASSOC);

echo "=== MEDICAL HISTORY RECORDS ===\n";
echo "Total records: " . count($records) . "\n";
echo json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

echo "\n\n=== RESIDENT INFO ===\n";
$resident = $pdo->query('SELECT resident_id, user_id, full_name FROM RESIDENT WHERE resident_id = 1')->fetch(PDO::FETCH_ASSOC);
echo "Resident ID 1: " . json_encode($resident, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

echo "\n\n=== APPOINTMENT INFO ===\n";
$appt = $pdo->query('SELECT * FROM APPOINTMENT WHERE resident_id = 1 ORDER BY appointment_id DESC LIMIT 3')->fetchAll(PDO::FETCH_ASSOC);
echo "Appointments for Resident 1: " . json_encode($appt, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

