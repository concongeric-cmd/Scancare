<?php
require_once 'db_config.php';
$pdo = getDatabaseConnection();

// Create admin user
$username = 'admin';
$password = password_hash('admin123', PASSWORD_BCRYPT);
$email = 'admin@scancare.local';
$full_name = 'System Administrator';
$role = 'Super Admin';

$query = 'INSERT INTO system_admins (username, email, password_hash, full_name, role, is_active, created_at) 
          VALUES (?, ?, ?, ?, ?, 1, NOW())
          ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)';

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute([$username, $email, $password, $full_name, $role]);
    echo "Admin user created/updated successfully!\n";
    echo "Username: $username\n";
    echo "Password: admin123\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
