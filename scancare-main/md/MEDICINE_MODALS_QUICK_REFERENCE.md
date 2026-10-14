


```javascript

showAddMedicineForm();


editMedicine(buttonElement);
```

```javascript
closeAddMedicineModal();
closeEditMedicineModal();
```


| Field | Type | Required | Auto-Behavior |
|
| Medicine Name | Text | ✅ Yes | - |
| Dosage/Strength | Text | ❌ No | - |
| Total Stock | Number | ✅ Yes | Triggers auto-sync to Available Stock |
| Available Stock | Number | ❌ No | Auto-syncs with Total Stock |

| Field | Type | Required | Notes |
|
| Medicine ID | Hidden | - | Set automatically |
| Medicine Name | Text | ✅ Yes | - |
| Dosage/Strength | Text | ❌ No | - |
| Total Stock | Number | ✅ Yes | Triggers status update |
| Available Stock | Number | ❌ No | Cannot exceed Total Stock |
| Dispensed | Number | ❌ No | Reference only |


```javascript
calculateMedicineStatus(quantity)





```


```
POST /dashboard_bhw.php
Body: {
  action: 'add_medicine',
  medicine_name: 'Amoxicillin',
  dosage: '500mg',
  quantity: 100
}
```

```
POST /dashboard_bhw.php
Body: {
  action: 'update_medicine',
  medicine_id: 5,
  medicine_name: 'Amoxicillin',
  dosage: '500mg',
  stock: 95,
  available: 90,
  dispensed: 5
}
```


Find: `style="background-color: #454B1B;"`  
Replace: `#454B1B` with your color code

Find: `max-w-md` in modal div  
Options: `max-w-sm`, `max-w-md`, `max-w-lg`, `max-w-xl`

Edit `calculateMedicineStatus()` function:
```javascript

bg-green-50 text-green-700  

bg-orange-50 text-orange-700

bg-slate-100 text-slate-700
```


```javascript
showAddMedicineForm();
```

```javascript
document.getElementById('addMedicineName').value = 'Test Medicine';
document.getElementById('addMedicineDosage').value = '250mg';
document.getElementById('addMedicineTotalStock').value = 50;
```

```javascript
autoSyncAvailableStock();
```

```javascript
calculateMedicineStatus(25); 
```


```javascript
typeof window.showAddMedicineForm 
typeof window.editMedicine        
typeof window.calculateMedicineStatus 
```

```javascript
window.getComputedStyle(document.getElementById('addMedicineModal')).display;

```

```javascript
{
  name: document.getElementById('addMedicineName').value,
  dosage: document.getElementById('addMedicineDosage').value,
  stock: document.getElementById('addMedicineTotalStock').value,
  available: document.getElementById('addMedicineAvailableStock').value
}
```


```sql
CREATE TABLE medicine (
  medicine_id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  dosage VARCHAR(100),
  stock_quantity INT DEFAULT 0,
  available INT DEFAULT 0,
  dispensed INT DEFAULT 0,
  status ENUM('Available', 'Low Stock', 'Out of Stock')
);
```


- **Dashboard:** `dashboard_bhw.php` (lines 1421-2472)
- **Database Config:** `db_config.php`
- **Documentation:** `MEDICINE_INVENTORY_MODALS_IMPLEMENTATION.md`


```
http:
```

```
POST http:
```


- **Mobile:** Full-width modal with padding
- **Tablet:** Max 448px (max-w-md)
- **Desktop:** Centered, max 448px


```javascript

showAddMedicineForm();



```

```javascript

const editBtn = document.querySelector('table tbody tr button');


editMedicine(editBtn);


```

```javascript
const quantity = 3;
const status = calculateMedicineStatus(quantity);

console.log(status.status);    
console.log(status.color);     
console.log(status.display);   
```


| Issue | Cause | Solution |
|
| Modal doesn't open | Missing HTML element | Check for modal IDs in HTML |
| Form not submitting | JavaScript error | Check browser console |
| Status not updating | Event not triggered | Trigger change event on input |
| Available > Total allowed | Validation missing | Edit form validates on submit |



**Quick Tip:** Use browser DevTools console to test functions in real-time!

`F12` → Console → Type: `showAddMedicineForm()` → Enter

