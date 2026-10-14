<?php
require_once 'db_config.php';

$pdo = getDatabaseConnection();
$message = '';
$action = $_GET['action'] ?? '';

if ($action === 'cleanup') {
    try {
        
        $pdo->exec("DELETE FROM users WHERE email NOT IN ('admin@example.com', 'resident@example.com', 'bhw@example.com')");
        $message = '✓ Database cleaned! Deleted test users.';
    } catch (Exception $e) {
        $message = '✗ Error: ' . $e->getMessage();
    }
}

if ($action === 'deleteall') {
    try {
        
        $pdo->exec("DELETE FROM users");
        $message = '✓ All users deleted! Database is now empty.';
    } catch (Exception $e) {
        $message = '✗ Error: ' . $e->getMessage();
    }
}


$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users");
$stmt->execute();
$count = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT email, user_role, created_at FROM users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Cleanup Tool</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #333;
            margin-bottom: 8px;
        }
        .subtitle {
            color: #666;
            font-size: 14px;
            margin-bottom: 30px;
        }
        .message {
            background: #c8e6c9;
            border: 1px solid #2e7d32;
            color: #2e7d32;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .info-box {
            background: #f5f5f5;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box strong {
            color: #667eea;
        }
        .user-list {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            max-height: 300px;
            overflow-y: auto;
        }
        .user-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }
        .user-item:last-child {
            border-bottom: none;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            margin: 8px 8px 8px 0;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }
        .button.primary {
            background: #667eea;
            color: white;
        }
        .button.primary:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }
        .button.danger {
            background: #d32f2f;
            color: white;
        }
        .button.danger:hover {
            background: #b71c1c;
            transform: translateY(-2px);
        }
        .button.secondary {
            background: #f5f5f5;
            color: #333;
            border: 2px solid #ddd;
        }
        .button.secondary:hover {
            background: #efefef;
        }
        .action-buttons {
            margin: 30px 0;
        }
        .counter {
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗑️ Database Cleanup Tool</h1>
        <p class="subtitle">Fix duplicate email registration errors</p>

        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="info-box">
            <strong>Total Users:</strong> <span class="counter"><?php echo $count; ?></span>
        </div>

        <h3 style="margin: 20px 0 10px 0;">Current Database Users:</h3>
        <div class="user-list">
            <?php if (count($users) > 0): ?>
                <?php foreach ($users as $user): ?>
                    <div class="user-item">
                        📧 <strong><?php echo htmlspecialchars($user['email']); ?></strong>
                        <span style="float: right; color: #999;"><?php echo $user['user_role']; ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 20px;">No users in database</p>
            <?php endif; ?>
        </div>

        <h3 style="margin: 20px 0 10px 0;">Choose Action:</h3>

        <div class="action-buttons">
            <p style="margin-bottom: 15px; color: #666;">
                <strong>Option 1:</strong> Just use a different email when signing up (recommended)
            </p>
            <a href="login.php?signup=1" class="button primary">
                ✎ Go to Sign Up with New Email
            </a>
        </div>

        <div class="action-buttons">
            <p style="margin-bottom: 15px; color: #666;">
                <strong>Option 2:</strong> Delete test users to start fresh
            </p>
            <a href="?action=cleanup" class="button secondary" onclick="return confirm('Delete test users? (Keep: admin, resident, bhw)');">
                🗑️ Clean Test Data
            </a>
        </div>

        <div class="action-buttons">
            <p style="margin-bottom: 15px; color: #666;">
                <strong>Option 3:</strong> Delete ALL users (start completely fresh)
            </p>
            <a href="?action=deleteall" class="button danger" onclick="return confirm('WARNING: Delete ALL users? This cannot be undone!');">
                ⚠️ Delete ALL Users
            </a>
        </div>

        <div style="background: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 15px; border-radius: 6px; margin-top: 30px;">
            <strong>💡 Recommended:</strong> Just use a new email address when signing up. No need to delete existing users!
        </div>
    </div>
</body>
</html>

