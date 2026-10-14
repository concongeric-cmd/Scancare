


1. **approve_appointment_handler.php** - Backend handler (NEW)
2. **dashboard_bhw.php** - BHW frontend with updated JavaScript (MODIFIED)
3. **dashboard_resident.php** - Resident frontend with component include (MODIFIED)
4. **resident_medical_history.php** - Medical history display component (NEW)


**Location:** `c:\xampp\htdocs\itelec4\approve_appointment_handler.php`  
**Type:** PHP Backend Handler  
**Status:** NEW

Handles BHW appointment approval requests:
- Updates appointment status to "Approved"
- Creates medical history record
- Returns JSON response

- PDO prepared statements (SQL injection prevention)
- Database transaction support
- Error handling with logging
- Sanitized input/output

```
POST /approve_appointment_handler.php
Content-Type: multipart/form-data

Parameters:
- appointment_id (INT, required) - ID of appointment to approve
- diagnosis (TEXT, optional) - Initial diagnosis
- treatment_plan (TEXT, optional) - Treatment recommendations
```

**Success:**
```json
{
  "success": true,
  "message": "✅ Appointment approved successfully! Medical history record created.",
  "data": {
    "appointment_id": 42,
    "history_id": 156,
    "resident_id": 15,
    "visit_date": "2026-06-15",
    "visit_type": "Routine Checkup",
    "bhw_name": "Juan Dela Cruz",
    "previous_status": "scheduled",
    "new_status": "approved"
  }
}
```

**Error:**
```json
{
  "success": false,
  "message": "❌ Error message here",
  "error_code": 404,
  "data": null
}
```


**Query 1: Fetch Appointment**
```sql
SELECT 
    appointment_id,
    resident_id,
    appointment_date,
    appointment_time,
    purpose,
    status
FROM APPOINTMENT
WHERE appointment_id = ?
LIMIT 1
```

**Query 2: Update Appointment**
```sql
UPDATE APPOINTMENT
SET 
    status = 'approved',
    updated_at = NOW()
WHERE appointment_id = ?
```

**Query 3: Create Medical History**
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
) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
```


**Location:** `c:\xampp\htdocs\itelec4\dashboard_bhw.php`  
**Type:** PHP/HTML/JavaScript  
**Status:** MODIFIED


**Old:**
```html
<button onclick="changeAppointmentStatus(this, 'approve')" ...>
    <i class="fas fa-check"></i> Approve
</button>
```

**New:**
```html
<button 
    onclick="changeAppointmentStatus(this, 'approve', <?php echo intval($apt['appointment_id']); ?>)" 
    data-appointment-id="<?php echo intval($apt['appointment_id']); ?>"
    data-resident-id="<?php echo intval($apt['resident_id']); ?>"
    ...>
    <i class="fas fa-check"></i> Approve
</button>
```


**Old Function:**
```javascript
function changeAppointmentStatus(button, status) {
    const statusMap = {
        'approve': 'Approved',
        'no-show': 'No Show',
        'void': 'Void'
    };
    alert(`Appointment status changed to: ${statusMap[status]}...`);
    
}
```

**New Functions:**
```javascript
function changeAppointmentStatus(button, status, appointmentId) {
    const statusMap = {
        'approve': 'Approved',
        'no-show': 'No Show',
        'void': 'Void'
    };
    
    const newStatus = statusMap[status] || status;
    
    if (button.disabled) {
        return;
    }
    
    if (status === 'approve') {
        handleAppointmentApproval(button, appointmentId, newStatus);
    } else {
        if (confirm(`Are you sure you want to mark this appointment as "${newStatus}"?`)) {
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            console.log(`Appointment ${appointmentId} marked as ${newStatus}`);
            alert(`Appointment status changed to: ${newStatus}\n\n(Backend implementation pending for this status)`);
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-times"></i> No Show';
        }
    }
}

