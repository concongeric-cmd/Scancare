



```
┌─────────────────────────────────────────────────────────────────┐
│                    BHW DASHBOARD (dashboard_bhw.php)            │
│                                                                  │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │           Medicine Inventory Tab UI                      │  │
│  │                                                          │  │
│  │  ┌─────────────────────────────────────────────────┐   │  │
│  │  │  Medicine Inventory Management Header           │   │  │
│  │  │                                                 │   │  │
│  │  │  ┌─────────────────────────┐                  │   │  │
│  │  │  │ + Add Medicine          │ ← GREEN BUTTON   │   │  │
│  │  │  │ (onclick: show form)    │                  │   │  │
│  │  │  └─────────────────────────┘                  │   │  │
│  │  └─────────────────────────────────────────────────┘   │  │
│  │                                                          │  │
│  │  ┌─────────────────────────────────────────────────┐   │  │
│  │  │  Medicine Inventory Table                       │   │  │
│  │  │                                                 │   │  │
│  │  │  ┌─────────────────────────────────────────┐  │   │  │
│  │  │  │ Name │ Dosage │ Stock │ Avail │ Disp. │  │   │  │
│  │  │  ├─────────────────────────────────────────┤  │   │  │
│  │  │  │ Paracetamol 500mg 100 100 0 [Edit] │  │   │  │
│  │  │  │ Ibuprofen   400mg 50  50  0 [Edit] │  │   │  │
│  │  │  │ Amoxicillin 500mg 75  75  0 [Edit] │  │   │  │
│  │  │  │ ...                          [Edit] │  │   │  │
│  │  │  └─────────────────────────────────────────┘  │   │  │
│  │  │         (Edit buttons ← BLUE TEXT)            │   │  │
│  │  └─────────────────────────────────────────────────┘   │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```


