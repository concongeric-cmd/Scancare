


This guide explains how to link the **BHW appointment approval action** to the **Resident's medical history display**. When a BHW clicks "Approve" on an appointment:

1. ✅ Appointment status updates to "Approved"
2. ✅ Medical history record is automatically created
3. ✅ Resident sees the visit in their medical history immediately after approval


```
┌─────────────────────────────────────────────────────────────────┐
│                    BHW DASHBOARD                                │
│  (dashboard_bhw.php?module=appointments)                        │
│                                                                 │
│  [Appointment Table] ──[Approve Button]──                       │
│                              │                                  │
│                              └─► AJAX Call                      │
└─────────────────────────────────────────────────────────────────┘
                                   │
                                   ▼
┌─────────────────────────────────────────────────────────────────┐
│              BACKEND HANDLER                                    │
│  (approve_appointment_handler.php)                              │
│                                                                 │
│  1. Receive appointment_id from AJAX                            │
│  2. Fetch appointment details from APPOINTMENT table            │
│  3. Update status to "Approved"                                 │
│  4. INSERT into medical_history table                           │
│  5. Return JSON response                                        │
└─────────────────────────────────────────────────────────────────┘
                                   │
                                   ▼
┌─────────────────────────────────────────────────────────────────┐
│              DATABASE                                           │
│                                                                 │
│  APPOINTMENT table ─── status = "Approved"                      │
│  medical_history table ─── NEW record inserted                  │
└─────────────────────────────────────────────────────────────────┘
                                   │
                                   ▼
┌─────────────────────────────────────────────────────────────────┐
│              RESIDENT PORTAL                                    │
│  (dashboard_resident.php)                                       │
│                                                                 │
│  [Medical Visit History Component]                              │
│  (components/resident_medical_history.php)                      │
│                                                                 │
│  ► Queries medical_history table                                │
│  ► Displays all visits with diagnosis & treatment               │
│  ► Shows newly created records from approvals                   │
└─────────────────────────────────────────────────────────────────┘
```


- **File**: `approve_appointment_handler.php`
- **Purpose**: Processes BHW approval and creates medical history
- **Key Logic**:
  - Receives `appointment_id` from AJAX
  - Updates APPOINTMENT table status to 'approved'
  - Inserts record into medical_history table
  - Returns JSON response with success/error

- **File**: `dashboard_bhw.php`
- **Changes**:
  - Updated approve buttons with `data-appointment-id` attribute
  - Replaced placeholder function with real `changeAppointmentStatus()` 
  - Added `handleAppointmentApproval()` function that makes AJAX calls
  - Button shows loading state and updates on success

- **File**: `components/resident_medical_history.php`
- **Purpose**: Displays medical history in resident portal
- **Features**:
  - Fetches records from medical_history table
  - Shows empty state if no records
  - Lists all visits with diagnosis, treatment, notes
  - Displays statistics (total visits, latest visit date)
  - Responsive Tailwind CSS styling



```sql

DESCRIBE APPOINTMENT;


DESCRIBE medical_history;
```

**Expected medical_history columns:**
```
- history_id (INT, Primary Key, AUTO_INCREMENT)
- user_id (INT, Foreign Key to resident)
- visit_date (DATE)
- visit_type (VARCHAR)
- bhw_name (VARCHAR)
- diagnosis (TEXT)
- treatment (TEXT)
- notes (TEXT)
- created_at (TIMESTAMP)
```


The `dashboard_bhw.php` file has been updated with:

1. **Data attributes on approve button:**
```php
<button 
    onclick="changeAppointmentStatus(this, 'approve', <?php echo intval($apt['appointment_id']); ?>)" 
    data-appointment-id="<?php echo intval($apt['appointment_id']); ?>"
    ...>
    <i class="fas fa-check"></i> Approve
</button>
```

2. **JavaScript function:**
```javascript
function changeAppointmentStatus(button, status, appointmentId) {
    if (status === 'approve') {
        handleAppointmentApproval(button, appointmentId, newStatus);
    }
}

function handleAppointmentApproval(button, appointmentId, newStatus) {
    
    
    
}
```


Ensure `approve_appointment_handler.php` is in the root directory and contains:

```php




```

**Handler Logic Flow:**
```
1. Validate appointment_id parameter
2. Get database connection
3. Start transaction
4. Fetch appointment details
5. Update APPOINTMENT.status = 'approved'
6. INSERT into medical_history table
7. Commit transaction
8. Return success response with data
```


In `dashboard_resident.php`, add the include statement:

```php
<?php
require_once 'db_config.php';




include 'components/resident_medical_history.php';
?>
```

**Location in HTML:**
```html
<!
<div class="dashboard-container">
    <!
    <section id="appointments">
        <!
    </section>

    <!
    <?php include 'components/resident_medical_history.php'; ?>

    <!
</div>
```



