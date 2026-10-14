<?php


require_once 'db_config.php';


try {
    $pdo = getDatabaseConnection();
} catch (Exception $e) {
    die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
}




if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch') {
    header('Content-Type: application/json');
    
    try {
        
        $query = "
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
        ";
        
        $stmt = $pdo->query($query);
        $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        
        $data = [];
        foreach ($medicines as $medicine) {
            $data[] = [
                'medicine_id' => intval($medicine['medicine_id']),
                'name' => htmlspecialchars($medicine['name']),
                'dosage' => htmlspecialchars($medicine['dosage']),
                'stock_quantity' => intval($medicine['stock_quantity']),
                'available' => intval($medicine['available']),
                'dispensed' => intval($medicine['dispensed']),
                'status' => htmlspecialchars($medicine['status']),
                'action' => '<button class="btn btn-sm btn-primary" onclick="editMedicine(' . $medicine['medicine_id'] . ')">Edit</button>'
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $data,
            'count' => count($data)
        ]);
        exit;
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}




if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    header('Content-Type: application/json');
    
    try {
        $medicine_id = intval($_POST['medicine_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $dosage = trim($_POST['dosage'] ?? '');
        $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
        $available = intval($_POST['available'] ?? 0);
        $dispensed = intval($_POST['dispensed'] ?? 0);
        $status = trim($_POST['status'] ?? 'Available');
        
        if (!$medicine_id || !$name) {
            echo json_encode(['success' => false, 'error' => 'Medicine ID and name required']);
            exit;
        }
        
        $update_stmt = $pdo->prepare("
            UPDATE medicine 
            SET name = ?, dosage = ?, stock_quantity = ?, available = ?, dispensed = ?, status = ?
            WHERE medicine_id = ?
        ");
        
        $update_stmt->execute([
            $name,
            $dosage,
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
    <title>BHW Medicine Inventory</title>
    
    <!
    <script src="https:
    
    <!
    <link rel="stylesheet" href="https:
    
    <!
    <link rel="stylesheet" href="https:
    
    <!
    <link href="https:
    
    <style>
        .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }
        
        
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            color: #64748b;
            font-size: 0.875rem;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.25rem 0.5rem;
            margin: 0 2px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #10b981;
            color: white;
            border-color: #10b981;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }
        
        table.dataTable tbody tr:hover {
            background-color: #f1f5f9;
        }
    </style>
</head>
<body class="bg-slate-100">
    
    <!
    <nav class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <h1 class="text-2xl font-heading font-bold text-slate-900">
                <i class="fas fa-pills mr-2 text-emerald-600"></i>ScanCare
            </h1>
            <a href="dashboard_bhw.php" class="text-slate-600 hover:text-slate-900">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
    </nav>
    
    <!
    <div class="max-w-7xl mx-auto px-6 py-8">
        
        <!
        <div class="mb-8">
            <h2 class="text-3xl font-heading font-bold text-slate-900">Medicine Inventory Management</h2>
            <p class="text-slate-600 mt-2">View and manage all available medicines in the health unit</p>
        </div>
        
        <!
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            
            <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-600">Total Medicines</p>
                        <p class="text-3xl font-bold text-slate-900" id="totalMedicines">0</p>
                    </div>
                    <i class="fas fa-pills text-4xl text-emerald-100"></i>
                </div>
            </div>
            
            <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-600">Available Items</p>
                        <p class="text-3xl font-bold text-slate-900" id="totalAvailable">0</p>
                    </div>
                    <i class="fas fa-check-circle text-4xl text-blue-100"></i>
                </div>
            </div>
            
            <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-600">Dispensed</p>
                        <p class="text-3xl font-bold text-slate-900" id="totalDispensed">0</p>
                    </div>
                    <i class="fas fa-hand-holding-medical text-4xl text-orange-100"></i>
                </div>
            </div>
            
            <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-600">Out of Stock</p>
                        <p class="text-3xl font-bold text-slate-900" id="outOfStock">0</p>
                    </div>
                    <i class="fas fa-exclamation-circle text-4xl text-red-100"></i>
                </div>
            </div>
        </div>
        
        <!
        <div class="bg-white rounded-lg border border-slate-200 shadow-sm p-6">
            
            <h3 class="text-lg font-heading font-bold text-slate-900 mb-6">
                <i class="fas fa-list mr-2"></i>All Medicines
            </h3>
            
            <div class="overflow-x-auto">
                <table id="medicineTable" class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-6 py-3 text-left font-semibold text-slate-700">ID</th>
                            <th class="px-6 py-3 text-left font-semibold text-slate-700">Medicine Name</th>
                            <th class="px-6 py-3 text-left font-semibold text-slate-700">Dosage</th>
                            <th class="px-6 py-3 text-center font-semibold text-slate-700">Total Stock</th>
                            <th class="px-6 py-3 text-center font-semibold text-slate-700">Available</th>
                            <th class="px-6 py-3 text-center font-semibold text-slate-700">Dispensed</th>
                            <th class="px-6 py-3 text-left font-semibold text-slate-700">Status</th>
                            <th class="px-6 py-3 text-center font-semibold text-slate-700">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!
    <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="border-b border-slate-200 px-6 py-4 flex items-center justify-between">
                <h3 class="text-lg font-bold text-slate-900">Edit Medicine</h3>
                <button onclick="closeEditModal()" class="text-slate-500 hover:text-slate-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="editForm" class="p-6 space-y-4">
                <input type="hidden" id="editMedicineId" name="medicine_id">
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Medicine Name</label>
                    <input type="text" id="editName" name="name" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" required>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Dosage</label>
                    <input type="text" id="editDosage" name="dosage" placeholder="e.g., 500mg" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Total Stock</label>
                        <input type="number" id="editStock" name="stock_quantity" min="0" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Available</label>
                        <input type="number" id="editAvailable" name="available" min="0" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Dispensed</label>
                        <input type="number" id="editDispensed" name="dispensed" min="0" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" required>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Status</label>
                    <select id="editStatus" name="status" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" required>
                        <option value="Available">Available</option>
                        <option value="Out of Stock">Out of Stock</option>
                        <option value="Low Stock">Low Stock</option>
                    </select>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg transition">
                        <i class="fas fa-save mr-2"></i>Save Changes
                    </button>
                    <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-2 bg-slate-300 hover:bg-slate-400 text-slate-700 font-semibold rounded-lg transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!
    <script src="https:
    
    <!
    <script src="https:
    
    <!
    <script>
        let medicineTable;
        
        $(document).ready(function() {
            
            medicineTable = $('#medicineTable').DataTable({
                ajax: {
                    url: window.location.href + '?action=fetch',
                    type: 'GET',
                    dataSrc: 'data',
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        $('#medicineTable tbody').html('<tr><td colspan="8" class="px-6 py-4 text-center text-red-600">Error loading data: ' + error + '</td></tr>');
                    }
                },
                columns: [
                    { data: 'medicine_id' },
                    { data: 'name' },
                    { data: 'dosage' },
                    { data: 'stock_quantity', className: 'text-center' },
                    { data: 'available', className: 'text-center' },
                    { data: 'dispensed', className: 'text-center' },
                    { 
                        data: 'status',
                        render: function(data) {
                            if (data === 'Available') {
                                return '<span class="inline-flex items-center gap-1 px-3 py-1 bg-emerald-50 text-emerald-700 text-xs font-bold rounded-full"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>' + data + '</span>';
                            } else if (data === 'Out of Stock') {
                                return '<span class="inline-flex items-center gap-1 px-3 py-1 bg-red-50 text-red-700 text-xs font-bold rounded-full"><span class="w-2 h-2 rounded-full bg-red-500"></span>' + data + '</span>';
                            } else {
                                return '<span class="inline-flex items-center gap-1 px-3 py-1 bg-yellow-50 text-yellow-700 text-xs font-bold rounded-full"><span class="w-2 h-2 rounded-full bg-yellow-500"></span>' + data + '</span>';
                            }
                        }
                    },
                    { 
                        data: 'action',
                        className: 'text-center',
                        orderable: false
                    }
                ],
                pageLength: 10,
                searching: true,
                ordering: true,
                paging: true,
                info: true,
                language: {
                    emptyTable: 'No medicines available in table'
                }
            });
            
            
            loadStatistics();
            
            
            setInterval(loadStatistics, 5000);
        });
        
        function loadStatistics() {
            $.ajax({
                url: window.location.href + '?action=fetch',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        let totalMeds = response.data.length;
                        let totalAvail = response.data.reduce((sum, m) => sum + m.available, 0);
                        let totalDisp = response.data.reduce((sum, m) => sum + m.dispensed, 0);
                        let outStock = response.data.filter(m => m.status === 'Out of Stock').length;
                        
                        $('#totalMedicines').text(totalMeds);
                        $('#totalAvailable').text(totalAvail);
                        $('#totalDispensed').text(totalDisp);
                        $('#outOfStock').text(outStock);
                        
                        medicineTable.ajax.reload(null, false);
                    }
                }
            });
        }
        
        function editMedicine(medicineId) {
            
            let row = medicineTable.row(function(idx, data, node) {
                return data.medicine_id === medicineId;
            });
            
            if (row.any()) {
                let data = row.data();
                document.getElementById('editMedicineId').value = data.medicine_id;
                document.getElementById('editName').value = data.name;
                document.getElementById('editDosage').value = data.dosage;
                document.getElementById('editStock').value = data.stock_quantity;
                document.getElementById('editAvailable').value = data.available;
                document.getElementById('editDispensed').value = data.dispensed;
                document.getElementById('editStatus').value = data.status;
                
                document.getElementById('editModal').classList.remove('hidden');
            }
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('editForm').reset();
        }
        
        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update');
            
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('✓ Medicine updated successfully');
                        closeEditModal();
                        loadStatistics();
                    } else {
                        alert('✗ Error: ' + response.error);
                    }
                },
                error: function(xhr, status, error) {
                    alert('✗ Request error: ' + error);
                }
            });
        });
    </script>
</body>
</html>

