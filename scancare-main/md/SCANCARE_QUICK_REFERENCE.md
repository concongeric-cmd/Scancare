


Copy and paste into phpMyAdmin → SQL tab:

```sql
ALTER TABLE users 
ADD COLUMN date_of_birth DATE NOT NULL DEFAULT '1900-01-01' AFTER last_name;

ALTER TABLE users 
ADD COLUMN address VARCHAR(255) NOT NULL DEFAULT '' AFTER date_of_birth;

UPDATE users SET date_of_birth = '1900-01-01' WHERE date_of_birth = '1900-01-01';
UPDATE users SET address = 'Not Provided' WHERE address = '';
```

- ✅ [login.php](login.php) - Already updated with new form fields & validation
- ✅ [profile.php](profile.php) - Already updated to display user data from database


```php

if (empty($date_of_birth)) {
    $errors[] = 'Date of birth is required.';
} else {
    $date_obj = DateTime::createFromFormat('Y-m-d', $date_of_birth);
    if (!$date_obj || $date_obj->format('Y-m-d') !== $date_of_birth) {
        $errors[] = 'Invalid date of birth format (use YYYY-MM-DD).';
    }
}


if (empty($address)) {
    $errors[] = 'Address is required.';
} elseif (strlen($address) < 5) {
    $errors[] = 'Address must be at least 5 characters.';
}
```

```php
$stmt = $pdo->prepare("
    INSERT INTO users 
    (email, password_hash, first_name, middle_initial, last_name, 
     date_of_birth, address, phone_number, user_role, is_active, 
     created_at, updated_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
");

$result = $stmt->execute([
    $email, $password_hash, $first_name, $middle_initial, $last_name,
    $date_of_birth, $address, $phone, $user_role
]);
```

```php
$stmt = $pdo->prepare("
    SELECT 
        user_id, first_name, middle_initial, last_name, date_of_birth, 
        address, email, phone_number, user_role, is_active, created_at, last_login
    FROM users 
    WHERE user_id = ? 
    LIMIT 1
");

$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
```

```php
$patientData = [
    'firstName' => htmlspecialchars($user['first_name'] ?? ''),
    'dateOfBirth' => htmlspecialchars($user['date_of_birth'] ?? ''),
    'address' => htmlspecialchars($user['address'] ?? ''),
    
];
```



```html
<!
<div>
    <label class="block text-sm font-semibold text-slate-900 mb-2.5">Date of Birth *</label>
    <input 
        type="date" 
        name="date_of_birth" 
        required 
        class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent transition-all" 
        title="Select your date of birth"
    >
</div>

<!
<div>
    <label class="block text-sm font-semibold text-slate-900 mb-2.5">Address *</label>
    <textarea 
        name="address" 
        required 
        rows="3" 
        class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent transition-all resize-none" 
        placeholder="Enter your complete address (Purok, Street, Barangay, Municipality)"
    ></textarea>
</div>
```


**Table:** `users`

```sql
+
| Field           | Type      | Null | Key | Default             |
+
| user_id         | int(11)   | NO   | PRI | NULL                |
| email           | varchar   | NO   | UNI | NULL                |
| password_hash   | varchar   | NO   |     | NULL                |
| first_name      | varchar   | NO   |     | NULL                |
| middle_initial  | varchar(1)| YES  |     | NULL                |
| last_name       | varchar   | NO   |     | NULL                |
| date_of_birth   | DATE      | NO   |     | 1900-01-01  [NEW]   |
| address         | varchar   | NO   |     | [NEW]               |
| phone_number    | varchar   | NO   |     | NULL                |
| user_role       | varchar   | NO   |     | NULL                |
| is_active       | tinyint   | NO   |     | 1                   |
| created_at      | timestamp | NO   |     | CURRENT_TIMESTAMP   |
| updated_at      | timestamp | NO   |     | CURRENT_TIMESTAMP   |
| last_login      | timestamp | YES  |     | NULL                |
+
```


```bash

SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME='users' AND TABLE_SCHEMA='scancare_db';

SELECT user_id, first_name, middle_initial, last_name, date_of_birth, address 
FROM users LIMIT 5;

SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME='users' AND TABLE_SCHEMA='scancare_db' 
AND (COLUMN_NAME='date_of_birth' OR COLUMN_NAME='address');
```


| Endpoint | Purpose |
|
| `http:
| `http:
| `http:


```bash

mysql -u root -p scancare_db

DESC users;
SELECT user_id, first_name, date_of_birth, address FROM users LIMIT 1;
```


| Issue | Solution |
|
| "Column 'date_of_birth' doesn't exist" | Run SQL ALTER script |
| "Date must be between..." | Use HTML5 date picker or YYYY-MM-DD format |
| Profile shows blank fields | Check user was created with new fields |
| Address shows "Not Provided" | Verify UPDATE statement ran or re-enter data |
| Profile redirects to login | Ensure session is active (`$_SESSION['user_id']` set) |



**Last Updated:** 2026-06-08  
**Status:** ✅ Production Ready

