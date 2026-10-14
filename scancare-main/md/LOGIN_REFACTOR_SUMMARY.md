


**OLD:**
```php
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
```

**NEW:**
```php
if (isset($_SESSION['resident_id']) && $_SESSION['user_type'] === 'resident') {
    header('Location: dashboard.php');
    exit;
}
if (isset($_SESSION['worker_id']) && $_SESSION['user_type'] === 'health_worker') {
    header('Location: bhw_dashboard.php');
    exit;
}
```

**WHY:** Separate ID columns for residents vs workers + renamed `user_role` → `user_type`

**OLD:**
```php

$stmt = $pdo->prepare("SELECT user_id, first_name, middle_initial, last_name, user_role, password_hash, is_active 
                       FROM users WHERE LOWER(email) = ?");
```

**NEW:**
```php

$stmt = $pdo->prepare("SELECT resident_id, full_name, email, password_hash, qr_code_id 
                       FROM RESIDENT WHERE LOWER(email) = ?");


$stmt = $pdo->prepare("SELECT worker_id, full_name, email, password_hash, role 
                       FROM HEALTH_WORKER WHERE LOWER(email) = ?");
```

**WHY:** Two separate tables require sequential checking


**For Residents:**
```php
$_SESSION['resident_id'] = $resident['resident_id'];  
$_SESSION['user_type'] = 'resident';                   
$_SESSION['user_name'] = $resident['full_name'];       
```

**For Health Workers:**
```php
$_SESSION['worker_id'] = $worker['worker_id'];        
$_SESSION['user_type'] = 'health_worker';              
$_SESSION['worker_role'] = $worker['role'];            
```


**Changes:**
- Removed `first_name`, `middle_initial`, `last_name` → Now single `full_name` field
- Changed role options from `['resident', 'bhw', 'admin']` → `['resident', 'health_worker']`
  - Admin accounts must be manually assigned in database
- Check both tables for duplicate emails before creating account
- Insert into either RESIDENT or HEALTH_WORKER based on user selection

**New Insert Logic:**

For Residents:
```php
INSERT INTO RESIDENT 
(full_name, date_of_birth, address, contact_number, email, password_hash, is_senior_citizen, qr_code_id)
VALUES (?, ?, ?, ?, ?, ?, 0, NULL)
```

For Health Workers:
```php
INSERT INTO HEALTH_WORKER 
(full_name, role, contact_number, email, password_hash)
VALUES (?, 'bhw', ?, ?, ?)
```


```html
<input name="last_name" />
<input name="first_name" />
<input name="middle_initial" />
```

```html
<input name="full_name" placeholder="Juan Dela Cruz" />
<!
```

**OLD:**
```html
<option value="resident">Resident</option>
<option value="bhw">Barangay Health Worker</option>
<option value="admin">System Administrator</option>
```

**NEW:**
```html
<option value="resident">Resident</option>
<option value="health_worker">Barangay Health Worker</option>
<!
```


| Old Session Variable | New Session Variables | Context |
|
| `$_SESSION['user_id']` | `$_SESSION['resident_id']` OR `$_SESSION['worker_id']` | Different IDs for each table |
| `$_SESSION['user_role']` | `$_SESSION['user_type']` | 'resident' or 'health_worker' |
| N/A | `$_SESSION['worker_role']` | (NEW) Stores 'bhw', 'admin', etc. for workers |
| `$_SESSION['first_name']` | REMOVED | Use `$_SESSION['user_name']` instead |
| `$_SESSION['last_name']` | REMOVED | Use `$_SESSION['user_name']` instead |


These files reference the OLD session variables and must be updated:

1. **dashboard.php** - Uses `$_SESSION['user_id']` → Change to `$_SESSION['resident_id']`
2. **bhw_dashboard.php** - Uses `$_SESSION['user_id']` → Change to `$_SESSION['worker_id']`
3. **admin_dashboard.php** - Uses `$_SESSION['user_id']` → Change to `$_SESSION['worker_id']`
4. **Any query files** - Database queries need to match new table names & FK references
5. **logout.php** - May need updating for session variable names
6. **Any middleware/auth checks** - Update to check for `resident_id` or `worker_id`


1. ✅ **login.php refactored** (DONE)
2. ⏳ **Update dashboard.php** - Change session variable checks
3. ⏳ **Update bhw_dashboard.php** - Change session variable checks
4. ⏳ **Update database queries** - Use new table names & joins
5. ⏳ **Update medical history queries** - Use `PATIENT_RECORD` + JOIN `HEALTH_WORKER`
6. ⏳ **Update appointment queries** - Use `APPOINTMENT` table with new FK structure
7. ⏳ **Test login flow** - Both resident and health worker accounts
8. ⏳ **Test signup flow** - Create new residents and health workers


- [ ] Resident Login - Email/password from RESIDENT table
- [ ] Health Worker Login - Email/password from HEALTH_WORKER table
- [ ] Resident Signup - New RESIDENT account created
- [ ] Health Worker Signup - New HEALTH_WORKER account created with 'bhw' role
- [ ] Duplicate Email Prevention - Both tables checked
- [ ] Session Variables Set Correctly - Check browser developer tools
- [ ] Dashboard Redirects - Resident to dashboard.php, Worker to bhw_dashboard.php
- [ ] Logout - Session cleared properly


- **Admin Creation:** Admin accounts must be created manually in the HEALTH_WORKER table with `role = 'admin'`. Signup only creates 'bhw' accounts.
- **Email Uniqueness:** Must be unique across BOTH tables (no resident and worker with same email)
- **QR Code:** RESIDENT table has optional `qr_code_id` field (currently NULL on signup)
- **Password Hashing:** Still uses BCRYPT with cost=10 (unchanged)
- **Error Logging:** All attempts logged to error_log for debugging


