<?php


require_once 'db_config.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Medicine Insert Diagnostic</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .success { color: green; background: #f0f0f0; padding: 10px; margin: 10px 0; border-left: 4px solid green; }
        .error { color: red; background: #fff0f0; padding: 10px; margin: 10px 0; border-left: 4px solid red; }
        .info { color: blue; background: #f0f0ff; padding: 10px; margin: 10px 0; border-left: 4px solid blue; }
        h2 { color: #333; }
        code { background: #eee; padding: 2px 6px; }
        table { border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #4CAF50; color: white; }
    </style>
</head>
<body>

<h2>🔍 Medicine Inventory Diagnostic Report</h2>

<?php


echo "<h3>1. Testing Database Connection</h3>";
try {
    $pdo = getDatabaseConnection();
    if ($pdo) {
        echo '<div class="success">✅ Database connection successful</div>';
    } else {
        echo '<div class="error">❌ Database connection returned false</div>';
        exit;
    }
} catch (Exception $e) {
    echo '<div class="error">❌ Connection error: ' . $e->getMessage() . '</div>';
    exit;
}


echo "<h3>2. Checking Medicine Table Structure</h3>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'medicine'");
    $result = $stmt->fetch();
    
    if ($result) {
        echo '<div class="success">✅ Medicine table exists</div>';
    } else {
        echo '<div class="error">❌ Medicine table does not exist!</div>';
        echo '<div class="info">Need to create table first</div>';
        exit;
    }
} catch (Exception $e) {
    echo '<div class="error">❌ Error checking table: ' . $e->getMessage() . '</div>';
    exit;
}


echo "<h3>3. Medicine Table Structure</h3>";
try {
    $stmt = $pdo->query("DESCRIBE medicine");
    $columns = $stmt->fetchAll();
    
    echo "<table>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($col['Type'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($col['Null'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($col['Key'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo '<div class="error">❌ Error reading table structure: ' . $e->getMessage() . '</div>';
}


echo "<h3>4. Current Medicines in Database</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM medicine");
    $row = $stmt->fetch();
    $count = $row['count'] ?? 0;
    
    echo '<div class="info">📦 Total medicines: ' . $count . '</div>';
    
    if ($count > 0) {
        echo "<p>Existing medicines:</p>";
        $stmt = $pdo->query("SELECT medicine_id, name, dosage, stock_quantity FROM medicine ORDER BY medicine_id DESC LIMIT 10");
        $medicines = $stmt->fetchAll();
        
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Dosage</th><th>Stock</th></tr>";
        foreach ($medicines as $med) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($med['medicine_id']) . "</td>";
            echo "<td>" . htmlspecialchars($med['name']) . "</td>";
            echo "<td>" . htmlspecialchars($med['dosage']) . "</td>";
            echo "<td>" . htmlspecialchars($med['stock_quantity']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo '<div class="error">❌ Error counting medicines: ' . $e->getMessage() . '</div>';
}


echo "<h3>5. Testing INSERT Operation</h3>";
try {
    $test_name = "Test Medicine " . time();
    $test_dosage = "100mg";
    $test_qty = 50;
    $test_status = "Available";
    
    echo '<div class="info">Attempting to insert test medicine...</div>';
    echo '<div class="info">Name: ' . $test_name . '</div>';
    echo '<div class="info">Dosage: ' . $test_dosage . '</div>';
    echo '<div class="info">Quantity: ' . $test_qty . '</div>';
    
    $stmt = $pdo->prepare("
        INSERT INTO medicine (name, dosage, stock_quantity, available, dispensed, status) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    if ($stmt->execute([$test_name, $test_dosage, $test_qty, $test_qty, 0, $test_status])) {
        $inserted_id = $pdo->lastInsertId();
        echo '<div class="success">✅ Test INSERT successful! New medicine ID: ' . $inserted_id . '</div>';
        
        
        $verify_stmt = $pdo->prepare("SELECT * FROM medicine WHERE medicine_id = ?");
        $verify_stmt->execute([$inserted_id]);
        $verify_result = $verify_stmt->fetch();
        
        if ($verify_result) {
            echo '<div class="success">✅ Verification: Medicine found in database</div>';
            echo '<table>';
            echo '<tr><th>Column</th><th>Value</th></tr>';
            foreach ($verify_result as $key => $value) {
                echo '<tr><td>' . htmlspecialchars($key) . '</td><td>' . htmlspecialchars($value) . '</td></tr>';
            }
            echo '</table>';
        }
    } else {
        echo '<div class="error">❌ Test INSERT failed</div>';
        $error_info = $stmt->errorInfo();
        echo '<div class="error">Error: ' . htmlspecialchars($error_info[2]) . '</div>';
    }
} catch (Exception $e) {
    echo '<div class="error">❌ Exception during test insert: ' . $e->getMessage() . '</div>';
}


echo "<h3>6. PHP Error Log Check</h3>";
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    echo '<div class="info">Error log location: ' . $error_log . '</div>';
    echo '<p>Last 20 lines of error log:</p>';
    echo '<pre>';
    $lines = file($error_log);
    $recent = array_slice($lines, -20);
    foreach ($recent as $line) {
        echo htmlspecialchars($line);
    }
    echo '</pre>';
} else {
    echo '<div class="info">Could not locate error log</div>';
}

?>

</body>
</html>

