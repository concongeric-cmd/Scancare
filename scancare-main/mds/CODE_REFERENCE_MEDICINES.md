



```html
<button onclick="showAddMedicineForm()" style="background-color: #454B1B;" 
    class="hover:opacity-95 text-white px-4 py-2 rounded-lg font-semibold text-sm transition-all">
    <i class="fas fa-plus mr-2"></i> Add Medicine
</button>
```

```html
<button onclick="editMedicine(this)" class="text-[#454B1B] hover:opacity-70 text-sm font-semibold">
    Edit
</button>
```

```html
<tr class="hover:bg-slate-50 transition-colors" 
    data-medicine-id="<?php echo $medicine['medicine_id']; ?>" 
    data-stock="<?php echo $stock; ?>" 
    data-available="<?php echo $available; ?>" 
    data-dispensed="<?php echo $dispensed; ?>" 
    data-dosage="<?php echo htmlspecialchars($medicine['dosage']); ?>" 
    data-name="<?php echo htmlspecialchars($medicine['name']); ?>">
    <td class="px-6 py-4 font-medium text-slate-900"><?php echo htmlspecialchars($medicine['name']); ?></td>
    <td class="px-6 py-4 text-slate-600 font-medium"><?php echo htmlspecialchars($medicine['dosage']); ?></td>
    <td class="px-6 py-4 text-center"><span class="font-bold text-slate-900"><?php echo $stock; ?></span></td>
    <td class="px-6 py-4 text-center"><span class="font-bold text-emerald-600"><?php echo $available; ?></span></td>
    <td class="px-6 py-4 text-center"><span class="text-slate-600 font-medium"><?php echo $dispensed; ?></span></td>
    <td class="px-6 py-4">
        <!
    </td>
    <td class="px-6 py-4 text-center">
        <button onclick="editMedicine(this)" class="text-[#454B1B] hover:opacity-70 text-sm font-semibold">Edit</button>
    </td>
</tr>
```


```html
<div id="addMedicineModal" style="display: none;" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-900">Add New Medicine</h3>
            <button onclick="closeAddMedicineModal()" class="text-slate-500 hover:text-slate-700 text-2xl leading-none">&times;</button>
        </div>
        
        <form id="addMedicineForm" onsubmit="handleAddMedicineSubmit(event)" class="p-6 space-y-4">
            <div>
                <label for="addMedicineName" class="block text-sm font-semibold text-slate-700 mb-2">Medicine Name *</label>
                <input type="text" id="addMedicineName" name="medicine_name" 
                    placeholder="e.g., Paracetamol, Amoxicillin" 
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B]" required>
            </div>
            
            <div>
                <label for="addMedicineDosage" class="block text-sm font-semibold text-slate-700 mb-2">Dosage/Strength</label>
                <input type="text" id="addMedicineDosage" name="dosage" 
                    placeholder="e.g., 500mg tablet, 250mg capsule" 
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B]">
            </div>
            
            <div>
                <label for="addMedicineQuantity" class="block text-sm font-semibold text-slate-700 mb-2">Initial Stock Quantity *</label>
                <input type="number" id="addMedicineQuantity" name="quantity" min="0" 
                    placeholder="0" 
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B]" required>
            </div>
            
            <div>
                <label for="addMedicineCondition" class="block text-sm font-semibold text-slate-700 mb-2">Condition/Storage Notes</label>
                <textarea id="addMedicineCondition" name="condition" 
                    placeholder="e.g., Store in cool place, expiry date: 2026-12-31" 
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B]" rows="3"></textarea>
            </div>
            
            <div class="flex gap-2 justify-end pt-4">
                <button type="button" onclick="closeAddMedicineModal()" 
                    class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 font-semibold hover:bg-slate-50 transition-all">
                    Cancel
                </button>
                <button type="submit" style="background-color: #454B1B;" 
                    class="px-4 py-2 text-white rounded-lg font-semibold hover:opacity-95 transition-all">
                    Add Medicine
                </button>
            </div>
        </form>
    </div>
</div>
```

