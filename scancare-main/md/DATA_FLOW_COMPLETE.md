


```
┌────────────────────────────────────┐
│  Identify Patient Tab              │
├────────────────────────────────────┤
│  Search Input: "Maria Santos"      │
│  Or: "09306938679" (phone)         │
│  Or: "maria@email.com" (email)     │
│  Or: "3" (resident ID)             │
└────────────────────────────────────┘
         ↓
    Frontend Validation:
    ✓ Check not empty
    ✓ Check length < 100
    ✓ Enable loading spinner
         ↓
    Disable Submit Button
    Show "Searching..." text
```

```
JavaScript Code (dashboard_bhw.php):
    fetch('search_patient_process.php', {
        method: 'POST',
        body: 'search_term=Maria+Santos'
    })

HTTP Request Sent:
┌─────────────────────────────────────┐
│ POST /search_patient_process.php    │
├─────────────────────────────────────┤
│ search_term: "Maria Santos"         │
│ Session: worker_id = 1              │
│ Session: worker_role = "bhw"        │
└─────────────────────────────────────┘
```


```
search_patient_process.php:

1. SESSION VALIDATION
   ✓ Check $_SESSION['worker_id'] exists
   ✓ Check $_SESSION['worker_role'] = 'bhw'
   ✓ If not: Return 401 Unauthorized

2. BUILD PARAMETERIZED QUERY
   ✓ Table: resident
   ✓ WHERE conditions:
     - full_name LIKE ?
     - OR email LIKE ?
     - OR contact_number LIKE ?
     - OR resident_id = ?
   
   ✓ Parameters:
     - '%Maria Santos%'
     - '%Maria Santos%'
     - '%Maria Santos%'
     - is_numeric('Maria Santos') ? -1

3. EXECUTE QUERY
   Query: SELECT resident_id, full_name, email, 
                 contact_number, date_of_birth
          FROM resident
          WHERE (full_name LIKE '%Maria Santos%'
             OR email LIKE '%Maria Santos%'
             OR contact_number LIKE '%Maria Santos%'
             OR resident_id = -1)
          LIMIT 5

4. PROCESS RESULTS
   Results from DB:
   [
     {resident_id: 3, full_name: "Maria Santos", ...},
     {resident_id: 7, full_name: "Maria Gonzalez", ...}
   ]

5. DETERMINE RESPONSE TYPE
   Count = 2 (multiple matches)
   → Return action: 'select' with patient list
```


```
HTTP Response (200 OK):
┌─────────────────────────────────────┐
│ Content-Type: application/json      │
├─────────────────────────────────────┤
│ {                                   │
│   "success": true,                  │
│   "action": "select",               │
│   "count": 2,                       │
│   "patients": [                     │
│     {                               │
│       "id": 3,                      │
│       "name": "Maria Santos",       │
│       "email": "maria@email.com",   │
│       "phone": "09306938679",       │
│       "dob": "Jan 15, 1990"         │
│     },                              │
│     {                               │
│       "id": 7,                      │
│       "name": "Maria Gonzalez",     │
│       "email": "m.gonz@email.com",  │
│       "phone": "09271234567",       │
│       "dob": "Mar 22, 1985"         │
│     }                               │
│   ],                                │
│   "message": "2 results found..."   │
│ }                                   │
└─────────────────────────────────────┘
```


```
JavaScript Code:
    .then(data => {
        if (data.success) {
            if (data.action === 'redirect') {
                
                window.location.href = 
                  '?module=patient-profile&patient_id=' + data.patient_id;
            
            } else if (data.action === 'select') {
                
                if (data.patients.length > 0) {
                    const firstPatient = data.patients[0];
                    
                    
                    
                    window.location.href = 
                      '?module=patient-profile&patient_id=3';
                }
            }
        }
    })

Result: Browser navigates to:
?module=patient-profile&patient_id=3
```


```
URL Changes to:
dashboard_bhw.php?module=patient-profile&patient_id=3

Browser reloads page with new URL
```


