


The BHW Dashboard is a specialized appointment management system for Barangay Health Workers (BHW). It provides a clean, database-driven interface to view, assign, and manage resident appointments with full tracking and audit logging.


```sql
CREATE TABLE appointments (
    appointment_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,              
    bhw_id INT COMMENT 'NULL if unassigned',  
    service_id INT NOT NULL,           
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    reason TEXT COMMENT 'Reason for appointment',
    status ENUM('scheduled', 'completed', 'canceled', 'no_show'),
    notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (bhw_id) REFERENCES users(user_id),
    FOREIGN KEY (service_id) REFERENCES services(service_id)
);
```

- **`appointment_id`**: Primary key, unique identifier
- **`user_id`**: Resident's ID (never changes)
- **`bhw_id`**: Assigned BHW's ID (starts as NULL, updated when BHW clicks "Assign to Me")
- **`status`**: Current state (`scheduled` → `completed`)
- **`service_id`**: Links to available health services



**SQL Query:**
```sql
SELECT 
    a.appointment_id,
    a.user_id,
    a.bhw_id,
    a.appointment_date,
    a.appointment_time,
    a.reason,
    a.status,
    u.first_name,
    u.last_name,
    u.phone_number,
    s.service_name,
    bhw.first_name as bhw_first_name,
    bhw.last_name as bhw_last_name
FROM appointments a
JOIN users u ON a.user_id = u.user_id
LEFT JOIN services s ON a.service_id = s.service_id
LEFT JOIN users bhw ON a.bhw_id = bhw.user_id
WHERE a.status = 'scheduled'
AND (a.bhw_id IS NULL OR a.bhw_id = ?)
ORDER BY a.appointment_date ASC, a.appointment_time ASC;
```

**What It Does:**
- Joins appointment data with resident names (from users table)
- Joins with services table to get service names
- Left joins with BHW table to get assigned worker's name
- Filters for unassigned appointments OR appointments assigned to current BHW
- Shows in chronological order

**Output:**
| appointment_id | first_name | last_name | date | time | service_name | bhw_id | reason |
|
| 1 | Maria | Santos | 2026-06-10 | 09:00 | Vaccination | NULL | Routine Check |
| 2 | Juan | Cruz | 2026-06-10 | 10:00 | Checkup | 5 | Follow-up |


**Action:** POST with `action=assign_to_me&appointment_id=XX`

**SQL Query:**
```sql
UPDATE appointments 
SET bhw_id = ?, 
    updated_at = NOW()
WHERE appointment_id = ? 
AND status = 'scheduled'
AND (bhw_id IS NULL);
```

**Parameters:**
1. `?` = Current BHW's user_id (from `$_SESSION['user_id']`)
2. `?` = Appointment ID from button click

**Validation Rules:**
- ✅ Appointment must exist
- ✅ Status must be 'scheduled'
- ✅ `bhw_id` must be NULL (not already assigned)
- ✅ Only unassigned appointments can be taken

**Activity Log Entry:**
```sql
INSERT INTO activity_logs 
(user_id, user_role_snapshot, action_type, action_description, timestamp)
VALUES (?, 'bhw', 'admin_action', ?, NOW());
```

**Frontend Response:**
- Success: `{"success": true, "message": "Appointment assigned to you successfully!"}`
- Failure: `{"success": false, "message": "Appointment is already assigned or not available"}`
- Page reloads to show updated assignments


**Action:** POST with `action=mark_completed&appointment_id=XX`

**SQL Query:**
```sql
UPDATE appointments 
SET status = 'completed', 
    updated_at = NOW()
WHERE appointment_id = ? 
AND bhw_id = ?
AND status = 'scheduled';
```

**Parameters:**
1. `?` = Appointment ID from button click
2. `?` = Current BHW's user_id (verification)

**Validation Rules:**
- ✅ Appointment must exist
- ✅ Status must be 'scheduled'
- ✅ `bhw_id` must match current BHW (only BHW assigned can mark complete)

**What Happens After:**
- ✅ Appointment disappears from "Scheduled Appointments" table
- ✅ Appointment moves to "Upcoming Appointments" → "Medical Visit History" (resident view)
- ✅ Recorded in activity logs for audit trail
- ✅ Completion count updates on dashboard

**Activity Log Entry:**
```sql
INSERT INTO activity_logs 
(user_id, user_role_snapshot, action_type, action_description, timestamp)
VALUES (?, 'bhw', 'admin_action', ?, NOW());
```


```php
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['user_role'] !== 'bhw') {
    header('Location: login.php');
    exit;
}
```
- Checks user is logged in
- Enforces BHW-only access
- Redirects unauthorized users

