


```
┌─────────────────────────────────────────────────────────────────┐
│                        User Interface                            │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  BHW Dashboard (HTML + Tailwind CSS + JavaScript)          │ │
│  │  - Statistics Cards (Unassigned, My Assignments, Completed)│ │
│  │  - Scheduled Appointments Table                            │ │
│  │  - Completed Appointments History                          │ │
│  └────────────────────────────────────────────────────────────┘ │
└───────────────────────────┬──────────────────────────────────────┘
                            │
                    AJAX POST Requests
                   (JSON Responses)
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Backend PHP Logic                          │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  bhw_dashboard_appointment_manager.php                     │ │
│  │  - Session Validation                                      │ │
│  │  - Action Handlers (assign, complete)                     │ │
│  │  - Data Fetching Queries                                  │ │
│  │  - JSON Response Generation                               │ │
│  └────────────────────────────────────────────────────────────┘ │
└───────────────────────────┬──────────────────────────────────────┘
                            │
                    PDO Prepared Statements
                   (SQL Queries with Binding)
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│                      MySQL Database                              │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  appointments Table                                      │  │
│  │  - appointment_id (PK)                                   │  │
│  │  - user_id (Resident FK)                                │  │
│  │  - bhw_id (BHW FK) ← Gets updated on assign             │  │
│  │  - status ← Gets updated on complete                    │  │
│  │  - appointment_date, appointment_time, reason, etc      │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                  │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  users Table (Residents & BHWs)                          │  │
│  │  - user_id, first_name, last_name, user_role            │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                  │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  services Table (Health Services)                         │  │
│  │  - service_id, service_name, category                    │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                  │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  activity_logs Table (Audit Trail)                       │  │
│  │  - log_id, user_id, action_description, timestamp        │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```


```
┌─────────────┐
│  BHW Login  │
└──────┬──────┘
       │
       ▼
┌──────────────────────────────┐
│ Session Created              │
│ $_SESSION['user_id'] = 2     │
│ $_SESSION['user_role'] = bhw │
└──────┬───────────────────────┘
       │
       ▼
┌──────────────────────────────────────────┐
│ Fetch Scheduled Appointments             │
│ SELECT * FROM appointments WHERE         │
│ status = 'scheduled'                     │
│ AND (bhw_id IS NULL OR bhw_id = 2)      │
└──────┬───────────────────────────────────┘
       │
       ▼
┌────────────────────────────────────────────┐
│ Join with Users & Services                 │
│ u.first_name, u.last_name                  │
│ s.service_name                             │
│ bhw.first_name, bhw.last_name             │
└──────┬─────────────────────────────────────┘
       │
       ▼
┌────────────────────────────────────────────┐
│ Render HTML Table                          │
│ - Patient names (from JOIN)                │
│ - Appointment details                      │
│ - Action buttons                           │
└────────────────────────────────────────────┘
```

