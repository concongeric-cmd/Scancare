<?php
require_once 'db_config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 SYSTEM_ADMINS TABLE DIAGNOSTIC</h1>";
echo "<hr>";

try {
    $pdo = getDatabaseConnection();
    
    
    echo "<h2>TABLE STRUCTURE:</h2>";
    $stmt = $pdo->query("DESCRIBE system_admins");
    $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    foreach ($structure as $col) {
        echo $col['Field'] . " | " . $col['Type'] . "\n";
    }
    echo "</pre>";
    
    
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM system_admins");
    $count = $stmt->fetch()['cnt'];
    
    echo "<h2>TABLE DATA:</h2>";
    echo "<p><strong>Total admin accounts: $count</strong></p>";
    
    if ($count > 0) {
        echo "<h3>Existing admin accounts:</h3>";
        echo "<pre>";
        
        $stmt = $pdo->query("SELECT admin_id, username, email, full_name, role, is_active FROM system_admins");
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($admins as $admin) {
            echo "ID: " . $admin['admin_id'] . "\n";
            echo "Username: " . $admin['username'] . "\n";
            echo "Email: " . $admin['email'] . "\n";
            echo "Name: " . $admin['full_name'] . "\n";
            echo "Role: " . $admin['role'] . "\n";
            echo "Active: " . $admin['is_active'] . "\n";
            echo "
        }
        echo "</pre>";
    } else {
        echo "<p style='color: red;'><strong>⚠️ NO ADMIN ACCOUNTS FOUND!</strong></p>";
        echo "<p>You need to add an admin account to login.</p>";
    }
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>ERROR: " . $e->getMessage() . "</h2>";
}
?>