function handleAppointmentApproval(button, appointmentId, newStatus) {
    console.log('Approving appointment ID:', appointmentId);
    
    if (!appointmentId || appointmentId <= 0) {
        alert('❌ Error: Invalid appointment ID');
        return;
    }
    
    button.disabled = true;
    const originalHTML = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Approving...';
    
    const diagnosis = document.getElementById('appointmentDiagnosis')?.value || '';
    const treatmentPlan = document.getElementById('appointmentTreatment')?.value || '';
    
    const formData = new FormData();
    formData.append('appointment_id', appointmentId);
    if (diagnosis) formData.append('diagnosis', diagnosis);
    if (treatmentPlan) formData.append('treatment_plan', treatmentPlan);
    
    console.log('Sending approval request:', {
        appointment_id: appointmentId,
        diagnosis: diagnosis,
        treatment_plan: treatmentPlan
    });
    
    fetch('approve_appointment_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Server response:', data);
        
        if (data.success) {
            console.log('Approval successful:', data.data);
            
            alert(`✅ ${data.message}\n\n📋 Medical History Record Created\nResident ID: ${data.data.resident_id}\nVisit Date: ${data.data.visit_date}\nVisit Type: ${data.data.visit_type}`);
            
            button.innerHTML = '<i class="fas fa-check-circle"></i> Approved';
            button.style.backgroundColor = '#10b981';
            button.style.opacity = '0.7';
            button.disabled = true;
            
            const row = button.closest('tr');
            if (row) {
                row.style.backgroundColor = '#f0fdf4';
            }
            
            setTimeout(() => {
                location.reload();
            }, 2000);
            
        } else {
            console.error('Approval failed:', data.message);
            alert(`❌ ${data.message}`);
            
            button.disabled = false;
            button.innerHTML = originalHTML;
        }
    })
    .catch(error => {
        console.error('Network error:', error);
        alert(`❌ Network error: ${error.message}`);
        
        button.disabled = false;
        button.innerHTML = originalHTML;
    });
}
```


**Location:** `c:\xampp\htdocs\itelec4\dashboard_resident.php`  
**Type:** PHP/HTML  
**Status:** MODIFIED


**Added before closing `</main>` tag:**

```html
<!
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-12 py-6 sm:py-8 lg:py-12">
    <?php 
    
    
    include 'components/resident_medical_history.php'; 
    ?>
</div>
```

**Context:**
- Added after the main dashboard grid
- Before closing `</main>` tag
- Full width section for medical history display


**Location:** `c:\xampp\htdocs\itelec4\components\resident_medical_history.php`  
**Type:** PHP Component  
**Status:** NEW

Displays medical history records for logged-in resident with full UI

- Empty state when no records
- Statistics cards (total visits, latest visit)
- Full medical visit records with all details
- Responsive Tailwind CSS design
- Error handling

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
LIMIT 100
```


**1. Empty State (No Records)**
```html
<div class="bg-gradient-to-br from-blue-50 to-indigo-50 ...">
    <!
    <!
</div>
```

**2. Statistics Cards**
- Total Visits
- Latest Visit
- Status
- Record Count

**3. Medical Visit Card**
For each visit record:
- Date and Health Worker Name
- Diagnosis section
- Treatment Plan section
- Additional Notes
- Record metadata


