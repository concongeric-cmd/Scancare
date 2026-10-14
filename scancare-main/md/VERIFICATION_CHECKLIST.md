


**Date**: 2026-06-11  
**Purpose**: Verify that all Medicine Inventory buttons and forms are working correctly  


- ✅ Logged in as BHW user
- ✅ Navigated to Dashboard
- ✅ Currently viewing "Medicine Inventory" tab
- ✅ Browser DevTools open (F12) - Console tab visible
- ✅ Cleared browser cache (optional but recommended)


```
Steps:
1. Look at the "Medicine Inventory Management" section header
2. On the right side, look for a green button with "+ Add Medicine" text

Expected Result: ✅ Button is visible and has a green background
```

```
Steps:
1. Click the green "+ Add Medicine" button
2. Observe if a modal window opens

Expected Result: ✅ Modal opens with white background and form fields
                   Modal should have:
                   - Title: "Add New Medicine"
                   - Close button (X) in top right
                   - 4 form fields
```

```
Expected Modal Content:
- ✅ Medicine Name field (text input, marked as required with *)
- ✅ Dosage/Strength field (text input)
- ✅ Initial Stock Quantity field (number input, marked as required)
- ✅ Condition/Storage Notes field (textarea)
- ✅ Cancel button (gray)
- ✅ Add Medicine button (green)
```

```
Steps:
1. In the modal, fill in:
   - Medicine Name: "Test Medicine"
   - Dosage: "100mg"
   - Initial Stock Quantity: "50"
   - Notes: "Test entry"
   
2. Click "Add Medicine" button

Expected Result: ✅ Button shows loading spinner
                   ✅ Success alert: "✅ Medicine added successfully!"
                   ✅ Modal closes
                   ✅ Page reloads automatically
                   ✅ New medicine appears in table
```

```
Steps:
1. Click "+ Add Medicine" again
2. Leave Medicine Name blank
3. Enter other fields
4. Click "Add Medicine"

Expected Result: ✅ Error alert: "❌ Medicine name is required."
                   Modal stays open
                   No page reload
```

```
Steps:
1. Click "+ Add Medicine"
2. Enter Medicine Name: "Test"
3. Enter Quantity: "-10" (negative number)
4. Click "Add Medicine"

Expected Result: ✅ Error alert: "❌ Quantity must be a non-negative number."
                   Modal stays open
                   No page reload
```

```
Steps:
1. In the modal, enter some data
2. Click "Cancel" button
3. Click "+ Add Medicine" again

Expected Result: ✅ Modal opens with empty/reset form fields
                   Previous data is not retained
```


```
Steps:
1. Look at the medicine table
2. In the "Actions" column (rightmost), check each row

Expected Result: ✅ Each medicine row has an "Edit" button
                   Blue/teal text color
                   All rows have the button
```

```
Steps:
1. In the medicine table, find any medicine row
2. Click the "Edit" button on that row
3. Observe if a modal opens

Expected Result: ✅ Modal opens titled "Edit Medicine"
                   ✅ Form fields are PRE-FILLED with current medicine data
```

```
Setup: Click Edit on "Paracetamol 500mg" (or any existing medicine)

Expected Modal Shows:
- ✅ Medicine Name: "Paracetamol" (or actual name)
- ✅ Dosage: "500mg" (or actual dosage)
- ✅ Stock: "100" (or actual stock number)
- ✅ Available: "100" (or actual available)
- ✅ Dispensed: "0" (or actual dispensed)

Verify: All fields match the table row values
```

```
Steps:
1. Click Edit on any medicine
2. Wait for modal to open and form to pre-fill
3. Change Stock value:
   - From: 100
   - To: 90
4. Click "Update Medicine"

Expected Result: ✅ Button shows loading spinner
                   ✅ Success alert: "✅ Medicine updated successfully!"
                   ✅ Modal closes
                   ✅ Page reloads
                   ✅ Table row updates showing new stock value (90)
```

```
Steps:
1. Click Edit on a medicine
2. Set:
   - Stock: 50
   - Dispensed: 60 (more than stock)
3. Click "Update Medicine"

Expected Result: ✅ Error alert: "❌ Validation Error: Dispensed quantity cannot exceed Total Stock."
                   Modal stays open
                   No page reload
```

```
Steps:
1. Click Edit on a medicine
2. Change Stock from current value to 0
3. Click "Update Medicine"
4. Wait for page reload
5. Check the Status column

Expected Result: ✅ Status changes to "Out of Stock" (gray badge)
                   OR: Status remains "Available" if stock > 0

Verify: Status badge updates based on stock level
- Stock > 5: "Available" (green)
- 0 < Stock <= 5: "Low Stock" (orange)
- Stock = 0: "Out of Stock" (gray)
```


```
Steps:
1. Add a new medicine via "+ Add Medicine" button
2. Page reloads
3. Close the browser tab completely
4. Reopen the application and navigate to Medicine Inventory

Expected Result: ✅ The medicine you added is still in the table
                   ✅ Data persisted to database
```

```
Steps:
1. Edit a medicine (change its stock)
2. Page reloads
3. Check the table

Expected Result: ✅ New stock value appears in table
                   ✅ Available column shows correct calculated value
                   ✅ Dispensed column shows correct value
                   ✅ Status badge is correct
```

```
Steps:
1. In Edit modal, set:
   - Stock: 100
   - Dispensed: 25
2. Click "Update"
3. Wait for page reload
4. Check table

Expected Result: ✅ Available column shows: 75 (100 - 25)
                   Verify: Available = Stock - Dispensed
```


