<?php
require_once 'db_config.php';

echo "<h1>✨ CREATE USER & VERIFY APPOINTMENTS</h1>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $role = $_POST['role'] ?? 'resident';
    
    if (empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        echo "<div style='color: red; font-size: 16px;'>❌ All fields required!</div>";
    } else {
        try {
            $pdo = getDatabaseConnection();
            
            
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                echo "<div style='color: red; font-size: 16px;'>❌ User already exists!</div>";
            } else {
                
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
                $insert_stmt = $pdo->prepare("INSERT INTO users (email, password_hash, first_name, last_name, user_role, is_active) VALUES (?, ?, ?, ?, ?, 1)");
                $insert_stmt->execute([$email, $hash, $first_name, $last_name, $role]);
                
                $user_id = $pdo->lastInsertId();
                
                echo "<div style='color: green; font-size: 18px; margin: 20px 0;'>";
                echo "✅ <strong>USER CREATED SUCCESSFULLY!</strong><br>";
                echo "User ID: " . $user_id . "<br>";
                echo "Email: " . $email . "<br>";
                echo "Name: " . $first_name . " " . $last_name . "<br>";
                echo "Role: " . $role . "<br>";
                echo "</div>";
                
                
                $app_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM appointments WHERE user_id = ?");
                $app_stmt->execute([$user_id]);
                $app_result = $app_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($app_result['count'] === 0 || $app_result['count'] == 0) {
                    echo "<div style='color: green; font-size: 16px;'>";
                    echo "✅ <strong>VERIFIED: This user has 0 appointments (correct!)</strong>";
                    echo "</div>";
                } else {
                    echo "<div style='color: red; font-size: 16px;'>";
                    echo "❌ ERROR: User has " . $app_result['count'] . " appointments! This should not happen!";
                    echo "</div>";
                }
                
                echo "<div style='background: #f0f0f0; padding: 15px; margin-top: 20px; border-radius: 5px;'>";
                echo "<strong>Next Steps:</strong><br>";
                echo "1. Go to: http:
                echo "2. Login with Email: " . $email . "<br>";
                echo "3. Login with Password: " . $password . "<br>";
                echo "4. Verify in: http:
                echo "</div>";
            }
            
        } catch (Exception $e) {
            echo "<div style='color: red; font-size: 16px;'>❌ ERROR: " . $e->getMessage() . "</div>";
        }
    }
}
?>

<form method="POST" style="max-width: 500px; border: 2px solid #333; padding: 20px; background: #f9f9f9; border-radius: 5px;">
    
    <h2>Create New User Account</h2>
    
    <div style="margin-bottom: 15px;">
        <label><strong>Email:</strong></label><br>
        <input type="email" name="email" required style="width: 100%; padding: 8px; font-size: 14px;">
    </div>
    
    <div style="margin-bottom: 15px;">
        <label><strong>Password:</strong></label><br>
        <input type="password" name="password" required style="width: 100%; padding: 8px; font-size: 14px;">
    </div>
    
    <div style="margin-bottom: 15px;">
        <label><strong>First Name:</strong></label><br>
        <input type="text" name="first_name" required style="width: 100%; padding: 8px; font-size: 14px;">
    </div>
    
    <div style="margin-bottom: 15px;">
        <label><strong>Last Name:</strong></label><br>
        <input type="text" name="last_name" required style="width: 100%; padding: 8px; font-size: 14px;">
    </div>
    
    <div style="margin-bottom: 15px;">
        <label><strong>Account Type:</strong></label><br>
        <select name="role" style="width: 100%; padding: 8px; font-size: 14px;">
            <option value="resident">Resident</option>
            <option value="bhw">BHW (Health Worker)</option>
            <option value="admin">Administrator</option>
        </select>
    </div>
    
    <button type="submit" style="background: #4CAF50; color: white; padding: 12px 20px; font-size: 16px; border: none; border-radius: 4px; cursor: pointer; width: 100%;">
        ✅ CREATE ACCOUNT & VERIFY
    </button>
    
</form>

<hr style="margin-top: 30px;">

<h2>📊 Current Users in Database:</h2>
<?php
try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->query("SELECT user_id, email, first_name, last_name, user_role, is_active, created_at FROM users ORDER BY user_id");
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr style='background: #333; color: white;'>";
    echo "<th>ID</th><th>Email</th><th>Name</th><th>Role</th><th>Active</th><th>Created</th>";
    echo "</tr>";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . $row['first_name'] . " " . $row['last_name'] . "</td>";
        echo "<td>" . $row['user_role'] . "</td>";
        echo "<td>" . ($row['is_active'] ? '✓ Yes' : '✗ No') . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>


