<?php
require 'db_config.php';
$pdo = getDatabaseConnection();
if (!$pdo) {
    echo "NO PDO\n";
    exit(1);
}
foreach (['resident', 'RESIDENT'] as $t) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '" . addslashes($t) . "'");
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "TABLE $t: " . json_encode($rows) . "\n";
    } catch (Exception $e) {
        echo "TABLE $t ERROR: " . $e->getMessage() . "\n";
    }
}
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM resident LIKE 'is_active'");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "IS_ACTIVE: " . json_encode($cols) . "\n";
} catch (Exception $e) {
    echo "IS_ACTIVE ERROR: " . $e->getMessage() . "\n";
}
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM resident LIKE 'deleted_at'");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "DELETED_AT: " . json_encode($cols) . "\n";
} catch (Exception $e) {
    echo "DELETED_AT ERROR: " . $e->getMessage() . "\n";
}
try {
    $stmt = $pdo->query("SELECT resident_id, is_active, deleted_at FROM resident LIMIT 5");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "SAMPLE_ROWS: " . json_encode($rows) . "\n";
} catch (Exception $e) {
    echo "SAMPLE_ROWS ERROR: " . $e->getMessage() . "\n";
}
