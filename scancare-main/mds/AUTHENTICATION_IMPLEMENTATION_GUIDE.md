



1. Open phpMyAdmin and select your ScanCare database
2. Go to "SQL" tab
3. Copy the entire contents of DATABASE_SCHEMA.sql
4. Paste it into the SQL editor in phpMyAdmin
5. Click "Go" to execute the script

This creates:
- `users` table (authentication + profile data)
- `activity_logs` table (audit trail for all user actions)

SAMPLE CREDENTIALS (from DATABASE_SCHEMA.sql):
- Resident: resident@example.com / resident123
- BHW: bhw@example.com / bhw123
- Admin: admin@example.com / admin123

NOTE: These are MD5 placeholder hashes. In production, use proper bcrypt hashing
with password_hash() when registering new users.




Your existing login.php should have a form that POSTs to login_process.php.

Example form structure (update your existing form):

```html
<form method="POST" action="login_process.php" style="margin-top: 20px;">
    
    <div style="margin-bottom: 20px;">
        <label style="display: block; font-weight: 600; margin-bottom: 8px;">Email Address</label>
        <input type="email" name="email" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
    </div>
    
    <div style="margin-bottom: 20px;">
        <label style="display: block; font-weight: 600; margin-bottom: 8px;">Password</label>
        <input type="password" name="password" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
    </div>
    
    <?php if (isset($_SESSION['login_error'])): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 15px;">
            <?php echo htmlspecialchars($_SESSION['login_error']); unset($_SESSION['login_error']); ?>
        </div>
    <?php endif; ?>
    
    <button type="submit" style="background: #454B1B; color: white; padding: 12px 24px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; width: 100%;">
        Login
    </button>
</form>
```

KEY REQUIREMENTS:
- Form action MUST be: action="login_process.php"
- Form method MUST be: method="POST"
- Fields MUST be named: email, password




The provided logout.php now:
✓ Logs the logout action to activity_logs BEFORE destroying session
✓ Properly cleans up session variables and cookies
✓ Redirects to landing.php

NO CHANGES NEEDED - Use as-is.




To display the audit trail in your admin dashboard, create a new file
or add this to existing admin_dashboard.php:

```php
<?php
session_start();


if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Activity Logs</title>
    <!
</head>
<body>
    <!
    
    <div style="padding: 20px;">
        <h1>Activity Audit Trail</h1>
        
        <?php
        
        include 'admin_logs.php';
        ?>
    </div>
</body>
</html>
```

This will display:
- Filterable activity logs (by action type, date range)
- User information (name, email, role)
- Timestamps and IP addresses
- Pagination for large datasets




When creating new users, use bcrypt hashing:

```php
<?php


require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $user_role = $_POST['user_role'] ?? 'resident';
    
    
    if (empty($email) || empty($password) || empty($first_name)) {
        die('All fields are required');
    }
    
    
    $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    
    
    $query = "INSERT INTO users 
              (email, password_hash, first_name, last_name, user_role) 
              VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $connection->prepare($query);
    $stmt->bind_param('sssss', $email, $password_hash, $first_name, $last_name, $user_role);
    
    if ($stmt->execute()) {
        header('Location: login.php?registered=1');
    } else {
        die('Registration error: ' . $stmt->error);
    }
    
    $stmt->close();
}
?>
```




To log additional user actions (beyond login/logout), use this pattern:

```php
<?php


require_once 'db_config.php';




$log_query = "INSERT INTO activity_logs 
              (user_id, user_role_snapshot, action_type, action_description, ip_address, user_agent) 
              VALUES (?, ?, 'scheduled_appointment', ?, ?, ?)";

$log_stmt = $connection->prepare($log_query);
$description = "Scheduled appointment for " . date('Y-m-d H:i');
$ip = $_SERVER['REMOTE_ADDR'];
$agent = $_SERVER['HTTP_USER_AGENT'];

$log_stmt->bind_param('issss', $_SESSION['user_id'], $_SESSION['user_role'], $description, $ip, $agent);
$log_stmt->execute();
$log_stmt->close();
?>
```

Available action types:
- login
- logout
- scheduled_appointment
- canceled_appointment
- updated_profile
- viewed_medical_history
- downloaded_report
- admin_action




1. ALWAYS use prepared statements (?)
   - Prevents SQL injection
   - Used in all provided code

2. Hash passwords with bcrypt
   - password_hash($password, PASSWORD_BCRYPT)
   - password_verify($password, $hash)
   - Never store plain-text passwords

3. Validate all user inputs
   - Sanitize with trim()
   - Validate format (email, date, etc.)
   - Reject invalid data

4. Use HTTPS in production
   - Encrypts session cookies
   - Protects credentials in transit

5. Set secure session configuration
   - Use httponly cookies
   - Use secure flag (HTTPS only)
   - Set appropriate session timeout

6. Log failed login attempts
   - Consider rate limiting
   - Alert admins to suspicious activity

7. Enforce access control
   - Always verify $_SESSION['user_role']
   - Redirect unauthorized users to login.php




Issue: "Headers already sent" error when redirecting from login_process.php
Solution: Ensure no output (whitespace, echo, print) appears before session_start()

Issue: Passwords don't match after hashing
Solution: Use password_verify() instead of ==
Correct:   password_verify($password, $hash)
Wrong:     $password === $hash

Issue: Activity logs not recording
Solution: Verify db_config.php is included and $connection is valid

Issue: Users can't login with sample credentials
Solution: Sample hashes are placeholders. Register a new user using bcrypt.

Issue: Session variables lost after page refresh
Solution: Verify session_start() is called at the TOP of every file (before any output)




Regular Maintenance:
- Backup activity_logs table monthly
- Archive old logs (> 1 year) to separate table
- Monitor table size (can grow large over time)

Example: Archive old logs
```sql

INSERT INTO activity_logs_archive
SELECT * FROM activity_logs
WHERE timestamp < DATE_SUB(NOW(), INTERVAL 1 YEAR);

DELETE FROM activity_logs
WHERE timestamp < DATE_SUB(NOW(), INTERVAL 1 YEAR);
```

Permission Management:
- Admin: Full access to users and activity_logs tables
- BHW: Can only see their own activity and relevant appointment logs
- Resident: Cannot access activity_logs directly




✓ DATABASE_SCHEMA.sql
  - SQL script for creating tables in phpMyAdmin
  
✓ login_process.php
  - Handles login form submissions
  - Validates credentials
  - Logs login to activity_logs
  - Creates session variables
  
✓ logout.php (UPDATED)
  - Logs logout action
  - Destroys session securely
  - Redirects to landing.php
  
✓ admin_logs.php
  - Displays activity audit trail
  - Includes filtering and pagination
  - Requires admin role verification
  
✓ This implementation guide
  - Complete setup and usage instructions




1. ✓ Execute DATABASE_SCHEMA.sql in phpMyAdmin
2. ✓ Update login.php form to POST to login_process.php
3. ✓ Replace logout.php with updated version
4. ✓ Create admin dashboard page that includes admin_logs.php
5. ✓ Test login/logout flow and verify activity_logs are created
6. ✓ Test admin audit trail viewer
7. ✓ Register production users with secure bcrypt hashes
8. ✓ Enable HTTPS for production deployment

============================================================================