```html
<div id="editMedicineModal" style="display: none;" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-900">Edit Medicine</h3>
            <button onclick="closeEditMedicineModal()" class="text-slate-500 hover:text-slate-700 text-2xl leading-none">&times;</button>
        </div>
        
        <form id="editMedicineForm" onsubmit="handleEditMedicineSubmit(event)" class="p-6 space-y-4">
            <input type="hidden" id="editMedicineId" value="">
            
            <div>
                <label for="editMedicineName" class="block text-sm font-semibold text-slate-700 mb-2">Medicine Name *</label>
                <input type="text" id="editMedicineName" name="medicine_name" 
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B]" required>
            </div>
            
            <div>
                <label for="editMedicineDosage" class="block text-sm font-semibold text-slate-700 mb-2">Dosage</label>
                <input type="text" id="editMedicineDosage" name="dosage" 
                    placeholder="e.g., 500mg" 
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B]">
            </div>
            
            <div class="grid grid-cols-3 gap-2">
                <div>
                    <label for="editMedicineStock" class="block text-sm font-semibold text-slate-700 mb-2">Stock *</label>
                    <input type="number" id="editMedicineStock" name="stock" min="0" 
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B]" required>
                </div>
                <div>
                    <label for="editMedicineAvailable" class="block text-sm font-semibold text-slate-700 mb-2">Available</label>
                    <input type="number" id="editMedicineAvailable" name="available" min="0" 
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B]">
                </div>
                <div>
                    <label for="editMedicineDispensed" class="block text-sm font-semibold text-slate-700 mb-2">Dispensed</label>
                    <input type="number" id="editMedicineDispensed" name="dispensed" min="0" 
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B]">
                </div>
            </div>
            
            <div class="flex gap-2 justify-end pt-4">
                <button type="button" onclick="closeEditMedicineModal()" 
                    class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 font-semibold hover:bg-slate-50 transition-all">
                    Cancel
                </button>
                <button type="submit" style="background-color: #454B1B;" 
                    class="px-4 py-2 text-white rounded-lg font-semibold hover:opacity-95 transition-all">
                    Update Medicine
                </button>
            </div>
        </form>
    </div>
</div>
```


```javascript
function showAddMedicineForm() {
    
    const modal = document.getElementById('addMedicineModal');
    if (!modal) {
        alert('Modal not found. Please refresh the page.');
        return;
    }
    
    
    document.getElementById('addMedicineForm').reset();
    document.getElementById('addMedicineName').value = '';
    document.getElementById('addMedicineDosage').value = '';
    document.getElementById('addMedicineQuantity').value = '';
    document.getElementById('addMedicineCondition').value = '';
    
    
    modal.style.display = 'flex';
}

function closeAddMedicineModal() {
    const modal = document.getElementById('addMedicineModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function closeEditMedicineModal() {
    const modal = document.getElementById('editMedicineModal');
    if (modal) {
        modal.style.display = 'none';
    }
}
```

```javascript
function handleAddMedicineSubmit(event) {
    event.preventDefault();
    
    const medicineName = document.getElementById('addMedicineName').value.trim();
    const dosage = document.getElementById('addMedicineDosage').value.trim();
    const quantity = parseInt(document.getElementById('addMedicineQuantity').value) || 0;
    const condition = document.getElementById('addMedicineCondition').value.trim();
    
    
    if (!medicineName) {
        alert('❌ Medicine name is required.');
        return;
    }
    
    if (quantity < 0) {
        alert('❌ Quantity must be a non-negative number.');
        return;
    }
    
    if (dosage && dosage.length > 50) {
        alert('❌ Dosage information is too long. Please keep it under 50 characters.');
        return;
    }
    
    console.log('Add Medicine Form Data:');
    console.log('  Name:', medicineName);
    console.log('  Dosage:', dosage);
    console.log('  Quantity:', quantity);
    
    
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Adding...';
    
    
    const formData = new FormData();
    formData.append('action', 'add_medicine');
    formData.append('medicine_name', medicineName);
    formData.append('dosage', dosage);
    formData.append('quantity', quantity);
    formData.append('condition', condition);
    
    console.log('Sending medicine to server...');
    
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Server response:', data);
        
        if (data.success) {
            
            alert('✅ Medicine added successfully!');
            
            
            closeAddMedicineModal();
            
            
            console.log('Reloading page to show new medicine...');
            location.reload();
        } else {
            alert('❌ Error adding medicine: ' + (data.message || 'Unknown error'));
            console.error('Server error:', data);
            
            
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('❌ Error adding medicine. Check console for details.');
        
        
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}
```

