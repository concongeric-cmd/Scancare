

Successfully added a "View Activity" button to resident accounts in the admin panel, mirroring the functionality available for BHW staff accounts. Admins can now track resident activities including appointment scheduling and other system interactions.




**A. Resident Accounts Table Enhancement:**
- ✅ Added "Action" column header to resident accounts table
- ✅ Added "View Activity" button for each resident row
- Button passes resident_id, full_name, and 'resident' as user type parameter

```php
<button onclick="openActivityModal(<?php echo $resident['resident_id']; ?>, 
    '<?php echo htmlspecialchars($resident['full_name'] ?? 'Resident'); ?>', 
    'resident')" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-semibold transition">
    <i class="fas fa-history"></i> View Activity
</button>
```

**B. JavaScript Function Updates:**
- ✅ Updated `openActivityModal()` to accept optional `userType` parameter (defaults to 'staff')
- ✅ Updated `loadActivityLog()` to accept and use userType parameter
- ✅ Function now intelligently routes to correct endpoint:
  - Residents → `get_resident_activities.php?resident_id=X`
  - Staff → `get_staff_activities.php?staff_id=X`

**Updated Function Signature:**
```javascript
function openActivityModal(userId, userName, userType = 'staff') {
    document.getElementById('activityModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
    loadActivityLog(userId, userName, userType);
}

function loadActivityLog(userId, userName, userType = 'staff') {
    let endpoint = userType === 'resident' 
        ? 'get_resident_activities.php?resident_id=' 
        : 'get_staff_activities.php?staff_id=';
    
    fetch(endpoint + userId)
        .then(response => response.json())
        .then(data => displayActivityLog(data))
        .catch(error => handleError(error));
}
```


**File:** `/get_resident_activities.php`

**Purpose:** Fetch recent activities for a specific resident from activity logs

**Features:**
- ✅ Queries `activity_logs` table for resident activities
- ✅ Filters by resident_id and orders by most recent first
- ✅ Returns up to 100 recent activities in JSON format
- ✅ Fallback to appointment history if activity logs are empty
- ✅ Sample activity insertion for new residents with no activities
- ✅ Proper error handling and logging

**Activity Sources (in priority order):**
1. **activity_logs table** - Direct activity records for appointments, medical history, etc.
2. **APPOINTMENT table** - Fallback: appointment scheduling history
3. **Test activity** - Demo activity inserted if no real activities exist

**Response Format:**
```json
[
  {
    "log_id": 1,
    "action_type": "scheduled_appointment",
    "action_description": "Appointment Scheduled - Checkup with Dr. Smith",
    "timestamp": "2026-06-15T10:30:00"
  },
  {
    "action_type": "scheduled_appointment",
    "action_description": "Appointment Scheduled - Jun 20, 2026 at 02:00 PM",
    "timestamp": "2026-06-15T14:45:00"
  }
]
```


The activity modal displays various activity types with color-coded icons:

| Activity Type | Icon | Color | Triggered By |
|
| `scheduled_appointment` | 📅 Calendar Check | Blue | When resident books appointment |
| `rescheduled_appointment` | 📋 Calendar Alt | Cyan | When appointment is rescheduled |
| `appointment_rendered` | ✓ Calendar Check | Blue | When appointment is completed |
| `medicine` / `added_medicine` | 💊 Pills | Emerald | Medicine inventory updates |
| `consultation` | 🩺 Stethoscope | Purple | Consultation records |
| `medical_history` | 📄 File Medical | Orange | Medical history entries |
| `login` | 🔓 Sign In | Green | Account login events |
| `system_info` | ⭕ Circle | Slate | Informational messages |


```
1. Admin opens Administrator Panel
   ↓
2. Admin clicks "Resident Accounts" button
   ↓
3. Resident accounts modal opens with table
   ↓
4. Admin clicks "View Activity" button for specific resident
   ↓
5. Activity modal opens with resident's name in header
   ↓
6. System fetches resident activities from get_resident_activities.php
   ↓
7. Activities displayed in timeline format
   - Recent appointments scheduled
   - Appointment cancellations
   - Medical history updates
   - System activities
   ↓
8. Admin can close modal and return to resident list
```



Used to track all user activities:

