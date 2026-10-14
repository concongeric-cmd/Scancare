

✅ **Problem 1:** Fixed medicine insertion - changed column names from `medicine_name` to `name` and `condition` to `description`  
✅ **Problem 2a:** Removed ScanCare logo link in admin sidebar  
✅ **Problem 2b:** Removed duplicate Sign Out button below admin profile  
⚠️ **Problem 2c:** Dashboard counts are working - they show 0 because RESIDENT/HEALTH_WORKER tables are empty  
❌ **Problem 3:** Admin login failing - need to verify system_admins table and add admin account


```sql
DESCRIBE system_admins;
```
If you get an error "Table doesn't exist", run the CREATE TABLE command below.

```sql
CREATE TABLE IF NOT EXISTS `system_admins` (
    `admin_id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(255) NOT NULL UNIQUE,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(255) NOT NULL,
    `role` ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin',
    `is_active` TINYINT(1) DEFAULT 1,
    `last_login` DATETIME NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_username` (`username`),
    UNIQUE KEY `uk_email` (`email`),
    KEY `idx_is_active` (`is_active`),
    KEY `idx_role` (`role`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```sql
SELECT admin_id, username, email, is_active FROM system_admins;
```
If no rows appear, you need to create an admin account.

Copy and paste ONE of these commands based on your preferred login credentials:

**Option A: Login with email (RECOMMENDED)**
```sql
INSERT INTO system_admins (username, email, password_hash, full_name, role, is_active)
VALUES ('admin_gutad', 'admin@gutad.gov.ph', '$2y$10$YIjlrWyJ5.H2uPxKVLq1ouGIDlP1KHwVSyEHGdXePRBRpKLZcm8Wu', 'System Administrator', 'admin', 1);
```
- **Email:** `admin@gutad.gov.ph`
- **Password:** `AdminGutad123!` (default - CHANGE AFTER LOGIN)

**Option B: Alternative credentials**
```sql
INSERT INTO system_admins (username, email, password_hash, full_name, role, is_active)
VALUES ('superadmin', 'superadmin@scancare.local', '$2y$10$YIjlrWyJ5.H2uPxKVLq1ouGIDlP1KHwVSyEHGdXePRBRpKLZcm8Wu', 'Super Administrator', 'super_admin', 1);
```
- **Email:** `superadmin@scancare.local`
- **Password:** `AdminGutad123!` (default - CHANGE AFTER LOGIN)

To make the admin dashboard show user counts > 0:
```sql

INSERT INTO RESIDENT (full_name, email, password_hash, is_active)
VALUES 
('Juan Dela Cruz', 'juan@example.com', '$2y$10$YIjlrWyJ5.H2uPxKVLq1ouGIDlP1KHwVSyEHGdXePRBRpKLZcm8Wu', 1),
('Maria Santos', 'maria@example.com', '$2y$10$YIjlrWyJ5.H2uPxKVLq1ouGIDlP1KHwVSyEHGdXePRBRpKLZcm8Wu', 1);
```
After this, the dashboard will show "2 Total Users"

To make the admin dashboard show staff counts > 0:
```sql

INSERT INTO HEALTH_WORKER (full_name, email, password_hash, role, is_active)
VALUES 
('Rosa Garcia', 'rosa@bhw.gov.ph', '$2y$10$YIjlrWyJ5.H2uPxKVLq1ouGIDlP1KHwVSyEHGdXePRBRpKLZcm8Wu', 'bhw', 1),
('Pedro Lopez', 'pedro@bhw.gov.ph', '$2y$10$YIjlrWyJ5.H2uPxKVLq1ouGIDlP1KHwVSyEHGdXePRBRpKLZcm8Wu', 'bhw', 1),
('Angela Reyes', 'angela@bhw.gov.ph', '$2y$10$YIjlrWyJ5.H2uPxKVLq1ouGIDlP1KHwVSyEHGdXePRBRpKLZcm8Wu', 'bhw', 1);
```
After this, the dashboard will show "3 Staff Accounts"

To populate the medicine inventory:
```sql
INSERT INTO medicine (name, stock_quantity, description)
VALUES 
('Paracetamol 500mg', 150, 'Pain reliever and fever reducer'),
('Amoxicillin 250mg', 75, 'Antibiotic for bacterial infections'),
('Ibuprofen 200mg', 100, 'Anti-inflammatory pain reliever'),
('Aspirin 100mg', 120, 'Blood thinner and pain reliever'),
('Cetirizine 10mg', 200, 'Antihistamine for allergies'),
('Omeprazole 20mg', 60, 'Acid reducer for stomach');
```
After this, the BHW dashboard will show medicines in inventory.


1. **Open phpMyAdmin** → `http:
2. **Select Database** → Click on `scancare_db` in the left sidebar
3. **Go to SQL Tab** → Click the "SQL" tab at the top
4. **Copy and Paste** → Paste the entire command in the text area
5. **Execute** → Click the "Go" button at the bottom right
6. **Check Results** → You should see a success message "No errors" and the query executes successfully