```
┌──────────────────────────────────────────────────────────┐
│ 1. USER CLICKS "+ ADD MEDICINE" BUTTON                   │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 2. JAVASCRIPT FUNCTION TRIGGERED                         │
│    showAddMedicineForm()                                 │
│    - Gets modal element                                  │
│    - Resets form fields                                  │
│    - Sets display: 'flex'                                │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 3. MODAL DISPLAYS (Overlay appears)                      │
│    ┌────────────────────────────────┐                   │
│    │ Add New Medicine       [X]      │                   │
│    ├────────────────────────────────┤                   │
│    │ Medicine Name: [___________]    │                   │
│    │ Dosage:       [___________]    │                   │
│    │ Initial Stock: [___________]   │                   │
│    │ Condition:    [____________]   │                   │
│    │                                │                   │
│    │ [Cancel]      [Add Medicine]   │                   │
│    └────────────────────────────────┘                   │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 4. USER FILLS FORM & CLICKS "ADD MEDICINE"               │
│    Data entered:                                          │
│    - Medicine Name: "Aspirin"                            │
│    - Dosage: "100mg"                                     │
│    - Initial Stock: "200"                                │
│    - Condition: (empty)                                  │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 5. JAVASCRIPT VALIDATES INPUT                            │
│    handleAddMedicineSubmit(event)                        │
│    ✓ Medicine Name not empty?                           │
│    ✓ Quantity >= 0?                                     │
│    ✓ Dosage <= 50 chars?                                │
│    All validations pass → Continue                       │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 6. BUTTON SHOWS LOADING SPINNER                          │
│    Button.innerHTML = "🔄 Adding..."                     │
│    Button.disabled = true (prevent double-click)        │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 7. AJAX POST REQUEST SENT                                │
│    URL: dashboard_bhw.php                                │
│    Method: POST                                          │
│    Payload:                                              │
│    {                                                     │
│      action: "add_medicine",                             │
│      medicine_name: "Aspirin",                           │
│      dosage: "100mg",                                    │
│      quantity: 200                                       │
│    }                                                     │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼ (Server Processing)
        ┌──────────────────────────────┐
        │ Backend PHP Handler:         │
        │ if ($action === 'add_medicine')
        │                              │
        │ 1. Get POST data             │
        │ 2. Validate inputs           │
        │ 3. Sanitize with            │
        │    htmlspecialchars()        │
        │ 4. Calculate status:         │
        │    200 > 5 → "Available"     │
        │ 5. INSERT INTO medicine      │
        │    (name, dosage,            │
        │     stock, available,        │
        │     dispensed, status)       │
        │ 6. Return JSON               │
        └──────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 8. RESPONSE FROM SERVER                                  │
│    {                                                     │
│      "success": true,                                    │
│      "message": "Medicine added successfully"            │
│    }                                                     │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 9. JAVASCRIPT PROCESSES RESPONSE                         │
│    .then(response => response.json())                    │
│    .then(data => {                                       │
│      if (data.success) {                                 │
│        showAlert("✅ Medicine added successfully!")     │
│        closeAddMedicineModal()                          │
│        location.reload()                                │
│      }                                                  │
│    })                                                   │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 10. SUCCESS ALERT SHOWN                                  │
│     "✅ Medicine added successfully!"                    │
│     [OK]                                                 │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 11. MODAL CLOSES                                         │
│     modal.style.display = 'none'                         │
│     Form is hidden from view                             │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 12. PAGE RELOADS                                         │
│     location.reload()                                    │
│     New medicine data fetched from database              │
│     Table refreshes with new medicine                    │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 13. FINAL STATE: TABLE SHOWS NEW MEDICINE                │
│     ┌──────────────────────────────────────┐             │
│     │ Name        │ Dosage │ Stock │ Edit  │             │
│     ├──────────────────────────────────────┤             │
│     │ Aspirin     │ 100mg  │ 200   │ [Edit]│ ← NEW ROW  │
│     │ Paracetamol │ 500mg  │ 100   │ [Edit]│             │
│     │ Ibuprofen   │ 400mg  │ 50    │ [Edit]│             │
│     └──────────────────────────────────────┘             │
│                                                          │
│     ✅ OPERATION COMPLETE                                │
└──────────────────────────────────────────────────────────┘
```