```
dashboard_bhw.php PHP Code (lines 41-120):

1. CHECK URL PARAMETERS
   ✓ isset($_GET['patient_id']) = TRUE (value: 3)
   ✓ $active_module === 'patient-profile' = TRUE

2. FETCH RESIDENT BASIC INFO
   Query:
   SELECT resident_id, full_name, email, contact_number,
          date_of_birth, sex, address, barangay, city, 
          province, postal_code
   FROM resident
   WHERE resident_id = 3
   LIMIT 1

   Result: $resident array
   {
     resident_id: 3,
     full_name: "Maria Santos",
     email: "maria@email.com",
     contact_number: "09306938679",
     date_of_birth: "1990-01-15",
     sex: "Female",
     address: "123 Main St",
     barangay: "San Antonio",
     city: "Makati",
     province: "Metro Manila",
     postal_code: "1200"
   }

3. FETCH MEDICAL HISTORY
   Query:
   SELECT history_id, resident_id, diagnosis, treatment,
          notes, created_at
   FROM medical_history
   WHERE resident_id = 3
   ORDER BY created_at DESC
   LIMIT 10

   Result: $medical_history array [last 10 records]
   [
     {
       history_id: 45,
       diagnosis: "Hypertension",
       treatment: "Lisinopril 10mg",
       notes: "Patient stable",
       created_at: "2026-06-10"
     },
     {
       history_id: 44,
       diagnosis: "Type 2 Diabetes",
       treatment: "Metformin 500mg",
       notes: "Monitor glucose levels",
       created_at: "2026-06-08"
     },
     
   ]

4. FETCH APPOINTMENTS
   Query:
   SELECT appointment_id, resident_id, appointment_date,
          appointment_time, purpose, status, created_at
   FROM appointment
   WHERE resident_id = 3
   ORDER BY appointment_date DESC, appointment_time DESC
   LIMIT 10

   Result: $appointments array [last 10 appointments]
   [
     {
       appointment_id: 102,
       appointment_date: "2026-06-15",
       appointment_time: "10:00",
       purpose: "Follow-up Checkup",
       status: "scheduled"
     },
     {
       appointment_id: 101,
       appointment_date: "2026-06-10",
       appointment_time: "14:30",
       purpose: "BP Monitoring",
       status: "completed"
     },
     
   ]

5. BUILD PATIENT DATA OBJECT
   $patient_data = array_merge($resident, [
     'medical_history' => $medical_history,
     'appointments' => $appointments
   ]);

6. SET STATE VARIABLES
   $patient_identified = true;
   
```


```
HTML Template (dashboard_bhw.php lines 768-967):

IF ($patient_identified && $patient_data) {
    
    
    
    ├─ Full Name: Maria Santos
    ├─ Email: maria@email.com (clickable)
    ├─ Phone: 09306938679 (clickable)
    ├─ Resident ID: 3
    ├─ Sex: Female
    ├─ Date of Birth: Jan 15, 1990
    └─ Address: 123 Main St, San Antonio, Makati, 
                Metro Manila 1200

    
    ├─ [Record 1]
    │  ├─ Diagnosis: Hypertension
    │  ├─ Treatment: Lisinopril 10mg
    │  ├─ Notes: Patient stable
    │  └─ Date: Jun 10, 2026
    ├─ [Record 2]
    │  ├─ Diagnosis: Type 2 Diabetes
    │  ├─ Treatment: Metformin 500mg
    │  ├─ Notes: Monitor glucose levels
    │  └─ Date: Jun 08, 2026
    └─ ... more records (scrollable, max 10)

    
    ├─ [Appointment 1]
    │  ├─ Purpose: Follow-up Checkup
    │  ├─ Date: Jun 15, 2026 @ 10:00 AM
    │  └─ Status: Scheduled (amber badge)
    ├─ [Appointment 2]
    │  ├─ Purpose: BP Monitoring
    │  ├─ Date: Jun 10, 2026 @ 2:30 PM
    │  └─ Status: Completed (green badge)
    └─ ... more appointments (scrollable, max 10)

    
    ├─ ← Back to Identify Patient
    └─ 🩺 Record Consultation

} ELSE {
    
}
```


```
Screen Display:
┌──────────────────────────────────────────────────────┐
│ Patient Profile                                       │
├──────────────────────────────────────────────────────┤
│                                                       │
│ [COMPLETE PROFILE CARD]                              │
│ ├─ Full Name: Maria Santos                           │
│ ├─ Email: maria@email.com                            │
│ ├─ Phone: 09306938679                                │
│ ├─ Resident ID: 3                                    │
│ ├─ Sex: Female                                       │
│ ├─ DOB: Jan 15, 1990                                 │
│ └─ Address: 123 Main St, San Antonio, Makati,        │
│             Metro Manila 1200                        │
│                                                       │
│ [MEDICAL HISTORY CARD]                               │
│ ├─ Hypertension (Jun 10, 2026)                       │
│ │  Treatment: Lisinopril 10mg                        │
│ │  Notes: Patient stable                             │
│ ├─ Type 2 Diabetes (Jun 08, 2026)                    │
│ │  Treatment: Metformin 500mg                        │
│ │  Notes: Monitor glucose levels                     │
│ └─ [scroll for more...]                              │
│                                                       │
│ [APPOINTMENT DETAILS CARD]                            │
│ ├─ Follow-up Checkup (Jun 15, 2026 @ 10:00 AM)      │
│ │  Status: Scheduled [amber]                         │
│ ├─ BP Monitoring (Jun 10, 2026 @ 2:30 PM)           │
│ │  Status: Completed [green]                         │
│ └─ [scroll for more...]                              │
│                                                       │
│ [← Back] [🩺 Record Consultation]                    │
└──────────────────────────────────────────────────────┘
```


```
User Action: Click "← Back to Identify Patient" button

HTML:
<a href="?module=identify-patient">
    ← Back to Identify Patient
</a>

Browser navigates to:
dashboard_bhw.php?module=identify-patient

Page reloads:
- $active_module = 'identify-patient'
- $patient_identified = false (condition not met)
- $patient_data = null

Template shows placeholder instead of profile
```


```
Search Input: Resident ID "3"
    ↓
Backend Query:
    resident_id = ? (parameter: 3)
    Result count: 1
    ↓
Response:
    {
      "success": true,
      "action": "redirect",
      "patient_id": 3,
      "patient_name": "Maria Santos"
    }
    ↓
Frontend:
    window.location.href = '?module=patient-profile&patient_id=3'
    ↓
[Same as STEP 6-10 above]
```


```
Search Input: "xyz123nonexistent"
    ↓
Backend Query:
    full_name LIKE '%xyz123nonexistent%' OR ...
    Result count: 0
    ↓
Response (HTTP 404):
    {
      "success": false,
      "error_type": "not_found",
      "message": "No patient records match your search criteria..."
    }
    ↓
Frontend:
    if (!data.success) {
        showManualSearchError(data.message);
        
        
        
    }
    ↓
Display: Error message on Identify Patient tab
```


```
Input: Any string (name, email, phone, ID)
Type: VARCHAR (string)
Max Length: 100 characters
Trimming: Yes (trim whitespace)

Usage in Queries:
- For text fields: WRAPPED IN WILDCARDS "%term%"
- For ID fields: CONVERTED TO INTEGER intval()
  If not numeric: Use -1 (impossible match)
```

```
Input: URL query string ?patient_id=3
Type: INTEGER
Processing: intval() conversion
Validation: > 0 check
Database: Direct match with resident_id column
```

```
Column: resident_id
Type: INT (Primary Key)
Range: Positive integers
Default: Auto-increment
```

```
Database Storage: YYYY-MM-DD format
Display Format: M d, YYYY
Example: "Jan 15, 1990"
PHP Function: date('M d, Y', strtotime($date))
```


```
✓ Search term not empty
✓ Search term length < 100
✓ Patient ID > 0
✓ Patient ID is integer
```

```
✓ Connection successful
✓ Query prepared correctly
✓ Parameters bound safely (no SQL injection)
✓ Query executed without errors
```

```
✓ Results fetched
✓ Results array not empty (if expected)
✓ All required fields present
✓ Data types correct
```

```
✓ $patient_identified = true
✓ $patient_data not null
✓ Template renders correctly
✓ All 3 cards visible
✓ No HTML errors
```


- ✅ Search accepts: Name, Email, Phone, Resident ID
- ✅ Single match → Direct profile display
- ✅ Multiple matches → First match profile display
- ✅ No matches → Error message, stay on search
- ✅ Complete Profile displays 6 demographics
- ✅ Medical History displays last 10 records
- ✅ Appointments displays last 10 with status badges
- ✅ Back button clears patient state
- ✅ All errors handled gracefully
- ✅ Session security maintained
- ✅ No modal overlay
- ✅ Auto-redirect on multiple matches

**Status: ✅ ALL COMPLETE**

