

This guide documents the complete activity logging system implemented across the ScanCare platform. All resident interactions (logins, appointments, BHW actions) are now automatically tracked with accurate timestamps and timezone synchronization.


Timestamps logged in the system were not matching Philippine Standard Time (PST/UTC+8), causing discrepancies between user actions and recorded times.


**File: `db_config.php` (Line 13)**
```php
date_default_timezone_set('Asia/Manila');
```
- Sets the application timezone to Philippine Standard Time
- Applied globally across all PHP files that include `db_config.php`
- Ensures all `time()`, `strtotime()`, and `date()` functions use PST

**File: `db_config.php` (Line 24)**
```php
define('DB_TIMEZONE', '+08:00');  
```

**File: `db_config.php` (getDatabaseConnection function, Line 43)**
```php
PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . ", time_zone = '" . DB_TIMEZONE . "'"
```
- Sets MySQL session timezone to +08:00 (PST) on every connection
- All `NOW()` and `CURRENT_TIMESTAMP` functions now use PST
- Ensures timestamp consistency between application and database

To verify timezone is correctly set in MySQL:
```sql
SELECT @@session.time_zone;  
SELECT NOW();                 
```


**File: `login.php` (Lines 57-75)**

1. User enters credentials and submits login form
2. System validates email and password against database
3. **ON SUCCESS**: Before redirecting, system automatically logs the login activity
4. Activity is inserted into `activity_logs` table with:
   - User ID
   - User Role
   - Action Type: `login`
   - Description: "Successfully logged into the [resident/bhw/admin] portal"
   - Timestamp: Current time in PST (via MySQL NOW())
   - IP Address: User's IP
   - User Agent: Browser/device info

```php
if ($user && password_verify($password, $user['password_hash']) && $user['is_active']) {
    
    
    
    $action_description = "Successfully logged into the " . ucfirst($user['user_role']) . " portal";
    logActivity(
        $pdo,
        $user['user_id'],
        $user['user_role'],
        'login',
        $action_description
    );
    
    
}
```

```sql
CREATE TABLE activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_role_snapshot VARCHAR(20),          
    action_type VARCHAR(50),                 
    action_description TEXT,                 
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  
    ip_address VARCHAR(45),                  
    user_agent TEXT,                         
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_action_type (action_type),
    INDEX idx_timestamp (timestamp)
);
```

When **Juan Dela Cruz** (user_id: 5, role: resident) logs in:
```
log_id: 47
user_id: 5
user_role_snapshot: resident
action_type: login
action_description: Successfully logged into the resident portal
timestamp: 2026-06-09 14:32:15  (PST)
ip_address: 127.0.0.1
user_agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)...
```


**File: `schedule_appointment.php` (Already implemented)**

1. Resident submits "Schedule an Appointment" form
2. System validates all appointment details (date, time, service, etc)
3. **Step 1**: Appointment is inserted into `appointments` table
4. **Step 2**: Activity is automatically logged to `activity_logs` table
5. Response sent back to frontend with confirmation


**Step 1: Appointments Table Insertion** (Line 70-77)
```php
$appointment_query = "INSERT INTO appointments 
    (user_id, patient_name, service_id, appointment_date, appointment_time, reason, status) 
    VALUES (?, ?, ?, ?, ?, ?, 'scheduled')";

$appointment_stmt->execute([
    $user_id,
    $patient_name,
    $service_id,
    $appointment_date,
    $appointment_time,
    $reason
]);
```

**Step 2: Activity Logs Insertion** (Line 88-96)
```php
$action_description = "Scheduled appointment (ID: $appointment_id) - Service: Service #$service_id - Date: $appointment_date at $appointment_time";

$log_stmt = $pdo->prepare("
    INSERT INTO activity_logs 
    (user_id, user_role_snapshot, action_type, action_description, ip_address, user_agent) 
    VALUES (?, ?, 'scheduled_appointment', ?, ?, ?)
");

$log_stmt->execute([
    $user_id,
    $_SESSION['user_role'],
    $action_description,
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['HTTP_USER_AGENT']
]);
```

When **Maria Santos** (user_id: 2) schedules an appointment:
```
log_id: 52
user_id: 2
user_role_snapshot: resident
action_type: scheduled_appointment
action_description: Scheduled appointment (ID: 15) - Service: Service #3 - Date: 2026-06-15 at 14:00
timestamp: 2026-06-09 15:45:22  (PST)
ip_address: 127.0.0.1
user_agent: Mozilla/5.0...
```


