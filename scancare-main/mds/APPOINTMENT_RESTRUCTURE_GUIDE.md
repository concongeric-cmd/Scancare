

**Date:** 2026-06-08  
**Status:** ✅ Complete  


The resident appointment portal has been restructured to follow a logical user workflow:

1. **View upcoming appointments** (what's scheduled)
2. **Check medical history** (past completed visits)
3. **Schedule new appointments** (book next visit)

This aligns with how residents naturally interact with their health records.


```
1. Schedule an Appointment (form at top)
2. Upcoming Appointments (list)
3. Medical Visit History (list)
```

```
1. Upcoming Appointments (list) - TOP
2. Medical Visit History (list) - MIDDLE
3. Schedule an Appointment (form) - BOTTOM
```

**Benefits:**
- ✅ More intuitive flow for residents
- ✅ First see what's already booked
- ✅ Then see past visits
- ✅ Finally schedule new appointment
- ✅ Matches typical appointment workflow


```
User schedules appointment → Appointment created with status='scheduled'
→ Appears in "Upcoming Appointments"
→ Staff manually records the visit elsewhere
```

```
User schedules appointment 
    ↓
Appointment created (appointments.appointment_id, status='scheduled')
    ↓
Resident views appointment in "Upcoming Appointments" section
    ↓
[Staff marks appointment as COMPLETE via admin/workflow panel]
    ↓
AUTOMATIC TRANSITION:
    - appointment.status = 'completed'
    - NEW medical_history record created with:
      * visit_date = appointment.appointment_date
      * visit_type = appointment.reason
      * bhw_name = staff_member_name
      * notes = visit notes + appointment time
    - Appointment removed from "Upcoming Appointments"
    - Medical history entry added to "Medical Visit History"
```


When an appointment is marked complete, fields map seamlessly:

| Appointment Field | Medical History Field | Example |
|
| `appointment_date` | `visit_date` | 2026-06-15 |
| `appointment_time` | stored in `notes` | "Visit Time: 2:30 PM" |
| `reason` | `visit_type` | "Routine Check-up" |
| (new input) | `bhw_name` | "Dr. Maria Santos" |
| (new input) | `notes` | "Vital signs stable..." |



**Restructured HTML Sections:**

```html
<!
<section id="upcoming-appointments">
    <!
    <!
</section>

<!
<section id="medical-history">
    <!
    <!
</section>

<!
<section id="schedule-appointment">
    <!
    <!
</section>
```


**Purpose:** Handle appointment completion and automatic transfer to medical history

**Endpoint:** `/appointment_workflow.php`

**Request:**
```php
POST /appointment_workflow.php
{
    "action": "complete_appointment",
    "appointment_id": 5,
    "bhw_name": "Dr. Maria Santos",
    "notes": "Vital signs stable. No concerns. Follow-up in 3 months."
}
```

**Transaction Flow:**
1. ✅ Validate appointment exists and not already completed
2. ✅ START DATABASE TRANSACTION
3. ✅ FETCH appointment details (date, time, reason, user_id)
4. ✅ CREATE medical_history record with mapped fields
5. ✅ UPDATE appointment status to 'completed'
6. ✅ COMMIT TRANSACTION
7. ✅ Return success response

**Response:**
```json
{
    "success": true,
    "message": "Appointment marked as completed and transferred to medical history",
    "data": {
        "appointment_id": 5,
        "history_id": 12,
        "user_id": 3,
        "visit_date": "2026-06-15",
        "visit_type": "Routine Check-up",
        "bhw_name": "Dr. Maria Santos"
    }
}
```


1. ✅ View "Upcoming Appointments" - See all scheduled visits
2. ✅ View "Medical Visit History" - See past completed visits
3. ✅ Scroll to bottom and use "Schedule an Appointment" form to book new visits


**Option 1: Via API (AJAX)**

```javascript

fetch('/itelec4/appointment_workflow.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
        action: 'complete_appointment',
        appointment_id: 5,
        bhw_name: 'Dr. Maria Santos',
        notes: 'Routine check-up completed. All vitals normal.'
    })
})
.then(res => res.json())
.then(data => {
    if (data.success) {
        alert('Appointment completed! Moved to medical history.');
        location.reload();
    } else {
        alert('Error: ' + data.message);
    }
});
```

**Option 2: Via SQL (Direct Database - for testing)**

```sql

INSERT INTO medical_history (
    user_id, 
    visit_date, 
    visit_type, 
    bhw_name, 
    notes, 
    status, 
    created_at, 
    updated_at
) 
SELECT 
    user_id,
    appointment_date,
    reason,
    'Dr. Maria Santos',
    CONCAT('Visit Time: ', TIME_FORMAT(appointment_time, '%l:%i %p'), '\nNotes: Routine checkup completed.'),
    'completed',
    NOW(),
    NOW()
FROM appointments
WHERE appointment_id = 5;


UPDATE appointments 
SET status = 'completed', updated_at = NOW() 
WHERE appointment_id = 5;
```



```
┌─────────────────────────────────────────────┐
│  Welcome to Your Health Dashboard           │
│  Manage appointments & view health history  │
└─────────────────────────────────────────────┘

┌─────────────────────────────────────────────┐
│ 📅 UPCOMING APPOINTMENTS                    │
│ ┌─────────────────────────────────────────┐ │
│ │ Routine Check-up                        │ │
│ │ 📅 Jun 15, 2026 at 2:30 PM             │ │
│ │ Status: SCHEDULED                       │ │
│ └─────────────────────────────────────────┘ │
└─────────────────────────────────────────────┘

┌─────────────────────────────────────────────┐
│ 📋 MEDICAL VISIT HISTORY                    │
│ ┌─────────────────────────────────────────┐ │
│ │ Vaccination                             │ │
│ │ 📅 May 20, 2026                         │ │
│ │ 👨‍⚕️ Dr. Ricardo Reyes                   │ │
│ │ Status: COMPLETED                       │ │
│ └─────────────────────────────────────────┘ │
└─────────────────────────────────────────────┘

┌─────────────────────────────────────────────┐
│ ➕ SCHEDULE AN APPOINTMENT                   │
│ ┌─────────────────────────────────────────┐ │
│ │ Preferred Date: [______]                │ │
│ │ Preferred Time: [______]                │ │
│ │ Reason: [________]                      │ │
│ │ [SCHEDULE APPOINTMENT BUTTON]           │ │
│ └─────────────────────────────────────────┘ │
└─────────────────────────────────────────────┘
```


- Uses existing: `appointments` table
- Uses existing: `medical_history` table
- No schema modifications needed


**Appointments table:**
```
status = 'scheduled'  → Upcoming (displayed in section 1)
status = 'pending'    → Upcoming (displayed in section 1)
status = 'completed'  → Hidden from Upcoming (not >= CURDATE or status not in list)
```

**Medical History table:**
```
status = 'completed'  → Displayed in Medical Visit History section
```


✅ **PDO Prepared Statements** - Prevents SQL injection  
✅ **Database Transactions** - Ensures data consistency  
✅ **Input Validation** - All inputs validated before use  
✅ **XSS Protection** - htmlspecialchars() on all output  
✅ **User Isolation** - Residents only see their own appointments  



1. Login as resident (e.g., resident1@gmail.com)
2. Navigate to dashboard
3. ✅ Verify order: Upcoming → Medical History → Schedule
4. ✅ Check responsive layout on mobile


1. Scroll to bottom "Schedule an Appointment" section
2. Fill form:
   - Preferred Date: June 20, 2026
   - Preferred Time: 3:00 PM
   - Reason: Routine Check-up
3. Click "Schedule Appointment"
4. ✅ Success message appears
5. ✅ New appointment appears in "Upcoming Appointments" section


**Setup:** Have an appointment scheduled in the database

**Execute workflow:**
```javascript

fetch('/itelec4/appointment_workflow.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
        action: 'complete_appointment',
        appointment_id: 1,
        bhw_name: 'Dr. Maria Santos',
        notes: 'Check-up completed. All vitals normal.'
    })
})
.then(res => res.json())
.then(data => console.log(data));
```

**Verify:**
1. ✅ Appointment removed from "Upcoming Appointments"
2. ✅ New entry appears in "Medical Visit History"
3. ✅ Fields properly mapped:
   - Visit Date = appointment date
   - Visit Type = appointment reason
   - BHW Name = entered name
   - Notes = visit time + notes


| File | Changes |
|
| `dashboard_resident.php` | Reordered sections (upcoming → history → schedule) |
| `appointment_workflow.php` | **NEW** - Backend handler for appointment completion |


1. **Auto-complete via time** - Automatically mark past appointments as completed
2. **Admin interface** - UI for staff to complete appointments
3. **Appointment reminders** - Notify residents before appointment time
4. **Reschedule option** - Allow residents to reschedule upcoming appointments
5. **Cancellation** - Let residents cancel appointments
6. **Appointment notes** - Residents can add notes to appointments


**What's New:**
- ✅ Intuitive layout order (upcoming → history → schedule)
- ✅ Automatic data transfer when appointments complete
- ✅ Seamless field mapping (date, time, reason → history)
- ✅ Transaction-safe completion workflow
- ✅ No data loss - appointments preserved in history

**User Benefit:**
- ✅ Clear workflow: see upcoming, check history, book new
- ✅ All appointment data automatically recorded
- ✅ Historical tracking of all health visits
- ✅ Better health record organization

**Status: ✅ Production Ready**

