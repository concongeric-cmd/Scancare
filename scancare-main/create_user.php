<!DOCTYPE html>
<html>
<head>
    <title>Create User Account</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 { color: #454B1B; margin-bottom: 20px; }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        input:focus {
            outline: none;
            border-color: #454B1B;
            background: #fffaf0;
        }
        button {
            background: #454B1B;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            width: 100%;
        }
        button:hover {
            background: #363A15;
        }
        .success {
            background: #DCFCE7;
            color: #166534;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #16A34A;
        }
        .error {
            background: #FEE2E2;
            color: #991B1B;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #DC2626;
        }
        .info {
            background: #DBEAFE;
            color: #0C2340;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #0284C7;
        }
        .field-desc {
            font-size: 12px;
            color: #999;
            margin-top: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>➕ Create New User Account</h1>
        
        <div class="info">
            <strong>This tool adds a new user to the database.</strong><br>
            Fill in your details below and click "Create Account".
        </div>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" required placeholder="example: gabrieltala021@gmail.com">
                <div class="field-desc">This will be your login email</div>
            </div>

            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" required placeholder="At least 6 characters">
                <div class="field-desc">Choose a strong password</div>
            </div>

            <div class="form-group">
                <label for="first_name">First Name *</label>
                <input type="text" id="first_name" name="first_name" required placeholder="e.g., Gabriel">
            </div>

            <div class="form-group">
                <label for="last_name">Last Name *</label>
                <input type="text" id="last_name" name="last_name" required placeholder="e.g., Tala">
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="text" id="phone" name="phone" placeholder="e.g., 09171234567">
            </div>

            <div class="form-group">
                <label for="role">Account Type *</label>
                <select id="role" name="role" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="resident">Resident (Patient)</option>
                    <option value="bhw">BHW (Health Worker)</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>

            <button type="submit">✓ Create Account</button>
        </form>

        <?php
        require_once 'db_config.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $role = $_POST['role'] ?? 'resident';

            
            $errors = [];

            if (empty($email)) {
                $errors[] = "Email is required";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Email is not valid";
            }

            if (empty($password)) {
                $errors[] = "Password is required";
            } elseif (strlen($password) < 6) {
                $errors[] = "Password must be at least 6 characters";
            }

            if (empty($first_name)) {
                $errors[] = "First name is required";
            }

            if (empty($last_name)) {
                $errors[] = "Last name is required";
            }

            if (!in_array($role, ['resident', 'bhw', 'admin'])) {
                $errors[] = "Invalid role selected";
            }

            if (count($errors) > 0) {
                echo "<div class='error'>";
                echo "<strong>❌ Errors:</strong><br>";
                foreach ($errors as $error) {
                    echo "• $error<br>";
                }
                echo "</div>";
            } else {
                
                try {
                    $pdo = getDatabaseConnection();
                    
                    if ($pdo === false) {
                        throw new Exception('Database connection failed');
                    }

                    
                    $check_query = "SELECT user_id FROM users WHERE email = ?";
                    $check_stmt = $pdo->prepare($check_query);
                    $check_stmt->execute([$email]);

                    if ($check_stmt->rowCount() > 0) {
                        echo "<div class='error'>";
                        echo "<strong>❌ Error:</strong> This email already exists in the database!";
                        echo "</div>";
                    } else {
                        
                        $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

                        
                        $insert_query = "INSERT INTO users 
                                        (email, password_hash, first_name, last_name, phone_number, user_role, is_active) 
                                        VALUES (?, ?, ?, ?, ?, ?, TRUE)";
                        $insert_stmt = $pdo->prepare($insert_query);
                        $insert_stmt->execute([$email, $password_hash, $first_name, $last_name, $phone, $role]);

                        echo "<div class='success'>";
                        echo "<strong>✓ Account Created Successfully!</strong><br><br>";
                        echo "Email: <code>" . htmlspecialchars($email) . "</code><br>";
                        echo "Name: " . htmlspecialchars($first_name) . " " . htmlspecialchars($last_name) . "<br>";
                        echo "Role: " . htmlspecialchars($role) . "<br><br>";
                        echo "<strong>Next Steps:</strong><br>";
                        echo "1. Go to <a href='login.php' style='color: #0284C7; text-decoration: underline;'>login page</a><br>";
                        echo "2. Login with your email and password<br>";
                        echo "3. Check if your login is recorded!";
                        echo "</div>";
                    }

                } catch (Exception $e) {
                    echo "<div class='error'>";
                    echo "<strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage());
                    echo "</div>";
                }
            }
        }
        ?>

        <hr style="margin-top: 30px; margin-bottom: 30px; border: none; border-top: 1px solid #ddd;">

        <h2 style="color: #454B1B; font-size: 16px;">📖 How This Works</h2>
        <ol style="font-size: 14px; line-height: 1.8; color: #555;">
            <li>Fill in your details (email, password, name)</li>
            <li>Click "Create Account"</li>
            <li>Your account is added to the database</li>
            <li>You can then login with your email and password</li>
            <li>Your login will be recorded in activity_logs ✓</li>
        </ol>

        <hr style="margin-top: 30px; margin-bottom: 30px; border: none; border-top: 1px solid #ddd;">

        <h2 style="color: #454B1B; font-size: 16px;">🔗 Quick Links</h2>
        <ul style="font-size: 14px; line-height: 1.8;">
            <li><a href="login.php">Login Page</a></li>
            <li><a href="create_user.php">Create Another Account</a></li>
        </ul>
    </div>
</body>
</html>