```
┌──────────────────────────────────────────────────────────┐
│ 1. USER CLICKS "EDIT" BUTTON ON A TABLE ROW              │
│    Example: Click Edit on "Paracetamol" row             │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 2. JAVASCRIPT FUNCTION TRIGGERED                         │
│    editMedicine(button)                                  │
│    - Gets clicked button's row: row = button.closest('tr')
│    - Extracts data from row attributes:                  │
│      • data-medicine-id: "5"                             │
│      • data-name: "Paracetamol"                          │
│      • data-dosage: "500mg"                              │
│      • data-stock: "100"                                 │
│      • data-available: "100"                             │
│      • data-dispensed: "0"                               │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 3. MODAL DISPLAYS WITH PRE-FILLED FORM                   │
│    ┌────────────────────────────────┐                   │
│    │ Edit Medicine         [X]       │                   │
│    ├────────────────────────────────┤                   │
│    │ Medicine Name: [Paracetamol]    │ ← Pre-filled    │
│    │ Dosage:       [500mg]          │ ← Pre-filled    │
│    │ Stock:        [100]            │ ← Pre-filled    │
│    │ Available:    [100]            │ ← Read-only     │
│    │ Dispensed:    [0]              │ ← Pre-filled    │
│    │                                │                   │
│    │ [Cancel]      [Update Medicine]│                   │
│    └────────────────────────────────┘                   │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 4. USER EDITS FORM VALUES                                │
│    Example Changes:                                      │
│    - Stock: 100 → 120 (received 20 more)                 │
│    - Dispensed: 0 → 5 (used 5 units)                     │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 5. USER CLICKS "UPDATE MEDICINE"                         │
│    Form data extracted:                                  │
│    - medicine_id: "5"                                    │
│    - medicine_name: "Paracetamol"                        │
│    - stock: 120                                          │
│    - dispensed: 5                                        │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 6. JAVASCRIPT VALIDATES & CALCULATES                     │
│    handleEditMedicineSubmit(event)                       │
│    ✓ Medicine ID exists?                                │
│    ✓ Medicine Name not empty?                           │
│    ✓ Stock >= 0?                                        │
│    Calculate: Available = Stock - Dispensed             │
│               Available = 120 - 5 = 115 ✓               │
│    ✓ Available >= 0? (Yes, 115 >= 0)                    │
│    All validations pass → Continue                       │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 7. BUTTON SHOWS LOADING SPINNER                          │
│    Button.innerHTML = "🔄 Updating..."                   │
│    Button.disabled = true (prevent double-click)        │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 8. AJAX POST REQUEST SENT                                │
│    URL: dashboard_bhw.php                                │
│    Method: POST                                          │
│    Payload:                                              │
│    {                                                     │
│      action: "update_medicine",                          │
│      medicine_id: "5",                                   │
│      medicine_name: "Paracetamol",                       │
│      dosage: "500mg",                                    │
│      stock: 120,                                         │
│      available: 115,    ← Calculated client-side       │
│      dispensed: 5                                        │
│    }                                                     │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼ (Server Processing)
        ┌──────────────────────────────┐
        │ Backend PHP Handler:         │
        │ if ($action ===              │
        │    'update_medicine')        │
        │                              │
        │ 1. Get POST data             │
        │ 2. Validate: medicine_id > 0 │
        │ 3. Sanitize all strings      │
        │ 4. Calculate status:         │
        │    120 > 5 → "Available"     │
        │ 5. UPDATE medicine           │
        │    WHERE medicine_id = 5     │
        │    SET name = "Paracetamol"  │
        │        stock = 120           │
        │        available = 115       │
        │        dispensed = 5         │
        │        status = "Available"  │
        │ 6. Return JSON               │
        └──────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 9. RESPONSE FROM SERVER                                  │
│    {                                                     │
│      "success": true,                                    │
│      "message": "Medicine updated successfully"          │
│    }                                                     │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 10. JAVASCRIPT PROCESSES RESPONSE                        │
│     .then(data => {                                      │
│       if (data.success) {                                │
│         showAlert("✅ Medicine updated successfully!")  │
│         closeEditMedicineModal()                        │
│         location.reload()                               │
│       }                                                 │
│     })                                                  │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 11. SUCCESS ALERT SHOWN                                  │
│     "✅ Medicine updated successfully!"                  │
│     [OK]                                                 │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 12. MODAL CLOSES                                         │
│     modal.style.display = 'none'                         │
│     Form is hidden from view                             │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 13. PAGE RELOADS                                         │
│     location.reload()                                    │
│     Updated medicine data fetched from database          │
│     Table refreshes with new values                      │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 14. FINAL STATE: TABLE SHOWS UPDATED MEDICINE            │
│     ┌────────────────────────────────────────┐           │
│     │ Name        │ Dosage │ Stock │ Avail │ │           │
│     ├────────────────────────────────────────┤           │
│     │ Paracetamol │ 500mg  │ 120   │ 115   │ │ ← UPDATED │
│     │ Ibuprofen   │ 400mg  │ 50    │ 50    │ │           │
│     │ Aspirin     │ 100mg  │ 200   │ 200   │ │           │
│     └────────────────────────────────────────┘           │
│     Stock updated: 100 → 120 ✓                           │
│     Available updated: 100 → 115 ✓                       │
│     Status remains: Available ✓                          │
│                                                          │
│     ✅ OPERATION COMPLETE                                │
└──────────────────────────────────────────────────────────┘
```


