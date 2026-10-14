<?php
require_once 'db_config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>📊 TEST ADMIN DASHBOARD COUNTS</h1>";
echo "<hr>";

try {
    $pdo = getDatabaseConnection();
    
    echo "<h2>Query 1: Count residents</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM resident");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_users = intval($result['count'] ?? 0);
    echo "<p>✓ Total Residents: <strong>$total_users</strong></p>";
    
    echo "<h2>Query 2: Count BHW staff</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM health_worker WHERE role = 'bhw'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_staff = intval($result['count'] ?? 0);
    echo "<p>✓ Total BHW Staff: <strong>$total_staff</strong></p>";
    
    echo "<hr>";
    echo "<h2>Dashboard Will Display:</h2>";
    echo "<p><strong>Total Users:</strong> $total_users</p>";
    echo "<p><strong>Staff Accounts:</strong> $total_staff</p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>ERROR: " . $e->getMessage() . "</h2>";
}
?>