```javascript
function editMedicine(button) {
    
    const row = button.closest('tr');
    const medicineId = row.getAttribute('data-medicine-id');
    const medicineName = row.getAttribute('data-name') || row.cells[0].textContent.trim();
    const dosage = row.getAttribute('data-dosage') || row.cells[1].textContent.trim();
    const stockQuantity = row.getAttribute('data-stock') || row.cells[2].textContent.trim();
    const available = row.getAttribute('data-available') || row.cells[3].textContent.trim();
    const dispensed = row.getAttribute('data-dispensed') || row.cells[4].textContent.trim();
    
    
    const modal = document.getElementById('editMedicineModal');
    const form = document.getElementById('editMedicineForm');
    
    if (!modal || !form) {
        alert('Modal not found. Please refresh the page.');
        return;
    }
    
    
    document.getElementById('editMedicineId').value = medicineId;
    document.getElementById('editMedicineName').value = medicineName;
    document.getElementById('editMedicineDosage').value = dosage;
    document.getElementById('editMedicineStock').value = parseInt(stockQuantity);
    document.getElementById('editMedicineAvailable').value = parseInt(available);
    document.getElementById('editMedicineDispensed').value = parseInt(dispensed);
    
    console.log('Edit Medicine Modal Opened:', {
        id: medicineId,
        name: medicineName,
        dosage: dosage,
        stock: stockQuantity,
        available: available,
        dispensed: dispensed
    });
    
    
    modal.style.display = 'flex';
}
```

```javascript
function handleEditMedicineSubmit(event) {
    event.preventDefault();
    
    const medicineId = document.getElementById('editMedicineId').value;
    const medicineName = document.getElementById('editMedicineName').value.trim();
    const dosage = document.getElementById('editMedicineDosage').value.trim();
    const stock = parseInt(document.getElementById('editMedicineStock').value) || 0;
    const available = parseInt(document.getElementById('editMedicineAvailable').value) || 0;
    const dispensed = parseInt(document.getElementById('editMedicineDispensed').value) || 0;
    
    
    const calculatedAvailable = stock - dispensed;
    if (calculatedAvailable < 0) {
        alert('❌ Validation Error: Dispensed quantity cannot exceed Total Stock.\n\nAdjust the values so that: Available + Dispensed = Total Stock');
        return;
    }
    
    if (!medicineId || !medicineName || stock < 0) {
        alert('Please fill in all required fields with valid values.');
        return;
    }
    
    console.log('Submitting medicine update:', {
        id: medicineId,
        name: medicineName,
        dosage: dosage,
        stock: stock,
        available: calculatedAvailable,
        dispensed: dispensed
    });
    
    
    const formData = new FormData();
    formData.append('action', 'update_medicine');
    formData.append('medicine_id', medicineId);
    formData.append('medicine_name', medicineName);
    formData.append('dosage', dosage);
    formData.append('stock', stock);
    formData.append('available', calculatedAvailable);
    formData.append('dispensed', dispensed);
    
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Medicine updated successfully!');
            closeEditMedicineModal();
            console.log('Reloading page to show updated medicine...');
            location.reload();
        } else {
            alert('❌ Error updating medicine: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating medicine. Please try again.');
    });
}
```


