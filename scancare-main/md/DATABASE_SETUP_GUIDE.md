



```sql

CREATE DATABASE IF NOT EXISTS scancare_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


USE scancare_db;


CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('resident', 'bhw', 'admin') NOT NULL DEFAULT 'resident',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    reason VARCHAR(255) NOT NULL,
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_date (appointment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```


```sql

INSERT INTO users (full_name, email, password, role) VALUES 
('Maria Santos', 'resident@scancare.com', '$2y$12$...[hashed_password]...', 'resident');


INSERT INTO users (full_name, email, password, role) VALUES 
('Dr. Juan Dela Cruz', 'bhw@scancare.com', '$2y$12$...[hashed_password]...', 'bhw');


INSERT INTO users (full_name, email, password, role) VALUES 
('Admin User', 'admin@scancare.com', '$2y$12$...[hashed_password]...', 'admin');
```


**File: `db_config.php`** (Already created in your project)

The database configuration file includes:
- **PDO Connection**: Secure connection with prepared statements
- **Helper Functions**:
  - `getDatabaseConnection()` - Get PDO connection
  - `hashPassword($password)` - Hash password with bcrypt
  - `verifyPassword($password, $hash)` - Verify password
  - `sanitizeInput($input)` - Sanitize user input
  - `validateEmail($email)` - Validate email format
  - `generateUserId($role)` - Generate unique user ID


Prevents SQL injection attacks:
```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
```

Secure password storage:
```php
$hashed_password = hashPassword($plain_password);  
verifyPassword($plain_password, $hashed_password);  
```

Removes dangerous characters:
```php
$safe_input = sanitizeInput($user_input);
```

- Session starts at page beginning
- Automatic session validation on protected pages
- Session variables stored securely



```php
<?php

require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'login') {
    $email = sanitizeInput($_POST['email']);
    $password = trim($_POST['password']);
    
    try {
        $pdo = getDatabaseConnection();
        
        
        $stmt = $pdo->prepare("SELECT user_id, full_name, email, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        
        if ($user && verifyPassword($password, $user['password'])) {
            
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['login_time'] = time();
            
            
            if ($user['role'] === 'resident') {
                header('Location: dashboard.php');
            } elseif ($user['role'] === 'bhw') {
                header('Location: bhw_dashboard.php');
            } elseif ($user['role'] === 'admin') {
                header('Location: admin_dashboard.php');
            }
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    } catch (Exception $e) {
        $error = 'Login error: ' . $e->getMessage();
    }
}
?>
```



```php
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'signup') {
    $full_name = sanitizeInput($_POST['full_name']);
    $email = sanitizeInput($_POST['email']);
    $password = trim($_POST['password']);
    $password_confirm = trim($_POST['password_confirm']);
    $user_role = sanitizeInput($_POST['user_role']);
    $phone = sanitizeInput($_POST['phone']);
    
    
    $errors = [];
    if (empty($full_name)) $errors[] = 'Name required.';
    if (!validateEmail($email)) $errors[] = 'Invalid email.';
    if (strlen($password) < 8) $errors[] = 'Password min 8 characters.';
    if ($password !== $password_confirm) $errors[] = 'Passwords mismatch.';
    if (!in_array($user_role, ['resident', 'bhw', 'admin'])) $errors[] = 'Invalid role.';
    
    if (empty($errors)) {
        try {
            $pdo = getDatabaseConnection();
            
            
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $errors[] = 'Email already registered.';
            } else {
                
                $hashed_password = hashPassword($password);
                
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (full_name, email, password, role)
                    VALUES (?, ?, ?, ?)
                ");
                
                $stmt->execute([$full_name, $email, $hashed_password, $user_role]);
                
                
                $user_id = $pdo->lastInsertId();
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $full_name;
                $_SESSION['email'] = $email;
                $_SESSION['user_role'] = $user_role;
                
                
                header('Location: ' . ($user_role === 'resident' ? 'dashboard.php' : 'bhw_dashboard.php'));
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'Registration error: ' . $e->getMessage();
        }
    }
}
?>
```


```php
<?php session_start(); ?>
```

```php
<?php if (isset($_SESSION['user_id'])): ?>
    <!
    <div class="relative group">
        <!
    </div>
<?php else: ?>
    <!
    <a href="registration.php">Registration</a>
<?php endif; ?>
```

- ✅ `system-goals.php`
- ✅ `service-areas.php`
- ✅ `surveillance.php`
- ✅ `compliance.php`
- ✅ `landing.php` (already has session_start)
- ✅ `dashboard.php` (already has session_start)
- ✅ `bhw_dashboard.php` (already has session_start)
- ✅ `user-portals.php` (already has session_start)


Run the SQL commands in phpMyAdmin to create the database and tables.

Replace the demo credentials section with PDO database queries (see template above).

- Try registering a new user
- Login with new credentials
- Navigate to sub-pages to verify session persistence
- Check that profile dropdown appears on all pages

```php

echo hashPassword('resident123');  
echo hashPassword('bhw123');       
echo hashPassword('admin123');     
```

Copy these hashed values and insert into the database.


**Solution**: Check database credentials in `db_config.php` (DB_HOST, DB_USER, DB_NAME)

**Solution**: Ensure MySQL service is running and database exists

**Solution**: Verify each page starts with `<?php session_start(); ?>`

**Solution**: Ensure password was hashed using `hashPassword()` before storage


| Column | Type | Constraints |
|
| user_id | INT | PRIMARY KEY, AUTO_INCREMENT |
| full_name | VARCHAR(100) | NOT NULL |
| email | VARCHAR(100) | UNIQUE, NOT NULL |
| password | VARCHAR(255) | NOT NULL (bcrypt hash) |
| role | ENUM | 'resident', 'bhw', 'admin' |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |
| updated_at | TIMESTAMP | AUTO UPDATE |

| Column | Type | Constraints |
|
| appointment_id | INT | PRIMARY KEY, AUTO_INCREMENT |
| user_id | INT | FOREIGN KEY → users |
| appointment_date | DATE | NOT NULL |
| appointment_time | TIME | NOT NULL |
| reason | VARCHAR(255) | NOT NULL |
| status | ENUM | 'scheduled', 'completed', 'cancelled' |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |


