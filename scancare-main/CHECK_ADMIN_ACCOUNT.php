<?php
require_once 'db_config.php';

try {
    $pdo = getDatabaseConnection();
    
    echo "<h2>Checking SYSTEM_ADMINS Table</h2>";
    
    
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'system_admins'");
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "<p>✓ Table 'system_admins' exists</p>";
    } else {
        echo "<p>✗ Table 'system_admins' DOES NOT EXIST</p>";
        echo "<p><strong>ERROR:</strong> You need to create the SYSTEM_ADMINS table first.</p>";
        exit;
    }
    
    
    echo "<h3>Looking for admin account with username: 'it46'</h3>";
    
    $stmt = $pdo->prepare("
        SELECT admin_id, username, email, full_name, is_active, password_hash 
        FROM system_admins 
        WHERE LOWER(username) = 'it46'
    ");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "<p>✓ Account found!</p>";
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><td><strong>Admin ID:</strong></td><td>" . htmlspecialchars($admin['admin_id']) . "</td></tr>";
        echo "<tr><td><strong>Username:</strong></td><td>" . htmlspecialchars($admin['username']) . "</td></tr>";
        echo "<tr><td><strong>Email:</strong></td><td>" . htmlspecialchars($admin['email']) . "</td></tr>";
        echo "<tr><td><strong>Full Name:</strong></td><td>" . htmlspecialchars($admin['full_name']) . "</td></tr>";
        echo "<tr><td><strong>Active:</strong></td><td>" . ($admin['is_active'] ? 'Yes (1)' : 'No (0)') . "</td></tr>";
        echo "<tr><td><strong>Password Hash:</strong></td><td>" . substr(htmlspecialchars($admin['password_hash']), 0, 50) . "...</td></tr>";
        echo "</table>";
        
        echo "<h3>Testing password verification</h3>";
        $test_password = "IT46Scancare";
        if (password_verify($test_password, $admin['password_hash'])) {
            echo "<p>✓ Password verification PASSED - The password is correct!</p>";
        } else {
            echo "<p>✗ Password verification FAILED - The password doesn't match the hash</p>";
        }
    } else {
        echo "<p>✗ Account NOT found</p>";
        
        
        echo "<h3>All admin accounts in database:</h3>";
        $stmt = $pdo->prepare("SELECT admin_id, username, email, full_name, is_active FROM system_admins");
        $stmt->execute();
        $all_admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($all_admins) > 0) {
            echo "<table border='1' cellpadding='10'>";
            echo "<tr><th>Admin ID</th><th>Username</th><th>Email</th><th>Full Name</th><th>Active</th></tr>";
            foreach ($all_admins as $a) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($a['admin_id']) . "</td>";
                echo "<td>" . htmlspecialchars($a['username']) . "</td>";
                echo "<td>" . htmlspecialchars($a['email']) . "</td>";
                echo "<td>" . htmlspecialchars($a['full_name']) . "</td>";
                echo "<td>" . ($a['is_active'] ? 'Yes' : 'No') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No admin accounts found in database</p>";
        }
    }
} catch (PDOException $e) {
    echo "<p><strong>Database Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

