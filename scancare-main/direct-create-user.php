<?php
require_once 'db_config.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $role = $_POST['role'] ?? 'resident';

    if (!$email || !$password || !$first_name) {
        $message = 'All fields are required';
        $message_type = 'error';
    } else {
        try {
            $pdo = getDatabaseConnection();
            
            
            $pdo->prepare("DELETE FROM users WHERE email = ?")->execute([$email]);
            
            
            $hash = password_hash($password, PASSWORD_BCRYPT);
            
            
            $pdo->prepare("
                INSERT INTO users (email, password_hash, first_name, last_name, user_role, is_active) 
                VALUES (?, ?, ?, ?, ?, 1)
            ")->execute([$email, $hash, $first_name, $last_name, $role]);
            
            $message = "✓ User '{$email}' created! Go to login.php and use: {$email} / {$password}";
            $message_type = 'success';
            
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Direct User Creator</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #f0f0f0;
            padding: 20px;
        }
        .container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #454B1B; margin-bottom: 20px; }
        .message {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #454B1B;
            background: #fffaf0;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #454B1B;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
        }
        button:hover {
            background: #363A15;
        }
        .note {
            background: #e7f3ff;
            border-left: 4px solid #0284C7;
            padding: 10px;
            margin-top: 20px;
            font-size: 12px;
            color: #0C4A8E;
        }
        a {
            color: #454B1B;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>⚙️ Direct User Creator</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php if ($message_type === 'success'): ?>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="login.php" style="display: inline-block; background: #454B1B; color: white; padding: 10px 30px; border-radius: 4px;">
                        Go to Login →
                    </a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" placeholder="gabrieltala021@gmail.com" required>
                </div>

                <div class="form-group">
                    <label>Password *</label>
                    <input type="text" name="password" placeholder="test123" value="test123" required>
                </div>

                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" placeholder="Gabriel" value="Gabriel" required>
                </div>

                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" placeholder="Tala" value="Tala">
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <select name="role">
                        <option value="resident">Resident</option>
                        <option value="bhw">BHW</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <button type="submit">✓ Create User Now</button>
            </form>

            <div class="note">
                <strong>How it works:</strong><br>
                1. Fill the form<br>
                2. Click "Create User Now"<br>
                3. If user exists, it will be replaced<br>
                4. Use the email & password to login<br>
                <strong>Default:</strong> gabrieltala021@gmail.com / test123
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

