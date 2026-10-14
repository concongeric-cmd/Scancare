<?php


require_once 'db_config.php';

session_start();
$health_worker_id = $_SESSION['worker_id'] ?? null;
$resident_id = intval($_GET['resident_id'] ?? 0);

if (!$health_worker_id || !$resident_id) {
    echo 'Invalid access';
    exit;
}




function getAvailableMedicines($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT 
                medicine_id,
                name,
                dosage,
                stock_quantity
            FROM medicine
            WHERE stock_quantity > 0
            ORDER BY name ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
    } catch (Exception $e) {
        error_log("Get medicines error: " . $e->getMessage());
        return [];
    }
}




if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_consultation') {
        $diagnosis = trim($_POST['diagnosis'] ?? '');
        $treatment = trim($_POST['treatment'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        try {
            $pdo = getDatabaseConnection();
            
            
            $stmt = $pdo->prepare("
                INSERT INTO patient_record 
                (resident_id, health_worker_id, visit_date, diagnosis, treatment, notes)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $resident_id,
                $health_worker_id,
                date('Y-m-d'),
                htmlspecialchars($diagnosis),
                htmlspecialchars($treatment),
                htmlspecialchars($notes)
            ]);
            
            $record_id = $pdo->lastInsertId();
            
            error_log("CONSULTATION SAVED: Record ID $record_id for Resident $resident_id");
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Consultation saved successfully',
                'record_id' => $record_id
            ]);
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
    
    if ($action === 'dispense_medicine') {
        $medicines_json = $_POST['medicines'] ?? '[]';
        $medicines = json_decode($medicines_json, true);
        
        try {
            $pdo = getDatabaseConnection();
            
            foreach ($medicines as $med) {
                $medicine_id = intval($med['medicine_id'] ?? 0);
                $quantity = intval($med['quantity'] ?? 0);
                
                if ($medicine_id > 0 && $quantity > 0) {
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO medicine_dispensing
                        (resident_id, medicine_id, health_worker_id, dispense_date, quantity_given)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $resident_id,
                        $medicine_id,
                        $health_worker_id,
                        date('Y-m-d'),
                        $quantity
                    ]);
                    
                    
                    $update_stmt = $pdo->prepare("
                        UPDATE medicine 
                        SET stock_quantity = stock_quantity - ?
                        WHERE medicine_id = ?
                    ");
                    $update_stmt->execute([$quantity, $medicine_id]);
                }
            }
            
            error_log("MEDICINE DISPENSED: " . count($medicines) . " items for Resident $resident_id");
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Medicine dispensed successfully']);
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
}

$pdo = getDatabaseConnection();
$available_medicines = getAvailableMedicines($pdo);


