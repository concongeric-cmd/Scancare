<?php


require_once 'db_config.php';

$accounts = [
    [
        'email' => 'resident1@gmail.com',
        'password' => 'TestPass123',
        'first_name' => 'Maria',
        'middle_initial' => 'A',
        'last_name' => 'Santos',
        'date_of_birth' => '1985-03-15',
        'address' => '123 Main Street, Barangay Gutad',
        'phone_number' => '09171234567',
        'user_role' => 'resident'
    ],
    [
        'email' => 'resident2@gmail.com',
        'password' => 'TestPass123',
        'first_name' => 'Juan',
        'middle_initial' => 'C',
        'last_name' => 'Dela Cruz',
        'date_of_birth' => '1990-07-22',
        'address' => '456 Oak Avenue, Barangay Gutad',
        'phone_number' => '09175555555',
        'user_role' => 'resident'
    ],
    [
        'email' => 'resident3@gmail.com',
        'password' => 'TestPass123',
        'first_name' => 'Ana',
        'middle_initial' => 'M',
        'last_name' => 'Rodriguez',
        'date_of_birth' => '1995-11-08',
        'address' => '789 Pine Road, Barangay Gutad',
        'phone_number' => '09179999999',
        'user_role' => 'resident'
    ],
    [
        'email' => 'bhw@gmail.com',
        'password' => 'TestPass123',
        'first_name' => 'Ricardo',
        'middle_initial' => 'B',
        'last_name' => 'Reyes',
        'date_of_birth' => '1988-05-30',
        'address' => '321 Health Center, Barangay Gutad',
        'phone_number' => '09176666666',
        'user_role' => 'bhw'
    ],
    [
        'email' => 'admin@gmail.com',
        'password' => 'AdminPass123',
        'first_name' => 'Admin',
        'middle_initial' => 'G',
        'last_name' => 'Manager',
        'date_of_birth' => '1980-01-01',
        'address' => '100 Admin Building, Barangay Gutad',
        'phone_number' => '09178888888',
        'user_role' => 'admin'
    ]
];

