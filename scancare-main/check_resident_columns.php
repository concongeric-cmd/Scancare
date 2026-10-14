<?php
require 'db_config.php';
$pdo = getDatabaseConnection();

try {
    
    $stmt = $pdo->query("SHOW COLUMNS FROM RESIDENT");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "RESIDENT table columns:\n";
    foreach ($cols as $col) {
        echo "- {$col['Field']} ({$col['Type']})\n";
    }
    
    
    echo "\nColumn presence:\n";
    echo "is_active: " . (in_array('is_active', array_column($cols, 'Field')) ? "YES" : "NO") . "\n";
    echo "deleted_at: " . (in_array('deleted_at', array_column($cols, 'Field')) ? "YES" : "NO") . "\n";
    
    
    echo "\nInactive residents (is_active = 0 OR deleted_at IS NOT NULL):\n";
    $stmt = $pdo->query("SELECT resident_id, full_name, email, is_active, deleted_at FROM RESIDENT WHERE is_active = 0 OR deleted_at IS NOT NULL");
    $inactive = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Count: " . count($inactive) . "\n";
    if (count($inactive) > 0) {
        print_r($inactive);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

