<?php


require_once 'db_config.php';


function getMedicineInventory($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT 
                medicine_id,
                name,
                dosage,
                stock_quantity,
                status
            FROM medicine
            ORDER BY name ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
    } catch (Exception $e) {
        error_log("Get medicines error: " . $e->getMessage());
        return [];
    }
}


function getMedicineStatusBadge($stock_quantity) {
    if ($stock_quantity == 0) {
        return '<span class="inline-flex items-center gap-2 px-3 py-1 bg-slate-100 text-slate-700 text-xs font-bold rounded-full">
                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                    Out of Stock
                </span>';
    } else {
        return '<span class="inline-flex items-center gap-2 px-3 py-1 bg-emerald-50 text-emerald-700 text-xs font-bold rounded-full">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    Available
                </span>';
    }
}

$pdo = getDatabaseConnection();
$medicines = getMedicineInventory($pdo);
?>

<!
<div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
    
    <!
    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
        <h3 class="text-lg font-bold text-slate-900">Medicine Inventory</h3>
        <button onclick="openAddMedicineModal()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-semibold transition">
            <i class="fas fa-plus mr-2"></i>Add Medicine
        </button>
    </div>
    
    <!
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="px-6 py-3 text-left text-xs font-bold text-slate-700 uppercase">Medicine Name</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-slate-700 uppercase">Dosage</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-slate-700 uppercase">Total Stock</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-slate-700 uppercase">Available</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-slate-700 uppercase">Dispensed</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-slate-700 uppercase">Status</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-slate-700 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($medicines)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                            <i class="fas fa-inbox text-4xl text-slate-300 mb-3 block"></i>
                            <p class="font-medium">No medicines in inventory</p>
                            <p class="text-sm text-slate-400">Click "Add Medicine" to get started</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($medicines as $medicine): ?>
                    <tr class="border-b border-slate-200 hover:bg-slate-50 transition">
                        <!
                        <td class="px-6 py-4">
                            <span class="font-medium text-slate-900"><?php echo htmlspecialchars($medicine['name']); ?></span>
                        </td>
                        
                        <!
                        <td class="px-6 py-4 text-slate-600">
                            <?php echo htmlspecialchars($medicine['dosage'] ?? 'N/A'); ?>
                        </td>
                        
                        <!
                        <td class="px-6 py-4 text-center">
                            <span class="font-bold text-slate-900"><?php echo intval($medicine['stock_quantity']); ?></span>
                        </td>
                        
                        <!
                        <td class="px-6 py-4 text-center">
                            <span class="font-bold text-emerald-600"><?php echo intval($medicine['stock_quantity']); ?></span>
                        </td>
                        
                        <!
                        <td class="px-6 py-4 text-center text-slate-600">
                            0
                        </td>
                        
                        <!
                        <td class="px-6 py-4 text-center">
                            <?php echo getMedicineStatusBadge($medicine['stock_quantity']); ?>
                        </td>
                        
                        <!
                        <td class="px-6 py-4 text-center">
                            <button 
                                onclick="editMedicine(<?php echo $medicine['medicine_id']; ?>, '<?php echo htmlspecialchars($medicine['name']); ?>', '<?php echo htmlspecialchars($medicine['dosage']); ?>', <?php echo $medicine['stock_quantity']; ?>)"
                                class="px-3 py-1 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded text-sm font-semibold transition"
                            >
                                <i class="fas fa-edit mr-1"></i>Edit
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!
<script>

function openAddMedicineModal() {
    alert('Open Add Medicine Modal');
    
}


function editMedicine(medicineId, name, dosage, quantity) {
    alert('Edit Medicine ID: ' + medicineId);
    
}
</script>