```
1. User clicks Approve button on appointment row
   └─ Button has appointment_id = 42, resident_id = 15

2. JavaScript changeAppointmentStatus() triggers
   └─ Calls handleAppointmentApproval(button, 42)

3. handleAppointmentApproval() makes AJAX POST to approve_appointment_handler.php
   └─ Sends: appointment_id=42, diagnosis="", treatment_plan=""

4. Backend handler processes request:
   ├─ SELECT appointment details WHERE appointment_id = 42
   ├─ UPDATE APPOINTMENT SET status = 'approved' WHERE appointment_id = 42
   ├─ INSERT INTO medical_history VALUES (
   │  user_id=15,                    
   │  visit_date='2026-06-10',       
   │  visit_type='Routine Checkup',  
   │  bhw_name='Juan Dela Cruz',     
   │  diagnosis=NULL,
   │  treatment=NULL,
   │  notes='Appointment Time: 10:30 AM'
   │)
   └─ COMMIT transaction

5. Backend returns JSON:
   {
     "success": true,
     "message": "✅ Appointment approved successfully! Medical history record created.",
     "data": {
       "appointment_id": 42,
       "history_id": 156,
       "resident_id": 15,
       "visit_date": "2026-06-10",
       "visit_type": "Routine Checkup",
       "bhw_name": "Juan Dela Cruz"
     }
   }

6. JavaScript receives response:
   ├─ Show success alert
   ├─ Update button appearance (green, disabled)
   ├─ Highlight table row
   └─ Reload page after 2 seconds

7. Resident logs in to portal:
   ├─ Dashboard loads
   ├─ resident_medical_history.php component queries medical_history
   ├─ Finds new record for this resident
   ├─ Displays visit in "Medical Visit History" section
   └─ Resident sees diagnosis, treatment, and notes
```



```sql
UPDATE APPOINTMENT
SET status = 'approved', updated_at = NOW()
WHERE appointment_id = ?;
```


```sql
INSERT INTO medical_history (
    user_id,
    visit_date,
    visit_type,
    bhw_name,
    diagnosis,
    treatment,
    notes,
    created_at
) VALUES (?, ?, ?, ?, ?, ?, ?, NOW());
```


```sql
SELECT 
    history_id,
    user_id,
    visit_date,
    visit_type,
    bhw_name,
    diagnosis,
    treatment,
    notes,
    created_at
FROM medical_history
WHERE user_id = ?
ORDER BY visit_date DESC
LIMIT 100;
```


- [ ] Database tables exist with correct structure
- [ ] `approve_appointment_handler.php` file is in root directory
- [ ] BHW dashboard loads without errors
- [ ] Approve button has correct appointment_id in HTML
- [ ] JavaScript console shows no errors
- [ ] Click Approve → success alert appears
- [ ] New record appears in medical_history table (verify in phpMyAdmin)
- [ ] Resident portal shows new medical history record
- [ ] Multiple approvals create multiple history records
- [ ] Empty state shows when no history exists



```
Project Root (c:\xampp\htdocs\itelec4\)
│
├── approve_appointment_handler.php  ✅ NEW - Backend handler
├── dashboard_bhw.php                ✅ MODIFIED - Updated JS & buttons
├── dashboard_resident.php           ✅ MODIFIED - Added component include
├── db_config.php                    ✅ EXISTING - No changes needed
│
└── components/
    ├── resident_medical_history.php ✅ NEW - Display component
    └── (other components...)
```


1. **Start XAMPP/MySQL**
   ```
   Apache: Running
   MySQL: Running
   ```

2. **Test BHW Dashboard**
   ```
   Navigate: http:
   Module: ?module=appointments
   Action: Click "Approve" on any appointment
   Expected: Success alert, page reloads
   ```

3. **Check Database**
   ```
   phpMyAdmin → medical_history table
   Verify: New record inserted with correct resident_id
   ```

4. **Test Resident Portal**
   ```
   Navigate: http:
   Expected: Medical history section shows approved visits
   ```


- Check if button has `data-appointment-id` attribute
- Verify appointment exists in APPOINTMENT table
- Check browser console for JavaScript errors

- Verify `user_id` in medical_history matches `resident_id` in appointment
- Check if APPOINTMENT table uses `resident_id` or `user_id` column
- Query database directly: `SELECT * FROM medical_history WHERE user_id = 15;`

- Verify `approve_appointment_handler.php` is in root directory
- Check file name spelling exactly
- Look for PHP errors in `error.log`

- Verify BHW is logged in (`$_SESSION['worker_id']`, `$_SESSION['user_name']`)
- Verify resident is logged in (`$_SESSION['resident_id']`)
- Check session validation in both dashboards


| Source (Appointment) | Target (Medical History) | Notes |
|
| appointment_id | - | Not stored in history |
| resident_id | user_id | Foreign key mapping |
| appointment_date | visit_date | DATE format |
| appointment_time | notes | Stored in notes field |
| purpose | visit_type | Visit type/reason |
| BHW Session | bhw_name | From `$_SESSION['user_name']` |
| - | diagnosis | Optional, from form input |
| - | treatment | Optional, from form input |
| approval timestamp | created_at | AUTO_TIMESTAMP |


✅ **Implemented:**
- PDO prepared statements prevent SQL injection
- htmlspecialchars() sanitizes all output
- Session validation in all handlers
- Transaction support for data consistency
- Error logging to track issues

✅ **Best Practices:**
- Verify BHW authorization before approval
- Log all approval actions with timestamps
- Validate appointment_id is numeric
- Ensure resident_id matches session


For issues or questions about this integration:
1. Check the troubleshooting section above
2. Review browser console logs (F12)
3. Check PHP error log in XAMPP
4. Verify database tables and columns match schema
5. Test with sample data first


| File | Type | Purpose | Status |
|
| approve_appointment_handler.php | PHP Backend | Handles approval & creates history | ✅ NEW |
| dashboard_bhw.php | PHP/HTML/JS | BHW interface with approve buttons | ✅ MODIFIED |
| dashboard_resident.php | PHP/HTML | Resident portal main page | ✅ MODIFIED |
| resident_medical_history.php | PHP/HTML Component | Medical history display | ✅ NEW |



**Last Updated:** 2026-06-10  
**Version:** 1.0  
**Status:** Production Ready ✅

