<!DOCTYPE html>
<html>
<head>
    <title>ScanCare - Final Cleanup & Fix</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #d32f2f; margin-bottom: 20px; }
        h2 { color: #1976d2; margin-top: 25px; margin-bottom: 15px; }
        .status-box {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            font-weight: bold;
        }
        .success { background: #c8e6c9; color: #2e7d32; border-left: 4px solid #2e7d32; }
        .error { background: #ffcdd2; color: #c62828; border-left: 4px solid #c62828; }
        .warning { background: #fff3cd; color: #856404; border-left: 4px solid #ffc107; }
        .info { background: #bbdefb; color: #1565c0; border-left: 4px solid #1976d2; }
        code { background: #f5f5f5; padding: 2px 5px; border-radius: 3px; }
        button {
            background: #d32f2f;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            margin-top: 10px;
        }
        button:hover { background: #b71c1c; }
        ul { margin: 10px 0 0 20px; }
        li { margin: 8px 0; }
        .step { margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 3px solid #1976d2; }
        .step h3 { color: #1976d2; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 FINAL CLEANUP & FIX - CRITICAL ISSUE RESOLVED</h1>

        <div class="status-box success">
            <strong>✓ PROBLEM FOUND AND FIXED!</strong>
            <p>Your registration system was using JSON files instead of the database. This has been corrected.</p>
        </div>

        <h2>What Was Wrong</h2>
        <div class="step">
            <h3>❌ The Problem</h3>
            <ul>
                <li><code>login.php</code> had OLD registration code that saved to <code>registered_users.json</code> (a JSON file)</li>
                <li>This JSON file had stale email data from previous tests</li>
                <li>When you tried to sign up, it checked this JSON file, not the database</li>
                <li>When you tried to login, it checked the database (which was empty)</li>
                <li><strong>Result:</strong> Database empty, JSON file full of stale data</li>
            </ul>
        </div>

        <h2>What Was Fixed</h2>
        <div class="step">
            <h3>✓ The Solution</h3>
            <ul>
                <li>Updated <code>login.php</code> signup handler to use the database (PDO)</li>
                <li>Now both registration and login use the same database</li>
                <li>Removed all JSON file dependencies</li>
                <li>Same code as your working <code>signup_process.php</code></li>
            </ul>
        </div>

        <h2>Step 1: Delete Stale JSON Data</h2>
        <div class="step">
            <?php
            $json_file = __DIR__ . '/registered_users.json';
            
            if (file_exists($json_file)) {
                
                $content = file_get_contents($json_file);
                $data = json_decode($content, true);
                
                echo '<strong style="color: #d32f2f;">⚠ Stale data detected in registered_users.json</strong><br>';
                echo '<p>Found ' . count($data) . ' old registration(s) that need to be removed:</p>';
                
                echo '<div style="background: #ffe6e6; padding: 10px; border-radius: 3px; margin: 10px 0; font-family: monospace; font-size: 12px; max-height: 200px; overflow-y: auto;">';
                echo htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT));
                echo '</div>';
                
                echo '<form method="POST">';
                echo '<button type="submit" name="delete_json" onclick="return confirm(\'Delete stale JSON data?\')">🗑 Delete registered_users.json</button>';
                echo '</form>';
                
                if (isset($_POST['delete_json'])) {
                    unlink($json_file);
                    echo '<div class="status-box success">✓ Deleted - JSON file removed</div>';
                }
            } else {
                echo '<div class="status-box success">✓ No stale JSON file found - clean!</div>';
            }
            ?>
        </div>

        <h2>Step 2: Clean Database</h2>
        <div class="step">
            <?php
            require_once 'db_config.php';
            
            $pdo = getDatabaseConnection();
            if ($pdo) {
                try {
                    $count = $pdo->query("SELECT COUNT(*) as cnt FROM users")->fetch()['cnt'];
                    
                    if ($count > 0) {
                        echo '<strong style="color: #ff9800;">⚠ Database has ' . $count . ' user(s)</strong><br>';
                        echo '<p>These might be old test data. Do you want to delete them and start fresh?</p>';
                        
                        
                        $users = $pdo->query("SELECT user_id, email, first_name, last_name, created_at FROM users")->fetchAll();
                        
                        echo '<div style="background: #ffe6e6; padding: 10px; border-radius: 3px; margin: 10px 0;">';
                        foreach ($users as $user) {
                            echo '<div style="padding: 5px; border-bottom: 1px solid #ddd;">';
                            echo htmlspecialchars($user['email']) . ' (' . $user['first_name'] . ' ' . $user['last_name'] . ')';
                            echo '</div>';
                        }
                        echo '</div>';
                        
                        echo '<form method="POST">';
                        echo '<button type="submit" name="clean_db" onclick="return confirm(\'Delete ALL users from database?\')">🗑 Clean Database</button>';
                        echo '</form>';
                        
                        if (isset($_POST['clean_db'])) {
                            $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
                            $pdo->exec("TRUNCATE TABLE users");
                            $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
                            echo '<div class="status-box success">✓ Database cleaned - all old users deleted</div>';
                        }
                    } else {
                        echo '<div class="status-box success">✓ Database is empty and clean!</div>';
                    }
                } catch (Exception $e) {
                    echo '<div class="status-box error">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
            } else {
                echo '<div class="status-box error">Cannot connect to database</div>';
            }
            ?>
        </div>

        <h2>Step 3: Test New Registration</h2>
        <div class="step">
            <h3>Now test the fixed system</h3>
            <ol style="margin: 10px 0 0 20px;">
                <li>Go to <a href="login.php?signup=1" style="color: #1976d2; text-decoration: underline;">login.php?signup=1</a></li>
                <li>Create a test account:
                    <ul style="margin: 10px 0 0 20px;">
                        <li>Full Name: Test User</li>
                        <li>Email: <code>newtest@example.com</code></li>
                        <li>Password: <code>password123</code></li>
                        <li>Role: Resident</li>
                        <li>Phone: <code>09001234567</code></li>
                    </ul>
                </li>
                <li>Click "Sign Up"</li>
            </ol>
        </div>

        <h2>Step 4: Verify in Database</h2>
        <div class="step">
            <h3>Check phpMyAdmin</h3>
            <ol style="margin: 10px 0 0 20px;">
                <li>Go to <a href="http:
                <li>Select <code>scancare_db</code> → <code>users</code> table</li>
                <li>Verify the new user appears with:
                    <ul style="margin: 10px 0 0 20px;">
                        <li>✓ Email correct</li>
                        <li>✓ Password is hashed (starts with <code>$2y$10$</code>)</li>
                        <li>✓ <code>created_at</code> timestamp is set</li>
                        <li>✓ <code>is_active = 1</code></li>
                    </ul>
                </li>
            </ol>
        </div>

        <h2>Step 5: Test Login</h2>
        <div class="step">
            <h3>Login with your new account</h3>
            <ol style="margin: 10px 0 0 20px;">
                <li>Go to <a href="login.php" style="color: #1976d2; text-decoration: underline;">login.php</a></li>
                <li>Enter:
                    <ul style="margin: 10px 0 0 20px;">
                        <li>Email: <code>newtest@example.com</code></li>
                        <li>Password: <code>password123</code></li>
                    </ul>
                </li>
                <li>Click "Sign In"</li>
                <li>Should redirect to dashboard</li>
                <li>Check phpMyAdmin - <code>last_login</code> should be updated</li>
            </ol>
        </div>

        <h2>Summary of Changes</h2>
        <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
            <tr style="background: #f5f5f5;">
                <th style="border: 1px solid #ddd; padding: 10px; text-align: left;">File</th>
                <th style="border: 1px solid #ddd; padding: 10px; text-align: left;">Change</th>
            </tr>
            <tr>
                <td style="border: 1px solid #ddd; padding: 10px;"><code>login.php</code></td>
                <td style="border: 1px solid #ddd; padding: 10px;">✓ Signup handler now uses database (PDO) instead of JSON</td>
            </tr>
            <tr style="background: #f9f9f9;">
                <td style="border: 1px solid #ddd; padding: 10px;"><code>registered_users.json</code></td>
                <td style="border: 1px solid #ddd; padding: 10px;">✓ Delete (clean up stale data)</td>
            </tr>
            <tr>
                <td style="border: 1px solid #ddd; padding: 10px;"><code>scancare_db.users</code></td>
                <td style="border: 1px solid #ddd; padding: 10px;">✓ Clean old test data</td>
            </tr>
        </table>

        <h2>Why This Happened</h2>
        <div class="status-box info">
            <p>Your system had:</p>
            <ul style="margin: 10px 0 0 20px;">
                <li><strong>OLD CODE:</strong> <code>login.php</code> with JSON-file-based registration</li>
                <li><strong>NEW CODE:</strong> <code>signup_process.php</code> with database-based registration</li>
                <li><strong>LOGIN:</strong> <code>login_process.php</code> checking database</li>
            </ul>
            <p style="margin-top: 10px;"><strong>The Conflict:</strong> Registration and login were using different storage systems!</p>
            <p>Now they're unified - both use the database via PDO.</p>
        </div>

        <div style="margin-top: 30px; padding: 15px; background: #c8e6c9; border-radius: 5px; color: #2e7d32;">
            <strong>✓ SYSTEM NOW FIXED!</strong>
            <p>Your authentication system now properly uses the database for both registration and login.</p>
        </div>
    </div>
</body>
</html>

