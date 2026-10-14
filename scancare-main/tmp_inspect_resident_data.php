<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=scancare_db;charset=utf8mb4', 'root', '', array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC));
    $queries = [
        'total' => 'SELECT COUNT(*) AS cnt FROM resident',
        'non_empty_address' => 'SELECT COUNT(*) AS cnt FROM resident WHERE TRIM(address) != ""',
        'non_empty_contact' => 'SELECT COUNT(*) AS cnt FROM resident WHERE TRIM(contact_number) != ""',
        'valid_dob' => 'SELECT COUNT(*) AS cnt FROM resident WHERE date_of_birth IS NOT NULL AND date_of_birth != "0000-00-00" AND date_of_birth != ""',
    ];
    foreach ($queries as $key => $sql) {
        $stmt = $pdo->query($sql);
        $row = $stmt->fetch();
        echo strtoupper($key) . ': ' . ($row['cnt'] ?? '0') . "\n";
    }
    echo "\nEXAMPLE NON-EMPTY ROWS:\n";
    $stmt = $pdo->query('SELECT resident_id, full_name, address, date_of_birth, contact_number, patient_code FROM resident WHERE TRIM(address) != "" OR TRIM(contact_number) != "" OR (date_of_birth IS NOT NULL AND date_of_birth != "0000-00-00" AND date_of_birth != "") LIMIT 20');
    while ($row = $stmt->fetch()) {
        echo json_encode($row) . "\n";
    }
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
}
