<?php


require_once 'medicine_inventory_system.php';


$medicines = getAllMedicines(true);  
$reorder_needed = getMedicinesNeedingReorder();
$reorder_ids = array_column($reorder_needed, 'medicine_id');


$update_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_stock') {
    $medicine_id = intval($_POST['medicine_id'] ?? 0);
    $new_quantity = intval($_POST['new_quantity'] ?? 0);
    $worker_id = $_SESSION['worker_id'] ?? null;
    
    $result = updateMedicineStock($medicine_id, $new_quantity, $worker_id);
    $update_message = $result['message'];
}

?>

<!
     BHW MEDICINE MANAGEMENT SECTION
     ===================================================================== 
<section class="mb-12">
    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">
        <i class="fas fa-pills mr-3 text-emerald-600"></i>Medicine Inventory Management
    </h2>

    <!
    <?php if ($update_message): ?>
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/20 border border-green-500 rounded-lg">
            <p class="text-green-700 dark:text-green-400 text-sm">
                <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($update_message); ?>
            </p>
        </div>
    <?php endif; ?>

    <!
    <?php if (!empty($reorder_needed)): ?>
        <div class="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-700 rounded-lg">
            <p class="text-yellow-800 dark:text-yellow-300 font-semibold mb-3">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?php echo count($reorder_needed); ?> Medicine(s) Need Reordering:
            </p>
            <ul class="text-yellow-700 dark:text-yellow-400 text-sm space-y-1">
                <?php foreach ($reorder_needed as $item): ?>
                    <li class="flex justify-between">
                        <span><?php echo htmlspecialchars($item['medicine_name']); ?></span>
                        <span class="font-semibold"><?php echo $item['stock_quantity']; ?> <?php echo $item['unit']; ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!
    <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
        
        <!
        <div class="bg-slate-50 dark:bg-slate-700/50 p-6 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                <i class="fas fa-list mr-2"></i>All Medicines (<?php echo count($medicines); ?>)
            </h3>
            <a href="medicine_add.php" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition">
                <i class="fas fa-plus mr-2"></i>Add Medicine
            </a>
        </div>

        <!
        <div class="overflow-x-auto">
            <?php if (empty($medicines)): ?>
                <div class="p-12 text-center">
                    <i class="fas fa-inbox text-4xl text-slate-300 dark:text-slate-600 mb-4"></i>
                    <p class="text-slate-600 dark:text-slate-400 font-medium">No medicines in inventory</p>
                </div>
            <?php else: ?>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/50">
                            <th class="text-left p-4 font-semibold text-slate-900 dark:text-white">Medicine Name</th>
                            <th class="text-left p-4 font-semibold text-slate-900 dark:text-white">Description</th>
                            <th class="text-left p-4 font-semibold text-slate-900 dark:text-white">Current Stock</th>
                            <th class="text-left p-4 font-semibold text-slate-900 dark:text-white">Status</th>
                            <th class="text-left p-4 font-semibold text-slate-900 dark:text-white">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medicines as $med): ?>
                            <tr class="border-b border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition">
                                
                                <!
                                <td class="p-4">
                                    <p class="font-semibold text-slate-900 dark:text-white">
                                        <?php echo htmlspecialchars($med['name']); ?>
                                    </p>
                                    <?php if (!empty($med['description'])): ?>
                                        <p class="text-slate-500 dark:text-slate-400 text-xs mt-1">
                                            <?php echo htmlspecialchars(substr($med['description'], 0, 50)); ?>...
                                        </p>
                                    <?php endif; ?>
                                </td>

                                <!
                                <td class="p-4">
                                    <p class="text-slate-700 dark:text-slate-300">
                                        Status: <span class="font-semibold"><?php echo htmlspecialchars($med['status'] ?? 'N/A'); ?></span>
                                    </p>
                                </td>

                                <!
                                <td class="p-4">
                                    <form method="POST" action="" class="flex items-center gap-2">
                                        <input type="hidden" name="action" value="update_stock">
                                        <input type="hidden" name="medicine_id" value="<?php echo $med['medicine_id']; ?>">
                                        <input 
                                            type="number" 
                                            name="new_quantity" 
                                            value="<?php echo $med['stock_quantity']; ?>"
                                            class="w-20 px-2 py-1 bg-slate-200 dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded text-slate-900 dark:text-white font-semibold"
                                            min="0"
                                            required
                                        >
                                        <button type="submit" class="px-2 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded text-xs font-medium transition">
                                            <i class="fas fa-save"></i>
                                        </button>
                                    </form>
                                </td>

                                <!
                                <!

                                <!
                                <!

                                <!
                                <td class="p-4">
                                    <?php 
                                        $is_out_of_stock = ($med['stock_quantity'] == 0);
                                        $status_text = $med['status'] ?? ($is_out_of_stock ? 'Out of Stock' : 'Available');
                                    ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                                        <?php if ($is_out_of_stock): ?>
                                            bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400
                                        <?php else: ?>
                                            bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400
                                        <?php endif; ?>
                                    ">
                                        <?php if ($is_out_of_stock): ?>
                                            <i class="fas fa-times-circle mr-1"></i>Out of Stock
                                        <?php else: ?>
                                            <i class="fas fa-check-circle mr-1"></i>Available
                                        <?php endif; ?>
                                    </span>
                                </td>

                                <!
                                <td class="p-4">
                                    <div class="flex gap-2">
                                        <a href="medicine_edit.php?id=<?php echo $med['medicine_id']; ?>" 
                                           class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-medium transition">
                                            <i class="fas fa-edit"></i>Edit
                                        </a>
                                        <a href="medicine_delete.php?id=<?php echo $med['medicine_id']; ?>" 
                                           class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-xs font-medium transition"
                                           onclick="return confirm('Delete this medicine?')">
                                            <i class="fas fa-trash"></i>Delete
                                        </a>
                                    </div>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </div>

</section>

<!
<style>
    input[type="number"]::-webkit-outer-spin-button,
    input[type="number"]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    input[type="number"] {
        -moz-appearance: textfield;
    }
</style>