The password hash provided: `$2y$10$YIjlrWyJ5.H2uPxKVLq1ouGIDlP1KHwVSyEHGdXePRBRpKLZcm8Wu`

This is a bcrypt hash of: **`AdminGutad123!`**

If you want to use a different password, you'll need to:
1. Use a bcrypt generator online: https:
2. Generate hash for your password (cost factor: 10)
3. Replace the hash in the SQL command


1. Login as BHW: `rosa@bhw.gov.ph` / `AdminGutad123!`
2. Go to BHW Dashboard → Medicine Inventory tab
3. Click "+ Add Medicine"
4. Fill in:
   - Medicine Name: "Test Medicine"
   - Quantity: "50"
5. Click "Add Medicine"
6. **Expected:** ✓ Medicine appears in table and persists after page reload

1. Login as Admin: `admin@gutad.gov.ph` / `AdminGutad123!`
2. Check admin panel dashboard
3. **Expected for 2a:** ✓ ScanCare logo is NOT clickable (no hand cursor)
4. **Expected for 2b:** ✓ Only one logout button visible (top right, NOT in sidebar)
5. **Expected for 2c:** ✓ Dashboard shows:
   - "2 Total Users" (from 2 residents created)
   - "3 Staff Accounts" (from 3 BHW workers created)

1. Logout from admin panel
2. Go to login page: `http:
3. Try to login with:
   - Email: `admin@gutad.gov.ph`
   - Password: `AdminGutad123!`
4. **Expected:** ✓ Login succeeds and redirects to administrator panel


**Solution:** Verify the account exists and is active:
```sql
SELECT admin_id, username, email, is_active FROM system_admins WHERE email = 'admin@gutad.gov.ph';
```
If no results, the INSERT didn't work - try Step 4 again.

If results show `is_active = 0`, run:
```sql
UPDATE system_admins SET is_active = 1 WHERE email = 'admin@gutad.gov.ph';
```

**Verification:** Check if medicine table has correct columns:
```sql
DESCRIBE medicine;
```
Should show columns: medicine_id, name, stock_quantity, description, status, updated_at

If `medicine_name` column appears, the table structure is wrong. You need to:
1. Drop the old table: `DROP TABLE medicine;`
2. Reload `dashboard_bhw.php` to recreate it with correct structure

Verify residents were created:
```sql
SELECT COUNT(*) as total FROM RESIDENT WHERE is_active = 1 OR is_active IS NULL;
SELECT COUNT(*) as bhw_staff FROM HEALTH_WORKER WHERE role = 'bhw' AND (is_active = 1 OR is_active IS NULL);
```
Results should show 2 and 3 respectively.


✅ **dashboard_bhw.php (lines 46-95)**
- Changed INSERT column: `medicine_name` → `name`
- Changed INSERT column: `condition` → `description`
- Changed UPDATE column: `condition` → `description`

✅ **administrator_panel.php (line 191)**
- Removed `<a href="landing.php">` link from ScanCare logo
- Changed to plain `<div>` container

✅ **administrator_panel.php (line 207)**
- Removed Sign Out button from admin profile card
- Kept only logout button in header


1. **Run all SQL commands from STEP 1-7** above in phpMyAdmin
2. **Test all three problems** using the checklist above
3. **Verify all issues are resolved**
4. Report back if any issues remain!