```
┌────────────────────────────────┐
│ User Clicks "Assign to Me"     │
│ Button (onclick event)         │
└──────────┬─────────────────────┘
           │
           ▼
┌──────────────────────────────────────────┐
│ JavaScript:                              │
│ - Show confirmation dialog               │
│ - Disable button (show spinner)          │
│ - Prepare AJAX data                      │
└──────────┬───────────────────────────────┘
           │
           ▼
┌──────────────────────────────────────────┐
│ AJAX POST Request:                       │
│ fetch("/bhw_dashboard...", {             │
│   method: 'POST',                        │
│   body: 'action=assign_to_me&            │
│           appointment_id=1'              │
│ })                                       │
└──────────┬───────────────────────────────┘
           │
           ▼
┌──────────────────────────────────────────┐
│ Backend PHP:                             │
│ - Check $_POST['action']                 │
│ - Validate appointment_id                │
│ - Run UPDATE query                       │
└──────────┬───────────────────────────────┘
           │
           ▼
┌──────────────────────────────────────────┐
│ SQL UPDATE:                              │
│ UPDATE appointments                      │
│ SET bhw_id = 2,                          │
│     updated_at = NOW()                   │
│ WHERE appointment_id = 1                 │
│ AND bhw_id IS NULL                       │
│ AND status = 'scheduled'                 │
└──────────┬───────────────────────────────┘
           │
           ▼
┌──────────────────────────────────────────┐
│ Database Response:                       │
│ Rows affected: 1 ✅                      │
│ (or 0 if already assigned)               │
└──────────┬───────────────────────────────┘
           │
           ▼
┌──────────────────────────────────────────┐
│ Log Activity:                            │
│ INSERT INTO activity_logs (...)          │
│ "Ricardo assigned appointment #1"        │
└──────────┬───────────────────────────────┘
           │
           ▼
┌──────────────────────────────────────────┐
│ Return JSON Response:                    │
│ {"success": true,                        │
│  "message": "Assigned successfully!"}    │
└──────────┬───────────────────────────────┘
           │
           ▼
┌──────────────────────────────────────────┐
│ Frontend:                                │
│ - Show alert with success message        │
│ - Call location.reload()                 │
│ - Page refreshes with new data           │
└──────────────────────────────────────────┘
```


```
┌────────────────────────────────────────────────────────────────┐
│ 👨‍⚕️ BHW Appointment Manager                                     │
│                                 Welcome, Ricardo Reyes  [Logout] │
└────────────────────────────────────────────────────────────────┘
```

```
┌─────────────────────┐  ┌─────────────────────┐  ┌─────────────────────┐
│ 🕐 Unassigned       │  │ ✅ My Assignments   │  │ ⭐ Completed        │
│                     │  │                     │  │                     │
│ COUNT: 4            │  │ COUNT: 2            │  │ COUNT: 8            │
│ (Yellow border)     │  │ (Blue border)       │  │ (Green border)      │
└─────────────────────┘  └─────────────────────┘  └─────────────────────┘
```

```
┌────────────────────────────────────────────────────────────────────┐
│ SCHEDULED APPOINTMENTS                          Total: 6            │
├────────────────────────────────────────────────────────────────────┤
│ Patient Name    │ Date & Time  │ Service  │ Reason │ Assigned │ Action│
├─────────────────┼──────────────┼──────────┼────────┼──────────┼───────┤
│ Maria Santos    │ Jun 10, 9AM  │ Vaccine  │ Booster│ Unassigned│[Assign]
│ (09176543210)   │              │          │        │          │       │
├─────────────────┼──────────────┼──────────┼────────┼──────────┼───────┤
│ Juan Cruz       │ Jun 10, 10AM │ Checkup  │ Annual │ Ricardo  │ [Done]
│ (09177654321)   │              │          │        │ Reyes    │ [👁️]  │
├─────────────────┼──────────────┼──────────┼────────┼──────────┼───────┤
│ ...             │ ...          │ ...      │ ...    │ ...      │ ...   │
└────────────────────────────────────────────────────────────────────┘
```

```
┌────────────────────────────────────────────────────────────────────┐
│ RECENT COMPLETED VISITS                                             │
├────────────────────────────────────────────────────────────────────┤
│ Patient          │ Date        │ Service      │ Reason              │
├──────────────────┼─────────────┼──────────────┼─────────────────────┤
│ Pedro Bacani     │ Jun 08, 2026│ Checkup      │ Blood pressure check│
│ Rosa Medina      │ Jun 07, 2026│ Prenatal     │ First prenatal visit│
│ ...              │ ...         │ ...          │ ...                 │
└────────────────────────────────────────────────────────────────────┘
```