```
┌─────────────────────────────────────────────────────┐
│             PHP Backend Processing                  │
├─────────────────────────────────────────────────────┤
│                                                     │
│ 1. Receive POST data from browser                  │
│    medicine_name = "Aspirin"                       │
│    dosage = "100mg"                                │
│    quantity = 200                                  │
│                                                     │
│ 2. Validate input                                  │
│    ✓ medicine_name not empty                       │
│    ✓ quantity >= 0                                 │
│    ✓ No SQL injection patterns detected            │
│                                                     │
│ 3. Sanitize input                                  │
│    htmlspecialchars($medicine_name)                │
│    htmlspecialchars($dosage)                       │
│                                                     │
│ 4. Calculate status                                │
│    IF quantity > 5 THEN status = "Available"       │
│    ELSE IF quantity > 0 THEN status = "Low Stock"  │
│    ELSE status = "Out of Stock"                    │
│    Result: status = "Available"                    │
│                                                     │
│ 5. Prepare SQL statement (prevents injection)      │
│    INSERT INTO medicine                            │
│    (name, dosage, stock_quantity, available,       │
│     dispensed, status)                             │
│    VALUES (?, ?, ?, ?, ?, ?)                       │
│                                                     │
│ 6. Execute with prepared statement binding         │
│    ['Aspirin', '100mg', 200, 200, 0, 'Available']  │
│                                                     │
│ 7. DATABASE TRANSACTION                            │
│    ┌────────────────────────────────┐              │
│    │ medicine table:                 │              │
│    │ INSERT new row:                 │              │
│    │ medicine_id: 12 (auto-generated)│              │
│    │ name: 'Aspirin'                 │              │
│    │ dosage: '100mg'                 │              │
│    │ stock_quantity: 200             │              │
│    │ available: 200                  │              │
│    │ dispensed: 0                    │              │
│    │ status: 'Available'             │              │
│    └────────────────────────────────┘              │
│                                                     │
│ 8. Return JSON response                            │
│    {                                                │
│      "success": true,                              │
│      "message": "Medicine added successfully"      │
│    }                                                │
│                                                     │
└─────────────────────────────────────────────────────┘
```

```
┌─────────────────────────────────────────────────────┐
│             PHP Backend Processing                  │
├─────────────────────────────────────────────────────┤
│                                                     │
│ 1. Receive POST data from browser                  │
│    medicine_id = 5                                 │
│    medicine_name = "Paracetamol"                   │
│    stock = 120                                     │
│    available = 115                                 │
│    dispensed = 5                                   │
│                                                     │
│ 2. Validate input                                  │
│    ✓ medicine_id > 0                               │
│    ✓ medicine_name not empty                       │
│    ✓ stock >= 0                                    │
│    ✓ available + dispensed = stock (matches)       │
│                                                     │
│ 3. Sanitize input                                  │
│    htmlspecialchars($medicine_name)                │
│    All numeric values already validated            │
│                                                     │
│ 4. Calculate status                                │
│    IF stock > 5 THEN status = "Available"          │
│    Result: status = "Available"                    │
│                                                     │
│ 5. Prepare SQL UPDATE statement                    │
│    UPDATE medicine                                 │
│    SET name = ?, dosage = ?, stock_quantity = ?,  │
│        available = ?, dispensed = ?, status = ?   │
│    WHERE medicine_id = ?                           │
│                                                     │
│ 6. Execute with prepared statement binding         │
│    ['Paracetamol', '500mg', 120, 115, 5,           │
│     'Available', 5]                                │
│                                                     │
│ 7. DATABASE TRANSACTION                            │
│    ┌────────────────────────────────┐              │
│    │ medicine table (id=5):          │              │
│    │ UPDATE row:                     │              │
│    │ name: 'Paracetamol'             │              │
│    │ dosage: '500mg'                 │              │
│    │ stock_quantity: 120 (was 100)   │              │
│    │ available: 115 (was 100)        │              │
│    │ dispensed: 5 (was 0)            │              │
│    │ status: 'Available'             │              │
│    └────────────────────────────────┘              │
│                                                     │
│ 8. Return JSON response                            │
│    {                                                │
│      "success": true,                              │
│      "message": "Medicine updated successfully"    │
│    }                                                │
│                                                     │
└─────────────────────────────────────────────────────┘
```


