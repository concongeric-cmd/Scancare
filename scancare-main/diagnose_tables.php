<?php
include 'db_config.php';
$pdo = getDatabaseConnection();


$result = $pdo->query('SHOW TABLES');
$tables = $result->fetchAll(PDO::FETCH_COLUMN);

echo "=== TABLES IN DATABASE ===\n";
foreach($tables as $table) {
    echo $table . "\n";
}


$check = $pdo->query("SHOW TABLES LIKE 'resident%'");
$resident_tables = $check->fetchAll(PDO::FETCH_COLUMN);

if (!empty($resident_tables)) {
    echo "\n=== RESIDENT TABLE SCHEMA ===\n";
    $result = $pdo->query('DESCRIBE ' . reset($resident_tables));
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }
}
?>

