<?php


require_once 'db_config.php';


$admin_email = 'adminscancareit46@gmail.com';
$admin_password = 'IT46Scancare';
$admin_name = 'IT46';
$admin_username = 'it46';  
$admin_role = 'admin';

try {
    $pdo = getDatabaseConnection();
    
    if ($pdo === false) {
        throw new Exception('Database connection failed');
    }
    
    echo "========================================<br>";
    echo "Admin Account Setup<br>";
    echo "========================================<br><br>";
    
    
    $check_stmt = $pdo->prepare("SELECT admin_id FROM SYSTEM_ADMINS WHERE LOWER(email) = ? OR LOWER(username) = ?");
    $check_stmt->execute([strtolower($admin_email), strtolower($admin_username)]);
    
    if ($check_stmt->rowCount() > 0) {
        echo "<span style='color: red;'><strong>❌ ERROR:</strong> Admin account already exists!</span><br>";
        echo "Email: " . htmlspecialchars($admin_email) . "<br>";
        echo "Username: " . htmlspecialchars($admin_username) . "<br><br>";
        echo "If you need to reset this account, manually delete the record from SYSTEM_ADMINS table and run this script again.";
        exit;
    }
    
    
    $password_hash = password_hash($admin_password, PASSWORD_BCRYPT, ['cost' => 10]);
    
    
    $insert_stmt = $pdo->prepare("
        INSERT INTO SYSTEM_ADMINS 
        (username, email, password_hash, full_name, role, is_active, created_at)
        VALUES (?, ?, ?, ?, ?, 1, NOW())
    ");
    
    $result = $insert_stmt->execute([
        $admin_username,
        strtolower($admin_email),
        $password_hash,
        $admin_name,
        $admin_role
    ]);
    
    if ($result) {
        $new_admin_id = $pdo->lastInsertId();
        
        echo "<span style='color: green;'><strong>✓ SUCCESS!</strong> Admin account created successfully</span><br><br>";
        echo "======================================== <strong>Admin Account Details</strong> ========================================<br>";
        echo "<table style='border-collapse: collapse; width: 100%; margin-top: 10px;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<td style='border: 1px solid #ddd; padding: 10px; font-weight: bold;'>Admin ID</td>";
        echo "<td style='border: 1px solid #ddd; padding: 10px;'>" . htmlspecialchars($new_admin_id) . "</td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td style='border: 1px solid #ddd; padding: 10px; font-weight: bold;'>Name</td>";
        echo "<td style='border: 1px solid #ddd; padding: 10px;'>" . htmlspecialchars($admin_name) . "</td>";
        echo "</tr>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<td style='border: 1px solid #ddd; padding: 10px; font-weight: bold;'>Username</td>";
        echo "<td style='border: 1px solid #ddd; padding: 10px;'>" . htmlspecialchars($admin_username) . "</td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td style='border: 1px solid #ddd; padding: 10px; font-weight: bold;'>Email</td>";
        echo "<td style='border: 1px solid #ddd; padding: 10px;'>" . htmlspecialchars($admin_email) . "</td>";
        echo "</tr>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<td style='border: 1px solid #ddd; padding: 10px; font-weight: bold;'>Password</td>";
        echo "<td style='border: 1px solid #ddd; padding: 10px;'><code>IT46Scancare</code> (hashed and stored securely)</td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td style='border: 1px solid #ddd; padding: 10px; font-weight: bold;'>Role</td>";
        echo "<td style='border: 1px solid #ddd; padding: 10px;'>" . htmlspecialchars($admin_role) . "</td>";
        echo "</tr>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<td style='border: 1px solid #ddd; padding: 10px; font-weight: bold;'>Status</td>";
        echo "<td style='border: 1px solid #ddd; padding: 10px;'><span style='color: green;'>Active</span></td>";
        echo "</tr>";
        echo "</table>";
        
        echo "<br><br>";
        echo "======================================== <strong>Login Instructions</strong> ========================================<br>";
        echo "<div style='background-color: #e8f5e9; border: 1px solid #4caf50; padding: 15px; border-radius: 5px; margin-top: 10px;'>";
        echo "<p><strong>1. Go to the Admin Login Portal:</strong></p>";
        echo "<p style='font-family: monospace; background-color: #f5f5f5; padding: 10px; border-radius: 3px;'>";
        echo htmlspecialchars(basename($_SERVER['REQUEST_URI'], '.php') === 'create_admin_account' ? 'http:
        echo "</p>";
        
        echo "<p><strong>2. Login with these credentials:</strong></p>";
        echo "<ul>";
        echo "<li><strong>Username:</strong> " . htmlspecialchars($admin_username) . "</li>";
        echo "<li><strong>Password:</strong> IT46Scancare</li>";
        echo "</ul>";
        
        echo "<p><strong>3. After successful login:</strong></p>";
        echo "<ul>";
        echo "<li>You will be redirected to the Administrator Panel</li>";
        echo "<li>You can manage residents and staff accounts</li>";
        echo "<li>View system statistics and activity logs</li>";
        echo "<li>Manage user accounts and system settings</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<br><br>";
        echo "======================================== <strong>IMPORTANT SECURITY NOTES</strong> ========================================<br>";
        echo "<div style='background-color: #fff3e0; border: 1px solid #ff9800; padding: 15px; border-radius: 5px; margin-top: 10px;'>";
        echo "<p><strong>⚠️ After Setup:</strong></p>";
        echo "<ol>";
        echo "<li><strong>Delete this file or change its access permissions</strong> - This script should only be run once. For security, remove or restrict access to this file after account creation.</li>";
        echo "<li><strong>Use a strong password in production</strong> - The current password should be changed to something more secure after first login.</li>";
        echo "<li><strong>Enable two-factor authentication</strong> - Consider implementing 2FA for admin accounts (future enhancement).</li>";
        echo "<li><strong>Review login logs</strong> - Monitor admin login attempts for suspicious activity.</li>";
        echo "</ol>";
        echo "</div>";
        
        echo "<br><br>";
        echo "======================================== <strong>Setup Complete</strong> ========================================<br>";
        echo "<div style='background-color: #f3e5f5; border: 1px solid #9c27b0; padding: 15px; border-radius: 5px; margin-top: 10px;'>";
        echo "<p>Your admin account has been successfully created and is ready to use!</p>";
        echo "<p><strong>Next Steps:</strong></p>";
        echo "<ul>";
        echo "<li>1. Log in to the admin portal at <code>admin_login.php</code></li>";
        echo "<li>2. Manage resident and staff accounts</li>";
        echo "<li>3. Monitor system activity and logs</li>";
        echo "<li>4. Delete this setup file for security</li>";
        echo "</ul>";
        echo "</div>";
        
    } else {
        echo "<span style='color: red;'><strong>❌ ERROR:</strong> Failed to create admin account</span><br>";
        echo "Error Details: " . json_encode($insert_stmt->errorInfo());
    }
    
} catch (Exception $e) {
    echo "<span style='color: red;'><strong>❌ ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "</span><br>";
    error_log("Admin Account Setup Error: " . $e->getMessage());
}
?>