```
┌─────────────────────────────────────────────────────────────┐
│ Layer 1: Session Authentication                             │
│ Check: if (!isset($_SESSION['user_id'])) → redirect         │
│ Check: if ($_SESSION['user_role'] !== 'bhw') → redirect    │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ Layer 2: SQL Injection Prevention                            │
│ $stmt = $pdo->prepare("SELECT * FROM appointments           │
│                        WHERE appointment_id = ?")           │
│ $stmt->execute([$appointment_id]);                           │
│ (Parameter binding prevents SQL injection)                  │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ Layer 3: Authorization Verification                         │
│ Query includes: WHERE bhw_id = ? (current user)            │
│ Prevents user from modifying other BHW's data              │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ Layer 4: Business Logic Validation                          │
│ - Check: bhw_id IS NULL (before assigning)                 │
│ - Check: status = 'scheduled' (before completing)          │
│ - Check: rowCount > 0 (verify action succeeded)            │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ Layer 5: Audit Logging                                      │
│ INSERT INTO activity_logs (user_id, action_description...)  │
│ Track all user actions for compliance                       │
└─────────────────────────────────────────────────────────────┘
```


```
                    ┌──────────────┐
                    │  SCHEDULED   │
                    │ (Initial)    │
                    └────────┬─────┘
                             │
                ┌────────────┼────────────┐
                ▼            ▼            ▼
         ┌──────────┐  ┌──────────┐ ┌─────────┐
         │COMPLETED │  │ CANCELED │ │ NO_SHOW │
         │(After    │  │(User     │ │(Missed) │
         │marking)  │  │cancels)  │ │         │
         └──────────┘  └──────────┘ └─────────┘
```

```
           ┌──────────────────┐
           │  Unassigned      │
           │  (bhw_id = NULL) │
           └────────┬─────────┘
                    │
            Click "Assign to Me"
                    │
                    ▼
           ┌──────────────────┐
           │  Assigned to Me  │
           │  (bhw_id = 2)    │
           └────────┬─────────┘
                    │
             Click "Done"
                    │
                    ▼
           ┌──────────────────┐
           │  Completed       │
           │  (status=complet)│
           └──────────────────┘
```


```
/itelec4/
├── bhw_dashboard_appointment_manager.php  ← Main Dashboard
├── db_config.php                          ← Database Connection
├── login.php                              ← Authentication
├── logout.php                             ← Session Termination
│
├── BHW_DASHBOARD_DOCUMENTATION.md         ← Technical Guide
├── BHW_DASHBOARD_TESTING_GUIDE.md         ← Testing Reference
├── BHW_APPOINTMENT_SQL_REFERENCE.sql      ← Database Setup
├── BHW_DASHBOARD_QUICK_START.md           ← Quick Start
├── BHW_DASHBOARD_VISUAL_GUIDE.md          ← This File
│
├── /images/
│   └── SClogo.png                         ← Logo
│
└── /includes/
    └── (Any shared components)
```


```
User Tries to Access /bhw_dashboard_appointment_manager.php
                    │
                    ▼
        Is user logged in?
        /     (YES)    \     (NO)
       │                │
       ▼                ▼
   Is role = 'bhw'?    Redirect to login.php
   /      \
  YES      NO
  │        │
  │        ▼
  │    Redirect to appropriate portal
  │    (resident dashboard, admin, etc)
  │
  ▼
Load BHW Dashboard
- Fetch all scheduled appointments
- Display statistics
- Render appointment table
```

```
User Clicks Button (Assign / Complete)
                    │
                    ▼
JavaScript captures click event
- Show confirmation dialog
                    │
        User clicks "OK"?
        /              \
      YES              NO
       │                │
       │                └─→ Cancel action
       │
       ▼
Send AJAX POST request
with action=assign_to_me/mark_completed
and appointment_id
                    │
                    ▼
Backend receives POST request
- Check $_POST['action']
- Validate appointment_id
                    │
        Is data valid?
        /              \
      YES              NO
       │                │
       ▼                ▼
Execute SQL UPDATE    Return error JSON
                    │
        Did UPDATE succeed?
        / (rowCount > 0) \ (rowCount = 0)
      YES                 NO
       │                  │
       ▼                  ▼
Return success JSON   Return failure JSON
- Log activity        (already assigned, etc)
- Include message
                    │
                    ▼
JavaScript receives response
- Show alert with message
- Call location.reload()
```


