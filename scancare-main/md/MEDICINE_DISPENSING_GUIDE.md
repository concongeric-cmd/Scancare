




```sql














CREATE TABLE IF NOT EXISTS `medicine` (
  `medicine_id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL COMMENT 'Medicine name',
  `stock_quantity` INT NOT NULL DEFAULT 0 COMMENT 'Current stock count',
  `description` TEXT NULL COMMENT 'Medicine details, dosage, usage',
  `status` VARCHAR(20) GENERATED ALWAYS AS (
    CASE 
      WHEN `stock_quantity` > 0 THEN 'Available'
      ELSE 'Out of Stock'
    END
  ) STORED COMMENT 'Auto-generated status based on stock_quantity',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Auto-updated timestamp',
  INDEX idx_status (status),
  INDEX idx_stock (stock_quantity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```


```sql







INSERT INTO `medicine` (`name`, `stock_quantity`, `description`) VALUES
(
  'Paracetamol 500mg',
  0,
  'For relief of fever and mild to moderate pain. Dosage: 1 tablet every 4-6 hours as needed (max 8 tablets per day). Do not exceed 4000mg daily.'
),
(
  'Amoxicillin 500mg',
  0,
  'Antibiotic used to treat bacterial infections including respiratory, ear, and urinary tract infections. Dosage: 1 capsule 3 times daily for 7-10 days. Must complete full course.'
),
(
  'Amlodipine 5mg',
  0,
  'Medication used to treat high blood pressure and chest pain (angina). Dosage: 1 tablet once daily, usually in the morning. May take 2-4 weeks for full effect.'
),
(
  'Metformin 500mg',
  0,
  'Oral diabetes medicine that helps control blood sugar levels in type 2 diabetes. Dosage: 1 tablet twice daily with meals. May be increased gradually as tolerated.'
),
(
  'Cetirizine 10mg',
  0,
  'Antihistamine used to treat allergy symptoms including sneezing, watery eyes, and itchy throat. Dosage: 1 tablet once daily in the evening. Non-drowsy formula.'
);


SELECT medicine_id, name, stock_quantity, status FROM medicine ORDER BY medicine_id;
```



This is the **Health Worker interface** for managing medicine stock.

```php
<?php


session_start();
require_once 'db_config.php';




if (!isset($_SESSION['worker_id']) || $_SESSION['user_type'] !== 'health_worker') {
    header('Location: login.php');
    exit;
}

$worker_id = $_SESSION['worker_id'];
$success_message = '';
$error_message = '';




if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_stock') {
    try {
        $pdo = getDatabaseConnection();
        
        $medicine_id = (int)$_POST['medicine_id'] ?? 0;
        $new_quantity = (int)$_POST['new_quantity'] ?? 0;
        
        if ($medicine_id > 0 && $new_quantity >= 0) {
            $stmt = $pdo->prepare("
                UPDATE medicine 
                SET stock_quantity = ?
                WHERE medicine_id = ?
            ");
            $stmt->execute([$new_quantity, $medicine_id]);
            
            $success_message = "✓ Medicine stock updated successfully!";
            error_log("BHW $worker_id updated medicine $medicine_id to quantity $new_quantity");
        }
    } catch (Exception $e) {
        $error_message = "Error updating stock: " . $e->getMessage();
    }
}




$medicines = [];
try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->query("
        SELECT 
            medicine_id,
            name,
            stock_quantity,
            description,
            status,
            updated_at
        FROM medicine
        ORDER BY name ASC
    ");
    $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error_message = "Error fetching medicines: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicine Inventory — ScanCare</title>
    <script src="https:
    <link rel="stylesheet" href="https:
    <style>
        .status-available { color: #16a34a; background-color: #dcfce7; }
        .status-out-of-stock { color: #dc2626; background-color: #fee2e2; }
    </style>
</head>
<body class="bg-slate-50">
    <div class="max-w-6xl mx-auto p-6">
        <h1 class="text-3xl font-bold text-slate-900 mb-6">
            <i class="fas fa-pills mr-3" style="color: #454B1B;"></i>
            Medicine Inventory Management
        </h1>
        
        <?php if (!empty($success_message)): ?>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6 text-green-800">
            <?php echo $success_message; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 text-red-800">
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>
        
        <!
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-white border border-slate-200 rounded-lg p-4">
                <div class="text-2xl font-bold text-slate-900"><?php echo count($medicines); ?></div>
                <div class="text-sm text-slate-600">Total Medicines</div>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-4">
                <div class="text-2xl font-bold text-green-600">
                    <?php echo count(array_filter($medicines, fn($m) => $m['stock_quantity'] > 0)); ?>
                </div>
                <div class="text-sm text-slate-600">In Stock</div>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-4">
                <div class="text-2xl font-bold text-red-600">
                    <?php echo count(array_filter($medicines, fn($m) => $m['stock_quantity'] == 0)); ?>
                </div>
                <div class="text-sm text-slate-600">Out of Stock</div>
            </div>
        </div>
        
        <!
        <div class="bg-white border border-slate-200 rounded-lg overflow-hidden">
            <table class="w-full">
                <thead class="bg-slate-100 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-sm text-slate-900">Medicine Name</th>
                        <th class="px-4 py-3 text-left font-semibold text-sm text-slate-900">Description</th>
                        <th class="px-4 py-3 text-center font-semibold text-sm text-slate-900">Current Stock</th>
                        <th class="px-4 py-3 text-center font-semibold text-sm text-slate-900">Status</th>
                        <th class="px-4 py-3 text-center font-semibold text-sm text-slate-900">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($medicines as $medicine): ?>
                    <tr class="border-b border-slate-200 hover:bg-slate-50 transition">
                        <td class="px-4 py-3 font-medium text-slate-900">
                            <?php echo htmlspecialchars($medicine['name']); ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">
                            <?php echo htmlspecialchars(substr($medicine['description'], 0, 50) . '...'); ?>
                        </td>
                        <td class="px-4 py-3 text-center font-semibold text-slate-900">
                            <?php echo $medicine['stock_quantity']; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold 
                                <?php echo ($medicine['stock_quantity'] > 0) ? 'status-available' : 'status-out-of-stock'; ?>">
                                <?php echo $medicine['status']; ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button type="button" 
                                    onclick="openEditModal(<?php echo $medicine['medicine_id']; ?>, '<?php echo htmlspecialchars($medicine['name']); ?>', <?php echo $medicine['stock_quantity']; ?>)"
                                    class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700 transition">
                                Edit Stock
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="mt-6 text-center">
            <a href="dashboard_bhw.php" class="px-6 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition">
                Back to Dashboard
            </a>
        </div>
    </div>
    
    <!
    <div id="editModal" style="display: none;" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4">
            <h2 class="text-xl font-bold text-slate-900 mb-4">Update Stock</h2>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_stock">
                <input type="hidden" id="medicine_id" name="medicine_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        Medicine: <span id="medicine_name"></span>
                    </label>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-700 mb-2">New Quantity</label>
                    <input type="number" 
                           id="new_quantity" 
                           name="new_quantity" 
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600"
                           min="0" 
                           required>
                </div>
                
                <div class="flex gap-4">
                    <button type="submit" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                        Save
                    </button>
                    <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-2 bg-slate-300 text-slate-900 rounded-lg hover:bg-slate-400 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openEditModal(medicineId, medicineName, currentQuantity) {
            document.getElementById('medicine_id').value = medicineId;
            document.getElementById('medicine_name').textContent = medicineName;
            document.getElementById('new_quantity').value = currentQuantity;
            document.getElementById('editModal').style.display = 'flex';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
    </script>
</body>
</html>
```



This shows **resident dashboard** what medicines are currently available.

```php
<?php


session_start();
require_once 'db_config.php';




if (!isset($_SESSION['resident_id']) || $_SESSION['user_type'] !== 'resident') {
    header('Location: login.php');
    exit;
}




$medicines = [];
$available_count = 0;
$out_of_stock_count = 0;

try {
    $pdo = getDatabaseConnection();
    
    $stmt = $pdo->query("
        SELECT 
            medicine_id,
            name,
            stock_quantity,
            description,
            status,
            updated_at
        FROM medicine
        ORDER BY 
            CASE WHEN status = 'Available' THEN 0 ELSE 1 END,
            name ASC
    ");
    $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $available_count = count(array_filter($medicines, fn($m) => $m['stock_quantity'] > 0));
    $out_of_stock_count = count(array_filter($medicines, fn($m) => $m['stock_quantity'] == 0));
    
} catch (Exception $e) {
    error_log("Error fetching medicines: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Medicines — ScanCare</title>
    <script src="https:
    <link rel="stylesheet" href="https:
</head>
<body class="bg-slate-50">
    <div class="max-w-6xl mx-auto p-6">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">
            <i class="fas fa-hospital mr-3" style="color: #454B1B;"></i>
            Available Medicines at RHU II
        </h1>
        <p class="text-slate-600 mb-6">Check which medicines are currently in stock</p>
        
        <!
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-white border border-slate-200 rounded-lg p-4">
                <div class="text-2xl font-bold text-slate-900"><?php echo count($medicines); ?></div>
                <div class="text-sm text-slate-600">Total Medicines</div>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-4 border-l-4" style="border-left-color: #16a34a;">
                <div class="text-2xl font-bold text-green-600"><?php echo $available_count; ?></div>
                <div class="text-sm text-slate-600">In Stock</div>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-4 border-l-4" style="border-left-color: #dc2626;">
                <div class="text-2xl font-bold text-red-600"><?php echo $out_of_stock_count; ?></div>
                <div class="text-sm text-slate-600">Out of Stock</div>
            </div>
        </div>
        
        <!
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($medicines as $medicine): 
                $is_available = $medicine['stock_quantity'] > 0;
                $status_class = $is_available ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200';
                $badge_class = $is_available ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
            ?>
            <div class="border rounded-lg p-4 <?php echo $status_class; ?>">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1">
                        <h3 class="font-bold text-slate-900"><?php echo htmlspecialchars($medicine['name']); ?></h3>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold whitespace-nowrap ml-2 <?php echo $badge_class; ?>">
                        <?php echo $medicine['status']; ?>
                    </span>
                </div>
                
                <p class="text-sm text-slate-600 mb-3">
                    <?php echo htmlspecialchars(substr($medicine['description'], 0, 100)); ?>...
                </p>
                
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-500">Current Stock:</span>
                    <span class="font-semibold text-slate-900"><?php echo $medicine['stock_quantity']; ?> units</span>
                </div>
                
                <div class="flex items-center justify-between text-xs text-slate-500 mt-2">
                    <span>Last Updated:</span>
                    <span><?php echo date('M d, Y', strtotime($medicine['updated_at'])); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="mt-8 text-center">
            <a href="dashboard.php" class="px-6 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition">
                Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>
```



In `dashboard_bhw.php`, add this link in the tab navigation:

```html
<a href="bhw_medicine_inventory.php" class="tab-button">
    <i class="fas fa-pills mr-2"></i>Medicine Inventory
</a>
```


In `dashboard.php`, add this link:

```html
<a href="resident_medicine_view.php" class="tab-button">
    <i class="fas fa-hospital mr-2"></i>Available Medicines
</a>
```


Run this query to confirm medicine table is set up correctly:

```sql

SELECT 
    medicine_id,
    name,
    stock_quantity,
    status,
    description,
    updated_at
FROM medicine
ORDER BY medicine_id;
```

Expected output (all with status "Out of Stock" by default):
```
| medicine_id | name               | stock_quantity | status         | description                          | updated_at          |
|
| 1           | Paracetamol 500mg  | 0              | Out of Stock   | For relief of fever...             | 2026-06-09 10:30 |
| 2           | Amoxicillin 500mg  | 0              | Out of Stock   | Antibiotic used to treat...        | 2026-06-09 10:30 |
| 3           | Amlodipine 5mg     | 0              | Out of Stock   | Medication used to treat...        | 2026-06-09 10:30 |
| 4           | Metformin 500mg    | 0              | Out of Stock   | Oral diabetes medicine...          | 2026-06-09 10:30 |
| 5           | Cetirizine 10mg    | 0              | Out of Stock   | Antihistamine used to treat...     | 2026-06-09 10:30 |
```


1. BHW logs in → Goes to Dashboard
2. Clicks "Medicine Inventory"
3. Views all medicines and their current stock
4. Clicks "Edit Stock" button for any medicine
5. Updates quantity based on actual physical count
6. Status automatically updates:
   - **Green "Available"** if quantity > 0
   - **Red "Out of Stock"** if quantity = 0

1. Resident logs in → Goes to Dashboard
2. Clicks "Available Medicines"
3. Sees which medicines are in stock (green) vs. out of stock (red)
4. Can plan visit based on medicine availability
5. Updated timestamp shows when stock was last checked

- **status column is GENERATED** → You don't update it manually
- When stock_quantity changes → status auto-updates
- No manual queries needed for status updates
- All stock changes are timestamped automatically


```sql

SELECT name, stock_quantity, status FROM medicine;

```

```sql

UPDATE medicine SET stock_quantity = 50 WHERE name = 'Paracetamol 500mg';


SELECT name, stock_quantity, status FROM medicine WHERE name = 'Paracetamol 500mg';

```

- Paracetamol: GREEN "Available" (50 units)
- Amoxicillin: RED "Out of Stock" (0 units)
- Others: RED "Out of Stock" (0 units)

```sql

UPDATE medicine SET stock_quantity = 0 WHERE name = 'Paracetamol 500mg';


SELECT name, stock_quantity, status FROM medicine WHERE name = 'Paracetamol 500mg';

```


```sql
SELECT medicine_id, name, stock_quantity, status, updated_at 
FROM medicine 
ORDER BY name;
```

```sql
SELECT medicine_id, name, stock_quantity 
FROM medicine 
WHERE stock_quantity > 0 
ORDER BY name;
```

```sql
SELECT medicine_id, name 
FROM medicine 
WHERE stock_quantity = 0;
```

```sql

UPDATE medicine SET stock_quantity = 100 WHERE name IN ('Paracetamol 500mg', 'Amoxicillin 500mg');
```

```sql
SELECT 
    COUNT(*) as total_medicines,
    SUM(CASE WHEN stock_quantity > 0 THEN 1 ELSE 0 END) as in_stock,
    SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
    SUM(stock_quantity) as total_units
FROM medicine;
```


- [ ] Medicine table created with GENERATED status column
- [ ] 5 sample medicines seeded with 0 stock
- [ ] BHW can view medicine inventory page
- [ ] BHW can update stock quantities
- [ ] Status automatically changes from "Out of Stock" to "Available" when stock > 0
- [ ] Status automatically changes back to "Out of Stock" when stock = 0
- [ ] Residents can view "Available Medicines" page
- [ ] Residents see green badges for in-stock medicines
- [ ] Residents see red badges for out-of-stock medicines
- [ ] Updated timestamps are accurate



**You're all set!** Copy the SQL, run in phpMyAdmin, then copy the PHP files to your project.

*Last Updated: June 9, 2026*