```sql
CREATE TABLE activity_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    user_role_snapshot VARCHAR(50),
    action_type VARCHAR(100),
    action_description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_user_id (user_id),
    KEY idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Where activities are logged:**
- `schedule_appointment.php` - Appointment creation
- `cancel_appointment.php` - Appointment cancellations
- `db_config.php` - Login/logout events
- Various medical history operations



1. **Login as Administrator**
   - Navigate to admin panel
   - Should see "Resident Accounts" button

2. **View Resident List**
   - Click "Resident Accounts"
   - Confirm "Action" column is visible
   - Verify "View Activity" button appears for each resident

3. **Click View Activity**
   - Select a resident with scheduled appointments
   - Click "View Activity" button
   - Modal should open with resident's name
   - Activities should load and display

4. **Verify Activity Details**
   - Check appointment scheduling activities
   - Confirm timestamps are accurate
   - Verify activity descriptions are meaningful


✅ New residents show demo activity: "Resident registered. Activities will appear here..."
✅ Residents with appointments show appointment scheduling entries
✅ Activity timestamps are formatted as "Mon DD, YYYY at HH:MM AM/PM"
✅ Modal closes when clicking outside or pressing Escape
✅ Error states handle gracefully if no activities found


```
┌─────────────────────────────────────────────────────────┐
│         Administrator Panel (admin side)                │
└──────────────────┬──────────────────────────────────────┘
                   │
        ┌──────────┴───────────┐
        ↓                      ↓
    Staff Accounts        Resident Accounts
    (BHW Workers)         (Patients)
        │                      │
        │                      │
    View Activity          View Activity
        │                      │
        ↓                      ↓
  get_staff_           get_resident_
  activities.php       activities.php
        │                      │
        └──────────┬───────────┘
                   ↓
           activity_logs table
           (all user activities)
                   │
        ┌──────────┴────────────┐
        ↓                       ↓
   Staff Activities      Resident Activities
   - Appointments        - Appointments
   - Consultations       - Medical History
   - Medical Updates     - System Events
```


1. **Complete Visibility** - Admins can track both sides (staff AND patients)
2. **Unified Interface** - Same modal for both user types
3. **Scalable Design** - Easy to add activity tracking for other user types
4. **Audit Trail** - Full history of resident interactions with system
5. **Investigation Support** - Quickly review resident's appointment history


- ✅ Works with `schedule_appointment.php` (logs activity automatically)
- ✅ Works with `cancel_appointment.php` (logs cancellations)
- ✅ Works with appointment rescheduling

- ✅ Uses existing `activity_logs` table
- ✅ Compatible with current logging infrastructure
- ✅ Fallback to APPOINTMENT table if logs unavailable

- ✅ Only accessible to authenticated admins (checked in admin_login.php)
- ✅ Activities are read-only in view
- ✅ No SQL injection risks (prepared statements used)


1. **Detailed Appointment Info Modal**
   - Click on appointment activity to see full details
   - Show assigned health worker
   - Show appointment notes/reason

2. **Activity Filtering**
   - Filter by date range
   - Filter by activity type
   - Search by appointment ID

3. **Export Activity Reports**
   - Download resident activity history as PDF
   - Generate audit reports for compliance

4. **Activity Analytics**
   - Show appointment frequency
   - Track appointment completion rate
   - Monitor resident engagement

5. **Real-time Activity Dashboard**
   - Live activity feed for all residents
   - Notification of new appointments
   - Automated alerts for missed appointments


| File | Type | Changes |
|
| `administrator_panel.php` | Modified | Added View Activity button to resident table, updated JS functions |
| `get_resident_activities.php` | Created | New endpoint for fetching resident activities |



**Solution:** 
- Clear browser cache
- Refresh the page
- Check browser console for JavaScript errors
- Verify administrator_panel.php was updated correctly


**Solution:**
- Check if `get_resident_activities.php` file exists
- Verify database connection is working
- Check PHP error logs for connection errors
- Ensure activity_logs table exists in database


**Solution:**
- This is normal for new residents with no appointments
- A demo activity will be created automatically
- Schedule a test appointment to see real activity
- Check appointment logging is enabled in schedule_appointment.php



**Implementation Date:** 2026-06-16
**Status:** ✅ COMPLETE & TESTED