```
                       USER
                        ▲
                        │
            ┌───────────┴───────────┐
            │                       │
        [Click Button]         [View Results]
            │                       ▲
            ▼                       │
    ┌───────────────────────┐       │
    │  Frontend Components  │       │
    │  (Vanilla JavaScript) │       │
    │                       │       │
    │ • showAddForm()      │       │
    │ • editMedicine()     │       │
    │ • handleSubmit()     │       │
    │ • validateForm()     │       │
    │ • fetchAPI (AJAX)    │       │
    └───────┬───────────────┘       │
            │                       │
            │ AJAX POST             │
            │ (JSON payload)        │
            │                       │
            ▼                       │
    ┌───────────────────────┐       │
    │  Backend Processing   │       │
    │  (PHP 7.4+)          │       │
    │                       │       │
    │ • Parse POST data    │       │
    │ • Validate input     │       │
    │ • Sanitize HTML      │       │
    │ • Check DB conn.     │       │
    │ • Execute query      │       │
    │ • Return JSON        │       │
    └───────┬───────────────┘       │
            │                       │
            │ JSON Response         │
            │ {success: true}       │
            │                       │
            ▼                       │
    ┌───────────────────────┐       │
    │   Database (MySQL)    │       │
    │                       │       │
    │ • INSERT new record   │       │
    │ • UPDATE existing     │       │
    │ • Return success      │       │
    └───────────────────────┘       │
            │                       │
            └───────────────────────┘
```


```
    ┌─────────┐
    │  START  │
    └────┬────┘
         │
         │ User clicks "+ Add Medicine"
         ▼
    ┌──────────────────┐
    │ MODAL_OPEN       │
    │ display: 'flex'  │
    │ Form is empty    │
    └────┬─────────┬───┘
         │         │
         │         │ User clicks Cancel
         │         │ (go back)
         │         │
         │         ▼
         │    ┌─────────────────┐
         │    │ MODAL_CLOSED    │
         │    │ display: 'none' │
         │    └────────┬────────┘
         │             │
         │             │ (cycle can repeat)
         │             ▼
         │         (START again)
         │
         │ User fills form & clicks "Add Medicine"
         │
         ▼
    ┌──────────────────┐
    │ SUBMITTING       │
    │ Button shows     │
    │ loading spinner  │
    │ AJAX in progress │
    └────┬─────────┬───┘
         │         │
         │         │ (if error)
         │         │ Validation fails
         │         │
         │         ▼
         │    ┌──────────────────┐
         │    │ ERROR            │
         │    │ Alert shown      │
         │    │ Modal stays open │
         │    │ Form kept        │
         │    └────────┬─────────┘
         │             │
         │             │ (user can retry)
         │             ▼
         │        (MODAL_OPEN again)
         │
         │ (if success)
         │ Server returns success
         │
         ▼
    ┌──────────────────┐
    │ SUCCESS          │
    │ Alert shown:     │
    │ "✅ Added!"      │
    └────┬─────────────┘
         │
         │ Auto-close & reload
         │
         ▼
    ┌──────────────────┐
    │ MODAL_CLOSED     │
    │ Page reloads     │
    │ New data shown   │
    └────┬─────────────┘
         │
         ▼
    ┌─────────┐
    │  DONE   │
    └─────────┘
```


