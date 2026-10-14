<?php


require_once 'db_config.php';

header('Content-Type: text/html; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $admin_email = $_POST['admin_email'] ?? 'adminscancare@gmail.com';
    
    if ($action === 'reset_password' && !empty($new_password)) {
        try {
            $pdo = getDatabaseConnection();
            
            
            $password_hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 10]);
            
            
            $stmt = $pdo->prepare("UPDATE system_admins SET password_hash = ? WHERE email = ?");
            $stmt->execute([$password_hash, $admin_email]);
            
            echo "<div style='color: green; background: #e6ffe6; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "<strong>✓ SUCCESS!</strong> Password has been reset for: <strong>$admin_email</strong><br>";
            echo "New Password: <strong>$new_password</strong><br>";
            echo "Password Hash: <code>" . substr($password_hash, 0, 50) . "...</code><br>";
            echo "<br>You can now login with:<br>";
            echo "Email: <strong>$admin_email</strong><br>";
            echo "Password: <strong>$new_password</strong>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div style='color: red; background: #ffe6e6; padding: 15px; border-radius: 5px;'>";
            echo "ERROR: " . $e->getMessage();
            echo "</div>";
        }
    }
}


try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->query("SELECT admin_id, username, email, full_name FROM system_admins");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $admins = [];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Password Reset</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        form { margin-top: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 14px; }
        input:focus { outline: none; border-color: #454B1B; box-shadow: 0 0 5px rgba(69,75,27,0.3); }
        button { background: #454B1B; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; width: 100%; font-size: 16px; }
        button:hover { opacity: 0.9; }
        .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        code { background: #f5f5f5; padding: 2px 5px; border-radius: 3px; font-family: monospace; }
        .admin-list { background: #f9f9f9; padding: 15px; border-radius: 4px; margin-top: 20px; }
        .admin-list h3 { margin-top: 0; }
        .admin-item { padding: 10px; background: white; margin-bottom: 10px; border-radius: 3px; border-left: 3px solid #454B1B; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Admin Password Reset</h1>
        
        <div class="warning">
            <strong>⚠️ WARNING:</strong> This will reset the password for the selected admin account. Make sure you remember the new password!
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="reset_password">
            
            <div class="form-group">
                <label for="admin_email">Select Admin Account:</label>
                <select name="admin_email" id="admin_email" required>
                    <?php foreach ($admins as $admin): ?>
                        <option value="<?php echo $admin['email']; ?>">
                            <?php echo $admin['full_name']; ?> (<?php echo $admin['email']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="text" id="new_password" name="new_password" placeholder="Enter new password" required>
                <small style="color: #888;">Tip: Use something simple like: <code>admin123</code> or <code>Password123</code></small>
            </div>
            
            <button type="submit">Reset Password</button>
        </form>
        
        <?php if (count($admins) > 0): ?>
        <div class="admin-list">
            <h3>📋 Available Admin Accounts:</h3>
            <?php foreach ($admins as $admin): ?>
                <div class="admin-item">
                    <strong><?php echo $admin['full_name']; ?></strong><br>
                    Email: <code><?php echo $admin['email']; ?></code><br>
                    Username: <code><?php echo $admin['username']; ?></code>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>

