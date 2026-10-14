<?php
include 'db_config.php';
$pdo = getDatabaseConnection();

echo "=== DATABASE STRUCTURE ANALYSIS ===\n\n";


try {
    $result = $pdo->query("SELECT COUNT(*) as count FROM appoinmen");
    $misspelled_count = $result->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✓ MISSPELLED TABLE 'appoinmen' EXISTS with $misspelled_count rows\n";
} catch (Exception $e) {
    echo "✗ Misspelled table 'appoinmen' does NOT exist\n";
    $misspelled_count = 0;
}

try {
    $result = $pdo->query("SELECT COUNT(*) as count FROM appointment");
    $correct_count = $result->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✓ CORRECT TABLE 'appointment' EXISTS with $correct_count rows\n";
} catch (Exception $e) {
    echo "✗ Correct table 'appointment' does NOT exist\n";
    $correct_count = 0;
}

echo "\n=== MISSPELLED TABLE STRUCTURE ===\n";
try {
    $result = $pdo->query("DESCRIBE appoinmen");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo "N/A\n";
}

echo "\n=== CORRECT TABLE STRUCTURE ===\n";
try {
    $result = $pdo->query("DESCRIBE appointment");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo "N/A\n";
}

echo "\n=== SAMPLE DATA FROM MISSPELLED TABLE ===\n";
try {
    $result = $pdo->query("SELECT * FROM appoinmen LIMIT 3");
    $rows = $result->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "N/A\n";
}
?>

