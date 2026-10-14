


**Sequence:**
1. ✅ Run SQL migration: `MIGRATION_REMOVE_AUTO_INCREMENT.sql`
2. ✅ Deploy updated `login.php` (for new registrations)
3. ⏳ **Run this script** - `UPDATE_EXISTING_ACCOUNTS_TO_6DIGIT_IDS.php` (for existing accounts)


**Purpose:** Convert ALL existing resident accounts from auto-increment IDs (1, 2, 3...) to unique 6-digit random numbers (100000-999999)

**Operations:**
1. Fetches all residents with ID < 100000
2. For each resident:
   - Generates unique 6-digit ID
   - Updates resident_id in RESIDENT table
   - Updates resident_id references in APPOINTMENT table
   - Updates resident_id references in MEDICAL_HISTORY table (if exists)
3. Disables/re-enables foreign keys for safe batch updates
4. Provides detailed HTML report with success/error tracking

**Example Output:**
```
[STEP 1] Foreign key checks disabled
[STEP 2] Found 5 existing residents to migrate
[STEP 3] Beginning ID generation and update process...
[UPDATED] ID 1 → 456789 | Juan Dela Cruz | juan@email.com
[UPDATED] ID 2 → 234567 | Maria Santos | maria@email.com
[UPDATED] ID 3 → 678901 | Pedro Reyes | pedro@email.com
[UPDATED] ID 4 → 345678 | Rosa Garcia | rosa@email.com
[UPDATED] ID 5 → 567890 | Anna Lopez | anna@email.com
[STEP 4] Foreign key checks re-enabled
[VERIFICATION] Total residents with 6-digit IDs: 5
[COMPLETE] Migration finished. Updated: 5, Errors: 0
```



1. **Open browser** and navigate to:
   ```
   http:
   ```

2. **Wait for script to complete** (displays HTML report)

3. **Verify results:**
   - ✅ All residents updated to 6-digit IDs
   - ✅ 0 errors
   - ✅ No foreign key violations

4. **After verification succeeds:**
   - Delete the migration script file for security
   - OR move to secure admin folder


```bash
cd c:\xampp\htdocs\itelec4
php UPDATE_EXISTING_ACCOUNTS_TO_6DIGIT_IDS.php > migration_report.html
```

Then open `migration_report.html` in browser to view report.



**Run this SQL in phpMyAdmin:**
```sql
SELECT resident_id, full_name, email 
FROM RESIDENT 
WHERE resident_id >= 100000
ORDER BY resident_id DESC;
```

**Expected Result:**
- All resident_id values are 6 digits (100000-999999)
- All records are displayed


**Run this SQL:**
```sql
SELECT resident_id, COUNT(*) as duplicate_count
FROM RESIDENT
GROUP BY resident_id
HAVING COUNT(*) > 1;
```

**Expected Result:**
- No rows returned (no duplicates)


**Run this SQL:**
```sql
SELECT a.appointment_id, a.resident_id, r.full_name
FROM appointment a
INNER JOIN RESIDENT r ON a.resident_id = r.resident_id
LIMIT 10;
```

**Expected Result:**
- All appointments linked to 6-digit resident IDs
- No NULL values


**Run this SQL:**
```sql
SELECT COUNT(*) as old_id_count
FROM RESIDENT
WHERE resident_id < 100000;
```

**Expected Result:**
- 0 (all old auto-increment IDs are gone)


| Table | Column | Update Type | Foreign Keys |
|
| **RESIDENT** | resident_id | Direct update to 6-digit | Primary Key |
| **APPOINTMENT** | resident_id | Cascade update via loop | Foreign Key reference |
| **MEDICAL_HISTORY** | resident_id | Cascade update via loop | Foreign Key reference (if exists) |


1. **Foreign Key Checks:** Script temporarily disables foreign key checks:
   ```php
   SET FOREIGN_KEY_CHECKS = 0  
   SET FOREIGN_KEY_CHECKS = 1  
   ```

2. **Duplicate Detection:** Checks both:
   - Existing resident_id values in database
   - Previously assigned 6-digit IDs in current migration

3. **Attempt Limit:** Max 50 attempts per ID (prevents infinite loops)

4. **Error Handling:** Catches exceptions and logs failures without stopping entire process

5. **Transaction-Like Behavior:** Updates related tables atomically


- Increase timeout: Edit line 8 `set_time_limit(300)` to larger value
- Example: `set_time_limit(600)` for 10 minutes

- Database likely has > 900,000 existing 6-digit IDs (extremely unlikely)
- Increase attempt limit: Edit line ~115 `$max_attempts = 50` to higher value

- SQL migration may not have completed successfully
- Run `MIGRATION_REMOVE_AUTO_INCREMENT.sql` first
- Delete all foreign key constraints and re-create them if needed

- Partially successful migration
- Check error log entries (red text in report)
- Can safely re-run script - duplicate IDs won't be re-assigned

- All existing residents already have 6-digit IDs
- This is expected if you already ran the migration previously
- Script is safe to run multiple times


- [ ] ✅ SQL migration run: `MIGRATION_REMOVE_AUTO_INCREMENT.sql`
- [ ] ✅ Database connection verified working
- [ ] ✅ Backup created of current database
- [ ] ✅ All users logged out (no active sessions)
- [ ] ✅ No concurrent edits or appointments being created
- [ ] ✅ Script file in place: `UPDATE_EXISTING_ACCOUNTS_TO_6DIGIT_IDS.php`


- [ ] ✅ Script executed successfully (no fatal errors)
- [ ] ✅ HTML report shows all updates successful
- [ ] ✅ Verification SQL queries confirm 6-digit IDs
- [ ] ✅ No duplicate IDs found
- [ ] ✅ Appointments linked correctly to new IDs
- [ ] ✅ Old IDs (< 100000) completely removed
- [ ] ✅ **Delete migration script for security**


**After successful execution:**
```
DELETE or MOVE: UPDATE_EXISTING_ACCOUNTS_TO_6DIGIT_IDS.php
```

This script contains database update logic and should not remain accessible after migration completes. Store backup copy securely if needed for audit purposes.


```
NEW REGISTRATIONS (via login.php):
- Resident ID: 6-digit random (100000-999999)
- Generation: Automatic during signup
- QR Code: Auto-displays on dashboard

EXISTING ACCOUNTS (via this script):
- Resident ID: Converted from auto-increment to 6-digit
- Foreign Keys: All updated in appointment & medical_history
- QR Code: Auto-displays with new 6-digit ID on dashboard

DATABASE STATE:
- No AUTO_INCREMENT on resident_id column
- All resident IDs: >= 100000
- No old IDs (1-99999) remain
- All foreign key relationships intact
```


After running both migrations (SQL + this script):
- ✅ All patients (new & existing) have 6-digit unique IDs
- ✅ QR codes display 6-digit numbers on dashboard
- ✅ No auto-increment IDs remain in system
- ✅ All appointments linked to correct 6-digit IDs
- ✅ System ready for production use


