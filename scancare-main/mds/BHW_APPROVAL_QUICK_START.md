



```
Project Root: c:\xampp\htdocs\itelec4\
├── approve_appointment_handler.php      ✅ NEW
├── dashboard_bhw.php                    ✅ UPDATED
├── dashboard_resident.php               ✅ UPDATED
└── components/
    └── resident_medical_history.php     ✅ NEW
```

**Check:** Open file explorer and verify all files exist.


1. Open XAMPP Control Panel
2. Start **Apache** (should show "Running" in green)
3. Start **MySQL** (should show "Running" in green)
4. Wait 5 seconds for services to fully initialize



```
URL: http:
Email: bhw@example.com
Password: (your BHW password)
```

**Expected:** Dashboard loads with menu options


```
URL: http:
```

**Expected:** See table of upcoming appointments


- Look for appointment with status "Scheduled" or "Pending"
- Click the **Green "✔ Approve" Button**

**Expected:**
- Button shows "Approving..." with spinner
- Success alert appears: "✅ Appointment approved successfully!"
- Button turns green and is disabled
- Page reloads after 2 seconds


**Option 1: Check Database (phpMyAdmin)**

```
URL: http:
Database: scancare_db
Table: medical_history
```

Look for:
- New row with matching resident_id
- visit_date = appointment date
- visit_type = appointment purpose
- bhw_name = your BHW name

**Option 2: Check Resident Portal**

```
URL: http:
Email: (resident email)
Password: (resident password)
```

Navigate to:
```
URL: http:
```

Scroll down to **"Medical Visit History"** section

**Expected:**
- Your approved appointment appears as a visit record
- Shows: Date, Health Worker Name, Diagnosis, Treatment, Notes
- Status: "Completed"



**Solution:**
1. Open Browser DevTools (F12)
2. Go to Console tab
3. Click Approve button again
4. Look for error messages
5. Check if `approve_appointment_handler.php` exists in root directory


**Solution:**
- File path for `approve_appointment_handler.php` is incorrect
- Verify file is in: `c:\xampp\htdocs\itelec4\approve_appointment_handler.php`
- Check spelling exactly (lowercase, no typos)


**Solution:**
1. Verify MySQL is running in XAMPP
2. Verify `db_config.php` has correct database credentials
3. Check database name is `scancare_db`


**Solution:**
1. Check if `components/resident_medical_history.php` exists
2. Verify file is included in `dashboard_resident.php`
3. Query database: 
   ```sql
   SELECT * FROM medical_history WHERE user_id = (resident_id);
   ```



**In phpMyAdmin:**
1. Click `scancare_db` database
2. Look for `medical_history` table
3. Should have these columns:
   - history_id (Primary Key)
   - user_id
   - visit_date
   - visit_type
   - bhw_name
   - diagnosis
   - treatment
   - notes
   - created_at

**If missing:**
```sql
CREATE TABLE IF NOT EXISTS medical_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    visit_date DATE NOT NULL,
    visit_type VARCHAR(100),
    bhw_name VARCHAR(100),
    diagnosis TEXT,
    treatment TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
```


**Verify columns:**
- appointment_id
- resident_id (or user_id)
- appointment_date
- appointment_time
- purpose
- status



```
When: BHW clicks Approve button
Receives: appointment_id, optional diagnosis & treatment
Does:
  1. Fetch appointment details
  2. Update appointment status to "approved"
  3. Create medical_history record
  4. Return success/error JSON
Returns: { success: true/false, data: {...} }
```


```
Updated:
  - Approve button now has data-appointment-id attribute
  - changeAppointmentStatus() function makes AJAX call
  - handleAppointmentApproval() processes response
  - Shows loading state and success message
```


```
Displays:
  - Empty state if no visits
  - Statistics (total visits, latest visit)
  - List of all medical visits
  - Each visit shows: date, worker, diagnosis, treatment, notes
```


```
Updated:
  - Includes resident_medical_history.php component
  - Medical history appears after main dashboard grid
  - Automatically shows approved visits
```



