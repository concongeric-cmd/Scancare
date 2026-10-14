<?php
include 'db_config.php';
$pdo = getDatabaseConnection();


$result = $pdo->query('DESCRIBE users');
$columns = $result->fetchAll(PDO::FETCH_ASSOC);

echo "=== USERS TABLE SCHEMA ===\n";
foreach($columns as $col) {
    echo $col['Field'] . " (" . $col['Type'] . ")\n";
}


echo "\n=== SAMPLE USER DATA ===\n";
$sample = $pdo->query('SELECT * FROM users LIMIT 1');
$row = $sample->fetch(PDO::FETCH_ASSOC);
echo json_encode($row, JSON_PRETTY_PRINT);
?>

