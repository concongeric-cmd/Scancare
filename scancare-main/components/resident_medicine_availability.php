<?php


require_once 'medicine_inventory_system.php';


$available_medicines = getAllMedicines(true);


$total_medicines = count($available_medicines);
$out_of_stock_count = 0;
$available_count = 0;

foreach ($available_medicines as $med) {
    if ($med['stock_quantity'] > 0) {
        $available_count++;
    } else {
        $out_of_stock_count++;
    }
}

?>

<!
     MEDICINE AVAILABILITY SECTION - RESIDENT DASHBOARD
     ===================================================================== 
<section class="mt-12">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">
            <i class="fas fa-pills mr-3 text-emerald-600"></i>Available Medicines at RHU
        </h2>
        <p class="text-slate-600 dark:text-slate-400">Check medicine availability before your visit</p>
    </div>

    <!
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <!
        <div class="bg-white dark:bg-slate-800 rounded-lg p-4 border border-slate-200 dark:border-slate-700">
            <p class="text-slate-600 dark:text-slate-400 text-sm font-medium">Total Medicines</p>
            <p class="text-3xl font-bold text-emerald-600 mt-1"><?php echo $total_medicines; ?></p>
        </div>
        
        <!
        <div class="bg-white dark:bg-slate-800 rounded-lg p-4 border border-slate-200 dark:border-slate-700">
            <p class="text-slate-600 dark:text-slate-400 text-sm font-medium">In Stock</p>
            <p class="text-3xl font-bold text-green-600 mt-1"><?php echo $available_count; ?></p>
        </div>
        
        <!
        <div class="bg-white dark:bg-slate-800 rounded-lg p-4 border border-slate-200 dark:border-slate-700">
            <p class="text-slate-600 dark:text-slate-400 text-sm font-medium">Out of Stock</p>
            <p class="text-3xl font-bold text-red-600 mt-1"><?php echo $out_of_stock_count; ?></p>
        </div>
    </div>

    <!
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        
        <?php if (empty($available_medicines)): ?>
            
            <!
            <div class="col-span-full bg-slate-50 dark:bg-slate-800 rounded-lg p-12 text-center border border-dashed border-slate-300 dark:border-slate-600">
                <i class="fas fa-inbox text-4xl text-slate-300 dark:text-slate-600 mb-4"></i>
                <p class="text-slate-600 dark:text-slate-400 font-medium">No medicines available at this time</p>
            </div>

        <?php else: ?>
            
            <?php foreach ($available_medicines as $med): ?>
                
                <?php 
                    $status = getMedicineStatus($med['stock_quantity']);
                    $is_available = $status === 'available';
                    $badge_color = $is_available ? 'green' : 'red';
                    $badge_text = $is_available ? 'Available' : 'Out of Stock';
                    $badge_icon = $is_available ? 'check-circle' : 'times-circle';
                ?>
                
                <!
                <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md transition">
                    
                    <!
                    <div class="flex justify-between items-start mb-3">
                        <h3 class="font-bold text-slate-900 dark:text-white text-lg flex-1">
                            <?php echo htmlspecialchars($med['name']); ?>
                        </h3>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ml-2 flex-shrink-0
                            <?php if ($is_available): ?>
                                bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400
                            <?php else: ?>
                                bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400
                            <?php endif; ?>
                        ">
                            <i class="fas fa-<?php echo $badge_icon; ?> mr-1"></i><?php echo $badge_text; ?>
                        </span>
                    </div>

                    <!
                    <?php if (!empty($med['description'])): ?>
                        <p class="text-slate-700 dark:text-slate-300 text-sm mb-4">
                            <?php echo htmlspecialchars($med['description']); ?>
                        </p>
                    <?php endif; ?>

                    <!
                    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-3 mb-4">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-600 dark:text-slate-400 text-sm">Stock Level</span>
                            <span class="font-bold text-slate-900 dark:text-white">
                                <?php echo $med['stock_quantity']; ?> <span class="text-slate-600 dark:text-slate-400 font-normal">units</span>
                            </span>
                        </div>
                    </div>

                    <!
                    <?php if (!empty($med['updated_at'])): ?>
                        <p class="text-xs text-slate-600 dark:text-slate-400 border-t border-slate-200 dark:border-slate-700 pt-3">
                            <i class="fas fa-sync mr-1"></i>
                            Last Updated: <?php echo date('M d, Y h:i A', strtotime($med['updated_at'])); ?>
                        </p>
                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

    <!
    <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 border border-blue-300 dark:border-blue-700 rounded-lg p-4">
        <p class="text-blue-700 dark:text-blue-300 text-sm">
            <i class="fas fa-info-circle mr-2"></i>
            <strong>Note:</strong> Medicine availability is updated regularly by the Rural Health Unit staff. 
            Please ask the health worker during your visit if you need a specific medicine.
        </p>
    </div>

</section>

<!
<style>
    @media (max-width: 768px) {
        .grid {
            grid-template-columns: 1fr;
        }
    }

    @media (prefers-color-scheme: dark) {
        .dark\:bg-slate-800 {
            background-color: #1e293b;
        }
    }
</style>

