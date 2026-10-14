


This document outlines the complete implementation of the **"+ Add Medicine"** and **"Edit Medicine"** modal functionalities for the ScanCare BHW Dashboard's Medicine Inventory Management module.



- **Trigger:** "+ Add Medicine" button in the inventory header
- **Form Fields:**
  - Medicine Name (required)
  - Dosage/Strength (optional)
  - Total Stock (required, number input)
  - Available Stock (auto-syncs with Total Stock)
- **Auto-Sync Logic:** When user changes Total Stock, Available Stock automatically updates to match
- **Stock Status Preview:** Real-time status badge showing:
  - 🟢 **Available** (Stock > 5)
  - 🟠 **Low Stock** (0 < Stock ≤ 5)
  - ⚫ **Out of Stock** (Stock = 0)
- **Form Actions:** Cancel (close without saving) or Add Medicine (submit)

- **Trigger:** "Edit" button on each medicine row in the table
- **Form Fields:**
  - Medicine ID (hidden)
  - Medicine Name (required)
  - Dosage/Strength (optional)
  - Total Stock (required)
  - Available Stock (optional)
  - Dispensed (optional, read-only reference)
- **Stock Status Preview:** Updates in real-time as user modifies stock quantities
- **Validation:** Available Stock cannot exceed Total Stock
- **Form Actions:** Cancel or Update Medicine


```javascript
{
  action: 'add_medicine',
  medicine_name: string,
  dosage: string,
  quantity: number,  
}
```

```javascript
{
  action: 'update_medicine',
  medicine_id: number,
  medicine_name: string,
  dosage: string,
  stock: number,           
  available: number,       
  dispensed: number        
}
```


**File:** `dashboard_bhw.php` (Lines 233-330)

**Request Type:** POST  
**Response:** JSON

**Success Response:**
```json
{
  "success": true,
  "message": "Medicine added successfully"
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "Error description"
}
```

**Database Operation:**
- Inserts into `medicine` table
- Auto-calculates status based on quantity:
  - qty > 5 → "Available"
  - 0 < qty ≤ 5 → "Low Stock"
  - qty = 0 → "Out of Stock"

**Request Type:** POST  
**Response:** JSON

**Database Operation:**
- Updates existing medicine record in `medicine` table
- Auto-recalculates status based on new stock quantity
- Preserves medicine ID for referential integrity


