<?php
include 'db_config.php';
$pdo = getDatabaseConnection();

echo "=== ALL TABLES IN DATABASE ===\n";
$result = $pdo->query("SHOW TABLES");
$tables = $result->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    try {
        $count = $pdo->query("SELECT COUNT(*) as count FROM $table")->fetch(PDO::FETCH_ASSOC)['count'];
        echo "$table ($count rows)\n";
    } catch (Exception $e) {
        echo "$table (error checking)\n";
    }
}

echo "\n=== CHECKING APPOINTMENT TABLE STRUCTURE ===\n";
$result = $pdo->query("DESCRIBE appointment");
$columns = $result->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo $col['Field'] . " | " . $col['Type'] . " | Null: " . $col['Null'] . " | Key: " . $col['Key'] . "\n";
}

echo "\n=== CHECKING FOREIGN KEYS ===\n";
$result = $pdo->query("
    SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_NAME IN ('appointment', 'resident', 'health_worker')
    AND REFERENCED_TABLE_NAME IS NOT NULL
");
$fks = $result->fetchAll(PDO::FETCH_ASSOC);
foreach ($fks as $fk) {
    echo $fk['TABLE_NAME'] . "." . $fk['COLUMN_NAME'] . " -> " . $fk['REFERENCED_TABLE_NAME'] . "." . $fk['REFERENCED_COLUMN_NAME'] . "\n";
}
?>
