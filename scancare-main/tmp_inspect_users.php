<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=scancare_db;charset=utf8mb4', 'root', '', array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC));
    echo "SHOW COLUMNS FROM users:\n";
    $stmt = $pdo->query('SHOW COLUMNS FROM users');
    while ($row = $stmt->fetch()) {
        echo implode(' | ', $row) . "\n";
    }
    echo "\nSAMPLE ROWS:\n";
    $stmt = $pdo->query('SELECT user_id, first_name, last_name, date_of_birth, address, phone_number, email FROM users LIMIT 20');
    while ($row = $stmt->fetch()) {
        echo json_encode($row) . "\n";
    }
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
}