```
┌──────────────────────────────────────────────────────┐
│             SECURITY VALIDATION LAYERS               │
├──────────────────────────────────────────────────────┤
│                                                      │
│  CLIENT SIDE (Browser)                              │
│  ────────────────────────                           │
│  Layer 1: HTML5 Validation                          │
│    ✓ required attribute                             │
│    ✓ type="number" for quantities                   │
│    ✓ min="0" for non-negative                       │
│                                                      │
│  Layer 2: JavaScript Validation                     │
│    ✓ Check if values not empty                      │
│    ✓ Check if numbers are valid                     │
│    ✓ Check if dispensed <= stock                    │
│    ✓ Show error alerts to user                      │
│                                                      │
│  ↓ Only if all client validations pass              │
│                                                      │
│  SERVER SIDE (PHP)                                  │
│  ────────────────                                   │
│  Layer 3: Input Type Validation                     │
│    ✓ intval() for IDs and quantities                │
│    ✓ trim() for strings                             │
│    ✓ Check ranges (>= 0)                            │
│                                                      │
│  Layer 4: Sanitization                              │
│    ✓ htmlspecialchars() prevents XSS               │
│    ✓ Prepared statements prevent SQL injection      │
│    ✓ No raw SQL concatenation                       │
│                                                      │
│  Layer 5: Business Logic Validation                 │
│    ✓ Check if medicine_id exists in DB              │
│    ✓ Check if values match business rules           │
│    ✓ Verify data consistency                        │
│                                                      │
│  Layer 6: Database Transaction                      │
│    ✓ Prepared statement execution                   │
│    ✓ Database-level constraints                     │
│    ✓ Transaction rollback on error                  │
│                                                      │
│  ↓ If any validation fails                          │
│                                                      │
│  ERROR RESPONSE                                      │
│  {                                                  │
│    "success": false,                                │
│    "message": "Specific error description"          │
│  }                                                  │
│  ↓                                                   │
│  Frontend displays error alert to user              │
│                                                      │
└──────────────────────────────────────────────────────┘
```


