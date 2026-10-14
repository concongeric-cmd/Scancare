<?php
include 'db_config.php';
$pdo = getDatabaseConnection();

echo "=== HEALTH_WORKER TABLE STRUCTURE ===\n";
$result = $pdo->query("DESCRIBE health_worker");
$columns = $result->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo $col['Field'] . " (" . $col['Type'] . ")\n";
}

echo "\n=== SAMPLE HEALTH_WORKER DATA ===\n";
$result = $pdo->query("SELECT * FROM health_worker LIMIT 1");
$row = $result->fetch(PDO::FETCH_ASSOC);
echo json_encode($row, JSON_PRETTY_PRINT);
?>
