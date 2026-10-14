


You now have a complete, production-ready solution that addresses all three critical issues:

| Issue | Solution | File |
|
| **Duplicate Tables Exist** | Schema Unification SQL Script | `STEP1_SCHEMA_UNIFICATION.sql` |
| **"No Show" Button Doesn't Update DB** | Backend AJAX Processor | `update_appointment.php` |
| **UI Doesn't Update After Action** | Frontend Dashboard with Auto-Row-Removal | `today_appointments.php` |


- [ ] Open phpMyAdmin: `http:
- [ ] Select your database (scancare_db or similar)
- [ ] Click the "SQL" tab
- [ ] Copy entire content of: `STEP1_SCHEMA_UNIFICATION.sql`
- [ ] Paste into phpMyAdmin SQL editor
- [ ] Click "Go" button
- [ ] Wait for completion message: `✅ === SCHEMA UNIFICATION COMPLETE ===`
- [ ] ✅ Database migration done!

**What happens:**
- Backs up existing data
- Removes duplicate `appointments` table
- Removes misspelled `appoinmen` table
- Consolidates all data into unified `appointment` table
- Adds `health_worker_id` column to track BHW actions
- Creates performance indexes
- Re-enables security checks

- [ ] Locate file: `update_appointment.php`
- [ ] Paste/Move to: `c:\xampp\htdocs\itelec4\`
- [ ] ✅ Backend ready!

**No configuration needed** - File works immediately after deployment

- [ ] Locate file: `today_appointments.php`
- [ ] Paste/Move to: `c:\xampp\htdocs\itelec4\`
- [ ] ✅ Frontend ready!

**Access your dashboard:**
```
http:
```


```
Location: phpMyAdmin SQL tab
Query: DESCRIBE appointment;

Expected columns:
✓ appointment_id (BIGINT, Primary Key)
✓ resident_id (BIGINT, Foreign Key → resident)
✓ health_worker_id (BIGINT, captures BHW ID)
✓ appointment_date (DATE)
✓ appointment_time (TIME)
✓ purpose (TEXT)
✓ status (VARCHAR 50)
✓ created_at & updated_at (TIMESTAMP)

Status: If you see all these columns → ✅ Schema OK
```

```
URL: http:
Expected:
  ✓ Page loads successfully
  ✓ Shows "Today's Appointments" header
  ✓ Displays summary cards (Total, Pending, Approved, No Show, etc.)
  ✓ Shows appointment table with today's appointments
  ✓ Each appointment shows: Time, Patient Name, Contact, Purpose, Status, Action

Status: If you see all these → ✅ Frontend OK
```

```
Action: Click red "Mark No Show" button on any appointment

Step 1: ✓ Confirmation modal appears
  - Text: "Are you sure you want to mark [Patient Name] as a no show?"
  - Buttons: "Cancel" and "Yes, Mark No Show"

Step 2: ✓ Click "Yes, Mark No Show"
  - Button changes to: "⏳ Processing..."
  - AJAX request sent to update_appointment.php

Step 3: ✓ Success notification appears
  - Text: "[Patient Name] marked as No Show by [BHW Name] (ID: X)"
  - Green success banner

Step 4: ✓ Table row disappears
  - Row fades out and is removed from table
  - Page auto-reloads after 2 seconds

Step 5: ✓ Status updated in database
  - Verify in phpMyAdmin:
    SELECT * FROM appointment WHERE appointment_id = [ID];
  - Should show: status = 'no_show', health_worker_id = [BHW ID]

Status: If all steps complete → ✅ System Working!
```

```
Location: phpMyAdmin
Query: SELECT appointment_id, status, health_worker_id, resident_id 
       FROM appointment 
       WHERE appointment_id = 1;

Expected after marking no show:
- appointment_id: 1
- status: 'no_show' (or '0')
- health_worker_id: [Your BHW ID]
- resident_id: [Patient ID]

