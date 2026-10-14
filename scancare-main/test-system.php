<?php


require_once 'db_config.php';

$test_results = [];


try {
    $pdo = getDatabaseConnection();
    $test_results['db_connection'] = $pdo ? '✓ Connected' : '✗ Failed';
    
    if ($pdo) {
        
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        $test_results['users_table'] = $stmt->rowCount() > 0 ? '✓ Exists' : '✗ Missing';
        
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $count = $stmt->fetch()['count'];
        $test_results['user_count'] = $count . ' users in database';
    }
} catch (Exception $e) {
    $test_results['db_connection'] = '✗ Error: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Registration & Login System - Live Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #454B1B;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            font-size: 14px;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .card h2 {
            color: #454B1B;
            margin-bottom: 15px;
            border-bottom: 2px solid #454B1B;
            padding-bottom: 10px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 13px;
        }
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            font-family: inherit;
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
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        button:hover {
            background: #363A15;
        }
        .status {
            margin-top: 20px;
            padding: 15px;
            border-radius: 4px;
            font-size: 13px;
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
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .test-box {
            background: #f9f9f9;
            border-left: 4px solid #454B1B;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            font-size: 13px;
        }
        .test-box strong {
            color: #454B1B;
        }
        .users-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .user-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            font-size: 12px;
        }
        .user-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <!
        <div class="header">
            <h1>🔐 User Registration & Login System - Live Test</h1>
            <p class="subtitle">Test the complete signup and login flow with real database integration</p>
        </div>

        <!
        <div class="card" style="margin-bottom: 20px;">
            <h2>System Status</h2>
            <?php foreach ($test_results as $test => $result): ?>
                <div class="test-box">
                    <strong><?php echo ucfirst(str_replace('_', ' ', $test)); ?>:</strong> <?php echo $result; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="grid">
            <!
            <div class="card">
                <h2>📝 Test Registration</h2>
                <p style="color: #666; font-size: 13px; margin-bottom: 15px;">Create a new test account:</p>
                
                <form id="signupForm">
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" id="signup_email" placeholder="test@example.com" required>
                    </div>

                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" id="signup_fname" placeholder="John" required>
                    </div>

                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" id="signup_lname" placeholder="Doe">
                    </div>

                    <div class="form-group">
                        <label>Password * (min 6 chars)</label>
                        <input type="password" id="signup_pass" placeholder="password123" required>
                    </div>

                    <div class="form-group">
                        <label>Confirm Password *</label>
                        <input type="password" id="signup_pass_confirm" placeholder="password123" required>
                    </div>

                    <div class="form-group">
                        <label>Role</label>
                        <select id="signup_role">
                            <option value="resident">Resident (Patient)</option>
                            <option value="bhw">BHW (Health Worker)</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>

                    <button type="submit">✓ Create Account</button>
                    <div id="signupStatus"></div>
                </form>
            </div>

            <!
            <div class="card">
                <h2>🔑 Test Login</h2>
                <p style="color: #666; font-size: 13px; margin-bottom: 15px;">Login with your created account:</p>
                
                <form id="loginForm">
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" id="login_email" placeholder="test@example.com" required>
                    </div>

                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" id="login_pass" placeholder="password123" required>
                    </div>

                    <button type="submit">✓ Login</button>
                    <div id="loginStatus"></div>
                </form>

                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                    <p style="color: #666; font-size: 12px; margin-bottom: 10px;"><strong>Quick Test Credentials:</strong></p>
                    <p style="font-size: 12px; margin: 5px 0;"><code>resident@example.com</code> / <code>password123</code></p>
                </div>
            </div>
        </div>

        <!
        <div class="card">
            <h2>👥 Users in Database</h2>
            <div class="users-list" id="usersList">
                <div style="padding: 15px; text-align: center; color: #999;">Loading users...</div>
            </div>
        </div>

        <!
        <div class="card" style="margin-top: 20px;">
            <h2>📚 How It Works</h2>
            
            <h3 style="color: #6B7D1F; margin: 15px 0 10px 0;">Registration Flow (signup_process.php)</h3>
            <ol style="margin: 0 0 15px 20px; font-size: 13px;">
                <li>User submits registration form</li>
                <li>Email & password are validated</li>
                <li>Password is hashed using <code>password_hash()</code> (BCRYPT)</li>
                <li>Account is inserted into <code>users</code> table</li>
                <li>Database automatically sets <code>created_at</code> timestamp</li>
                <li>User is logged in and redirected to dashboard</li>
            </ol>

            <h3 style="color: #6B7D1F; margin: 15px 0 10px 0;">Login Flow (login_process.php)</h3>
            <ol style="margin: 0 0 15px 20px; font-size: 13px;">
                <li>User submits login form with email & password</li>
                <li>Email is searched in <code>users</code> table</li>
                <li>Password is verified using <code>password_verify()</code></li>
                <li><code>last_login</code> timestamp is updated</li>
                <li>Session variables are created</li>
                <li>User is redirected to their dashboard</li>
            </ol>

            <h3 style="color: #6B7D1F; margin: 15px 0 10px 0;">Files Created</h3>
            <ul style="margin: 0 0 0 20px; font-size: 13px;">
                <li><code>signup_process.php</code> - Registration handler</li>
                <li><code>login_process.php</code> - Authentication handler</li>
                <li><code>db_config.php</code> - Database connection (already exists)</li>
                <li><code>SQL_CREATE_USERS_TABLE.sql</code> - Database schema</li>
            </ul>
        </div>
    </div>

    <script>
        
        function loadUsers() {
            fetch('get_users_list.php')
                .then(r => r.json())
                .then(data => {
                    const list = document.getElementById('usersList');
                    if (!data.users || data.users.length === 0) {
                        list.innerHTML = '<div style="padding: 15px; text-align: center; color: #999;">No users yet. Create one to test!</div>';
                    } else {
                        list.innerHTML = data.users.map(u => `
                            <div class="user-item">
                                <strong>${u.first_name} ${u.last_name}</strong><br>
                                Email: <code>${u.email}</code><br>
                                Role: <span style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">${u.user_role}</span><br>
                                Created: ${u.created_at}<br>
                                Last Login: ${u.last_login || 'Never'}
                            </div>
                        `).join('');
                    }
                });
        }

        
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const statusDiv = document.getElementById('signupStatus');
            
            const formData = new FormData();
            formData.append('email', document.getElementById('signup_email').value);
            formData.append('first_name', document.getElementById('signup_fname').value);
            formData.append('last_name', document.getElementById('signup_lname').value);
            formData.append('password', document.getElementById('signup_pass').value);
            formData.append('password_confirm', document.getElementById('signup_pass_confirm').value);
            formData.append('user_role', document.getElementById('signup_role').value);

            fetch('signup_process.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        statusDiv.className = 'status success';
                        statusDiv.innerHTML = '✓ Account created! Reload page to see in users list.';
                        document.getElementById('signupForm').reset();
                        setTimeout(loadUsers, 500);
                    } else {
                        statusDiv.className = 'status error';
                        statusDiv.innerHTML = '✗ ' + data.message;
                    }
                });
        });

        
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const statusDiv = document.getElementById('loginStatus');
            
            const formData = new FormData();
            formData.append('email', document.getElementById('login_email').value);
            formData.append('password', document.getElementById('login_pass').value);

            fetch('login_process.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        statusDiv.className = 'status success';
                        statusDiv.innerHTML = '✓ Login successful! Redirecting to ' + data.redirect;
                        setTimeout(() => window.location.href = data.redirect, 1000);
                    } else {
                        statusDiv.className = 'status error';
                        statusDiv.innerHTML = '✗ ' + data.message;
                    }
                });
        });

        
        loadUsers();
        
        setInterval(loadUsers, 5000);
    </script>
</body>
</html>

