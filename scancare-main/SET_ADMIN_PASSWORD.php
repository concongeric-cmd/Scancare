<?php
require_once 'db_config.php';


$new_password = 'admin123';
$password_hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 10]);

try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("UPDATE system_admins SET password_hash = ?");
    $stmt->execute([$password_hash]);
    
    echo "✓ SUCCESS! All admin passwords reset to: $new_password\n";
    echo "Password Hash: $password_hash\n";
    
    
    $stmt = $pdo->query("SELECT admin_id, email, full_name FROM system_admins");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nYou can login with any of these:\n";
    foreach ($admins as $admin) {
        echo "  Email: " . $admin['email'] . "\n";
    }
    echo "  Password: $new_password\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>

