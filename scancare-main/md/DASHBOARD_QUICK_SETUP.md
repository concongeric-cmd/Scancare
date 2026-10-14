



1. Open **phpMyAdmin** → Select **scancare_db** database
2. Click **SQL** tab
3. Copy this script:

```sql
CREATE TABLE IF NOT EXISTS medical_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    visit_date DATE NOT NULL,
    visit_type VARCHAR(100) NOT NULL,
    bhw_name VARCHAR(100),
    notes TEXT,
    status ENUM('completed', 'pending', 'cancelled') DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_visit_date (visit_date),
    INDEX idx_user_date (user_id, visit_date DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

4. Click **Execute** button
5. ✅ Done! Table is created


Still in the SQL tab, paste this:

```sql
INSERT INTO appointments (user_id, appointment_date, appointment_time, reason, status) 
VALUES (1, '2026-06-15', '10:30:00', 'Routine Check-up', 'scheduled');

INSERT INTO medical_history (user_id, visit_date, visit_type, bhw_name, notes, status) 
VALUES (1, '2026-05-15', 'Medical Checkup', 'Dr. Maria Gonzales', 'Routine health check, blood pressure normal', 'completed');
```

Click **Execute**


1. **Log in** to ScanCare with user ID = 1
2. **Open dashboard.php**
3. ✅ You should now see:
   - Upcoming Appointments displaying actual data (not hardcoded)
   - Medical Visit History showing database records


```
Every user saw:
- Routine Check-up - 2026-06-15 10:30 AM
- Follow-up Consultation - 2026-06-22 02:00 PM
- Medical Checkup - 2026-05-15
- Vaccination - 2026-04-22
(Even brand new users with no data)
```

```
New user = Empty states shown
User with appointments = Actual database data displayed
Each user = Sees only their own data
```


| Feature | Implementation |
|
| **Per-User Data** | Queries filter by `$_SESSION['user_id']` |
| **Empty States** | Pretty cards when no data exists |
| **XSS Protection** | All output uses `htmlspecialchars()` |
| **SQL Injection Protection** | PDO prepared statements |
| **Date Formatting** | Human-readable format (May 15, 2026) |
| **Time Formatting** | 12-hour format with AM/PM |


```sql

SHOW TABLES LIKE 'medical_history';
DESCRIBE medical_history;
```

1. Create brand new user account
2. Log in
3. Go to dashboard.php
4. Verify "No upcoming appointments scheduled." appears
5. Verify "No medical history recorded yet." appears

1. Log in as user_id = 1
2. Go to dashboard.php
3. Verify appointments display
4. Verify medical history displays

1. Fill form: "Schedule an Appointment"
2. Select future date and time
3. Choose reason
4. Click "Schedule Appointment"
5. ✅ Should appear in "Upcoming Appointments" below


**dashboard_resident.php** - Main changes:
- Lines 1-103: Database queries (replaced hardcoded arrays)
- Lines 180-225: Upcoming appointments HTML (added empty state)
- Lines 230-290: Medical history HTML (added empty state)


```php
SELECT appointment_id, appointment_date, appointment_time, reason, status
FROM appointments
WHERE user_id = ? 
AND appointment_date >= CURDATE()
AND status IN ('scheduled', 'pending')
ORDER BY appointment_date ASC
LIMIT 5
```

```php
SELECT history_id, visit_date, visit_type, bhw_name, notes, status
FROM medical_history
WHERE user_id = ?
AND status = 'completed'
ORDER BY visit_date DESC
LIMIT 10
```


- ✅ PDO prepared statements (prevents SQL injection)
- ✅ `htmlspecialchars()` on all output (prevents XSS)
- ✅ User ID filtering (prevents data leaks between users)
- ✅ Error handling (no sensitive info exposed)
- ✅ Date validation (prevents invalid data)


| Problem | Solution |
|
| Still seeing hardcoded data | Hard refresh browser (Ctrl+Shift+R) or clear cache |
| "Table medical_history not found" | Run CREATE TABLE script in phpMyAdmin |
| New appointments not appearing | Refresh page, check database directly |
| Empty state not showing | Verify `$upcoming_appointments` is empty array |
| Date shows wrong format | Check browser date/time settings |


```bash

mysql> SHOW TABLES LIKE 'medical_history';

mysql> SELECT * FROM appointments WHERE user_id = 1;

mysql> SELECT * FROM medical_history WHERE user_id = 1;

mysql> SELECT user_id, COUNT(*) FROM appointments GROUP BY user_id;
mysql> SELECT user_id, COUNT(*) FROM medical_history GROUP BY user_id;
```


The dashboard now:
- Displays **real database data** instead of hardcoded placeholders
- Shows **empty states** for new users with no records
- **Protects against XSS** with htmlspecialchars()
- **Protects against SQL injection** with PDO prepared statements
- **Isolates user data** so users only see their own records

**Status: ✅ Production Ready**

