<?php
require_once 'db_config.php';

echo "<h2>🔍 Login Test & Debug</h2>";
echo "<hr>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!$email || !$password) {
        echo "<p style='color:red;'>Please enter both email and password</p>";
    } else {
        try {
            $pdo = getDatabaseConnection();
            
            
            $stmt = $pdo->prepare("SELECT user_id, first_name, last_name, user_role, password_hash FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            echo "<h3>Results for: <strong>$email</strong></h3>";
            
            if (!$user) {
                echo "<p style='color:red;'><strong>❌ User NOT found</strong></p>";
            } else {
                echo "<p style='color:green;'><strong>✓ User found in database</strong></p>";
                echo "<ul>";
                echo "<li>Name: {$user['first_name']} {$user['last_name']}</li>";
                echo "<li>Role: {$user['user_role']}</li>";
                echo "<li>Password Hash: " . substr($user['password_hash'], 0, 30) . "...</li>";
                echo "</ul>";
                
                
                echo "<h4>Testing your password:</h4>";
                $is_valid = password_verify($password, $user['password_hash']);
                
                if ($is_valid) {
                    echo "<p style='color:green; font-weight:bold;'>✓✓✓ PASSWORD IS CORRECT ✓✓✓</p>";
                    echo "<p>You should be able to login successfully!</p>";
                } else {
                    echo "<p style='color:red; font-weight:bold;'>❌ PASSWORD IS WRONG ❌</p>";
                    echo "<p>The password you entered does not match what's in the database.</p>";
                }
            }
            
        } catch (Exception $e) {
            echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
        }
    }
} else {
?>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 30px auto; padding: 20px; }
        form { background: #f5f5f5; padding: 20px; border-radius: 8px; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        button { background: #454B1B; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        button:hover { background: #363A15; }
        hr { margin: 20px 0; border: none; border-top: 1px solid #ddd; }
    </style>
    
    <form method="POST">
        <p><strong>Test your login credentials:</strong></p>
        
        <label>Email:</label>
        <input type="email" name="email" placeholder="gabrieltala021@gmail.com" required>
        
        <label>Password:</label>
        <input type="password" name="password" placeholder="Your password" required>
        
        <button type="submit">Test Login</button>
    </form>
<?php
}
?>

