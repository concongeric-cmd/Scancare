<?php


require_once 'db_config.php';

try {
    $pdo = getDatabaseConnection();
    
    echo "<h1>Admin Accounts Check</h1>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Admin ID</th><th>Email</th><th>Username</th><th>Password Hash</th><th>Is Active</th></tr>";
    
    $stmt = $pdo->query("SELECT admin_id, email, username, password_hash, is_active FROM system_admins");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($admins as $admin) {
        echo "<tr>";
        echo "<td>{$admin['admin_id']}</td>";
        echo "<td>{$admin['email']}</td>";
        echo "<td>{$admin['username']}</td>";
        echo "<td>" . substr($admin['password_hash'], 0, 30) . "...</td>";
        echo "<td>" . ($admin['is_active'] ? "Yes" : "No") . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    
    echo "<h2>Testing Login with: adminscancare@gmail.com</h2>";
    
    $email = 'adminscancare@gmail.com';
    $password = 'password123'; 
    
    $stmt = $pdo->prepare("SELECT * FROM system_admins WHERE LOWER(email) = ? AND is_active = 1");
    $stmt->execute([strtolower($email)]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "<p style='color: green;'><strong>Email found in database</strong></p>";
        echo "<p>Password verification test (using password123): " . (password_verify($password, $admin['password_hash']) ? "✓ MATCHES" : "✗ DOES NOT MATCH") . "</p>";
        
        
        echo "<h3>Testing common passwords:</h3>";
        $common_passwords = ['password', 'admin', '123456', 'password123', '12345678', 'scancare'];
        foreach ($common_passwords as $test_pwd) {
            $result = password_verify($test_pwd, $admin['password_hash']) ? "✓ MATCH" : "✗ no match";
            echo "<p>$test_pwd: $result</p>";
        }
    } else {
        echo "<p style='color: red;'><strong>Email NOT found in database OR not active</strong></p>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
<hr>
<p><strong>Delete this file after checking.</strong></p>

