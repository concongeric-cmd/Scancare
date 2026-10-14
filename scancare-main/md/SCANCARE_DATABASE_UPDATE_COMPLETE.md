


**Last Updated:** 2026-06-08  
**Status:** ✅ Complete  
**System:** ScanCare Healthcare Management System


This document outlines the complete implementation of three new user fields in the ScanCare system:
- ✅ Middle Initial (VARCHAR(1), nullable)
- ✅ Date of Birth (DATE, NOT NULL)
- ✅ Address (VARCHAR(255), NOT NULL)



1. **Open phpMyAdmin** → Select `scancare_db` database
2. **Go to SQL tab** → Paste the script below
3. **Execute** and verify success

```sql

ALTER TABLE users 
ADD COLUMN date_of_birth DATE NOT NULL DEFAULT '1900-01-01' AFTER last_name;


ALTER TABLE users 
ADD COLUMN address VARCHAR(255) NOT NULL DEFAULT '' AFTER date_of_birth;


UPDATE users SET date_of_birth = '1900-01-01' WHERE date_of_birth = '1900-01-01';
UPDATE users SET address = 'Not Provided' WHERE address = '';
```

```
users
├── user_id (PK)
├── email
├── password_hash
├── first_name
├── middle_initial ← (already exists)
├── last_name
├── date_of_birth ← (NEW - DATE, NOT NULL)
├── address ← (NEW - VARCHAR(255), NOT NULL)
├── phone_number
├── user_role
├── is_active
├── created_at
├── updated_at
├── last_login
```


- **[login.php](login.php)** (Lines 76-280)


Added validation for the two new fields:

```php
if (empty($date_of_birth)) {
    $errors[] = 'Date of birth is required.';
} else {
    
    $date_obj = DateTime::createFromFormat('Y-m-d', $date_of_birth);
    if (!$date_obj || $date_obj->format('Y-m-d') !== $date_of_birth) {
        $errors[] = 'Invalid date of birth format (use YYYY-MM-DD).';
    } else {
        
        $birth_time = strtotime($date_of_birth);
        $current_time = time();
        $age = ($current_time - $birth_time) / (365.25 * 24 * 60 * 60);
        if ($age < 0) {
            $errors[] = 'Date of birth cannot be in the future.';
        }
    }
}

if (empty($address)) {
    $errors[] = 'Address is required.';
} elseif (strlen($address) < 5) {
    $errors[] = 'Address must be at least 5 characters.';
}
```

Updated the INSERT query to include all three fields:

```php
if ($has_middle_initial) {
    $stmt = $pdo->prepare("
        INSERT INTO users 
        (email, password_hash, first_name, middle_initial, last_name, date_of_birth, address, phone_number, user_role, is_active, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
    ");
    
    $result = $stmt->execute([
        $email,
        $password_hash,
        $first_name,
        !empty($middle_initial) ? $middle_initial : NULL,
        $last_name,
        $date_of_birth,
        $address,
        $phone,
        $user_role
    ]);
}
```

Added two new form input fields in the signup form:

```html
<div>
    <label class="block text-sm font-semibold text-slate-900 mb-2.5">Date of Birth *</label>
    <input type="date" name="date_of_birth" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent transition-all" title="Select your date of birth">
</div>

<div>
    <label class="block text-sm font-semibold text-slate-900 mb-2.5">Address *</label>
    <textarea name="address" required rows="3" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent transition-all resize-none" placeholder="Enter your complete address (Purok, Street, Barangay, Municipality)"></textarea>
</div>
```

- ✅ **Prepared Statements**: Uses PDO prepared statements to prevent SQL injection
- ✅ **Input Validation**: Date format, address length, and future date checks
- ✅ **Password Hashing**: Uses bcrypt (PASSWORD_BCRYPT)
- ✅ **Error Handling**: Comprehensive try-catch blocks with logging
- ✅ **Field Type Checking**: Validates user_role against allowed values


- **[profile.php](profile.php)** (Complete rewrite)


```php

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}


$stmt = $pdo->prepare("
    SELECT 
        user_id, first_name, middle_initial, last_name, date_of_birth, address,
        email, phone_number, user_role, is_active, created_at, last_login
    FROM users 
    WHERE user_id = ? 
    LIMIT 1
");

$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
```

All output is protected using `htmlspecialchars()`:

```php
$patientData = [
    'userId' => htmlspecialchars($user['user_id']),
    'firstName' => htmlspecialchars($user['first_name'] ?? ''),
    'middleInitial' => htmlspecialchars($user['middle_initial'] ?? ''),
    'lastName' => htmlspecialchars($user['last_name'] ?? ''),
    'fullName' => htmlspecialchars(trim($user['first_name'] . ' ' . ($user['middle_initial'] ? $user['middle_initial'] . ' ' : '') . $user['last_name'])),
    'dateOfBirth' => htmlspecialchars($user['date_of_birth'] ?? ''),
    'address' => htmlspecialchars($user['address'] ?? ''),
    'emailAddress' => htmlspecialchars($user['email'] ?? ''),
    'contactNumber' => htmlspecialchars($user['phone_number'] ?? ''),
    
];
```