try {
    $pdo = getDatabaseConnection();
    
    if ($pdo === false) {
        die("❌ Database connection failed!");
    }
    
    echo "<div style='font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5;'>";
    echo "<h2>🔧 ScanCare Test Accounts Creator</h2>";
    echo "<hr>";
    
    
    echo "<h3 style='color: #3498db;'>📋 Step 1: Checking Database Schema</h3>";
    
    try {
        
        $check_columns = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'users' AND COLUMN_NAME = 'date_of_birth'");
        $check_columns->execute();
        
        if ($check_columns->rowCount() === 0) {
            echo "<p style='color: #f39c12; padding: 10px; background: #fff3cd; border-radius: 4px;'>⏳ Adding missing column: date_of_birth...</p>";
            $pdo->exec("ALTER TABLE users ADD COLUMN date_of_birth DATE NULL AFTER last_name");
            echo "<p style='color: #27ae60; padding: 10px; background: #d4edda; border-radius: 4px;'>✅ Added: date_of_birth</p>";
        } else {
            echo "<p style='color: #27ae60; padding: 10px; background: #d4edda; border-radius: 4px;'>✅ Column exists: date_of_birth</p>";
        }
        
        
        $check_columns = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'users' AND COLUMN_NAME = 'address'");
        $check_columns->execute();
        
        if ($check_columns->rowCount() === 0) {
            echo "<p style='color: #f39c12; padding: 10px; background: #fff3cd; border-radius: 4px;'>⏳ Adding missing column: address...</p>";
            $pdo->exec("ALTER TABLE users ADD COLUMN address VARCHAR(255) NULL AFTER date_of_birth");
            echo "<p style='color: #27ae60; padding: 10px; background: #d4edda; border-radius: 4px;'>✅ Added: address</p>";
        } else {
            echo "<p style='color: #27ae60; padding: 10px; background: #d4edda; border-radius: 4px;'>✅ Column exists: address</p>";
        }
        
        
        $check_columns = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'users' AND COLUMN_NAME = 'middle_initial'");
        $check_columns->execute();
        
        if ($check_columns->rowCount() === 0) {
            echo "<p style='color: #f39c12; padding: 10px; background: #fff3cd; border-radius: 4px;'>⏳ Adding missing column: middle_initial...</p>";
            $pdo->exec("ALTER TABLE users ADD COLUMN middle_initial VARCHAR(1) NULL AFTER first_name");
            echo "<p style='color: #27ae60; padding: 10px; background: #d4edda; border-radius: 4px;'>✅ Added: middle_initial</p>";
        } else {
            echo "<p style='color: #27ae60; padding: 10px; background: #d4edda; border-radius: 4px;'>✅ Column exists: middle_initial</p>";
        }
        
        
        $check_columns = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'users' AND COLUMN_NAME = 'last_logout'");
        $check_columns->execute();
        
        if ($check_columns->rowCount() === 0) {
            echo "<p style='color: #f39c12; padding: 10px; background: #fff3cd; border-radius: 4px;'>⏳ Adding missing column: last_logout...</p>";
            $pdo->exec("ALTER TABLE users ADD COLUMN last_logout TIMESTAMP NULL AFTER last_login");
            echo "<p style='color: #27ae60; padding: 10px; background: #d4edda; border-radius: 4px;'>✅ Added: last_logout</p>";
        } else {
            echo "<p style='color: #27ae60; padding: 10px; background: #d4edda; border-radius: 4px;'>✅ Column exists: last_logout</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: #f39c12; padding: 10px; background: #fff3cd; border-radius: 4px;'>⚠️ Schema check: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "<hr>";
    echo "<h3 style='color: #3498db;'>👥 Step 2: Creating Test Accounts</h3>";
    
    $created_count = 0;
    $failed_count = 0;
    
    foreach ($accounts as $index => $account) {
        try {
            
            $email_lower = strtolower(trim($account['email']));
            
            
            $check = $pdo->prepare("SELECT user_id FROM users WHERE LOWER(email) = ?");
            $check->execute([$email_lower]);
            
            if ($check->rowCount() > 0) {
                echo "<p style='color: #ff6600; padding: 10px; background: #fff3cd; border-radius: 4px;'>";
                echo "⚠️ Account #{" . ($index + 1) . "}: <strong>" . htmlspecialchars($email_lower) . "</strong> already exists. Skipped.";
                echo "</p>";
                continue;
            }
            
            
            $password_hash = password_hash($account['password'], PASSWORD_BCRYPT, ['cost' => 10]);
            
            
            $stmt = $pdo->prepare("
                INSERT INTO users (
                    email, password_hash, first_name, middle_initial, last_name, 
                    date_of_birth, address, phone_number, user_role, is_active, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, TRUE, NOW())
            ");
            
            $success = $stmt->execute([
                $email_lower,
                $password_hash,
                $account['first_name'],
                $account['middle_initial'],
                $account['last_name'],
                $account['date_of_birth'],
                $account['address'],
                $account['phone_number'],
                $account['user_role']
            ]);
            
            if ($success) {
                $created_count++;
                $role_badge = match($account['user_role']) {
                    'resident' => '👤 Resident',
                    'bhw' => '⚕️ BHW',
                    'admin' => '👨‍💼 Admin',
                    default => $account['user_role']
                };
                
                echo "<p style='color: #27ae60; padding: 10px; background: #d4edda; border-radius: 4px;'>";
                echo "✅ Account #{" . ($index + 1) . "} Created:<br>";
                echo "<strong>Email:</strong> " . htmlspecialchars($email_lower) . "<br>";
                echo "<strong>Password:</strong> <code>" . htmlspecialchars($account['password']) . "</code><br>";
                echo "<strong>Name:</strong> " . htmlspecialchars($account['first_name'] . ' ' . $account['last_name']) . "<br>";
                echo "<strong>Role:</strong> " . $role_badge;
                echo "</p>";
            } else {
                $failed_count++;
                echo "<p style='color: #c0392b; padding: 10px; background: #f8d7da; border-radius: 4px;'>";
                echo "❌ Account #{" . ($index + 1) . "} Failed: " . htmlspecialchars($account['email']);
                echo "</p>";
            }
        } catch (Exception $e) {
            $failed_count++;
            echo "<p style='color: #c0392b; padding: 10px; background: #f8d7da; border-radius: 4px;'>";
            echo "❌ Account #{" . ($index + 1) . "} Error: " . htmlspecialchars($e->getMessage());
            echo "</p>";
        }
    }
    
    echo "<hr>";
    echo "<h3 style='color: #2c3e50;'>📊 Summary</h3>";
    echo "<p><strong>✅ Created:</strong> " . $created_count . " accounts</p>";
    echo "<p><strong>⚠️ Failed/Skipped:</strong> " . $failed_count . " accounts</p>";
    echo "<p><strong>Total:</strong> " . ($created_count + $failed_count) . " accounts</p>";
    
    echo "<hr>";
    echo "<h3 style='color: #2c3e50;'>🔗 Quick Links</h3>";
    echo "<ul>";
    echo "<li><a href='login.php' style='color: #3498db; text-decoration: none;'>Go to Login →</a></li>";
    echo "<li><a href='http:
    echo "</ul>";
    
    echo "<hr>";
    echo "<p style='color: #7f8c8d; font-size: 12px;'>";
    echo "💡 <strong>Tip:</strong> Delete this file (CREATE_TEST_ACCOUNTS.php) after testing for security!<br>";
    echo "📝 <strong>Test Accounts:</strong>";
    echo "<ul style='font-size: 12px;'>";
    echo "<li>👤 Resident 1: resident1@gmail.com / TestPass123</li>";
    echo "<li>👤 Resident 2: resident2@gmail.com / TestPass123</li>";
    echo "<li>👤 Resident 3: resident3@gmail.com / TestPass123</li>";
    echo "<li>⚕️ BHW: bhw@gmail.com / TestPass123</li>";
    echo "<li>👨‍💼 Admin: admin@gmail.com / AdminPass123</li>";
    echo "</ul>";
    echo "</p>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='font-family: Arial, sans-serif; padding: 20px;'>";
    echo "<h2 style='color: #c0392b;'>❌ Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>