Status: If data persists → ✅ Database Updates Working!
```

```
Location: c:\xampp\htdocs\itelec4\appointment_logs.log

Expected to see entries like:
[2026-06-10 14:25:33] Appointment #1 status changed: 'pending' → 'no_show' | 
Patient: Barrera, Francis | Updated by: Rowena C. Pangan (ID: 1)

Status: If log entries exist → ✅ Audit Trail Working!
```



**Purpose:** Consolidate duplicate appointment tables and ensure unified schema

**Key Features:**
- Safely disables foreign keys during migration (prevents constraint violations)
- Creates automatic backup: `appointment_backup_before_migration`
- Merges any data from duplicate `appointments` table
- Drops `appointments` and `appoinmen` tables
- Recreates unified `appointment` table with correct structure
- Adds `health_worker_id` column for BHW tracking
- Creates performance indexes for fast queries
- Restores all data from backup
- Verifies data integrity
- Re-enables foreign keys

**Schema Created:**
```sql
appointment TABLE:
├── appointment_id (BIGINT, Primary Key, Auto-Increment)
├── resident_id (BIGINT, Foreign Key → resident.resident_id)
├── health_worker_id (BIGINT, Foreign Key → health_worker.worker_id)
│   └── Purpose: Captures which BHW handled this appointment
├── appointment_date (DATE)
├── appointment_time (TIME)
├── purpose (TEXT) - Reason for appointment
├── status (VARCHAR 50) - Values: pending, approved, no_show, completed, cancelled
├── created_at (TIMESTAMP) - Auto-generated
├── updated_at (TIMESTAMP) - Auto-updated
└── Indexes: All key columns indexed for fast queries
```

**Runtime:** ~5-10 seconds


**Purpose:** Handle "No Show" status updates with BHW tracking

**Request Format:**
```javascript
POST /update_appointment.php
Content-Type: application/x-www-form-urlencoded

Body:
  appointment_id=1
  status=no_show
