<?php


require_once 'db_config.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>ScanCare - Diagnostic Check</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: monospace;
            background: #1e1e1e;
            color: #00ff00;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #0d0d0d;
            border: 2px solid #00ff00;
            padding: 20px;
            border-radius: 5px;
        }
        h1 { color: #ffff00; margin-bottom: 20px; }
        h2 { color: #00ffff; margin-top: 20px; margin-bottom: 10px; border-bottom: 1px solid #00ff00; padding-bottom: 5px; }
        .section { margin: 20px 0; padding: 15px; background: #1a1a1a; border-left: 3px solid #00ff00; }
        .success { border-left-color: #00ff00; }
        .error { border-left-color: #ff0000; color: #ff6666; }
        .info { border-left-color: #00ffff; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 12px;
        }
        th, td {
            border: 1px solid #00ff00;
            padding: 8px;
            text-align: left;
        }
        th { background: #333; color: #ffff00; }
        tr:nth-child(even) { background: #1a1a1a; }
        .status-ok { color: #00ff00; }
        .status-error { color: #ff0000; }
        code { background: #000; padding: 2px 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 SCANCARE DIAGNOSTIC CHECK</h1>
        <p>Checking database connection, structure, and data integrity...</p>

        <?php
        
        echo '<h2>1. Database Connection</h2>';
        $pdo = getDatabaseConnection();
        
        if ($pdo) {
            echo '<div class="section success">';
            echo '<span class="status-ok">✓ Database connection successful</span><br>';
            echo 'Host: ' . DB_HOST . '<br>';
            echo 'Database: ' . DB_NAME . '<br>';
            echo '</div>';
        } else {
            echo '<div class="section error">';
            echo '<span class="status-error">✗ Database connection FAILED</span><br>';
            echo 'Check db_config.php for correct credentials<br>';
            echo '</div>';
            exit;
        }

        
        echo '<h2>2. Users Table Structure</h2>';
        try {
            $stmt = $pdo->prepare("DESCRIBE users");
            $stmt->execute();
            $columns = $stmt->fetchAll();
            
            echo '<div class="section info">';
            echo '<table>';
            echo '<tr><th>Column Name</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>';
            
            foreach ($columns as $col) {
                echo '<tr>';
                echo '<td><code>' . $col['Field'] . '</code></td>';
                echo '<td>' . $col['Type'] . '</td>';
                echo '<td>' . $col['Null'] . '</td>';
                echo '<td>' . $col['Key'] . '</td>';
                echo '<td>' . ($col['Default'] ?? 'NULL') . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
            echo '</div>';
        } catch (Exception $e) {
            echo '<div class="section error">';
            echo '<span class="status-error">✗ Error reading table structure:</span><br>';
            echo htmlspecialchars($e->getMessage()) . '<br>';
            echo '</div>';
        }

        
        echo '<h2>3. Current Users in Database</h2>';
        try {
            $stmt = $pdo->prepare("SELECT user_id, email, first_name, last_name, user_role, is_active, created_at, last_login FROM users ORDER BY created_at DESC");
            $stmt->execute();
            $users = $stmt->fetchAll();
            
            echo '<div class="section info">';
            
            if (empty($users)) {
                echo '<span class="status-error">⚠ No users in database (DATABASE IS EMPTY)</span><br>';
            } else {
                echo '<span class="status-ok">✓ Found ' . count($users) . ' user(s)</span><br><br>';
                
                echo '<table>';
                echo '<tr>';
                echo '<th>ID</th>';
                echo '<th>Email</th>';
                echo '<th>Name</th>';
                echo '<th>Role</th>';
                echo '<th>Active</th>';
                echo '<th>Created At</th>';
                echo '<th>Last Login</th>';
                echo '</tr>';
                
                foreach ($users as $user) {
                    echo '<tr>';
                    echo '<td>' . $user['user_id'] . '</td>';
                    echo '<td><code>' . htmlspecialchars($user['email']) . '</code></td>';
                    echo '<td>' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '</td>';
                    echo '<td>' . $user['user_role'] . '</td>';
                    echo '<td>' . ($user['is_active'] ? '✓ Yes' : '✗ No') . '</td>';
                    echo '<td>' . ($user['created_at'] ?? 'N/A') . '</td>';
                    echo '<td>' . ($user['last_login'] ?? 'N/A') . '</td>';
                    echo '</tr>';
                }
                
                echo '</table>';
            }
            
            echo '</div>';
        } catch (Exception $e) {
            echo '<div class="section error">';
            echo '<span class="status-error">✗ Error reading users:</span><br>';
            echo htmlspecialchars($e->getMessage()) . '<br>';
            echo '</div>';
        }

        
        echo '<h2>4. Recent PHP Errors (last 50 lines)</h2>';
        $error_log = php_ini_scanned_files() ? dirname(php_ini_scanned_files()) . '/error.log' : ini_get('error_log');
        
        try {
            if (file_exists($error_log)) {
                $lines = file($error_log);
                $recent = array_slice($lines, -50);
                
                echo '<div class="section info">';
                echo '<small>Error log location: ' . $error_log . '</small><br><br>';
                echo '<code>';
                foreach ($recent as $line) {
                    
                    if (stripos($line, 'signup') !== false || stripos($line, 'login') !== false || stripos($line, 'db_') !== false) {
                        echo '<span style="background: #ff3333; color: #ffff00;">' . htmlspecialchars($line) . '</span>';
                    } else {
                        echo htmlspecialchars($line);
                    }
                }
                echo '</code>';
                echo '</div>';
            } else {
                echo '<div class="section error">';
                echo '<span class="status-error">✗ Error log not found at: ' . $error_log . '</span><br>';
                echo '</div>';
            }
        } catch (Exception $e) {
            echo '<div class="section error">';
            echo '<span class="status-error">✗ Error reading PHP error log:</span><br>';
            echo htmlspecialchars($e->getMessage()) . '<br>';
            echo '</div>';
        }

        
        echo '<h2>5. Test Quick Registration</h2>';
        echo '<div class="section info">';
        echo '<form method="POST" style="margin: 10px 0;">';
        echo '<input type="email" name="test_email" placeholder="test@example.com" required style="padding: 8px; width: 300px; background: #333; color: #00ff00; border: 1px solid #00ff00;">';
        echo '<button type="submit" name="do_test" style="padding: 8px 20px; background: #00ff00; color: #000; border: none; cursor: pointer;">Test Email Check</button>';
        echo '</form>';
        
        if (isset($_POST['do_test']) && !empty($_POST['test_email'])) {
            $test_email = trim($_POST['test_email']);
            
            try {
                $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
                $check->execute([$test_email]);
                
                if ($check->rowCount() > 0) {
                    echo '<span class="status-error">✗ Email EXISTS in database</span>';
                } else {
                    echo '<span class="status-ok">✓ Email does NOT exist - safe to register</span>';
                }
            } catch (Exception $e) {
                echo '<span class="status-error">✗ Error checking email:</span> ' . htmlspecialchars($e->getMessage());
            }
        }
        
        echo '</div>';

        
        echo '<h2>6. Database Cleanup (Optional)</h2>';
        echo '<div class="section">';
        echo '<form method="POST" style="margin: 10px 0;">';
        echo '<button type="submit" name="do_cleanup" style="padding: 8px 20px; background: #ff0000; color: #fff; border: none; cursor: pointer;" onclick="return confirm(\'Delete ALL users? This cannot be undone!\')">🗑 DELETE ALL USERS (Clean Database)</button>';
        echo '</form>';
        
        if (isset($_POST['do_cleanup'])) {
            try {
                
                $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
                $pdo->exec("TRUNCATE TABLE users");
                $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
                
                echo '<span class="status-ok">✓ Database cleaned - all users deleted</span>';
            } catch (Exception $e) {
                echo '<span class="status-error">✗ Error cleaning database:</span> ' . htmlspecialchars($e->getMessage());
            }
        }
        
        echo '</div>';

        ?>

        <h2>7. What To Do Next</h2>
        <div class="section info">
            <ol>
                <li><strong>Check Section 1:</strong> If connection fails, verify db_config.php</li>
                <li><strong>Check Section 3:</strong> Look for duplicate emails that shouldn't exist</li>
                <li><strong>Check Section 4:</strong> Look for error messages highlighted in red</li>
                <li><strong>Use Section 6:</strong> If database has stale data, clean it</li>
                <li><strong>Test Section 5:</strong> Try registering again after cleanup</li>
                <li><strong>Share output:</strong> Show the complete output above to help debug</li>
            </ol>
        </div>
    </div>
</body>
</html>