```
┌──────────────────────────────────────────────────────────────────┐
│                    BHW APPOINTMENT VIEW                          │
│                  (dashboard_bhw.php)                             │
│                                                                  │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ Appointment Table Row                                      │ │
│  │ ┌──────────┬──────────┬──────────┬─────────┬────────────┐ │ │
│  │ │ Resident │   Date   │   Time   │ Purpose │   Status  │ │ │
│  │ ├──────────┼──────────┼──────────┼─────────┼────────────┤ │ │
│  │ │ Maria... │ Jun 15   │ 10:00 AM │ Checkup │ Scheduled │ │ │
│  │ └──────────┴──────────┴──────────┴─────────┴────────────┘ │ │
│  │                              │                            │ │
│  │                              │ Actions                    │ │
│  │                              ▼                            │ │
│  │                    [✔ Approve] [✗ No Show]              │ │
│  │                         │                                 │ │
│  │                    (USER CLICKS)                          │ │
│  └────────────────────────────────────────────────────────────┘ │
│                              │                                  │
└──────────────────────────────┼──────────────────────────────────┘
                               │
                               ▼
┌──────────────────────────────────────────────────────────────────┐
│                   JAVASCRIPT HANDLER                             │
│                 (changeAppointmentStatus)                        │
│                                                                  │
│  changeAppointmentStatus(button, 'approve', appointmentId=42)   │
│                         │                                        │
│                         ▼                                        │
│              handleAppointmentApproval()                         │
│                         │                                        │
│  1. Disable button                                              │
│  2. Show "Approving..." spinner                                 │
│  3. Create FormData with appointment_id                         │
│  4. AJAX POST to approve_appointment_handler.php                │
│                         │                                        │
│              [NETWORK REQUEST]                                  │
└──────────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌──────────────────────────────────────────────────────────────────┐
│               BACKEND HANDLER (PHP)                              │
│         (approve_appointment_handler.php)                        │
│                                                                  │
│  1. Receive: appointment_id = 42                                │
│             diagnosis = (optional)                              │
│             treatment_plan = (optional)                         │
│                         │                                        │
│  2. Validate appointment_id                                     │
│                         │                                        │
│  3. Database Connection                                         │
│                         ▼                                        │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ BEGIN TRANSACTION                                        │  │
│  │                                                          │  │
│  │ SELECT * FROM APPOINTMENT WHERE appointment_id = 42     │  │
│  │ Result: {resident_id: 15, appointment_date: 2026-06-15} │  │
│  │                                                          │  │
│  │ UPDATE APPOINTMENT SET status = 'approved'              │  │
│  │                                                          │  │
│  │ INSERT INTO medical_history (                            │  │
│  │   user_id=15,                                            │  │
│  │   visit_date='2026-06-15',                               │  │
│  │   visit_type='Routine Checkup',                          │  │
│  │   bhw_name='Juan Dela Cruz'                              │  │
│  │ )                                                        │  │
│  │ Result: history_id = 156                                 │  │
│  │                                                          │  │
│  │ COMMIT TRANSACTION                                       │  │
│  └──────────────────────────────────────────────────────────┘  │
│                         │                                        │
│  4. Return JSON Success Response                                │
│     {                                                            │
│       success: true,                                             │
│       data: {                                                    │
│         appointment_id: 42,                                      │
│         history_id: 156,                                         │
│         resident_id: 15                                          │
│       }                                                          │
│     }                                                            │
│                         │                                        │
│              [NETWORK RESPONSE]                                 │
└──────────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌──────────────────────────────────────────────────────────────────┐
│                   JAVASCRIPT RESPONSE                            │
│            (.then(data => {...}))                               │
│                                                                  │
│  if (data.success) {                                             │
│    • Show success alert with details                             │
│    • Change button to green "Approved"                           │
│    • Disable button                                              │
│    • Highlight table row                                         │
│    • Reload page after 2 seconds                                 │
│  }                                                               │
│                                                                  │
│                    location.reload()                             │
└──────────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌──────────────────────────────────────────────────────────────────┐
│            DATABASE STATE AFTER APPROVAL                         │
│                                                                  │
│  APPOINTMENT Table:                                              │
│  ┌──────────────┬─────────────────────────┐                    │
│  │ appointment_id│ status                  │                    │
│  ├──────────────┼─────────────────────────┤                    │
│  │ 42           │ "approved"  ← CHANGED   │                    │
│  └──────────────┴─────────────────────────┘                    │
│                                                                  │
│  medical_history Table:                                          │
│  ┌───────┬──────────┬──────────────┬──────────────────────────┐  │
│  │ hist..│ visit_da.│ bhw_name     │ Created ✅ NEW RECORD   │  │
│  ├───────┼──────────┼──────────────┼──────────────────────────┤  │
│  │ 156   │ 26-06-15 │ Juan Cr...   │ From approval handler   │  │
│  └───────┴──────────┴──────────────┴──────────────────────────┘  │
└──────────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌──────────────────────────────────────────────────────────────────┐
│              RESIDENT PORTAL                                     │
│           (dashboard_resident.php)                               │
│                                                                  │
│  After resident logs in:                                         │
│  • Dashboard loads                                               │
│  • Medical History component included                            │
│  • Query: SELECT * FROM medical_history WHERE user_id=15        │
│  • Result: medical_history ID 156 found                          │
│  • Display: New visit card appears                               │
│                                                                  │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ MEDICAL VISIT HISTORY                                      │ │
│  │                                                            │ │
│  │ 📊 Statistics:                                             │ │
│  │   Total Visits: 1    Latest: Jun 15, 2026                 │ │
│  │                                                            │ │
│  │ 📋 Visit Record:                                           │ │
│  │   Date: June 15, 2026                                      │ │
│  │   Health Worker: Juan Dela Cruz                            │ │
│  │   Status: ✅ Completed                                     │ │
│  │                                                            │ │
│  │   Diagnosis: (awaiting consultation notes...)              │ │
│  │   Treatment: (no specific treatment prescribed)            │ │
│  │   Notes: Appointment Time: 10:00 AM                        │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                  │
│                    ✅ SUCCESS!                                   │
└──────────────────────────────────────────────────────────────────┘
```


```sql

INSERT INTO APPOINTMENT 
(resident_id, appointment_date, appointment_time, purpose, status)
VALUES (1, DATE_ADD(NOW(), INTERVAL 1 DAY), '10:00:00', 'Routine Checkup', 'scheduled');


SELECT * FROM APPOINTMENT WHERE status = 'scheduled' ORDER BY appointment_id DESC LIMIT 1;


SELECT COUNT(*) FROM medical_history;


UPDATE APPOINTMENT SET status = 'approved' WHERE appointment_id = (SELECT MAX(appointment_id) FROM APPOINTMENT);
INSERT INTO medical_history (user_id, visit_date, visit_type, bhw_name, diagnosis, treatment, notes, created_at)
SELECT resident_id, appointment_date, purpose, 'Test BHW', NULL, NULL, CONCAT('Appointment Time: ', appointment_time), NOW()
FROM APPOINTMENT WHERE status = 'approved' AND resident_id = 1;


SELECT * FROM medical_history WHERE user_id = 1 ORDER BY history_id DESC LIMIT 1;


SELECT * FROM medical_history WHERE user_id = 1 ORDER BY visit_date DESC;
```


- Transaction rollback on error
- Detailed error logging
- User-friendly error messages
- HTTP error codes

- PDO prepared statements
- htmlspecialchars() sanitization
- Session validation
- Input type validation

- Indexed queries (user_id, visit_date)
- Limit results to 100 records
- Efficient database transactions
- Minimal DOM manipulation



**Document Version:** 1.0  
**Last Updated:** 2026-06-10  
**Status:** Complete Reference ✅

