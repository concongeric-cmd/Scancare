<?php


require_once 'db_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}










if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch_medicines') {
    header('Content-Type: application/json');
    
    try {
        $pdo = getDatabaseConnection();
        if (!$pdo) {
            throw new Exception('Database connection failed');
        }
        
        
        $stmt = $pdo->query("
            SELECT 
                medicine_id,
                name,
                dosage,
                stock_quantity,
                available,
                dispensed,
                status
            FROM medicine
            ORDER BY name ASC
        ");
        
        if (!$stmt) {
            throw new Exception('Query failed');
        }
        
        $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        
        error_log("Fetched " . count($medicines) . " medicines from database");
        
        
        $data = [];
        foreach ($medicines as $medicine) {
            $dosage_value = isset($medicine['dosage']) && trim((string)$medicine['dosage']) !== '' && trim((string)$medicine['dosage']) !== '-' ? trim((string)$medicine['dosage']) : '';
            
            $data[] = [
                'medicine_id' => intval($medicine['medicine_id']),
                'name' => htmlspecialchars($medicine['name']),
                'dosage' => htmlspecialchars($dosage_value),  
                'stock_quantity' => intval($medicine['stock_quantity']),
                'available' => intval($medicine['available']),
                'dispensed' => intval($medicine['dispensed']),
                'status' => htmlspecialchars($medicine['status']),
                'id_for_actions' => intval($medicine['medicine_id'])
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $data,
            'count' => count($data),
            'debug' => 'Dosage values included: ' . implode(', ', array_map(function($m) { return $m['dosage']; }, $data))
        ]);
        exit;
        
    } catch (Exception $e) {
        error_log("Fetch medicines error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}




if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_medicine') {
    header('Content-Type: application/json');
    
    try {
        $pdo = getDatabaseConnection();
        if (!$pdo) {
            throw new Exception('Database connection failed');
        }
        
        
        $name = trim($_POST['name'] ?? '');
        $dosage = trim($_POST['dosage'] ?? '');  
        $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
        
        
        error_log("Adding medicine: name='$name', dosage='$dosage', stock='$stock_quantity'");
        
        
        if (empty($name)) {
            throw new Exception('Medicine name is required');
        }
        if ($stock_quantity < 0) {
            throw new Exception('Stock quantity cannot be negative');
        }
        
        
        
        
        
        $available = $stock_quantity;
        $dispensed = 0;
        $status = ($stock_quantity > 0) ? 'Available' : 'Out of Stock';
        
        
        if (empty($dosage)) {
            $dosage = '-';
        }
        
        
        $stmt = $pdo->prepare("
            INSERT INTO medicine (name, dosage, stock_quantity, available, dispensed, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            htmlspecialchars($name),
            htmlspecialchars($dosage),  
            $stock_quantity,
            $available,
            $dispensed,
            $status
        ]);
        
        
        $medicine_id = $pdo->lastInsertId();
        
        error_log("Medicine added successfully with ID: $medicine_id");
        
        echo json_encode([
            'success' => true,
            'message' => 'Medicine added successfully',
            'medicine_id' => $medicine_id,
            'data' => [
                'medicine_id' => $medicine_id,
                'name' => $name,
                'dosage' => $dosage,
                'stock_quantity' => $stock_quantity,
                'available' => $available,
                'dispensed' => $dispensed,
                'status' => $status
            ]
        ]);
        exit;
        
    } catch (Exception $e) {
        error_log("Add medicine error: " . $e->getMessage());
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}




if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_medicine') {
    header('Content-Type: application/json');
    
    try {
        $pdo = getDatabaseConnection();
        if (!$pdo) {
            throw new Exception('Database connection failed');
        }
        
        
        $medicine_id = intval($_POST['medicine_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $dosage = trim($_POST['dosage'] ?? '');
        $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
        $available = intval($_POST['available'] ?? 0);
        $dispensed = intval($_POST['dispensed'] ?? 0);
        
        
        if ($medicine_id <= 0) {
            throw new Exception('Invalid medicine ID');
        }
        if (empty($name)) {
            throw new Exception('Medicine name is required');
        }
        if ($stock_quantity < 0 || $available < 0 || $dispensed < 0) {
            throw new Exception('Quantities cannot be negative');
        }
        if ($available + $dispensed != $stock_quantity) {
            throw new Exception('Available + Dispensed must equal Stock Quantity');
        }
        
        
        $status = ($stock_quantity > 0) ? 'Available' : 'Out of Stock';
        
        
        $stmt = $pdo->prepare("
            UPDATE medicine
            SET name = ?, dosage = ?, stock_quantity = ?, available = ?, dispensed = ?, status = ?
            WHERE medicine_id = ?
        ");
        
        $stmt->execute([
            htmlspecialchars($name),
            htmlspecialchars($dosage),
            $stock_quantity,
            $available,
            $dispensed,
            $status,
            $medicine_id
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Medicine updated successfully'
        ]);
        exit;
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}




if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_medicine') {
    header('Content-Type: application/json');
    
    try {
        $pdo = getDatabaseConnection();
        if (!$pdo) {
            throw new Exception('Database connection failed');
        }
        
        $medicine_id = intval($_POST['medicine_id'] ?? 0);
        
        if ($medicine_id <= 0) {
            throw new Exception('Invalid medicine ID');
        }
        
        
        $stmt = $pdo->prepare("DELETE FROM medicine WHERE medicine_id = ?");
        $stmt->execute([$medicine_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Medicine deleted successfully'
        ]);
        exit;
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}




if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_medicine') {
    header('Content-Type: application/json');
    
    try {
        $pdo = getDatabaseConnection();
        if (!$pdo) {
            throw new Exception('Database connection failed');
        }
        
        $medicine_id = intval($_GET['medicine_id'] ?? 0);
        
        if ($medicine_id <= 0) {
            throw new Exception('Invalid medicine ID');
        }
        
        $stmt = $pdo->prepare("
            SELECT medicine_id, name, dosage, stock_quantity, available, dispensed, status
            FROM medicine
            WHERE medicine_id = ?
        ");
        $stmt->execute([$medicine_id]);
        
        $medicine = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$medicine) {
            throw new Exception('Medicine not found');
        }
        
        echo json_encode([
            'success' => true,
            'data' => $medicine
        ]);
        exit;
        
    } catch (Exception $e) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicine Inventory Management — ScanCare BHW Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap');
        .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-body { font-family: 'Inter', sans-serif; }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #454B1B;
            color: white;
            border-color: #454B1B;
        }
        
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            margin-left: 0.5rem;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        
        .status-available {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .status-out-of-stock {
            background-color: #f3f4f6;
            color: #374151;
        }
        
        .status-low-stock {
            background-color: #fed7aa;
            color: #92400e;
        }
    </style>
</head>
<body class="bg-slate-50 font-body text-slate-900">

    <!
    <nav class="bg-white border-b border-slate-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fas fa-pills text-2xl" style="color: #454B1B;"></i>
                <h1 class="text-2xl font-heading font-bold text-slate-900">Medicine Inventory</h1>
            </div>
            <a href="dashboard_bhw.php?module=inventory" class="text-slate-600 hover:text-slate-900">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
    </nav>

    <!
    <main class="max-w-7xl mx-auto px-6 py-8">

        <!
        <div class="bg-white border border-slate-200 rounded-xl p-6 mb-8 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-heading font-bold text-slate-900 mb-2">Available Medicines</h2>
                    <p class="text-slate-600">Track inventory and dispensing history</p>
                </div>
                <button onclick="openAddMedicineModal()" style="background-color: #454B1B;" class="hover:opacity-90 text-white px-6 py-3 rounded-lg font-semibold transition-all inline-flex items-center gap-2">
                    <i class="fas fa-plus"></i> Add Medicine
                </button>
            </div>
        </div>

        <!
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table id="medicineTable" class="w-full">
                    <thead>
                        <tr style="background-color: #f8fafc;">
                            <th class="px-6 py-4 text-left font-semibold text-slate-700">Medicine ID</th>
                            <th class="px-6 py-4 text-left font-semibold text-slate-700">Medicine Name</th>
                            <th class="px-6 py-4 text-left font-semibold text-slate-700">Dosage</th>
                            <th class="px-6 py-4 text-center font-semibold text-slate-700">Total Stock</th>
                            <th class="px-6 py-4 text-center font-semibold text-slate-700">Available</th>
                            <th class="px-6 py-4 text-center font-semibold text-slate-700">Dispensed</th>
                            <th class="px-6 py-4 text-left font-semibold text-slate-700">Status</th>
                            <th class="px-6 py-4 text-center font-semibold text-slate-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <!
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 text-sm text-slate-600">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Note:</strong> Inventory data is loaded from the database in real-time. Use the search box to filter medicines, and click "Edit" to update quantities.
            </div>
        </div>

    </main>

    <!
         ADD MEDICINE MODAL
         ======================================================================== 
    <div id="addMedicineModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50" style="display: none;">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-heading font-bold text-slate-900">Add New Medicine</h3>
                <button onclick="closeAddMedicineModal()" class="text-slate-500 hover:text-slate-700 text-2xl leading-none">&times;</button>
            </div>

            <form id="addMedicineForm" onsubmit="handleAddMedicine(event)" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Medicine Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" placeholder="e.g., Paracetamol, Amoxicillin" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Dosage/Strength</label>
                    <input type="text" name="dosage" placeholder="e.g., 500mg, 5mg, 10mg" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Initial Stock Quantity <span class="text-red-500">*</span></label>
                    <input type="number" name="stock_quantity" min="0" value="0" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>

                <div class="flex gap-3 justify-end pt-4 border-t border-slate-200">
                    <button type="button" onclick="closeAddMedicineModal()" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 font-semibold hover:bg-slate-50 transition-all">
                        Cancel
                    </button>
                    <button type="submit" style="background-color: #454B1B;" class="px-4 py-2 text-white rounded-lg font-semibold hover:opacity-90 transition-all inline-flex items-center gap-2">
                        <i class="fas fa-save"></i> Add Medicine
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!
         EDIT MEDICINE MODAL
         ======================================================================== 
    <div id="editMedicineModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50" style="display: none;">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-heading font-bold text-slate-900">Edit Medicine</h3>
                <button onclick="closeEditMedicineModal()" class="text-slate-500 hover:text-slate-700 text-2xl leading-none">&times;</button>
            </div>

            <form id="editMedicineForm" onsubmit="handleEditMedicine(event)" class="p-6 space-y-4">
                <input type="hidden" id="editMedicineId" name="medicine_id">

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Medicine Name <span class="text-red-500">*</span></label>
                    <input type="text" id="editName" name="name" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Dosage/Strength</label>
                    <input type="text" id="editDosage" name="dosage" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Stock <span class="text-red-500">*</span></label>
                        <input type="number" id="editStock" name="stock_quantity" min="0" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Available <span class="text-red-500">*</span></label>
                        <input type="number" id="editAvailable" name="available" min="0" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Dispensed <span class="text-red-500">*</span></label>
                        <input type="number" id="editDispensed" name="dispensed" min="0" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                </div>

                <p class="text-xs text-slate-500 bg-blue-50 p-3 rounded">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Constraint:</strong> Available + Dispensed must equal Total Stock
                </p>

                <div class="flex gap-3 justify-end pt-4 border-t border-slate-200">
                    <button type="button" onclick="closeEditMedicineModal()" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 font-semibold hover:bg-slate-50 transition-all">
                        Cancel
                    </button>
                    <button type="submit" style="background-color: #454B1B;" class="px-4 py-2 text-white rounded-lg font-semibold hover:opacity-90 transition-all inline-flex items-center gap-2">
                        <i class="fas fa-save"></i> Update Medicine
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!
         JAVASCRIPT - DataTables Initialization & AJAX Handlers
         ======================================================================== 
    <script>
        
        
        
        let medicineTable;

        $(document).ready(function() {
            console.log('Initializing DataTable...');
            
            
            medicineTable = $('#medicineTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: '<?php echo $_SERVER['PHP_SELF']; ?>?action=fetch_medicines',
                    dataSrc: function(json) {
                        console.log('DataTables AJAX Response:', json);
                        if (!json.success) {
                            console.error('Failed to fetch medicines:', json.error);
                            return [];
                        }
                        console.log('Data to render:', json.data);
                        return json.data;
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                    }
                },
                
                columns: [
                    { data: 'medicine_id', title: 'ID', visible: false },           
                    { data: 'name', title: 'Medicine Name' },                       
                    { data: 'dosage', title: 'Dosage' },                            
                    { data: 'stock_quantity', title: 'Total Stock', className: 'text-center' },
                    { data: 'available', title: 'Available', className: 'text-center' },
                    { data: 'dispensed', title: 'Dispensed', className: 'text-center' },
                    { data: 'status', title: 'Status', render: renderStatusBadge },
                    { data: 'id_for_actions', title: 'Actions', orderable: false, searchable: false, render: renderActions }
                ],
                columnDefs: [
                    { 
                        targets: 2,  
                        render: function(data) {
                            console.log('Rendering dosage:', data);
                            return '<span class="font-medium text-slate-600">' + (data || '-') + '</span>';
                        }
                    },
                    { 
                        targets: 3,  
                        render: function(data) {
                            return '<span class="font-bold text-slate-900">' + data + '</span>';
                        }
                    },
                    { 
                        targets: 4,  
                        render: function(data) {
                            return '<span class="font-bold text-emerald-600">' + data + '</span>';
                        }
                    },
                    { 
                        targets: 5,  
                        render: function(data) {
                            return '<span class="text-slate-600">' + data + '</span>';
                        }
                    }
                ],
                pageLength: 10,
                lengthChange: true,
                searching: true,
                ordering: true,
                paging: true,
                info: true,
                autoWidth: false,
                responsive: true,
                language: {
                    emptyTable: 'No medicines in inventory',
                    zeroRecords: 'No matching medicines found',
                    processing: 'Loading medicines...'
                },
                initComplete: function() {
                    console.log('DataTable initialized successfully');
                }
            });
        });

        
        
        

        
        function renderStatusBadge(data, type, row) {
            const status = String(data).toLowerCase();
            let badgeClass = 'status-badge status-out-of-stock';
            let badgeText = 'Out of Stock';
            let dotColor = 'bg-slate-400';

            if (status === 'available') {
                badgeClass = 'status-badge status-available';
                badgeText = 'Available';
                dotColor = 'bg-emerald-500';
            } else if (status === 'low stock') {
                badgeClass = 'status-badge status-low-stock';
                badgeText = 'Low Stock';
                dotColor = 'bg-amber-500';
            }

            return `<span class="${badgeClass}"><span class="w-1.5 h-1.5 rounded-full ${dotColor}"></span> ${badgeText}</span>`;
        }

        
        function renderActions(data, type, row) {
            const medicineId = data;
            return `
                <div class="flex items-center justify-center gap-2">
                    <button onclick="openEditMedicineModal(${medicineId})" class="px-3 py-1 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded text-sm font-semibold transition">
                        <i class="fas fa-edit mr-1"></i>Edit
                    </button>
                    <button onclick="deleteMedicine(${medicineId})" class="px-3 py-1 bg-red-50 text-red-600 hover:bg-red-100 rounded text-sm font-semibold transition">
                        <i class="fas fa-trash mr-1"></i>Delete
                    </button>
                </div>
            `;
        }

        
        
        

        function openAddMedicineModal() {
            document.getElementById('addMedicineModal').style.display = 'flex';
            document.getElementById('addMedicineForm').reset();
        }

        function closeAddMedicineModal() {
            document.getElementById('addMedicineModal').style.display = 'none';
        }

        async function handleAddMedicine(event) {
            event.preventDefault();

            const form = document.getElementById('addMedicineForm');
            const formData = new FormData(form);
            formData.append('action', 'add_medicine');
            
            
            console.log('Form Data being sent:');
            for (let [key, value] of formData.entries()) {
                console.log(`  ${key}: ${value}`);
            }

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                console.log('Server Response:', result);

                if (result.success) {
                    alert('✅ ' + result.message);
                    closeAddMedicineModal();
                    form.reset();
                    
                    setTimeout(function() {
                        medicineTable.ajax.reload();
                    }, 500);
                } else {
                    alert('❌ Error: ' + result.error);
                    console.error('Server error:', result.error);
                }
            } catch (error) {
                console.error('Fetch Error:', error);
                alert('❌ Failed to add medicine. Please try again.');
            }
        }

        
        
        

        async function openEditMedicineModal(medicineId) {
            try {
                const response = await fetch(window.location.href + '?action=get_medicine&medicine_id=' + medicineId);
                const result = await response.json();
                console.log('Get Medicine Response:', result);

                if (result.success) {
                    const medicine = result.data;
                    console.log('Loaded medicine dosage:', medicine.dosage);
                    
                    document.getElementById('editMedicineId').value = medicine.medicine_id;
                    document.getElementById('editName').value = medicine.name;
                    document.getElementById('editDosage').value = medicine.dosage;
                    document.getElementById('editStock').value = medicine.stock_quantity;
                    document.getElementById('editAvailable').value = medicine.available;
                    document.getElementById('editDispensed').value = medicine.dispensed;

                    console.log('Edit form populated with dosage:', document.getElementById('editDosage').value);
                    
                    document.getElementById('editMedicineModal').style.display = 'flex';
                } else {
                    alert('❌ Error: ' + result.error);
                    console.error('Error loading medicine:', result.error);
                }
            } catch (error) {
                console.error('Fetch Error:', error);
                alert('❌ Failed to load medicine data.');
            }
        }

        function closeEditMedicineModal() {
            document.getElementById('editMedicineModal').style.display = 'none';
        }

        async function handleEditMedicine(event) {
            event.preventDefault();

            const formData = new FormData(document.getElementById('editMedicineForm'));
            formData.append('action', 'update_medicine');
            
            
            console.log('Edit Form Data being sent:');
            for (let [key, value] of formData.entries()) {
                console.log(`  ${key}: ${value}`);
            }

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                console.log('Server Response:', result);

                if (result.success) {
                    alert('✅ ' + result.message);
                    closeEditMedicineModal();
                    
                    setTimeout(function() {
                        medicineTable.ajax.reload();
                    }, 500);
                } else {
                    alert('❌ Error: ' + result.error);
                    console.error('Server error:', result.error);
                }
            } catch (error) {
                console.error('Fetch Error:', error);
                alert('❌ Failed to update medicine. Please try again.');
            }
        }

        
        
        

        async function deleteMedicine(medicineId) {
            if (!confirm('Are you sure you want to delete this medicine? This action cannot be undone.')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete_medicine');
            formData.append('medicine_id', medicineId);

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert('✅ ' + result.message);
                    medicineTable.ajax.reload();
                } else {
                    alert('❌ Error: ' + result.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ Failed to delete medicine. Please try again.');
            }
        }

        
        
        

        document.getElementById('addMedicineModal').addEventListener('click', function(e) {
            if (e.target === this) closeAddMedicineModal();
        });

        document.getElementById('editMedicineModal').addEventListener('click', function(e) {
            if (e.target === this) closeEditMedicineModal();
        });

        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAddMedicineModal();
                closeEditMedicineModal();
            }
        });
    </script>

</body>
</html>

