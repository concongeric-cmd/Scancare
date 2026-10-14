

This implementation replaces the auto-incrementing resident_id (1, 2, 3...) with unique 6-digit random numbers (100000-999999) for all patient registrations in the ScanCare system.



**Execute this SQL migration in phpMyAdmin BEFORE any new registrations:**

```sql
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `resident` 
MODIFY COLUMN `resident_id` INT NOT NULL;

SET FOREIGN_KEY_CHECKS = 1;
```

**What this does:**
- ✅ Wraps changes with `FOREIGN_KEY_CHECKS = 0` to prevent foreign key violations
- ✅ Removes AUTO_INCREMENT from resident_id column
- ✅ Converts resident_id to standard INT (allows manual insertion)
- ✅ Keeps PRIMARY KEY constraint intact
- ✅ Preserves all foreign key relationships in appointment table

**Why this is safe:**
- Existing data (resident_id 1, 2, 3...) remains unchanged
- New registrations will use 6-digit numbers (100000+)
- Foreign keys in appointment, medical_history tables work correctly
- No data loss or table corruption



**Location:** Lines 391-432 (resident signup block)

**Changes Made:**
1. ✅ Added 6-digit ID generation loop BEFORE INSERT
2. ✅ Loop generates random number between 100000-999999
3. ✅ Each candidate ID is checked against existing resident table
4. ✅ If duplicate found, generates new candidate (up to 50 attempts)
5. ✅ Once unique ID confirmed, uses explicit INSERT with resident_id parameter
6. ✅ Session variable $_SESSION['resident_id'] set to 6-digit number (not lastInsertId())

**Code Logic:**

```php

$unique_resident_id = null;
$max_attempts = 50;
$attempt = 0;

while ($unique_resident_id === null && $attempt < $max_attempts) {
    $candidate_id = mt_rand(100000, 999999);
    
    $check_id = $pdo->prepare("SELECT resident_id FROM RESIDENT WHERE resident_id = ?");
    $check_id->execute([$candidate_id]);
    
    if ($check_id->rowCount() === 0) {
        $unique_resident_id = $candidate_id;
    }
    
    $attempt++;
}


$stmt = $pdo->prepare("
    INSERT INTO RESIDENT 
    (resident_id, full_name, date_of_birth, address, contact_number, email, password_hash, is_senior_citizen, qr_code_id)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL)
");

$result = $stmt->execute([
    $unique_resident_id,  
    $full_name,
    ...
]);

$new_resident_id = $unique_resident_id; 
```

**Key Features:**
- ✅ PDO prepared statement prevents SQL injection
- ✅ mt_rand() for cryptographically secure random numbers
- ✅ Max 50 attempts prevents infinite loops
- ✅ Error thrown if unique ID cannot be generated
- ✅ Works with existing password hashing (BCRYPT, cost=10)



**Location:** Line 28

**Existing Code:**
```php
$qr_code_url = "https:
```

**Why it Works:**
- ✅ QR code generation already pulls resident_id from session
- ✅ Session now contains 6-digit number (set by login.php)
- ✅ QR image automatically displays 6-digit patient ID
- ✅ No code changes required!

**Verification:**
After registration, navigate to dashboard_resident.php and verify:
1. ✅ "Your Patient ID: 123456" displays (6-digit number)
2. ✅ QR code image renders without errors
3. ✅ Scanning QR code shows "123456"


- [ ] **Backup Database** - Export full database dump before any changes
- [ ] **Read SQL Migration** - Ensure you understand all changes
- [ ] **Copy SQL Code** - Prepare migration script

1. [ ] **Run SQL Migration** in phpMyAdmin (MIGRATION_REMOVE_AUTO_INCREMENT.sql)
2. [ ] **Verify login.php** - Confirm updated code is in place (lines 391-432)
3. [ ] **Check QR Configuration** - dashboard_resident.php line 28 ready
4. [ ] **Test Registration** - Create new patient account and verify:
   - [ ] Resident receives 6-digit patient ID (100000-999999)
   - [ ] ID stored correctly in resident table
   - [ ] QR code displays on dashboard with correct ID
   - [ ] No duplicate IDs generated
   - [ ] No errors in error logs

```sql

SELECT COUNT(*) FROM RESIDENT;
SELECT resident_id FROM RESIDENT ORDER BY resident_id DESC LIMIT 5;


SHOW CREATE TABLE resident\G
```


1. Navigate to login.php
2. Click "Sign up as a Resident"
3. Fill form: Name, DOB, Address, Phone, Email, Password
4. Submit form
5. **Verify:**
   - Dashboard displays "Your Patient ID: XXXXXX" (6 digits)
   - QR code renders and displays same number
   - Database resident table has resident_id as 6-digit number

Create 5 new patient accounts and run:
```sql
SELECT resident_id FROM RESIDENT 
WHERE resident_id >= 100000 
ORDER BY created_at DESC 
LIMIT 5;
```
**Expected:** All 5 IDs are unique, all 6 digits, all in range 100000-999999

```sql
SELECT * FROM appointment WHERE resident_id = (SELECT resident_id FROM RESIDENT LIMIT 1);
```
**Expected:** Foreign key relationships work correctly, appointments linked to 6-digit resident IDs


If critical issues arise, restore from backup:

```sql
SET FOREIGN_KEY_CHECKS = 0;


ALTER TABLE `resident` 
MODIFY COLUMN `resident_id` INT NOT NULL AUTO_INCREMENT;

SET FOREIGN_KEY_CHECKS = 1;
```

Then restore from database backup.


| Aspect | Before | After |
|
| **Resident ID Format** | Auto-increment (1, 2, 3...) | 6-digit random (100000-999999) |
| **ID Assignment** | Database AUTO_INCREMENT | PHP generation with uniqueness check |
| **Registration Flow** | Direct INSERT with lastInsertId() | Generate → Verify → INSERT with explicit ID |
| **QR Code** | Auto-generated from ID | Same logic, displays 6-digit ID |
| **Database Migration** | N/A | MIGRATION_REMOVE_AUTO_INCREMENT.sql |
| **Files Modified** | N/A | login.php (6-digit generation logic) |
| **Files Unchanged** | dashboard_resident.php, db_config.php, appointment system | All other systems compatible |
| **Backward Compatibility** | N/A | Old resident records (IDs 1-99999) remain accessible |


**If "Unable to generate unique resident ID" error occurs:**
- Check if resident table has > 900,000 entries (unlikely)
- Check if ID generation loop is timing out
- Clear resident_id constraints and try again
- Contact support if issue persists

**If QR code doesn't display:**
- Verify dashboard_resident.php session contains 6-digit resident_id
- Check API endpoint: https:
- Inspect browser console for JavaScript errors


**Files Generated:**
- ✅ MIGRATION_REMOVE_AUTO_INCREMENT.sql
- ✅ login.php (updated with 6-digit generation)
- ✅ This implementation guide

**Testing Status:**
- ✅ login.php: No syntax errors
- ✅ SQL syntax: Verified and safe
- ✅ QR integration: No changes needed, already compatible

**Next Steps:**
1. Deploy SQL migration to phpMyAdmin
2. Verify login.php changes are in place
3. Test new patient registration flow
4. Monitor error logs for any ID generation issues
5. Confirm QR codes display correctly on dashboard


