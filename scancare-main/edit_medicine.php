<?php

session_start();
require_once 'db_config.php';


if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'bhw') {
    header('Location: login.php');
    exit;
}

$medicine_id = intval($_GET['id'] ?? 0);
$medicine = null;
$error = '';
$success = '';


if ($medicine_id > 0) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("SELECT * FROM medicine WHERE medicine_id = ?");
        $stmt->execute([$medicine_id]);
        $medicine = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$medicine) {
            $error = 'Medicine not found.';
        }
    } catch (Exception $e) {
        $error = 'Database error: ' . htmlspecialchars($e->getMessage());
    }
} else {
    $error = 'Invalid medicine ID.';
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $medicine_id > 0) {
    $medicine_name = trim($_POST['medicine_name'] ?? '');
    $quantity_stock = intval($_POST['quantity_stock'] ?? 0);
    $expiry_date = trim($_POST['expiry_date'] ?? '');
    $condition = trim($_POST['condition'] ?? '');
    
    
    $errors = [];
    if (empty($medicine_name)) $errors[] = 'Medicine name is required.';
    if ($quantity_stock < 0) $errors[] = 'Stock quantity cannot be negative.';
    if (!empty($expiry_date)) {
        $date_obj = DateTime::createFromFormat('Y-m-d', $expiry_date);
        if (!$date_obj || $date_obj->format('Y-m-d') !== $expiry_date) {
            $errors[] = 'Invalid expiry date format (use YYYY-MM-DD).';
        }
    }
    
    if (empty($errors)) {
        try {
            $pdo = getDatabaseConnection();
            $stmt = $pdo->prepare("
                UPDATE medicine 
                SET medicine_name = ?, quantity_stock = ?, expiry_date = ?, condition = ?, updated_at = NOW()
                WHERE medicine_id = ?
            ");
            $result = $stmt->execute([
                $medicine_name,
                $quantity_stock,
                $expiry_date ?: null,
                $condition,
                $medicine_id
            ]);
            
            if ($result) {
                $success = 'Medicine updated successfully!';
                
                $medicine['medicine_name'] = $medicine_name;
                $medicine['quantity_stock'] = $quantity_stock;
                $medicine['expiry_date'] = $expiry_date;
                $medicine['condition'] = $condition;
            } else {
                $error = 'Failed to update medicine.';
            }
        } catch (Exception $e) {
            $error = 'Database error: ' . htmlspecialchars($e->getMessage());
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Medicine — ScanCare</title>
    <script src="https:
    <link rel="stylesheet" href="https:
    <style>
        @import url('https:
        .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50">
    <div class="max-w-2xl mx-auto px-4 py-12">
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <div class="flex items-center gap-3 mb-6">
                <a href="bhw_dashboard.php" class="text-slate-600 hover:text-[#454B1B] transition-colors">
                    <i class="fa-solid fa-arrow-left text-xl"></i>
                </a>
                <h1 class="text-3xl font-heading font-bold text-slate-900">Edit Medicine</h1>
            </div>
            
            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 text-red-700">
                <i class="fa-solid fa-exclamation-circle mr-2"></i><?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 mb-6 text-emerald-700">
                <i class="fa-solid fa-check-circle mr-2"></i><?php echo $success; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($medicine): ?>
            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-semibold text-slate-900 mb-2">Medicine Name</label>
                    <input type="text" name="medicine_name" value="<?php echo htmlspecialchars($medicine['medicine_name']); ?>" 
                           class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B]" required>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-900 mb-2">Stock Quantity</label>
                    <input type="number" name="quantity_stock" value="<?php echo intval($medicine['quantity_stock']); ?>" min="0"
                           class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B]" required>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-900 mb-2">Expiry Date (YYYY-MM-DD)</label>
                    <input type="date" name="expiry_date" value="<?php echo htmlspecialchars($medicine['expiry_date'] ?? ''); ?>"
                           class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B]">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-900 mb-2">Condition / Notes</label>
                    <textarea name="condition" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B]" rows="4"><?php echo htmlspecialchars($medicine['condition'] ?? ''); ?></textarea>
                </div>
                
                <div class="flex gap-4">
                    <button type="submit" class="flex-1 bg-[#454B1B] hover:bg-[#333] text-white font-semibold py-3 rounded-lg transition-colors">
                        <i class="fa-solid fa-save mr-2"></i>Save Changes
                    </button>
                    <a href="bhw_dashboard.php" class="flex-1 bg-slate-200 hover:bg-slate-300 text-slate-900 font-semibold py-3 rounded-lg text-center transition-colors">
                        <i class="fa-solid fa-x mr-2"></i>Cancel
                    </a>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

