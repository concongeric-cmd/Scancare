<?php
require_once 'db_config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 TABLE NAMES AND COLUMNS DIAGNOSTIC</h1>";
echo "<hr>";

try {
    $pdo = getDatabaseConnection();
    
    
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>All Tables in Database:</h2>";
    echo "<pre>";
    foreach ($tables as $table) {
        echo "$table\n";
    }
    echo "</pre>";
    
    echo "<hr>";
    echo "<h2>Checking Specific Tables for Our Issues:</h2>";
    
    
    echo "<h3>MEDICINE TABLE:</h3>";
    $stmt = $pdo->query("DESCRIBE medicine");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    foreach ($cols as $col) {
        echo $col['Field'] . " | " . $col['Type'] . "\n";
    }
    echo "</pre>";
    
    
    echo "<h3>RESIDENT TABLE (checking various cases):</h3>";
    $resident_queries = [
        "SELECT COUNT(*) as cnt FROM resident",
        "SELECT COUNT(*) as cnt FROM RESIDENT",
        "SELECT COUNT(*) as cnt FROM Resident",
    ];
    
    foreach ($resident_queries as $query) {
        try {
            $stmt = $pdo->query($query);
            $result = $stmt->fetch();
            echo "✓ $query → " . $result['cnt'] . " rows\n";
        } catch (Exception $e) {
            echo "✗ $query → ERROR\n";
        }
    }
    echo "</pre>";
    
    
    echo "<h3>HEALTH_WORKER TABLE (checking various cases):</h3>";
    $worker_queries = [
        "SELECT COUNT(*) as cnt FROM health_worker",
        "SELECT COUNT(*) as cnt FROM HEALTH_WORKER",
        "SELECT COUNT(*) as cnt FROM Health_Worker",
    ];
    
    foreach ($worker_queries as $query) {
        try {
            $stmt = $pdo->query($query);
            $result = $stmt->fetch();
            echo "✓ $query → " . $result['cnt'] . " rows\n";
        } catch (Exception $e) {
            echo "✗ $query → ERROR\n";
        }
    }
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>ERROR: " . $e->getMessage() . "</h2>";
}
?>

