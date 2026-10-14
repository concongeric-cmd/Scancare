<?php


require_once 'db_config.php';

header('Content-Type: text/html; charset=utf-8');


$test_email = 'adminscancare@gmail.com';
$test_password = 'AdminGutad123!';  

echo "<h1>🔐 LOGIN DIAGNOSTIC TEST</h1>";
echo "<hr>";

echo "<h2>Testing with admin account:</h2>";
echo "<p><strong>Email:</strong> $test_email</p>";
echo "<p><strong>Password (hashed):</strong> ••••••••</p>";
echo "<hr>";

try {
    $pdo = getDatabaseConnection();
    
    
    echo "<h3>1. Query system_admins table (lowercase):</h3>";
    
    $stmt = $pdo->prepare("
        SELECT 
            admin_id,
            username,
            email,
            password_hash,
            full_name,
            role,
            is_active
        FROM system_admins 
        WHERE LOWER(email) = ? AND is_active = 1
        LIMIT 1
    ");
    $stmt->execute([strtolower($test_email)]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "<p style='color: green;'><strong>✓ Found admin account!</strong></p>";
        echo "<pre>";
        print_r($admin);
        echo "</pre>";
        
        echo "<h3>2. Testing password verification:</h3>";
        echo "<p>Password hash from DB: " . $admin['password_hash'] . "</p>";
        
        
        $test_passwords = [
            'AdminGutad123!',
            'admin123',
            'password123',
            'test123',
            'Admin@123',
            '',
        ];
        
        foreach ($test_passwords as $pwd) {
            $result = password_verify($pwd, $admin['password_hash']) ? "✓ MATCH" : "✗ no match";
            echo "<p>Testing '$pwd': $result</p>";
        }
        
    } else {
        echo "<p style='color: red;'><strong>✗ Admin account NOT FOUND</strong></p>";
        
        echo "<h3>All system_admins accounts:</h3>";
        $stmt = $pdo->query("SELECT admin_id, username, email, is_active FROM system_admins");
        $all_admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($all_admins);
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>ERROR: " . $e->getMessage() . "</h2>";
}
?>

