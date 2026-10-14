<?php


require_once 'db_config.php';

$pdo = getDatabaseConnection();
$created = 0;
$skipped = 0;
$errors = [];


$first_names_admin = ['John', 'Maria', 'Robert', 'Patricia', 'Michael'];
$first_names_bhw = ['Sarah', 'James', 'Jennifer', 'David', 'Lisa'];
$first_names_resident = ['Carlos', 'Anna', 'Jose', 'Maria', 'Antonio'];

$last_names = ['Santos', 'Garcia', 'Rodriguez', 'Martinez', 'Lopez', 'Gonzalez', 'Hernandez', 'Perez', 'Torres', 'Flores'];

$barangay_address = 'Barangay Gutad, Quezon City, Philippines';

function generateEmail($first, $last, $index, $role) {
    return strtolower($first) . '.' . strtolower($last) . $index . '@' . $role . '.local';
}

function createAccount($pdo, $email, $password, $first_name, $last_name, $role, &$created, &$skipped, &$errors) {
    
    $check_stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $check_stmt->execute([$email]);
    
    if ($check_stmt->rowCount() > 0) {
        $skipped++;
        return false;
    }
    
    
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO users (
                email, password_hash, first_name, last_name, middle_initial, 
                user_role, is_active, phone_number, address, date_of_birth,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $email,
            $hashed_password,
            $first_name,
            $last_name,
            strtoupper($last_name[0]),
            $role,
            true,
            '555-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT),
            'Barangay Gutad, Quezon City, Philippines',
            date('Y-m-d', strtotime('-' . rand(20, 70) . ' years')),
            date('Y-m-d H:i:s')
        ]);
        
        $created++;
        return true;
    } catch (Exception $e) {
        $skipped++;
        $errors[] = "Failed to create $email: " . $e->getMessage();
        return false;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create More Test Accounts — ScanCare</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            background: #F8FAF6;
            color: #1e293b;
        }
        .container {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 30px;
        }
        h2 { color: #454B1B; margin-bottom: 20px; }
        h3 { color: #454B1B; margin-top: 25px; margin-bottom: 15px; font-size: 1.2rem; }
        .success { color: #166534; background: #dcfce7; padding: 10px; margin: 8px 0; border-radius: 6px; }
        .skip { color: #92400e; background: #fef3c7; padding: 10px; margin: 8px 0; border-radius: 6px; }
        .error { color: #991b1b; background: #fee2e2; padding: 10px; margin: 8px 0; border-radius: 6px; }
        .summary { 
            background: #f8fafc; 
            border-left: 4px solid #454B1B;
            padding: 15px; 
            margin: 20px 0; 
            border-radius: 6px;
        }
        .summary p { margin: 5px 0; }
        .credentials {
            background: #f0f9ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 12px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        hr { border: none; border-top: 1px solid #e2e8f0; margin: 20px 0; }
        button {
            background: #454B1B;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            margin-top: 10px;
        }
        button:hover { background: #2a2d0f; }
        .accounts-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .accounts-table th, .accounts-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        .accounts-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #454B1B;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🆕 Create Additional Test Accounts</h2>
        <p>This tool will create 5 additional accounts for each role (Admin, BHW, Resident)</p>
        <hr>

        <?php
        
        echo "<h3>👨‍💼 Creating Admin Accounts (5)</h3>";
        $admin_accounts = [];
        for ($i = 4; $i < 9; $i++) {
            $first = $first_names_admin[$i % 5];
            $last = $last_names[$i % 10];
            $email = generateEmail($first, $last, $i, 'admin');
            $password = 'AdminPass123!';
            
            if (createAccount($pdo, $email, $password, $first, $last, 'admin', $created, $skipped, $errors)) {
                echo "<div class='success'>✅ Created: <strong>$email</strong></div>";
                $admin_accounts[] = ['email' => $email, 'password' => $password, 'name' => "$first $last"];
            } else {
                echo "<div class='skip'>⚠️ Skipped: $email (already exists)</div>";
            }
        }

        
        echo "<h3>⚕️ Creating BHW Accounts (5)</h3>";
        $bhw_accounts = [];
        for ($i = 5; $i < 10; $i++) {
            $first = $first_names_bhw[$i % 5];
            $last = $last_names[$i % 10];
            $email = generateEmail($first, $last, $i, 'bhw');
            $password = 'BHWPass123!';
            
            if (createAccount($pdo, $email, $password, $first, $last, 'bhw', $created, $skipped, $errors)) {
                echo "<div class='success'>✅ Created: <strong>$email</strong></div>";
                $bhw_accounts[] = ['email' => $email, 'password' => $password, 'name' => "$first $last"];
            } else {
                echo "<div class='skip'>⚠️ Skipped: $email (already exists)</div>";
            }
        }

        
        echo "<h3>👤 Creating Resident Accounts (5)</h3>";
        $resident_accounts = [];
        for ($i = 3; $i < 8; $i++) {
            $first = $first_names_resident[$i % 5];
            $last = $last_names[$i % 10];
            $email = generateEmail($first, $last, $i, 'resident');
            $password = 'ResPass123!';
            
            if (createAccount($pdo, $email, $password, $first, $last, 'resident', $created, $skipped, $errors)) {
                echo "<div class='success'>✅ Created: <strong>$email</strong></div>";
                $resident_accounts[] = ['email' => $email, 'password' => $password, 'name' => "$first $last"];
            } else {
                echo "<div class='skip'>⚠️ Skipped: $email (already exists)</div>";
            }
        }

        echo "<hr>";
        echo "<div class='summary'>";
        echo "<p><strong>✅ Accounts Created:</strong> $created</p>";
        echo "<p><strong>⚠️ Skipped:</strong> $skipped</p>";
        echo "<p><strong>Total Processed:</strong> " . ($created + $skipped) . "</p>";
        if (!empty($errors)) {
            echo "<p><strong>❌ Errors:</strong></p>";
            foreach ($errors as $error) {
                echo "<div class='error'>$error</div>";
            }
        }
        echo "</div>";

        
        echo "<h3>📋 New Account Credentials</h3>";
        
        if (!empty($admin_accounts)) {
            echo "<h4 style='color: #454B1B; margin-top: 15px;'>Admin Accounts:</h4>";
            foreach ($admin_accounts as $acc) {
                echo "<div class='credentials'>Email: {$acc['email']}<br>Password: {$acc['password']}<br>Name: {$acc['name']}</div>";
            }
        }
        
        if (!empty($bhw_accounts)) {
            echo "<h4 style='color: #454B1B; margin-top: 15px;'>BHW Accounts:</h4>";
            foreach ($bhw_accounts as $acc) {
                echo "<div class='credentials'>Email: {$acc['email']}<br>Password: {$acc['password']}<br>Name: {$acc['name']}</div>";
            }
        }
        
        if (!empty($resident_accounts)) {
            echo "<h4 style='color: #454B1B; margin-top: 15px;'>Resident Accounts:</h4>";
            foreach ($resident_accounts as $acc) {
                echo "<div class='credentials'>Email: {$acc['email']}<br>Password: {$acc['password']}<br>Name: {$acc['name']}</div>";
            }
        }
        
        echo "<hr>";
        echo "<p>📝 <strong>All passwords are secure and hashed in the database.</strong></p>";
        echo "<p><a href='login.php' style='color: #454B1B; text-decoration: none; font-weight: 600;'>→ Go to Login</a></p>";
        ?>
    </div>
</body>
</html>