**As BHW:**
1. Go to dashboard_bhw.php?module=appointments
2. If no appointments exist, ask a resident to book one
3. Or insert test data directly:

```sql
INSERT INTO APPOINTMENT 
(resident_id, appointment_date, appointment_time, purpose, status)
VALUES 
(1, '2026-06-15', '10:00:00', 'Routine Checkup', 'scheduled');
```


1. Click Approve button on test appointment
2. Verify success alert
3. Check database: `SELECT * FROM medical_history ORDER BY history_id DESC LIMIT 1;`
4. Login as that resident
5. Check dashboard_resident.php - should show visit in Medical History


```
BHW Action:                  APPROVAL BUTTON CLICK
                                    ↓
Frontend:                    JavaScript changeAppointmentStatus()
                                    ↓
Network:                     AJAX POST to approve_appointment_handler.php
                                    ↓
Backend Processing:          1. Validate appointment_id
                            2. Fetch appointment details
                            3. BEGIN TRANSACTION
                            4. UPDATE appointment status
                            5. INSERT medical_history record
                            6. COMMIT transaction
                                    ↓
Database Changes:           ✅ appointment.status = 'approved'
                            ✅ medical_history record created
                                    ↓
Response:                    JSON { success: true, data: {...} }
                                    ↓
Frontend:                    Show alert, reload page
                                    ↓
Resident Sees:              New visit in "Medical Visit History"
```


Check that implemented:

- ✅ Session validation (BHW must be logged in)
- ✅ Prepared statements (prevent SQL injection)
- ✅ HTML sanitization (htmlspecialchars)
- ✅ Transaction support (atomic operations)
- ✅ Error logging
- ✅ Input validation


When you click Approve, your browser console (F12 → Console tab) should show:

```javascript

Approving appointment ID: 42


Sending approval request: {
  appointment_id: 42,
  diagnosis: "",
  treatment_plan: ""
}


Server response: {
  success: true,
  message: "✅ Appointment approved successfully!...",
  data: {
    appointment_id: 42,
    history_id: 156,
    resident_id: 15,
    visit_date: "2026-06-15",
    visit_type: "Routine Checkup",
    bhw_name: "Juan Dela Cruz"
  }
}


Approval successful: {...}
```


Your integration is working correctly when:

1. ✅ Approve button responds to click (shows "Approving...")
2. ✅ Success alert appears with appointment details
3. ✅ Button turns green and disables
4. ✅ Page reloads after 2 seconds
5. ✅ New row appears in medical_history table
6. ✅ Resident sees visit in their Medical History
7. ✅ Resident data shows correct diagnosis/treatment
8. ✅ No JavaScript errors in console



1. **Clear old test data:**
   ```sql
   DELETE FROM medical_history WHERE history_id > 0;
   DELETE FROM APPOINTMENT WHERE status = 'approved' AND appointment_date >= '2026-06-01';
   ```

2. **Create fresh test appointment:**
   ```sql
   INSERT INTO APPOINTMENT 
   (resident_id, appointment_date, appointment_time, purpose, status)
   VALUES (1, DATE_ADD(NOW(), INTERVAL 1 DAY), '10:00:00', 'Test Visit', 'scheduled');
   ```

3. **Test approval again:**
   - Go to BHW dashboard
   - Click Approve
   - Check if medical_history record created


```
File: C:\xampp\apache\logs\error.log
Look for: "Add medicine error" or "Approval error"
```


| Task | URL | What to Look For |
|
| BHW Dashboard | localhost/itelec4/dashboard_bhw.php?module=appointments | Appointment table |
| Resident Dashboard | localhost/itelec4/dashboard_resident.php | Medical Visit History section |
| Database | localhost/phpmyadmin | medical_history table |
| Browser Console | F12 → Console | JavaScript errors |
| PHP Error Log | C:\xampp\apache\logs\error.log | Database errors |



**Status:** Ready to Test ✅  
**Last Updated:** 2026-06-10  
**Created for:** ScanCare BHW-Resident Integration

