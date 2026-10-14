<?php
require_once 'db_config.php';

$pdo = getDatabaseConnection();
if ($pdo === false) {
    die("DB connection failed");
}

echo "<h2>Checking account_requests table</h2>";


try {
    $stmt = $pdo->query("SHOW COLUMNS FROM account_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Columns:</h3>";
    foreach ($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")<br>";
    }
} catch (Exception $e) {
    echo "Table check error: " . $e->getMessage();
}

echo "<h3>All pending records:</h3>";
try {
    $stmt = $pdo->query("SELECT request_id, resident_id, status, created_at FROM account_requests WHERE status = 'pending' LIMIT 10");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Count: " . count($records) . "<br>";
    if (count($records) > 0) {
        echo "<pre>";
        print_r($records);
        echo "</pre>";
    } else {
        echo "No pending records found<br>";
        
        
        echo "<h4>All records regardless of status:</h4>";
        $stmt = $pdo->query("SELECT request_id, resident_id, status, created_at FROM account_requests LIMIT 10");
        $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Total: " . count($all) . "<br>";
        echo "<pre>";
        print_r($all);
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "Query error: " . $e->getMessage();
}

echo "<h3>Check with full joins:</h3>";
try {
    $stmt = $pdo->query("
        SELECT ar.*, r.email AS account_email, r.full_name AS resident_name, r.contact_number
        FROM account_requests ar
        LEFT JOIN RESIDENT r ON ar.resident_id = r.resident_id
        WHERE ar.status = 'pending'
        LIMIT 5
    ");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($records);
    echo "</pre>";
} catch (Exception $e) {
    echo "Join query error: " . $e->getMessage();
}
?>

