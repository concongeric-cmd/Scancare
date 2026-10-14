<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=scancare_db;charset=utf8mb4', 'root', '', array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC));
    $stmt = $pdo->query('SHOW COLUMNS FROM patient_record');
    echo "SHOW COLUMNS FROM patient_record:\n";
    while ($row = $stmt->fetch()) {
        echo implode(' | ', $row) . "\n";
    }
    echo "\nSAMPLE ROWS:\n";
    $stmt = $pdo->query('SELECT * FROM patient_record LIMIT 5');
    while ($row = $stmt->fetch()) {
        echo json_encode($row) . "\n";
    }
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
}
