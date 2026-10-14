<?php


require_once 'db_config.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $user_role = $_POST['user_role'] ?? 'resident';

    
    if (empty($full_name) || empty($email) || empty($password)) {
        $error_message = '❌ All fields are required!';
    } elseif (strlen($password) < 6) {
        $error_message = '❌ Password must be at least 6 characters!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = '❌ Invalid email format!';
    } else {
        try {
            $pdo = getDatabaseConnection();
            
            if ($pdo === false) {
                throw new Exception('Could not connect to database');
            }

            
            $check_stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $check_stmt->execute([$email]);

            if ($check_stmt->rowCount() > 0) {
                $error_message = '❌ Email already exists! Use a different email.';
            } else {
                
                $password_hash = password_hash($password, PASSWORD_BCRYPT);

                
                $name_parts = explode(' ', $full_name, 2);
                $first_name = $name_parts[0];
                $last_name = isset($name_parts[1]) ? $name_parts[1] : '';

                
                $insert_stmt = $pdo->prepare("
                    INSERT INTO users (email, password_hash, first_name, last_name, user_role, is_active) 
                    VALUES (?, ?, ?, ?, ?, 1)
                ");
                
                if ($insert_stmt->execute([$email, $password_hash, $first_name, $last_name, $user_role])) {
                    $success_message = "✓ User created successfully! You can now login.";
                } else {
                    $error_message = '❌ Failed to create user. Please try again.';
                }
            }

        } catch (Exception $e) {
            $error_message = '❌ Error: ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quick User Creator - ScanCare</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #454B1B 0%, #6B7D1F 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            padding: 40px;
        }

        h1 {
            color: #454B1B;
            margin-bottom: 10px;
            font-size: 24px;
        }

        .subtitle {
            color: #666;
            font-size: 13px;
            margin-bottom: 25px;
        }

        .message {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .success {
            background: #DCFCE7;
            color: #166534;
            border-left: 4px solid #16A34A;
        }

        .error {
            background: #FEE2E2;
            color: #991B1B;
            border-left: 4px solid #DC2626;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 6px;
            font-size: 14px;
        }

        input, select {
            width: 100%;
            padding: 10px 12px;
            border: 1.5px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: inherit;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #454B1B;
            background: #fffaf0;
            box-shadow: 0 0 0 3px rgba(69, 75, 27, 0.1);
        }

        button {
            width: 100%;
            padding: 11px;
            background: #454B1B;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 5px;
        }

        button:hover {
            background: #363A15;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(69, 75, 27, 0.3);
        }

        button:active {
            transform: translateY(0);
        }

        .quick-links {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
        }

        .quick-links a {
            display: inline-block;
            color: #454B1B;
            text-decoration: none;
            font-size: 13px;
            margin: 0 8px;
            transition: color 0.3s;
        }

        .quick-links a:hover {
            color: #6B7D1F;
        }

        .note {
            background: #DBEAFE;
            border-left: 4px solid #0284C7;
            padding: 10px 12px;
            border-radius: 4px;
            font-size: 12px;
            color: #0C4A8E;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>⚡ Quick User Creator</h1>
        <p class="subtitle">Create a test user and login immediately</p>

        <?php if ($success_message): ?>
            <div class="message success">
                <?php echo $success_message; ?>
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <a href="login.php" style="display: inline-block; background: #454B1B; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: 600;">
                    Go to Login Page →
                </a>
            </div>
        <?php else: ?>
            <?php if ($error_message): ?>
                <div class="message error">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" placeholder="e.g., John Doe" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="e.g., test@example.com" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Min 6 characters" required>
                </div>

                <div class="form-group">
                    <label for="user_role">Account Type</label>
                    <select id="user_role" name="user_role" required>
                        <option value="resident">👤 Resident (Patient)</option>
                        <option value="bhw">👨‍⚕️ BHW (Health Worker)</option>
                        <option value="admin">🔐 Administrator</option>
                    </select>
                </div>

                <button type="submit">✓ Create User</button>
            </form>

            <div class="note">
                <strong>💡 Tip:</strong> No email verification needed! Your account is created instantly and ready to use.
            </div>

            <div class="quick-links">
                <a href="login.php">← Back to Login</a>
                <span style="color: #ccc;">|</span>
                <a href="dashboard.php">Dashboard</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