**File: `db_config.php` (Lines 189-226)**

Centralized utility function to log any user activity to the database.

```php
function logActivity($pdo, $user_id, $user_role, $action_type, $action_description)
```

| Parameter | Type | Description |
|
| `$pdo` | PDO Object | Database connection |
| `$user_id` | int | ID of the user performing the action |
| `$user_role` | string | Role of the user (resident, bhw, admin) |
| `$action_type` | string | Type of action (login, logout, created_appointment, assigned_appointment, completed_appointment, etc) |
| `$action_description` | string | Human-readable description of what happened |


```php
logActivity($pdo, 5, 'resident', 'login', 'Successfully logged into the resident portal');
```

```php
logActivity($pdo, 2, 'resident', 'scheduled_appointment', 'Scheduled appointment (ID: 15) - Service: Checkup - Date: 2026-06-15 at 14:00');
```

```php
logActivity($pdo, 8, 'bhw', 'assigned_appointment', 'Assigned appointment ID 15 to themselves');
```

```php
logActivity($pdo, 8, 'bhw', 'completed_appointment', 'Marked appointment ID 15 as completed - Patient: Maria Santos');
```

- ✅ Automatic IP address capture
- ✅ Automatic user agent capture (browser/device info)
- ✅ Uses MySQL NOW() for accurate PST timestamp
- ✅ Error handling with logging
- ✅ Returns boolean (success/failure)


To add logging to other actions, simply include this after the action succeeds:

```php

logActivity($pdo, $user_id, $user_role, 'updated_profile', 'Updated profile information');


logActivity($pdo, $admin_id, 'admin', 'user_created', 'Created new user account: ' . $new_user_email);


logActivity($pdo, $user_id, 'resident', 'service_booked', 'Booked service: ' . $service_name);
```


**File: `admin_dashboard.php` (Section 2: Activity Logs)**

```php
$log_query = "
    SELECT 
        l.log_id,
        l.user_id,
        l.user_role_snapshot,
        l.action_type,
        l.action_description,
        l.timestamp,
        u.first_name,
        u.last_name,
        u.email
    FROM activity_logs l
    LEFT JOIN users u ON l.user_id = u.user_id
    ORDER BY l.timestamp DESC
    LIMIT 50
";
```

- **50 Most Recent Logs**: Ordered by timestamp descending
- **User Information**: Displays full name and email from users table
- **Role Badges**: Color-coded badges (resident=amber, bhw=green, admin=purple)
- **Timestamps**: Formatted as "Mar 15, 2026 2:30 PM" (PST)
- **Descriptions**: Full text of what action was performed
- **Action Types**: Uppercase and human-readable

```
Log ID  | User Name           | Email              | Role    | Action Performed          | Description                                    | Timestamp



```


**File: `admin_dashboard.php` (Section 3: Appointment Tracker)**

```sql
SELECT 
    a.appointment_id,
    a.user_id,
    a.bhw_id,
    a.appointment_date,
    a.appointment_time,
    a.status,
    a.created_at,
    a.reason,
    resident.first_name as resident_first_name,
    resident.last_name as resident_last_name,
    resident.phone_number as resident_phone,
    bhw.first_name as bhw_first_name,
    bhw.last_name as bhw_last_name,
    s.service_name,
    s.category as service_category
FROM appointments a
INNER JOIN users resident ON a.user_id = resident.user_id
LEFT JOIN users bhw ON a.bhw_id = bhw.user_id
LEFT JOIN services s ON a.service_id = s.service_id
ORDER BY a.appointment_date DESC, a.created_at DESC
LIMIT 100
```

- **100 Most Recent Appointments**: Shows historic and ongoing transactions
- **Status Badges**: Color-coded (scheduled=blue, completed=green, canceled=red)
- **Patient Information**: Resident name and phone number
- **BHW Assignment**: Shows assigned BHW or "Unassigned" if no BHW yet
- **Service Details**: Service name and category
- **Timeline**: Appointment date and creation date

```
Apt ID | Patient Name    | Contact      | Assigned BHW        | Service Type | Reason              | Status    | Date Created



```


