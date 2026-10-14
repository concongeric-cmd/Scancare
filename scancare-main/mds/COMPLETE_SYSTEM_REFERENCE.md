


This document provides a complete end-to-end implementation of user registration, secure password storage, and login authentication for your ScanCare application.



```sql
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Unique user identifier',
    
    
    email VARCHAR(255) NOT NULL UNIQUE COMMENT 'User email (must be unique)',
    password_hash VARCHAR(255) NOT NULL COMMENT 'Bcrypt hashed password (min 60 chars)',
    first_name VARCHAR(100) NOT NULL COMMENT 'User first name',
    last_name VARCHAR(100) COMMENT 'User last name',
    phone_number VARCHAR(20) COMMENT 'Contact phone number',
    
    
    user_role ENUM('resident', 'bhw', 'admin') DEFAULT 'resident' COMMENT 'User role type',
    is_active TINYINT(1) DEFAULT 1 COMMENT 'Account status: 1=active, 0=inactive',
    
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Account creation date/time',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last profile update date/time',
    last_login TIMESTAMP NULL COMMENT 'Last login date/time',
    
    
    INDEX idx_email (email),
    INDEX idx_role (user_role),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```


1. **Open phpMyAdmin:** http:
2. **Select Database:** Click `scancare_db` in left panel
3. **Click SQL Tab** at the top
4. **Paste the SQL script** above into the text area
5. **Click "Go"** button to execute
6. **Verify:** You should see "Your SQL query has been executed successfully"



**Purpose:** Handle user registration form submission and create new user accounts

**Key Security Features:**
- Input validation and sanitization
- Password hashing using `password_hash()` with BCRYPT
- Duplicate email detection
- Automatic timestamp recording

**How It Works:**

```php
1. Receive POST data from registration form
2. Validate all required fields
3. Check if email already exists
4. Hash password securely: password_hash($password, PASSWORD_BCRYPT)
5. Insert new user into database (MySQL handles created_at timestamp)
6. Create session variables
7. Return JSON response for redirect
```

**Called From:** Your registration form
**Returns:** JSON with success/error message and redirect URL


**Purpose:** Handle user login form submission and authenticate credentials

**Key Security Features:**
- Email format validation
- Secure password verification using `password_verify()`
- Last login timestamp update
- Session creation for authenticated users
- Role-based dashboard routing

**How It Works:**

```php
1. Receive POST data from login form (email + password)
2. Query database for user with that email
3. Check if user account is active
4. Verify password: password_verify($input_password, $database_hash)
5. Update last_login timestamp
6. Create session variables with user info
7. Return JSON with dashboard redirect URL based on role
```

**Called From:** Your login form
**Returns:** JSON with success/error message and redirect URL



When a user registers, the password is **never** stored in plain text:

```php

$password = "mypassword123";  


$password_hash = password_hash("mypassword123", PASSWORD_BCRYPT);


```


When a user logs in, the password is **never** directly compared:

```php

if ($input_password === $database_password) { ... }  


if (password_verify($input_password, $database_hash)) { ... }  
```



```
User enters in signup form:
├─ Email: test1234@gmail.com
├─ Name: Gabriel Tala
├─ Password: MyPassword123!

signup_process.php:
├─ Validates email format
├─ Validates password (min 6 chars)
├─ Hashes password → $2y$10$...
├─ Checks if email exists
└─ Inserts into users table

Database INSERT statement:
INSERT INTO users 
(email, password_hash, first_name, last_name, user_role, is_active) 
VALUES 
('test1234@gmail.com', '$2y$10$...', 'Gabriel', 'Tala', 'resident', 1)

MySQL automatically sets:
├─ created_at = CURRENT_TIMESTAMP (e.g., 2026-06-08 14:35:22)
├─ updated_at = CURRENT_TIMESTAMP (e.g., 2026-06-08 14:35:22)
└─ last_login = NULL (no login yet)

Result in Database:
┌──────────────────────────────────────────────────────────────┐
│ user_id │ email              │ password_hash    │ created_at │
├──────────────────────────────────────────────────────────────┤
│ 1       │ test1234@gmail.com │ $2y$10$AbCd...  │ 2026-06-08 │
│         │                    │                  │ 14:35:22   │
└──────────────────────────────────────────────────────────────┘
```



Your registration form should `POST` to `signup_process.php`:

```html
<form method="POST" action="signup_process.php">
    <input type="email" name="email" required>
    <input type="text" name="first_name" required>
    <input type="text" name="last_name">
    <input type="password" name="password" required>
    <input type="password" name="password_confirm" required>
    <select name="user_role">
        <option value="resident">Resident</option>
        <option value="bhw">BHW</option>
        <option value="admin">Admin</option>
    </select>
    <button type="submit">Sign Up</button>
</form>
```


Your login form should `POST` to `login_process.php`:

```html
<form method="POST" action="login_process.php">
    <input type="email" name="email" required>
    <input type="password" name="password" required>
    <button type="submit">Login</button>
</form>
```


| File | Purpose | Status |
|
| `signup_process.php` | Registration handler | ✓ Created |
| `login_process.php` | Login authentication | ✓ Updated |
| `db_config.php` | Database connection | ✓ Exists |
| `SQL_CREATE_USERS_TABLE.sql` | Database schema | ✓ Created |
| `test-system.php` | Complete system test page | ✓ Created |



**Go to:** http:

This page allows you to:
1. Create a test account in real-time
2. View all users in the database
3. Test login with any account
4. See live database updates


1. **Create Account**
   - Go to registration page
   - Fill in: email, password, name, role
   - Submit form
   - Should be logged in and see your dashboard

2. **Verify in Database**
   - Go to phpMyAdmin
   - View `users` table
   - See your new account with `created_at` timestamp

3. **Test Login**
   - Logout
   - Go to login page
   - Enter your email and password
   - Should be logged in
   - Check `last_login` timestamp was updated


```sql
SELECT user_id, email, first_name, last_name, user_role, created_at, last_login 
FROM users 
ORDER BY created_at DESC;
```

```sql
SELECT * FROM users WHERE email = 'test1234@gmail.com';
```

```sql
SELECT email, created_at, last_login, user_role 
FROM users 
ORDER BY created_at DESC;
```


**Solution:** Use a different email address, or delete the user from phpMyAdmin first.

**Solution:** Check that the password you're entering matches what was set during registration (passwords are case-sensitive).

**Solution:** Ensure `db_config.php` has correct credentials and XAMPP MySQL is running.

**Solution:** Run the SQL CREATE TABLE script in phpMyAdmin to create the `users` table.


After successful login, these are available in `$_SESSION`:

```php
$_SESSION['user_id']       
$_SESSION['email']         
$_SESSION['user_name']     
$_SESSION['user_role']     
$_SESSION['login_time']    
```

Use these in your dashboard/pages to display user info:

```php
<?php
session_start();
echo "Welcome " . $_SESSION['user_name'] . "!";
echo "Your role: " . $_SESSION['user_role'];
?>
```


1. ✅ **Create the database table** using the SQL script
2. ✅ **Place these PHP files** in your project directory
3. ✅ **Link your forms** to use signup_process.php and login_process.php
4. ✅ **Test the system** using test-system.php
5. ✅ **Verify timestamps** in phpMyAdmin that created_at and last_login are updating


If you encounter issues:
1. Check error messages in the JSON response
2. View the browser console (F12) for JavaScript errors
3. Check the database directly in phpMyAdmin
4. Verify db_config.php connection details



**System Complete! Your registration and login system is ready for production use.**