```php
$stmt = $pdo->prepare("UPDATE appointments SET bhw_id = ? ...");
$stmt->execute([$current_bhw_id, $appointment_id]);
```
- Uses prepared statements with parameter binding
- All user input is escaped automatically

```php

WHERE appointment_id = ? 
AND bhw_id = ?  
```


```php
$unassigned_count = count(array_filter($scheduled_appointments, 
    function($apt) { return $apt['bhw_id'] === null; }));

$my_appointments_count = count(array_filter($scheduled_appointments, 
    function($apt) { return $apt['bhw_id'] !== null; }));
```

Shows:
- **Unassigned**: Appointments with `bhw_id IS NULL`
- **My Assignments**: Appointments where `bhw_id = current_user_id`
- **Completed**: Total completed by current BHW


- Sortable columns: Patient, Date/Time, Service, Reason, Assigned To
- Status badges: Unassigned (red), Assigned (blue)
- Action buttons based on appointment state


**Assign Button** (Yellow):
```php
<?php if ($apt['bhw_id'] === null): ?>
    <button class="assign-btn">Assign to Me</button>
<?php endif; ?>
```
- Only shows if appointment has NO assigned BHW

**Done Button** (Green):
```php
<?php if ($apt['bhw_id'] == $current_bhw_id): ?>
    <button class="complete-btn">Mark as Done</button>
<?php endif; ?>
```
- Only shows if BHW is the assigned worker

```sql
SELECT * FROM appointments 
WHERE bhw_id = ? 
AND status = 'completed'
ORDER BY appointment_date DESC
LIMIT 10;
```
- Shows 10 most recent completions
- BHW-specific records only
- Audit trail reference


```
┌─────────────────────────────────────────┐
│  User clicks "Assign to Me" Button      │
└──────────────────┬──────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────┐
│  Frontend JavaScript:                    │
│  - Show confirmation dialog              │
│  - POST /bhw_dashboard.php               │
│  - action=assign_to_me                   │
│  - appointment_id=XX                     │
└──────────────────┬──────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────┐
│  Backend PHP:                            │
│  - Verify user is BHW                    │
│  - Validate appointment_id               │
│  - UPDATE appointments SET bhw_id = ?    │
│  - WHERE bhw_id IS NULL                  │
└──────────────────┬──────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────┐
│  Database Response:                      │
│  - SUCCESS: rows affected > 0            │
│  - FAIL: rows affected = 0               │
└──────────────────┬──────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────┐
│  JSON Response:                          │
│  {"success": true/false,                 │
│   "message": "..."}                      │
└──────────────────┬──────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────┐
│  Frontend:                               │
│  - Show success/error alert              │
│  - location.reload() on success          │
└─────────────────────────────────────────┘
```



1. **Login** with BHW credentials
2. **Navigate** to BHW Dashboard
3. **View** all scheduled appointments
4. **Assign** unassigned appointments to yourself
5. **Complete** your assigned appointments
6. **Track** completion history


```php




session_start();
require_once 'db_config.php';
```


```sql
SELECT * FROM appointments 
WHERE status = 'scheduled' 
AND bhw_id IS NULL
ORDER BY appointment_date ASC;
```

```sql
SELECT * FROM appointments 
WHERE status = 'scheduled' 
AND bhw_id = 5
ORDER BY appointment_date ASC;
```

```sql
SELECT * FROM appointments 
WHERE bhw_id = 5 
AND status = 'completed'
ORDER BY appointment_date DESC;
```

```sql
SELECT 
    COUNT(*) as total_scheduled,
    SUM(CASE WHEN bhw_id IS NULL THEN 1 ELSE 0 END) as unassigned,
    SUM(CASE WHEN bhw_id = 5 THEN 1 ELSE 0 END) as my_assignments,
    SUM(CASE WHEN bhw_id = 5 AND status = 'completed' THEN 1 ELSE 0 END) as completed
FROM appointments;
```


1. **Database Connection**: Uses PDO for secure connections
2. **Prepared Statements**: All queries use parameter binding
3. **Activity Logging**: Every action is recorded for audit trail
4. **JSON Responses**: AJAX actions return JSON for frontend handling
5. **Error Handling**: Try-catch blocks wrap all database operations
6. **Audit Trail**: All assignments and completions are logged


- **Frontend**: `bhw_dashboard_appointment_manager.php`
- **Database Config**: `db_config.php`
- **Login System**: `login.php`
- **Database Schema**: `SCANCARE_COMPLETE_DATABASE.sql`
- **Appointment Table**: Uses same schema as resident portal


For issues or questions about the BHW appointment system:
1. Check error logs in `php:
2. Verify session is active (user logged in as BHW)
3. Confirm appointments table has data
4. Test AJAX actions with browser console (F12)


