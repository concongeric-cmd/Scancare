<?php
require 'db_config.php';
$pdo = getDatabaseConnection();
if (!$pdo) {
    echo "NO PDO\n";
    exit(1);
}
try {
    $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM resident WHERE is_active = 0 OR deleted_at IS NOT NULL");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "INACTIVE_COUNT: " . json_encode($row) . "\n";
} catch (Exception $e) {
    echo "INACTIVE_COUNT ERROR: " . $e->getMessage() . "\n";
}
try {
    $stmt = $pdo->query("SELECT resident_id, full_name, email, contact_number, is_active, deleted_at FROM resident WHERE is_active = 0 OR deleted_at IS NOT NULL ORDER BY resident_id DESC LIMIT 50");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "INACTIVE_ROWS: " . json_encode($rows) . "\n";
} catch (Exception $e) {
    echo "INACTIVE_ROWS ERROR: " . $e->getMessage() . "\n";
}