try {
    $stmt = $pdo->prepare("SELECT full_name, contact_number FROM resident WHERE resident_id = ?");
    $stmt->execute([$resident_id]);
    $resident = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $resident = ['full_name' => 'Unknown', 'contact_number' => 'N/A'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Consultation</title>
    <script src="https:
    <link rel="stylesheet" href="https:
    <style>
        @import url('https:
        .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-100 p-6">
    <div class="max-w-7xl mx-auto">
        
        <!
        <div class="mb-6">
            <h1 class="text-3xl font-heading font-bold text-slate-900">Record Consultation</h1>
            <p class="text-slate-600">
                <i class="fas fa-user mr-2"></i><?php echo htmlspecialchars($resident['full_name']); ?>
                <span class="text-slate-400">•</span>
                <i class="fas fa-phone mr-2"></i><?php echo htmlspecialchars($resident['contact_number']); ?>
            </p>
        </div>
        
        <!
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    
                    <h2 class="text-xl font-heading font-bold text-slate-900 mb-6">
                        <i class="fas fa-stethoscope mr-2 text-emerald-600"></i>Consultation Notes & Diagnosis
                    </h2>
                    
                    <form id="consultationForm" class="space-y-5">
                        <input type="hidden" name="action" value="save_consultation">
                        
                        <!
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Patient Identification</label>
                            <input 
                                type="text" 
                                value="<?php echo htmlspecialchars($resident['full_name']); ?>"
                                readonly
                                class="w-full px-4 py-2 bg-slate-100 border border-slate-300 rounded-lg text-slate-600 cursor-not-allowed"
                            >
                            <p class="text-xs text-slate-500 mt-1">Resident ID: #<?php echo $resident_id; ?></p>
                        </div>
                        
                        <!
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Diagnosis <span class="text-red-500">*</span>
                            </label>
                            <textarea 
                                name="diagnosis"
                                placeholder="Enter diagnosis details..."
                                rows="4"
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                required
                            ></textarea>
                        </div>
                        
                        <!
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Treatment Plan <span class="text-red-500">*</span>
                            </label>
                            <textarea 
                                name="treatment"
                                placeholder="Describe the treatment plan..."
                                rows="4"
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                required
                            ></textarea>
                        </div>
                        
                        <!
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">General Clinical Notes</label>
                            <textarea 
                                name="notes"
                                placeholder="Any additional notes..."
                                rows="3"
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"
                            ></textarea>
                        </div>
                        
                        <!
                        <button 
                            type="submit"
                            class="w-full px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg transition flex items-center justify-center gap-2"
                        >
                            <i class="fas fa-save"></i>Save Consultation
                        </button>
                    </form>
                </div>
            </div>
            
            <!
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 sticky top-6">
                    
                    <h2 class="text-xl font-heading font-bold text-slate-900 mb-6">
                        <i class="fas fa-pills mr-2 text-blue-600"></i>Dispense Medicine
                    </h2>
                    
                    <form id="medicineForm" class="space-y-4">
                        <!
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Select Medicine
                            </label>
                            <select id="medicineSelect" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">
                                <?php foreach ($available_medicines as $med): ?>
                                    <option value="<?php echo $med['medicine_id']; ?>">
                                        <?php echo htmlspecialchars($med['name']); ?> (<?php echo htmlspecialchars($med['dosage'] ?? 'N/A'); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Quantity
                            </label>
                            <input 
                                type="number" 
                                id="quantityInput"
                                min="1" 
                                value="1"
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                        </div>
                        
                        <!
                        <button 
                            type="button"
                            onclick="addMedicineToList()"
                            class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition flex items-center justify-center gap-2"
                        >
                            <i class="fas fa-plus"></i>Add to Dispensing
                        </button>
                    </form>
                    
                    <!
                    <div class="mt-6 pt-6 border-t border-slate-200">
                        <h3 class="text-sm font-semibold text-slate-700 mb-3">Medicines to Dispense</h3>
                        <div id="dispensingList" class="space-y-2">
                            <p class="text-sm text-slate-500 italic">No medicines added yet</p>
                        </div>
                    </div>
                    
                    <!
                    <button 
                        type="button"
                        onclick="dispenseMedicines()"
                        id="dispenseBtn"
                        disabled
                        class="w-full mt-6 px-4 py-2 bg-slate-300 text-slate-500 font-semibold rounded-lg cursor-not-allowed flex items-center justify-center gap-2"
                    >
                        <i class="fas fa-check-circle"></i>Confirm Dispensing
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!
    <script>
        const dispensingList = [];
        
        function addMedicineToList() {
            const medicineSelect = document.getElementById('medicineSelect');
            const quantityInput = document.getElementById('quantityInput');
            
            const medicineId = medicineSelect.value;
            const quantity = parseInt(quantityInput.value);
            
            if (!medicineId) {
                alert('Please select a medicine');
                return;
            }
            
            if (quantity < 1) {
                alert('Please enter a valid quantity');
                return;
            }
            
            
            const selectedOption = medicineSelect.options[medicineSelect.selectedIndex];
            const medicineName = selectedOption.text;
            
            
            dispensingList.push({
                medicine_id: medicineId,
                quantity: quantity,
                name: medicineName
            });
            
            
            updateDispensingList();
            
            
            medicineSelect.value = '';
            quantityInput.value = '1';
        }
        
        function updateDispensingList() {
            const listDiv = document.getElementById('dispensingList');
            const dispenseBtn = document.getElementById('dispenseBtn');
            
            if (dispensingList.length === 0) {
                listDiv.innerHTML = '<p class="text-sm text-slate-500 italic">No medicines added yet</p>';
                dispenseBtn.disabled = true;
                dispenseBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700', 'text-white', 'cursor-pointer');
                dispenseBtn.classList.add('bg-slate-300', 'text-slate-500', 'cursor-not-allowed');
            } else {
                listDiv.innerHTML = dispensingList.map((item, index) => `
                    <div class="flex items-center justify-between bg-slate-50 p-3 rounded border border-slate-200">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">${item.name}</p>
                            <p class="text-xs text-slate-600">Qty: ${item.quantity}</p>
                        </div>
                        <button 
                            type="button"
                            onclick="removeMedicine(${index})"
                            class="px-2 py-1 text-red-600 hover:bg-red-50 rounded transition"
                        >
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                `).join('');
                
                dispenseBtn.disabled = false;
                dispenseBtn.classList.remove('bg-slate-300', 'text-slate-500', 'cursor-not-allowed');
                dispenseBtn.classList.add('bg-blue-600', 'hover:bg-blue-700', 'text-white', 'cursor-pointer');
            }
        }
        
        function removeMedicine(index) {
            dispensingList.splice(index, 1);
            updateDispensingList();
        }
        
        
        document.getElementById('consultationForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('✓ Consultation saved successfully');
                    e.target.reset();
                } else {
                    alert('✗ Error: ' + data.message);
                }
            } catch (error) {
                alert('Error: ' + error);
            }
        });
        
        
        function dispenseMedicines() {
            if (dispensingList.length === 0) {
                alert('No medicines to dispense');
                return;
            }
            
            if (!confirm('Confirm dispensing these medicines?')) return;
            
            const formData = new FormData();
            formData.append('action', 'dispense_medicine');
            formData.append('medicines', JSON.stringify(dispensingList));
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✓ ' + data.message);
                    dispensingList.length = 0;
                    updateDispensingList();
                    location.reload();
                } else {
                    alert('✗ Error: ' + data.message);
                }
            })
            .catch(error => alert('Error: ' + error));
        }
    </script>
</body>
</html>