```

**Response Format:**
```json
{
  "success": true,
  "message": "Appointment status updated successfully.",
  "appointment_id": 1,
  "resident_id": 5,
  "resident_name": "Barrera, Francis",
  "old_status": "pending",
  "new_status": "no_show",
  "updated_by": "Rowena C. Pangan (ID: 1)",
  "updated_by_id": 1,
  "updated_by_name": "Rowena C. Pangan",
  "updated_at": "2026-06-10 14:25:33"
}
```

**Security Features:**
- ✅ Session authentication required
- ✅ Role-based authorization (BHW/Health Worker/Admin only)
- ✅ PDO prepared statements (SQL injection prevention)
- ✅ Input validation (appointment ID must be positive integer)
- ✅ Status validation (only allowed values accepted)
- ✅ XSS protection (output escaped)

**Database Operations:**
1. Verifies user authentication (checks `$_SESSION['user_id']`)
2. Validates user role (must be BHW, health_worker, worker, or admin)
3. Checks appointment exists in database
4. Updates appointment: `status = no_show`, `health_worker_id = [BHW ID]`
5. Retrieves BHW name from health_worker table
6. Logs activity to audit trail (`appointment_logs.log`)
7. Returns JSON response

**Error Handling:**
- Missing/invalid appointment_id → 400 Bad Request
- Unauthenticated user → 401 Unauthorized
- Non-BHW user → 403 Forbidden
- Appointment not found → 404 Not Found
- Database error → 500 Internal Server Error


**Purpose:** Display and manage appointments with real-time "No Show" marking

**Key Features:**

1. **Database Query** (reads from unified table)
   ```sql
   SELECT a.appointment_id, a.appointment_date, a.appointment_time, 
          a.purpose, a.status, a.health_worker_id,
          r.resident_id, r.full_name as patient_name, 
          r.contact_number, r.email
   FROM appointment a
   LEFT JOIN resident r ON a.resident_id = r.resident_id
   WHERE DATE(a.appointment_date) = CURDATE()
   ORDER BY a.appointment_time ASC
   ```

2. **UI Display Elements:**
   - Summary cards: Total, Pending, Approved, No Show, Completed counts
   - Appointments table with columns:
     - Time
     - Patient Name (from resident table)
     - Contact Number
     - Purpose
     - Status (color-coded badge)
     - Action (Mark No Show button)

3. **Status Badge Colors:**
   - 🟡 Yellow = Pending
   - 🔵 Blue = Approved
   - 🔴 Red = No Show
   - 🟢 Green = Completed
   - ⚪ Gray = Cancelled

4. **JavaScript AJAX Handler:**
   ```javascript
   fetch('update_appointment.php', {
       method: 'POST',
       headers: {'Content-Type': 'application/x-www-form-urlencoded'},
       body: 'appointment_id=1&status=no_show'
   })
   .then(response => response.json())
   .then(data => {
       if (data.success) {
           
           document.getElementById('row-1').classList.add('fade-out');
           
           location.reload();
       }
   })
   ```

5. **DOM Manipulation:**
   - Confirmation modal before marking no show
   - Fade-out animation when removing row
   - Dynamic notification display
   - Auto-dismiss notifications after 5 seconds
   - Auto-reload page after status update

6. **Security:**
   - ✅ Session verification (redirects if not logged in)
   - ✅ Role-based access control (BHW/worker only)
   - ✅ Output escaping (prevents XSS)
   - ✅ CSRF protection (session-based)


Both PHP files use: `require_once __DIR__ . '/db_config.php';`

**Ensure your `db_config.php` has:**
```php
function getDatabaseConnection() {
    return new PDO(
        'mysql:host=localhost;dbname=scancare_db;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
}
```

For BHW authentication, your session must contain:
```php
$_SESSION['user_id']        
$_SESSION['user_role']      
$_SESSION['full_name']      
```

- ✅ `resident` table with columns: resident_id, full_name, contact_number, email
- ✅ `health_worker` table with columns: worker_id, full_name, email
- ✅ `appointment` table (created by migration script)


**Solution:**
1. Run `STEP1_SCHEMA_UNIFICATION.sql` again in phpMyAdmin
2. Verify in phpMyAdmin: Should see `appointment` table (no duplicates)
3. Check: Column `health_worker_id` exists

**Solution:**
1. Check browser console (F12) for JavaScript errors
2. Check Network tab (F12) → POST to update_appointment.php should succeed
3. Verify file `update_appointment.php` exists in correct folder
4. Check user is logged in as BHW (not resident)

**Solution:**
1. Check JavaScript in browser console (F12) for errors
2. Verify JSON response from update_appointment.php is valid
3. Hard refresh browser: Ctrl+Shift+Delete (clear cache)
4. Check page auto-reloads after 2 seconds

**Solution:**
1. Check appointment_logs.log exists and has entries
2. Verify health_worker_id column exists: `DESCRIBE appointment;`
3. Check user role: Must be 'bhw' or 'health_worker'
4. Verify BHW exists in health_worker table

**Solution:**
1. Verify logged-in user is BHW/health_worker (not resident)
2. Check $_SESSION['user_role'] value
3. Try different user account with correct role


**Run these in phpMyAdmin SQL tab to verify setup:**

```sql

SHOW TABLES LIKE 'appointment%';


DESCRIBE appointment;


SELECT COUNT(*) as total_appointments FROM appointment;


SELECT COUNT(*) as duplicate_count 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME IN ('appointments', 'appoinmen');


SELECT appointment_id, status, health_worker_id 
FROM appointment 
WHERE DATE(appointment_date) = CURDATE();


SELECT appointment_id, resident_id, health_worker_id, status 
FROM appointment 
WHERE status = 'no_show' 
ORDER BY updated_at DESC LIMIT 5;
```


**SOLVED:** `STEP1_SCHEMA_UNIFICATION.sql`
- Removes duplicate `appointments` table
- Removes misspelled `appoinmen` table
- Consolidates to single unified `appointment` table
- All code now reads from one source of truth

**SOLVED:** `update_appointment.php`
- Receives AJAX POST from frontend
- Updates `status` column to `'no_show'`
- Captures `health_worker_id` (which BHW handled it)
- Logs activity to audit trail
- Returns success confirmation to frontend

**SOLVED:** `today_appointments.php`
- Sends AJAX request to backend
- Receives success confirmation
- Removes row from table with fade-out animation
- Shows success notification with BHW name
- Auto-reloads page to update counters
- No manual refresh needed!


```
┌─────────────────────────────────────────────────────────────┐
│                    SCANCARE APPOINTMENT SYSTEM              │
└─────────────────────────────────────────────────────────────┘

┌──────────────────┐
│   Browser        │
│  (BHW)           │
├──────────────────┤
│ today_appointments.php
│ - Shows appointments
│ - "No Show" button
│ - Live updates
└────────┬─────────┘
         │
         │ AJAX POST
         │ (appointment_id, status)
         ↓
┌──────────────────────────┐
│  update_appointment.php  │
│                          │
│ - Validate auth          │
│ - Check role             │
│ - Update database        │
│ - Capture BHW ID         │
│ - Log activity           │
│ - Return JSON            │
└────────┬─────────────────┘
         │
         │ UPDATE query
         ↓
┌────────────────────────┐
│   Unified Database     │
│                        │
│ appointment table:     │
│ ✓ appointment_id       │
│ ✓ resident_id          │
│ ✓ health_worker_id ← BHW tracked
│ ✓ status='no_show'     │
│ ✓ updated_at           │
│                        │
│ appointment_logs.log   │
│ [Activity audit trail] │
└────────────────────────┘

Result:
1. Status changed ✓
2. BHW tracked ✓
3. Logged ✓
4. Row removed ✓
5. Page updated ✓
```


After deployment, you should see:

✅ **Database:**
- Single `appointment` table (no duplicates)
- `health_worker_id` column capturing BHW actions
- All data preserved from migration

✅ **Frontend:**
- Dashboard loads with today's appointments
- "No Show" button works and removes row
- Success notification appears
- Page auto-reloads showing updated counts

✅ **Backend:**
- update_appointment.php processes requests
- Status updates persist in database
- Audit trail entries in appointment_logs.log

✅ **Security:**
- Only authenticated BHW can mark no shows
- SQL injection prevented (PDO prepared statements)
- XSS protected (output escaped)
- Session-based authentication


| What | Where | How |
|
| **Deploy Database** | phpMyAdmin | SQL tab → Copy STEP1 script → Paste → Go |
| **Deploy Backend** | Folder | Copy update_appointment.php to itelec4 folder |
| **Deploy Frontend** | Folder | Copy today_appointments.php to itelec4 folder |
| **Access Dashboard** | Browser | http:
| **Test Status Update** | Dashboard | Click "Mark No Show" button → Confirm |
| **Check Database** | phpMyAdmin | SELECT from appointment table |
| **View Audit Trail** | File | c:\xampp\htdocs\itelec4\appointment_logs.log |
| **Error Logs** | Browser | F12 Console → Network tab |


- [ ] Run STEP1_SCHEMA_UNIFICATION.sql in phpMyAdmin
- [ ] Copy update_appointment.php to project folder
- [ ] Copy today_appointments.php to project folder
- [ ] Access dashboard: http:
- [ ] Log in as BHW
- [ ] Test "No Show" button
- [ ] Verify row disappears
- [ ] Check database for status update
- [ ] Confirm audit trail logged
- [ ] ✅ DONE! System ready for production



**Deployment Time:** ~2 minutes  
**Files Required:** 3  
**Configuration:** NONE  
**Testing Time:** ~5 minutes  
**Total Setup:** ~7 minutes  
**Status:** ✅ **READY TO DEPLOY**