```
Query Type          │ Complexity │ Optimization
─────────────────────┼────────────┼─────────────────────────
Fetch Appointments  │ O(n log n) │ INDEX on (status, bhw_id)
                    │            │ INDEX on appointment_date
─────────────────────┼────────────┼─────────────────────────
Update Single Row   │ O(1)       │ Primary key lookup
                    │            │ Prepared statement
─────────────────────┼────────────┼─────────────────────────
Join with Users     │ O(n)       │ Foreign key index
                    │            │ User_id index
─────────────────────┼────────────┼─────────────────────────
Count Statistics    │ O(n)       │ COUNT with WHERE clause
                    │            │ Index on status column
```


```
Action                  │ Database Calls │ Response Time
────────────────────────┼────────────────┼──────────────
Initial Page Load       │ 2-3 queries    │ 50-200ms
- Fetch appointments    │                │
- Count statistics      │                │
────────────────────────┼────────────────┼──────────────
Assign Appointment      │ 2 queries      │ 100-300ms
- UPDATE appointments   │                │
- INSERT activity_logs  │                │
────────────────────────┼────────────────┼──────────────
Mark Completed          │ 2 queries      │ 100-300ms
- UPDATE appointments   │                │
- INSERT activity_logs  │                │
────────────────────────┼────────────────┼──────────────
Page Reload             │ 2-3 queries    │ 50-200ms
- Fresh data fetch      │                │
```


```
┌────────────────────────────────────────────┐
│  External Systems That Can Integrate      │
├────────────────────────────────────────────┤
│                                            │
│ 1. SMS Notifications System                │
│    When: Appointment assigned, completed   │
│    Data: Resident phone, BHW name         │
│                                            │
│ 2. Email Notifications System              │
│    When: Appointment status changes        │
│    Data: Resident email, appointment info │
│                                            │
│ 3. Analytics Dashboard                     │
│    Data: From activity_logs table          │
│    Metrics: BHW performance, completion    │
│                                            │
│ 4. Mobile App API                          │
│    Endpoint: /bhw_dashboard_appointment    │
│    Response: JSON appointment data         │
│                                            │
│ 5. Reporting System                        │
│    Source: appointments table              │
│    Report: Monthly completion stats        │
│                                            │
└────────────────────────────────────────────┘
```


```
Device          │ Width      │ Layout
────────────────┼────────────┼─────────────────────
Mobile Phone    │ < 768px    │ Single column
                │            │ Stacked cards
                │            │ Horizontal scroll table
────────────────┼────────────┼─────────────────────
Tablet          │ 768-1024px │ 2-column grid
                │            │ Visible table
────────────────┼────────────┼─────────────────────
Desktop         │ > 1024px   │ 3-column grid
                │            │ Full table view
                │            │ Sidebar if available
```


```
Priority │ Feature                  │ Implementation
─────────┼──────────────────────────┼────────────────────────
HIGH    │ Real-time Notifications  │ WebSocket / Pusher
        │ Alert resident on assign │
─────────┼──────────────────────────┼────────────────────────
HIGH    │ Date Range Filtering     │ JavaScript date picker
        │ Filter appointments      │
─────────┼──────────────────────────┼────────────────────────
MEDIUM  │ Export to Excel/PDF      │ PHPExcel / TCPDF
        │ Generate reports         │
─────────┼──────────────────────────┼────────────────────────
MEDIUM  │ BHW Performance Dashboard│ Chart.js library
        │ Show completion stats    │
─────────┼──────────────────────────┼────────────────────────
LOW     │ Appointment Notes Editor │ Rich text editor
        │ BHW can add notes        │
```



**Last Updated**: 2026-06-09
**Audience**: Developers, System Architects, QA Team