```sql

SELECT @@session.time_zone;  


SELECT NOW();                 


SELECT @@system_time_zone, @@session.time_zone, NOW();
```

```sql
SELECT 
    log_id,
    user_id,
    action_type,
    action_description,
    timestamp,
    ip_address
FROM activity_logs
ORDER BY timestamp DESC
LIMIT 10;
```

```sql
SELECT 
    l.log_id,
    u.first_name,
    u.last_name,
    l.action_type,
    l.action_description,
    l.timestamp
FROM activity_logs l
JOIN users u ON l.user_id = u.user_id
WHERE l.user_id = 2  
ORDER BY l.timestamp DESC;
```

```sql
SELECT 
    COUNT(*) as login_count,
    DATE(timestamp) as login_date
FROM activity_logs
WHERE action_type = 'login'
AND DATE(timestamp) = CURDATE()
GROUP BY DATE(timestamp);
```

```sql
SELECT 
    l.log_id,
    u.first_name,
    u.last_name,
    l.action_description,
    l.timestamp
FROM activity_logs l
JOIN users u ON l.user_id = u.user_id
WHERE l.action_type = 'scheduled_appointment'
ORDER BY l.timestamp DESC
LIMIT 20;
```


| File | Changes | Purpose |
|
| `db_config.php` | Added timezone config + logActivity() function | Centralized timezone setup and activity logging |
| `login.php` | Added automatic activity logging on successful login | Track all resident/BHW/admin logins |
| `schedule_appointment.php` | Already has logging implemented | Track appointment creations |
| `admin_dashboard.php` | Displays activity logs and appointment tracker | Visualize all system activities |


1. Log in as a resident (e.g., `maria.santos@gmail.com`)
2. Go to Admin Dashboard
3. Scroll to "System Activity Logs" section
4. **Expected**: Your login should appear as a recent entry with:
   - Action: "Login"
   - Description: "Successfully logged into the resident portal"
   - Timestamp: Current time in PST

1. While logged in as resident, schedule an appointment
2. Fill in appointment details and submit
3. Go to Admin Dashboard
4. Scroll to "System Activity Logs"
5. **Expected**: New log entry showing:
   - Action: "Scheduled Appointment"
   - Description: With appointment ID and service details
   - Timestamp: When appointment was created

1. After scheduling appointment (Test 2)
2. Go to Admin Dashboard
3. Scroll to "Global Appointment & Service Tracker"
4. **Expected**: New appointment visible in table with:
   - Patient name (resident)
   - Status: "scheduled"
   - Service details
   - Created date/time

1. Check server time in terminal: `date`
2. Schedule appointment, note the time
3. Check activity log timestamp in Admin Dashboard
4. **Expected**: Timestamps match between server and database exactly


```php

logActivity($pdo, $user_id, $user_role, 'logout', 'Logged out from the platform');


logActivity($pdo, $bhw_id, 'bhw', 'assigned_appointment', 'Assigned appointment ID X to themselves');


logActivity($pdo, $bhw_id, 'bhw', 'completed_appointment', 'Marked appointment ID X as completed');


logActivity($pdo, $admin_id, 'admin', 'user_created', 'Created new user: ' . $new_user_email);


logActivity($pdo, $admin_id, 'admin', 'service_modified', 'Modified service: ' . $service_name);
```

```sql

SELECT 
    u.first_name, u.last_name, COUNT(*) as action_count
FROM activity_logs l
JOIN users u ON l.user_id = u.user_id
WHERE MONTH(l.timestamp) = MONTH(CURDATE())
GROUP BY l.user_id
ORDER BY action_count DESC
LIMIT 10;


SELECT 
    HOUR(timestamp) as hour,
    COUNT(*) as activity_count
FROM activity_logs
WHERE DATE(timestamp) = CURDATE()
GROUP BY HOUR(timestamp)
ORDER BY activity_count DESC;
```


✅ **Timezone Synchronization**: Application and database both use PST (UTC+8)  
✅ **Login Logging**: All logins automatically tracked with user details  
✅ **Appointment Logging**: All appointments automatically logged with details  
✅ **Activity Dashboard**: Admin can view all system activities in real-time  
✅ **Audit Trail**: Complete history of all user interactions for compliance  

The system now provides complete visibility into all resident, BHW, and admin activities with accurate PST timestamps!