```
Steps:
1. Open browser DevTools (F12)
2. Go to Network tab
3. Set throttling to "Offline" or disconnect internet
4. Try to Add or Edit a medicine
5. Click submit button

Expected Result: ✅ Error alert appears
                   ✅ Modal stays open (doesn't close)
                   ✅ Button re-enables after error
```

```
Steps:
1. Click "+ Add Medicine"
2. Leave ALL fields blank
3. Try clicking "Add Medicine"

Expected Result: ✅ Browser prevents submission (HTML required attribute)
                   OR: Alert shows "Medicine name is required"
```


```
Steps:
1. Add or Edit a medicine
2. Click submit button
3. IMMEDIATELY observe the button

Expected Result: ✅ Button changes to show loading spinner
                   ✅ Button becomes disabled (not clickable)
                   ✅ Button text changes to "Adding..." or "Updating..."
```

```
Steps:
1. Open Add or Edit modal
2. Click the "X" button in top right
3. Click the "Cancel" button

Expected Result: ✅ Both methods close the modal
                   Modal disappears
                   Overlay goes away
```

```
Steps:
1. Open Add Medicine modal
2. Notice which field has focus (should be first field)
3. Press Tab key several times

Expected Result: ✅ Focus moves through form fields in order
                   ✅ User can tab to all buttons and fields
                   ✅ Accessible keyboard navigation works
```


```
Steps:
1. Click "+ Add Medicine"
2. Medicine Name: "Acetaminophen Paracetamol Extra Strength Formula 500mg"
3. Click "Add Medicine"

Expected Result: ✅ Medicine is added successfully
                   ✅ Name displays in table (may wrap to new line)
                   ✅ No database errors
```

```
Steps:
1. Click "+ Add Medicine"
2. Medicine Name: "Aspirin-C (100mg) & Co-Enzyme"
3. Click "Add Medicine"

Expected Result: ✅ Medicine is added successfully
                   ✅ Special characters display correctly in table
                   ✅ No HTML/security issues
```

```
Steps:
1. Click "+ Add Medicine" five times quickly
2. Observe if multiple modals open or if only one appears

Expected Result: ✅ Only ONE modal opens at a time
                   ✅ Multiple clicks don't create duplicates
```

```
Steps:
1. Edit a medicine
2. Set Stock to: 999999
3. Set Dispensed to: 500000
4. Click "Update"

Expected Result: ✅ Update succeeds
                   ✅ Available shows: 499999 (999999 - 500000)
                   ✅ Large numbers are handled correctly
```


```
Steps:
1. Open DevTools (F12)
2. Go to Console tab
3. Perform any action (Add/Edit)
4. Check for red error messages

Expected Result: ✅ No red error messages appear
                   ✅ Console is clean
                   ✅ May have yellow warnings (okay)
```

```
Steps:
1. Open DevTools (F12)
2. Go to Network tab
3. Add or Edit a medicine
4. Check the requests

Expected Result: ✅ POST request to dashboard_bhw.php appears
                   ✅ Status is 200 (success)
                   ✅ Response shows JSON with "success": true
```

```
Steps:
1. Open DevTools (F12)
2. Go to Console tab
3. Click "+ Add Medicine"
4. Fill form and click "Add Medicine"
5. Check console output

Expected Result: ✅ You should see console.log messages:
                   - "Add Medicine Form Data:"
                   - "Name: Medicine Name"
                   - "Sending medicine to server..."
                   - "Server response: {...}"
                   - "Reloading page..."
```


- [ ] Button visible and clickable
- [ ] Modal opens
- [ ] Form has all fields
- [ ] Valid submission works
- [ ] Empty name validation works
- [ ] Invalid quantity validation works
- [ ] Form resets on close
- [ ] Success alert shows
- [ ] Page reloads with new data

- [ ] Edit button visible on all rows
- [ ] Modal opens with pre-filled data
- [ ] Can change stock value
- [ ] Validation prevents dispensed > stock
- [ ] Status updates automatically
- [ ] Changes persist after page reload

- [ ] Form validation works
- [ ] Network errors handled gracefully
- [ ] Buttons disable during submission
- [ ] Error messages are clear

- [ ] New medicines saved to database
- [ ] Updated medicines reflect in table
- [ ] Available always = Stock - Dispensed
- [ ] Status badges update correctly


1. Open F12 Console
2. Check for JavaScript errors
3. Try refreshing page (Ctrl+F5)
4. Check if modal exists in HTML

1. Check Console for validation errors
2. Verify all required fields are filled
3. Check Network tab for POST request
4. Look at Response tab for error message

1. Check Network tab - did POST request send?
2. Check Response - what did server return?
3. Check database directly - does data exist?
4. Check PHP error logs

1. Check if CSS is loading (DevTools > Network > XHR/CSS)
2. Inspect modal element (DevTools > Elements)
3. Clear browser cache (Ctrl+Shift+Delete)
4. Reload page (Ctrl+F5)


✅ **All tests pass if:**
1. Buttons are visible and clickable
2. Modals open with correct content
3. Forms validate input correctly
4. Data submits via AJAX (no page refresh during submit)
5. Success alerts appear on completion
6. Page reloads after success
7. Table updates with new/modified data
8. Data persists in database
9. No JavaScript errors in console
10. Network requests complete with status 200

**Status**: Ready for testing! 🎉

