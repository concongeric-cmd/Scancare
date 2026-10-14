<?php








require_once 'db_config.php';




$admin_credentials = [
    'username' => 'adminscancare',
    'email' => 'adminscancare@gmail.com',
    'password' => 'admin123',
    'full_name' => 'ScanCare Administrator',
    'role' => 'admin',
    'is_active' => 1
];




try {
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        echo '<div style="color: red; font-weight: bold;">ERROR: Database connection failed!</div>';
        exit;
    }
    
    
    $check_query = "SELECT admin_id FROM system_admins WHERE email = ? LIMIT 1";
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->execute([$admin_credentials['email']]);
    
    if ($check_stmt->rowCount() > 0) {
        echo '<div style="color: green; padding: 10px; border: 1px solid green; border-radius: 4px; margin-bottom: 10px;">';
        echo '<strong>✓ Admin account already exists:</strong><br>';
        echo 'Email: ' . htmlspecialchars($admin_credentials['email']) . '<br>';
        echo 'Username: ' . htmlspecialchars($admin_credentials['username']);
        echo '</div>';
    } else {
        
        $password_hash = password_hash($admin_credentials['password'], PASSWORD_BCRYPT, ['cost' => 10]);
        
        $insert_query = "
            INSERT INTO system_admins 
            (username, email, password_hash, full_name, role, is_active, created_at, last_login)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NULL)
        ";
        
        $insert_stmt = $pdo->prepare($insert_query);
        $result = $insert_stmt->execute([
            $admin_credentials['username'],
            $admin_credentials['email'],
            $password_hash,
            $admin_credentials['full_name'],
            $admin_credentials['role'],
            $admin_credentials['is_active']
        ]);
        
        if ($result) {
            echo '<div style="color: green; padding: 10px; border: 1px solid green; border-radius: 4px; margin-bottom: 10px;">';
            echo '<strong>✓ Admin account created successfully!</strong><br>';
            echo 'Email: ' . htmlspecialchars($admin_credentials['email']) . '<br>';
            echo 'Username: ' . htmlspecialchars($admin_credentials['username']) . '<br>';
            echo 'Password: ' . htmlspecialchars($admin_credentials['password']);
            echo '</div>';
        } else {
            echo '<div style="color: red; padding: 10px; border: 1px solid red; border-radius: 4px;">';
            echo '<strong>✗ Failed to create admin account!</strong><br>';
            echo 'Error: ' . implode(', ', $insert_stmt->errorInfo());
            echo '</div>';
        }
    }
    
    echo '<div style="margin-top: 20px; padding: 10px; background-color: #f0f0f0; border-radius: 4px;">';
    echo '<strong>Next Steps:</strong><br>';
    echo '1. Navigate to <a href="login_admin.php" style="color: blue;">login_admin.php</a><br>';
    echo '2. Log in with:<br>';
    echo '   - Username/Email: ' . htmlspecialchars($admin_credentials['email']) . '<br>';
    echo '   - Password: ' . htmlspecialchars($admin_credentials['password']) . '<br>';
    echo '3. Delete or disable this verification script after testing<br>';
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div style="color: red; padding: 10px; border: 1px solid red; border-radius: 4px;">';
    echo '<strong>Exception occurred:</strong><br>';
    echo htmlspecialchars($e->getMessage());
    echo '</div>';
}

?>

