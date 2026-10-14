<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=scancare_db;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    echo "SHOW COLUMNS FROM resident:\n";
    $stmt = $pdo->query('SHOW COLUMNS FROM resident');
    while ($row = $stmt->fetch()) {
        echo implode(' | ', $row) . "\n";
    }
    echo "\nSAMPLE ROWS:\n";
    $stmt = $pdo->query('SELECT resident_id, full_name, address, date_of_birth, contact_number, patient_code FROM resident LIMIT 20');
    while ($row = $stmt->fetch()) {
        echo json_encode($row) . "\n";
    }
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
}