**Section A: Personal Information**
Displays in a clean, read-only format:
- First Name
- Middle Initial (nullable - shows "—" if empty)
- Last Name
- Full Name (computed field, highlighted)
- Date of Birth (formatted: "Month DD, YYYY")
- Email Address
- Phone Number

**Section B: Address Information**
- Complete Address (displayed in a text area-like div with proper text wrapping)

**Section C: Account Information**
- User ID (with # prefix)
- Account Status (color-coded: green for Active, red for Inactive)
- User Role (Resident, BHW, or Admin)
- Member Since (formatted date)
- Last Login (formatted date and time)

- ✅ **Responsive Grid Layout**: Adapts to mobile and desktop
- ✅ **Error Handling**: Shows user-friendly error messages
- ✅ **Session Validation**: Redirects to login if not authenticated
- ✅ **Data Formatting**: Dates displayed in readable format (F d, Y)
- ✅ **Status Indicators**: Color-coded account status
- ✅ **Print Functionality**: Print button for profile document
- ✅ **Navigation**: Back to dashboard and logout buttons


- [ ] **SQL Execution**: Run ALTER TABLE commands and verify new columns exist
- [ ] **Signup Form**: Test with all fields including new date_of_birth and address
- [ ] **Date Validation**: 
  - [ ] Try future date (should fail)
  - [ ] Try valid date (should succeed)
  - [ ] Try invalid format (should fail)
- [ ] **Address Validation**: 
  - [ ] Try empty (should fail)
  - [ ] Try < 5 characters (should fail)
  - [ ] Try valid address (should succeed)
- [ ] **Database Insert**: Verify new user appears in database with all fields
- [ ] **Profile Page**: 
  - [ ] Log in with new user
  - [ ] Visit profile.php
  - [ ] Verify all data displays correctly
  - [ ] Verify special characters are escaped (XSS protection)
  - [ ] Test print functionality
- [ ] **Existing Users**: Verify profile page works with existing users (middle_initial nullable, address shows default)
- [ ] **Session Security**: Try accessing profile.php without login (should redirect)


| Feature | Implementation |
|
| **SQL Injection Prevention** | PDO Prepared Statements |
| **XSS Protection** | htmlspecialchars() on all output |
| **Password Security** | bcrypt hashing (cost=10) |
| **Session Management** | $_SESSION verification on profile access |
| **Error Logging** | error_log() for debugging without exposing to users |
| **Database Connection** | PDO with exception handling |
| **Input Validation** | Type checking, format validation, length validation |



**Signup:**
1. Navigate to http:
2. Fill in all required fields
3. Enter date of birth in format: YYYY-MM-DD (e.g., 1990-05-15)
4. Enter complete address (Purok, Street, Barangay, Municipality)
5. Click "Create Account"

**View Profile:**
1. Log in to your account
2. Navigate to http:
3. View all profile information including new fields
4. Print profile using "Print Profile" button


1. **[login.php](login.php)**
   - Added `$date_of_birth` and `$address` variable extraction
   - Added validation for new fields
   - Updated INSERT statement with new columns
   - Added HTML form fields for new inputs

2. **[profile.php](profile.php)**
   - Complete rewrite with database integration
   - Added session verification
   - Added secure data fetching with prepared statements
   - Added XSS protection
   - Updated display sections with new fields
   - Added error handling and user-friendly messages


```
User Signup Form 
    ↓
Input Validation (backend)
    ↓
Prepared Statement
    ↓
MySQL Database (users table)
    ↓
Session Creation
    ↓
User Profile Page
    ↓
Secure Data Display (htmlspecialchars)
```


| Task | Status | Files | Verification |
|
| SQL ALTER SCRIPT | ✅ Complete | [TASK 1](#task-1) | Execute in phpMyAdmin |
| SIGNUP FORM | ✅ Complete | [login.php](login.php) | Test at /login.php?signup=1 |
| PROFILE DISPLAY | ✅ Complete | [profile.php](profile.php) | Test at /profile.php |


If you encounter issues:

1. **Database columns not found**: Ensure SQL ALTER script was executed successfully
2. **Profile shows blank fields**: Check that user data was saved correctly during signup
3. **Date format errors**: Ensure HTML5 date input is supported in browser
4. **Address not saving**: Check textarea is properly named "address" in form



**Implementation Date:** 2026-06-08  
**Developer:** Full-Stack PHP Expert  
**System:** ScanCare v1.0