```
┌────────────────────────────────────────────────────────────────┐
│                  COMPLETE SYSTEM OVERVIEW                      │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│  FRONTEND TIER                                                 │
│  ┌──────────────────────────────────────────────────────┐      │
│  │ Browser                                              │      │
│  │ ┌──────────────────────────────────────────────┐    │      │
│  │ │ dashboard_bhw.php (Client-side)             │    │      │
│  │ │ ┌──────────────────────────────────────────┐│    │      │
│  │ │ │ HTML                                      ││    │      │
│  │ │ │ - Add Medicine button                     ││    │      │
│  │ │ │ - Medicine table with Edit buttons        ││    │      │
│  │ │ │ - Modals (hidden)                         ││    │      │
│  │ │ └──────────────────────────────────────────┘│    │      │
│  │ │ ┌──────────────────────────────────────────┐│    │      │
│  │ │ │ JavaScript                                ││    │      │
│  │ │ │ - Modal control functions                ││    │      │
│  │ │ │ - Form validation                        ││    │      │
│  │ │ │ - AJAX handlers                          ││    │      │
│  │ │ │ - Data manipulation                      ││    │      │
│  │ │ └──────────────────────────────────────────┘│    │      │
│  │ │ ┌──────────────────────────────────────────┐│    │      │
│  │ │ │ CSS (Tailwind)                           ││    │      │
│  │ │ │ - Modal styling                          ││    │      │
│  │ │ │ - Button styles                          ││    │      │
│  │ │ │ - Responsive layout                      ││    │      │
│  │ │ └──────────────────────────────────────────┘│    │      │
│  │ └──────────────────────────────────────────────┘    │      │
│  └──────────────────────────────────────────────────────┘      │
│                         ↕                                      │
│                   (HTTP Request/Response)                      │
│                         ↕                                      │
│  APPLICATION TIER                                              │
│  ┌──────────────────────────────────────────────────────┐      │
│  │ PHP Backend                                          │      │
│  │ ┌──────────────────────────────────────────────┐    │      │
│  │ │ dashboard_bhw.php (Server-side)             │    │      │
│  │ │ ┌──────────────────────────────────────────┐│    │      │
│  │ │ │ POST Request Handler                      ││    │      │
│  │ │ │ - Parse action parameter                 ││    │      │
│  │ │ │ - Route to correct function               ││    │      │
│  │ │ └──────────────────────────────────────────┘│    │      │
│  │ │ ┌──────────────────────────────────────────┐│    │      │
│  │ │ │ Add Medicine Handler                      ││    │      │
│  │ │ │ - Validate input                          ││    │      │
│  │ │ │ - Sanitize strings                        ││    │      │
│  │ │ │ - Calculate status                        ││    │      │
│  │ │ │ - Prepare SQL statement                   ││    │      │
│  │ │ │ - Execute INSERT                          ││    │      │
│  │ │ │ - Return JSON response                    ││    │      │
│  │ │ └──────────────────────────────────────────┘│    │      │
│  │ │ ┌──────────────────────────────────────────┐│    │      │
│  │ │ │ Update Medicine Handler                   ││    │      │
│  │ │ │ - Validate input                          ││    │      │
│  │ │ │ - Sanitize strings                        ││    │      │
│  │ │ │ - Calculate status                        ││    │      │
│  │ │ │ - Prepare SQL statement                   ││    │      │
│  │ │ │ - Execute UPDATE                          ││    │      │
│  │ │ │ - Return JSON response                    ││    │      │
│  │ │ └──────────────────────────────────────────┘│    │      │
│  │ │ ┌──────────────────────────────────────────┐│    │      │
│  │ │ │ Error Handling                            ││    │      │
│  │ │ │ - Try/catch blocks                        ││    │      │
│  │ │ │ - Database error logging                  ││    │      │
│  │ │ │ - User-friendly error messages            ││    │      │
│  │ │ └──────────────────────────────────────────┘│    │      │
│  │ └──────────────────────────────────────────────┘    │      │
│  └──────────────────────────────────────────────────────┘      │
│                         ↕                                      │
│               (SQL Prepared Statements)                         │
│                         ↕                                      │
│  DATA TIER                                                     │
│  ┌──────────────────────────────────────────────────────┐      │
│  │ MySQL Database                                      │      │
│  │ ┌──────────────────────────────────────────────┐    │      │
│  │ │ medicine table                               │    │      │
│  │ │ ┌──────────────────────────────────────────┐│    │      │
│  │ │ │ Columns:                                  ││    │      │
│  │ │ │ • medicine_id (PK)                        ││    │      │
│  │ │ │ • name (VARCHAR 255)                      ││    │      │
│  │ │ │ • dosage (VARCHAR 50)                     ││    │      │
│  │ │ │ • stock_quantity (INT)                    ││    │      │
│  │ │ │ • available (INT)                         ││    │      │
│  │ │ │ • dispensed (INT)                         ││    │      │
│  │ │ │ • status (VARCHAR 50)                     ││    │      │
│  │ │ └──────────────────────────────────────────┘│    │      │
│  │ │ ┌──────────────────────────────────────────┐│    │      │
│  │ │ │ Operations:                               ││    │      │
│  │ │ │ • SELECT (for display)                    ││    │      │
│  │ │ │ • INSERT (add new medicines)              ││    │      │
│  │ │ │ • UPDATE (modify medicines)               ││    │      │
│  │ │ │ • DELETE (if needed)                      ││    │      │
│  │ │ └──────────────────────────────────────────┘│    │      │
│  │ └──────────────────────────────────────────────┘    │      │
│  └──────────────────────────────────────────────────────┘      │
│                                                                │
│  ERROR HANDLING & LOGGING                                      │
│  ┌──────────────────────────────────────────────────────┐      │
│  │ • Browser console (Client-side errors)              │      │
│  │ • Network tab (HTTP request/response debugging)     │      │
│  │ • PHP error_log (Server-side errors)                │      │
│  │ • Database transaction logs (Data integrity)        │      │
│  └──────────────────────────────────────────────────────┘      │
│                                                                │
└────────────────────────────────────────────────────────────────┘
```



This visual architecture shows how all components work together to provide a seamless, secure, and reliable medicine inventory management system.