- **Backdrop:** Semi-transparent black blur effect (50% opacity with backdrop-blur)
- **Animation:** Fade-in and zoom-in entrance animation
- **Responsive:** Max-width 448px (max-w-md) with full-height scroll support on mobile
- **Theme:** Dark olive/green (#454B1B) for primary action buttons

- **Input Focus:** Teal ring (focus:ring-[#454B1B]) with border transparency
- **Buttons:**
  - Cancel: Light gray with hover effect
  - Add/Update: Dark olive with opacity hover
  - Icons for visual clarity (fas fa-* classes)

```html
<!
<span class="inline-flex items-center gap-1 px-3 py-1 bg-green-50 text-green-700 rounded-full font-medium text-xs">
  <i class="fas fa-circle text-xs"></i> Available
</span>

<!
<span class="inline-flex items-center gap-1 px-3 py-1 bg-orange-50 text-orange-700 rounded-full font-medium text-xs">
  <i class="fas fa-exclamation-triangle text-xs mr-1"></i> Low Stock
</span>

<!
<span class="inline-flex items-center gap-1 px-3 py-1 bg-slate-100 text-slate-700 rounded-full font-medium text-xs">
  <i class="fas fa-circle text-xs"></i> Out of Stock
</span>
```



Opens the Add Medicine modal with a cleared form.

**Behavior:**
1. Gets modal and form elements
2. Resets all form fields to empty
3. Sets status preview to "Out of Stock"
4. Enables submit button
5. Shows modal with display:flex
6. Auto-focuses Medicine Name input

Closes the Add Medicine modal without saving.

**Parameters:** `quantity` (number)  
**Returns:** Object with status info

```javascript
{
  status: 'Available' | 'Low Stock' | 'Out of Stock',
  color: 'green' | 'orange' | 'slate',
  display: '<span>HTML status badge</span>'
}
```

**Logic:**
- qty > 5 → Available (green)
- 0 < qty ≤ 5 → Low Stock (orange)
- qty ≤ 0 → Out of Stock (slate)

Auto-syncs Available Stock field with Total Stock field.

**Triggered by:** `onchange` event on Total Stock input  
**Actions:**
1. Gets Total Stock value
2. Sets Available Stock to match Total Stock
3. Triggers status preview update

Updates the stock status preview in the Edit modal.

**Triggered by:** `onchange` event on Stock or Available inputs  
**Actions:**
1. Reads current Stock value
2. Calculates new status
3. Updates status preview badge
4. Validates Available ≤ Stock

Opens the Edit Medicine modal for a specific medicine row.

**Parameters:** `button` (HTMLElement) - The Edit button that was clicked  
**Actions:**
1. Locates the table row containing the button
2. Extracts data from table cells
3. Populates edit form with extracted data
4. Triggers status preview update
5. Shows edit modal
6. Auto-focuses Medicine Name input

Closes the Edit Medicine modal without saving.

Processes Add Medicine form submission.

**Validation:**
- Medicine Name: Required, non-empty
- Total Stock: Required, must be ≥ 0

**Process:**
1. Prevents default form submission
2. Validates all required fields
3. Shows loading state on button
4. Sends AJAX POST request to dashboard_bhw.php
5. On success: Shows confirmation, closes modal, reloads page
6. On error: Shows error alert, keeps form intact

Processes Edit Medicine form submission.

**Validation:**
- Medicine Name: Required, non-empty
- Total Stock: Required, must be ≥ 0
- Available Stock: Cannot exceed Total Stock

**Process:**
1. Prevents default form submission
2. Validates all fields
3. Shows loading state on button
4. Sends AJAX POST request to dashboard_bhw.php
5. On success: Shows confirmation, closes modal, reloads page
6. On error: Shows error alert, keeps form intact


- **PHP Backend:** Uses `htmlspecialchars()` to prevent XSS
- **Database:** Uses prepared statements with parameter binding
- **Frontend:** Form validation before submission

- POST requests go through standard AJAX
- Server-side session validation (BHW authentication required)

**Frontend:**
- Required field checks
- Number range validation
- Stock quantity validation (available ≤ total)

**Backend:**
- Type checking and casting
- Database constraint validation
- Error logging for debugging


- **Mobile (< 768px):** Full-width modal with padding
- **Tablet/Desktop (≥ 768px):** Max-width 448px centered modal
- **Grid Layout:** 
  - Add form: Stacked fields on mobile, 2-column on larger screens
  - Edit form: 3-column grid for Stock/Available/Dispensed


- [ ] "+ Add Medicine" button opens modal
- [ ] Form fields are empty on open
- [ ] Entering medicine name shows in input
- [ ] Entering dosage shows in input
- [ ] Entering total stock shows in input
- [ ] Changing total stock auto-syncs available stock
- [ ] Status preview updates as stock changes
- [ ] Status shows "Available" for stock > 5
- [ ] Status shows "Low Stock" for 0 < stock ≤ 5
- [ ] Status shows "Out of Stock" for stock = 0
- [ ] Cancel button closes modal without saving
- [ ] Add Medicine button submits form (requires backend)
- [ ] Modal closes after successful submission
- [ ] Page reloads to show new medicine in table

- [ ] Edit button on medicine row opens modal
- [ ] Form populates with medicine data from row
- [ ] Medicine Name matches table row
- [ ] Dosage matches table row
- [ ] Total Stock matches table row
- [ ] Available Stock matches table row
- [ ] Dispensed Stock shows in read-only field
- [ ] Status preview shows correct status
- [ ] Changing stock updates status preview
- [ ] Available stock cannot exceed total stock validation
- [ ] Cancel button closes modal without saving
- [ ] Update Medicine button submits form
- [ ] Modal closes after successful update
- [ ] Page reloads to show updated medicine


1. **Refresh Reload:** Page reloads after add/edit instead of dynamic update
2. **Expiry Dates:** Not tracked in current implementation
3. **Batch Operations:** No bulk add/edit functionality
4. **Undo Functionality:** No undo for recent changes

1. Dynamic row updates without page reload using DOM manipulation
2. Add expiry date tracking
3. Bulk import from CSV
4. Medicine search/filter in modal
5. Stock history/audit trail
6. Low stock alerts
7. Medicine categories/classifications
8. Barcode scanning integration
9. Print inventory reports
10. Medicine interactions warnings


```html
<button onclick="showAddMedicineForm()" style="background-color: #454B1B;" class="text-white px-4 py-2 rounded-lg font-semibold">
  <i class="fas fa-plus mr-2"></i> Add Medicine
</button>
```

```html
<button onclick="editMedicine(this)" class="text-[#454B1B] hover:opacity-70 font-semibold">
  Edit
</button>
```

```javascript

const quantity = 25;
const status = calculateMedicineStatus(quantity);
console.log(status); 


document.getElementById('statusContainer').innerHTML = status.display;
```


- **Main File:** `dashboard_bhw.php`
- **Database Config:** `db_config.php`
- **Database Table:** `medicine`
  - Columns: `medicine_id`, `name`, `dosage`, `stock_quantity`, `available`, `dispensed`, `status`
- **Styles:** Tailwind CSS (CDN)
- **Icons:** Font Awesome (CDN)



**Modal doesn't open:**
1. Check browser console for JavaScript errors
2. Verify `dashboard_bhw.php` is not showing PHP errors
3. Ensure modal HTML elements exist (check for typos in IDs)

**Form data not submitting:**
1. Check network tab for failed AJAX requests
2. Verify backend handler is receiving the POST request
3. Check PHP error logs for database errors

**Status badge not updating:**
1. Verify `calculateMedicineStatus()` function exists
2. Check that status preview element has correct ID
3. Ensure change events are properly triggered


- All timestamps use Philippine Standard Time (UTC+8)
- Database uses UTF-8mb4 encoding for full Unicode support
- Session-based authentication required (BHW user verification)
- All data is sanitized to prevent XSS attacks
- Prepared statements prevent SQL injection



**Last Updated:** 2026-06-11  
**Version:** 1.0  
**Status:** ✅ Production Ready

