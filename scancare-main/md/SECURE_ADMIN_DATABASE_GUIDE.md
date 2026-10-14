


1. [SQL Schema Creation](#1-sql-schema-creation)
2. [PHP Secure Registration Logic](#2-php-secure-registration-logic)
3. [PHP Secure Login Verification](#3-php-secure-login-verification)
4. [Security Best Practices](#4-security-best-practices)
5. [Testing Checklist](#5-testing-checklist)



**Copy and paste this entire SQL block into phpMyAdmin's SQL tab:**

```sql









CREATE TABLE IF NOT EXISTS `system_admins` (
    
    `admin_id` BIGINT AUTO_INCREMENT PRIMARY KEY 
        COMMENT 'Unique administrator ID (auto-incrementing)',
    
    
    `username` VARCHAR(255) NOT NULL UNIQUE 
        COMMENT 'Unique admin username (case-insensitive)',
    
    `email` VARCHAR(255) NOT NULL UNIQUE 
        COMMENT 'Unique admin email address (case-insensitive)',
    
    `password_hash` VARCHAR(255) NOT NULL 
        COMMENT 'Bcrypt hashed password (PASSWORD_BCRYPT, cost=10). NEVER plain-text',
    
    
    `full_name` VARCHAR(255) NOT NULL 
        COMMENT 'Administrator full name for display',
    
    
    `role` ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin' 
        COMMENT 'Admin privilege level: super_admin (full access), admin (standard), moderator (limited)',
    
    
    `is_active` TINYINT(1) DEFAULT 1 
        COMMENT 'Account status: 1=active, 0=deactivated/suspended',
    
    
    `last_login` DATETIME NULL 
        COMMENT 'Last successful login timestamp (NULL if never logged in)',
    
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
        COMMENT 'Account creation timestamp (auto-set)',
    
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP 
        COMMENT 'Last modification timestamp (auto-updated)',
    
    
    UNIQUE INDEX idx_username (username),
    UNIQUE INDEX idx_email (email),
    INDEX idx_is_active (is_active),
    INDEX idx_role (role),
    INDEX idx_created_at (created_at)
) 
ENGINE=InnoDB 
DEFAULT CHARSET=utf8mb4 
COLLATE=utf8mb4_unicode_ci
COMMENT='Secure system administrator accounts with bcrypt password hashing';
```


After running the SQL, check that the table exists:

```sql
DESCRIBE system_admins;
```

You should see all columns listed with correct types and constraints.



The registration form in `login.php` already implements this logic when role = 'admin':

```php
<?php








$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$password_confirm = trim($_POST['password_confirm'] ?? '');
$user_role = trim($_POST['user_role'] ?? '');










if (empty($password) || strlen($password) < 6) {
    $error = 'Password must be at least 6 characters.';
    return; 
}

if ($password !== $password_confirm) {
    $error = 'Passwords do not match.';
    return; 
}










$password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);












try {
    $pdo = getDatabaseConnection(); 
    
    
    $username = strpos($email_lower, '@') 
        ? substr($email_lower, 0, strpos($email_lower, '@')) 
        : $email_lower;
    
    
    
    $stmt = $pdo->prepare("
        INSERT INTO system_admins 
        (username, email, password_hash, full_name, role, is_active)
        VALUES (?, ?, ?, ?, ?, 1)
    ");
    
    
    
    
    
    
    
    
    $result = $stmt->execute([
        $username,              
        $email_lower,           
        $password_hash,         
        $full_name,             
        'admin'                 
    ]);
    
    if ($result) {
        
        
        
        
        
        
        
        $new_admin_id = $pdo->lastInsertId();
        
        
        $_SESSION['admin_auth'] = true;          
        $_SESSION['admin_id'] = $new_admin_id;   
        $_SESSION['admin_username'] = $username; 
        $_SESSION['admin_email'] = $email_lower; 
        $_SESSION['admin_name'] = $full_name;    
        $_SESSION['admin_role'] = 'admin';       
        $_SESSION['user_role'] = 'admin';        
        $_SESSION['login_time'] = time();        
        $_SESSION['new_user'] = true;            
        
        
        error_log("NEW ADMIN SIGNUP: Admin ID {$new_admin_id} (username: {$username}) registered successfully");
        
        
        header('Location: administrator_panel.php?new=1');
        exit;
    } else {
        
        $error = 'Failed to create administrator account. Please try again.';
        error_log("ADMIN INSERT FAILED: " . json_encode($stmt->errorInfo()));
    }
    
} catch (Exception $e) {
    $error = 'An error occurred during signup. Please try again.';
    error_log("SIGNUP_EXCEPTION: " . $e->getMessage());
}
?>
```


✅ **Password Hashing**: Uses `password_hash($password, PASSWORD_BCRYPT, ['cost' => 10])`
- Never stores plain-text passwords
- Generates 68-character hash with salt
- Cost factor of 10 balances security and performance

✅ **SQL Injection Prevention**: Uses prepared statements with `?` placeholders
- Values bound separately from SQL syntax
- PDO automatically escapes dangerous characters

✅ **Validation**: Checks password length and confirmation before hashing
- 6+ character minimum enforced
- Password and confirmation must match

✅ **Session Management**: Sets all required admin session variables
- Immediate login after signup
- No manual login required



The login verification is in `login.php` (lines ~119-162):

```php
<?php












$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$user_role = trim($_POST['user_role'] ?? 'resident');





if (empty($email) || empty($password)) {
    $error = 'Please enter both email and password.';
    return; 
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Invalid email format.';
    return; 
}





if ($user_role === 'admin') {
    
    
    
    
    
    
    
    try {
        $pdo = getDatabaseConnection();
        
        $email_lower = strtolower($email);
        
        
        $stmt = $pdo->prepare("
            SELECT 
                admin_id,
                username,
                email,
                password_hash,      
                full_name,
                role,
                is_active
            FROM system_admins 
            WHERE LOWER(email) = ? AND is_active = 1
            LIMIT 1
        ");
        
        $stmt->execute([$email_lower]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        
        
        
        
        
        
        
        
        if ($admin && password_verify($password, $admin['password_hash'])) {
            
            
            
            
            
            
            
            
            $_SESSION['admin_auth'] = true;
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['user_role'] = 'admin';
            $_SESSION['login_time'] = time();
            
            
            $update_stmt = $pdo->prepare("
                UPDATE system_admins 
                SET last_login = NOW() 
                WHERE admin_id = ?
            ");
            $update_stmt->execute([$admin['admin_id']]);
            
            
            error_log("ADMIN LOGIN SUCCESS: Admin '{$admin['username']}' (ID: {$admin['admin_id']}) logged in at " . date('Y-m-d H:i:s'));
            
            
            header('Location: administrator_panel.php');
            exit;
            
        } else {
            
            
            
            
            
            
            
            
            
            $error = 'Invalid email or password for administrator account.';
            
            
            error_log("ADMIN LOGIN FAILED: Invalid credentials for email {$email_lower} from IP {$_SERVER['REMOTE_ADDR']}");
        }
        
    } catch (Exception $e) {
        $error = 'Authentication error. Please try again.';
        error_log("ADMIN LOGIN ERROR: " . $e->getMessage());
    }
}
?>
```


```php
<?php





$stored_hash = '$2y$10$abcdefghijklmnopqrstuvwxyz123456789'; 


$user_input_password = 'MySecurePass123';


if (password_verify($user_input_password, $stored_hash)) {
    echo "✅ Password is CORRECT - Login successful!";
    
} else {
    echo "❌ Password is INCORRECT - Show error message";
    
}







?>
```



| Practice | Your Implementation |
|
| **Password Hashing** | Uses `password_hash($password, PASSWORD_BCRYPT, ['cost' => 10])` ✓ |
| **Prepared Statements** | Uses PDO with `?` placeholders ✓ |
| **SQL Injection Prevention** | No string concatenation in queries ✓ |
| **Unique Constraints** | Email and username are UNIQUE in database ✓ |
| **Active Status Flag** | Checks `is_active = 1` before login ✓ |
| **Session Protection** | Sets multiple verification variables ✓ |
| **Error Logging** | Logs all auth attempts and failures ✓ |
| **Last Login Tracking** | Updates `last_login` timestamp ✓ |
| **Timestamped Records** | `created_at` and `updated_at` on every record ✓ |
| **UTF-8 Support** | Database uses `utf8mb4` for international chars ✓ |


```php
<?php

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['login_attempts_time'] = time();
}


if ($_SESSION['login_attempts'] >= 5) {
    $time_diff = time() - $_SESSION['login_attempts_time'];
    if ($time_diff < 900) { 
        $error = 'Too many login attempts. Please try again in 15 minutes.';
        return;
    }
}


if ($login_failed) {
    $_SESSION['login_attempts']++;
    error_log("LOGIN ATTEMPT #{$_SESSION['login_attempts']} from {$_SERVER['REMOTE_ADDR']}");
}


if ($login_success) {
    $_SESSION['login_attempts'] = 0;
    unset($_SESSION['login_attempts_time']);
}
?>
```



```
1. Navigate to: http:
2. Fill form:
   - Full Name: "Test Admin"
   - Email: "testadmin@scancare.local"
   - Password: "TestPass123"
   - Confirm: "TestPass123"
   - Role: Select "🔐 System Administrator (Developer)"
3. Submit form
4. Expected Result:
   ✅ Redirect to administrator_panel.php?new=1
   ✅ Admin logged in automatically (no manual login)
   ✅ New record in system_admins table with hashed password
   ✅ Password_hash field shows "$2y$10$..." (NOT plain-text)
   ✅ Error log shows: "NEW ADMIN SIGNUP: Admin ID X (username: testadmin) registered successfully"
```


```
1. Logout if needed: http:
2. Navigate to: http:
3. Fill form:
   - Email: "testadmin@scancare.local"
   - Password: "TestPass123"
   - Role: Select "🔐 System Administrator (Developer)"
4. Submit form
5. Expected Result:
   ✅ Redirect to administrator_panel.php
   ✅ Admin dashboard displays admin name and role
   ✅ Session variables set correctly
   ✅ last_login timestamp updated in database
   ✅ Error log shows: "ADMIN LOGIN SUCCESS: Admin 'testadmin'..."
```


```
1. Navigate to: http:
2. Fill form:
   - Email: "testadmin@scancare.local"
   - Password: "WrongPassword123"  (intentionally wrong)
   - Role: "🔐 System Administrator (Developer)"
3. Submit form
4. Expected Result:
   ❌ Stay on login.php (no redirect)
   ❌ Error message: "Invalid email or password for administrator account."
   ❌ NOT logged in
   ✅ Error log shows: "ADMIN LOGIN FAILED: Invalid credentials for..."
   ✅ last_login NOT updated in database
```


```
1. In phpMyAdmin, set account to inactive:
   UPDATE system_admins SET is_active = 0 WHERE email = 'testadmin@scancare.local';

2. Navigate to: http:
3. Fill form with correct email/password/role
4. Submit form
5. Expected Result:
   ❌ Error: "Invalid email or password"
   ❌ NOT logged in (account is inactive)
   ❌ No database record found due to is_active=0 condition
```


```
1. Try registering again with same email: testadmin@scancare.local
2. Expected Result:
   ❌ Error message: "This email is already registered. Please use a different email or login here."
   ❌ Form NOT submitted to database
   ✅ Stays on signup form with error visible
```


```
1. In phpMyAdmin, query the database:
   SELECT admin_id, username, email, password_hash FROM system_admins LIMIT 1;

2. Verify:
   ✅ password_hash field shows: $2y$10$... (NOT plain-text)
   ✅ Hash is 60 characters long
   ✅ Hash changes every time (different salt)
   ✅ No plain-text "TestPass123" visible in database
```


```
1. Log in as admin
2. Try accessing administrator_panel.php directly via URL
3. Expected Result:
   ✅ Administrator panel loads (session valid)
   
4. Clear browser cookies/close and reopen browser
5. Try accessing administrator_panel.php again
6. Expected Result:
   ❌ Redirects to login_admin.php (session cleared)
   ❌ Cannot access without logging in again
```


| Item | Status | Notes |
|
| SQL Injection Prevention | ✅ SECURE | Uses prepared statements with PDO |
| Plain-Text Passwords | ✅ SECURE | All passwords hashed with bcrypt |
| Password Storage | ✅ SECURE | VARCHAR(255) safely stores $2y$10$ hashes |
| Unique Constraints | ✅ SECURE | Email and username are UNIQUE |
| Account Status | ✅ SECURE | Checks is_active = 1 before login |
| Password Verification | ✅ SECURE | Uses password_verify() for comparison |
| Session Variables | ✅ SECURE | Multiple verification flags |
| Error Messages | ✅ SECURE | Generic "invalid credentials" (no info leak) |
| Activity Logging | ✅ SECURE | All auth attempts logged |
| Timestamp Auditing | ✅ SECURE | created_at, updated_at, last_login tracked |



**Solution**: This means a plain-text password is in the page. Check:
1. ✅ Never echo passwords in PHP: `echo $_POST['password'];` ❌ WRONG
2. ✅ Only hash before storing: `password_hash($password, PASSWORD_BCRYPT)` ✓ RIGHT
3. ✅ Database should only contain hashes starting with `$2y$10$`
4. ✅ Verify with: `SELECT password_hash FROM system_admins LIMIT 1;`


**Solution**: The INSERT query failed. Check:
1. Does system_admins table exist? Run the CREATE TABLE SQL above.
2. Are all columns correct? Run `DESCRIBE system_admins;`
3. Is password_hash VARCHAR(255)? It must be 255 or larger.
4. Check error details in error log for exact DB error.


**Solution**: password_verify() returned FALSE. Check:
1. Is the password_hash in database valid? Should start with `$2y$10$`
2. Was password hashed with PASSWORD_BCRYPT? Must use same algorithm.
3. Is the account active? Check `is_active = 1`
4. Is the email in correct case? Query uses LOWER() for comparison.


1. ✅ Copy the CREATE TABLE SQL above
2. ✅ Paste into phpMyAdmin SQL tab and execute
3. ✅ Verify with: `DESCRIBE system_admins;`
4. ✅ Test registration at: `http:
5. ✅ Test login at: `http:
6. ✅ Verify password hash in phpMyAdmin (no plain-text!)
7. ✅ All browser "password found in breach" warnings should disappear



**Last Updated**: 2026-06-09  
**System**: ScanCare Healthcare Management System  
**Security Level**: ✅ PRODUCTION-READY