```php
if ($action === 'add_medicine') {
    header('Content-Type: application/json');
    $medicine_name = trim($_POST['medicine_name'] ?? '');
    $dosage = trim($_POST['dosage'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 0);
    
    
    error_log("ADD MEDICINE DEBUG - Name: '$medicine_name', Dosage: '$dosage', Quantity: $quantity");
    
    try {
        $pdo = getDatabaseConnection();
        
        if ($pdo === false) {
            error_log("ADD MEDICINE ERROR: Database connection failed");
            echo json_encode(['success' => false, 'message' => 'Database connection failed']);
            exit;
        }
        
        if (empty($medicine_name)) {
            error_log("ADD MEDICINE ERROR: Medicine name is empty");
            echo json_encode(['success' => false, 'message' => 'Medicine name is required']);
            exit;
        }
        
        if ($quantity < 0) {
            error_log("ADD MEDICINE ERROR: Invalid quantity");
            echo json_encode(['success' => false, 'message' => 'Quantity must be non-negative']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO medicine (name, dosage, stock_quantity, available, dispensed, status) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $status = ($quantity > 5) ? 'Available' : (($quantity > 0) ? 'Low Stock' : 'Out of Stock');
        $clean_name = htmlspecialchars($medicine_name);
        $clean_dosage = htmlspecialchars($dosage);
        
        error_log("ADD MEDICINE: Executing insert with - Name: '$clean_name', Dosage: '$clean_dosage', Qty: $quantity, Status: '$status'");
        
        if ($stmt->execute([$clean_name, $clean_dosage, $quantity, $quantity, 0, $status])) {
            error_log("ADD MEDICINE SUCCESS: Medicine inserted successfully");
            echo json_encode(['success' => true, 'message' => 'Medicine added successfully']);
            exit;
        } else {
            error_log("ADD MEDICINE ERROR: Execute returned false");
            echo json_encode(['success' => false, 'message' => 'Failed to execute database insert']);
            exit;
        }
        
    } catch (Exception $e) {
        $error_msg = "Add medicine error: " . $e->getMessage();
        error_log($error_msg);
        echo json_encode(['success' => false, 'message' => $error_msg]);
        exit;
    }
}
```

```php
if ($action === 'update_medicine') {
    header('Content-Type: application/json');
    $medicine_id = intval($_POST['medicine_id'] ?? 0);
    $name = trim($_POST['medicine_name'] ?? '');
    $dosage = trim($_POST['dosage'] ?? '');
    $stock = intval($_POST['stock'] ?? 0);
    $available = intval($_POST['available'] ?? $stock);
    $dispensed = intval($_POST['dispensed'] ?? 0);
    
    try {
        $pdo = getDatabaseConnection();
        if ($pdo !== false && $medicine_id > 0) {
            $status = ($stock > 5) ? 'Available' : (($stock > 0) ? 'Low Stock' : 'Out of Stock');
            $stmt = $pdo->prepare("
                UPDATE medicine 
                SET name = ?, dosage = ?, stock_quantity = ?, available = ?, dispensed = ?, status = ? 
                WHERE medicine_id = ?
            ");
            if ($stmt->execute([htmlspecialchars($name), htmlspecialchars($dosage), $stock, $available, $dispensed, $status, $medicine_id])) {
                echo json_encode(['success' => true, 'message' => 'Medicine updated successfully']);
                exit;
            }
        }
    } catch (Exception $e) {
        error_log("Medicine update error: " . $e->getMessage());
    }
    echo json_encode(['success' => false, 'message' => 'Error updating medicine']);
    exit;
}
```


```php

$stmt = $pdo->query("SELECT medicine_id, name, dosage, stock_quantity, available, dispensed, status FROM medicine ORDER BY name ASC");
$medicine_list = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
```


```
Button Click → Event Handler → Validation → AJAX POST → Response Handler → UI Update
```

- Form validation before submission ✅
- Network error catching ✅
- Server error responses ✅
- User feedback via alerts ✅
- Button state management (enabled/disabled) ✅

- HTML sanitization: `htmlspecialchars()` ✅
- SQL injection prevention: Prepared statements ✅
- Data type validation ✅

- Loading spinners during submission ✅
- Clear success/error messages ✅
- Modal close on success ✅
- Page reload to reflect changes ✅
- Form reset before new entry ✅

