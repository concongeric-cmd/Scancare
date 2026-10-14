<?php


session_start();
require_once 'db_config.php';




if (!isset($_SESSION['worker_id']) || $_SESSION['user_type'] !== 'health_worker') {
    header('Location: login.php');
    exit;
}

$worker_id = intval($_SESSION['worker_id']);
$worker_name = $_SESSION['user_name'] ?? 'Health Worker';




$approved_patients = [];
try {
    $pdo = getDatabaseConnection();
    
    $approved_query = "
        SELECT 
            a.appointment_id,
            a.resident_id,
            r.full_name as patient_name,
            r.date_of_birth,
            r.sex,
            r.contact_number,
            r.email,
            a.appointment_date,
            a.appointment_time,
            a.purpose
        FROM appointment a
        INNER JOIN resident r ON a.resident_id = r.resident_id
        WHERE a.status = 'approved'
        ORDER BY a.appointment_date ASC, a.appointment_time ASC
    ";
    
    $stmt = $pdo->prepare($approved_query);
    $stmt->execute();
    $approved_patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("APPROVED PATIENTS QUERY ERROR: " . $e->getMessage());
    $approved_patients = [];
}




$pending_appointments = [];
try {
    $pdo = getDatabaseConnection();
    
    $pending_query = "
        SELECT 
            a.appointment_id,
            a.resident_id,
            r.full_name as patient_name,
            r.contact_number,
            r.email,
            a.appointment_date,
            a.appointment_time,
            a.purpose,
            a.status,
            TIMESTAMPDIFF(HOUR, a.created_at, NOW()) as hours_waiting
        FROM appointment a
        LEFT JOIN resident r ON a.resident_id = r.resident_id
        WHERE a.status IN ('pending', 'approved')
        ORDER BY a.appointment_date ASC, a.appointment_time ASC
        LIMIT 20
    ";
    
    $stmt = $pdo->prepare($pending_query);
    $stmt->execute();
    $pending_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("PENDING APPOINTMENTS QUERY ERROR: " . $e->getMessage());
    $pending_appointments = [];
}

?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BHW Consultation Dashboard — ScanCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap');
        .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-body { font-family: 'Inter', sans-serif; }
        body { background: #F8FAF6; }
        .fade-out { animation: fadeOut 0.4s ease-out forwards; }
        @keyframes fadeOut {
            from { opacity: 1; transform: translateX(0); }
            to { opacity: 0; transform: translateX(-100px); }
        }
    </style>
</head>
<body class="font-body">

    <!
    <nav class="border-b border-slate-200 bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-heading font-bold text-slate-900">Consultation Dashboard</h1>
                <p class="text-sm text-slate-600 mt-1">Manage patient consultations and appointments</p>
            </div>
            <div class="text-right">
                <p class="font-semibold text-slate-900"><?php echo htmlspecialchars($worker_name); ?></p>
                <p class="text-xs text-slate-500">BHW • <?php echo date('M d, Y'); ?></p>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-6 py-8">

        <!
             APPOINTMENTS QUEUE WITH NO SHOW BUTTON
             ==================================================================== 
        <div class="mb-12">
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h2 class="text-xl font-heading font-bold text-slate-900 flex items-center gap-2">
                        <i class="fas fa-calendar-check text-[#454B1B]"></i>
                        Pending Appointments Queue
                    </h2>
                    <p class="text-sm text-slate-600 mt-1">Click "No Show" to mark appointment as no-show</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-3 text-left font-semibold text-slate-700">Patient Name</th>
                                <th class="px-6 py-3 text-left font-semibold text-slate-700">Date & Time</th>
                                <th class="px-6 py-3 text-left font-semibold text-slate-700">Contact</th>
                                <th class="px-6 py-3 text-left font-semibold text-slate-700">Purpose</th>
                                <th class="px-6 py-3 text-center font-semibold text-slate-700">Status</th>
                                <th class="px-6 py-3 text-center font-semibold text-slate-700">Action</th>
                            </tr>
                        </thead>
                        <tbody id="appointmentQueueBody" class="divide-y divide-slate-200">
                            <?php if (empty($pending_appointments)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                    <i class="fas fa-inbox text-4xl text-slate-300 mb-3 block"></i>
                                    No pending appointments
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($pending_appointments as $apt): 
                                    $status_color = $apt['status'] === 'approved' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800';
                                ?>
                                <tr id="apt-row-<?php echo $apt['appointment_id']; ?>" class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4 font-medium text-slate-900">
                                        <?php echo htmlspecialchars($apt['patient_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-slate-600 text-xs">
                                        <?php echo date('M d, Y', strtotime($apt['appointment_date'])) . ' • ' . 
                                                  date('h:i A', strtotime($apt['appointment_time'])); ?>
                                    </td>
                                    <td class="px-6 py-4 text-slate-600 text-xs">
                                        <?php echo htmlspecialchars($apt['contact_number'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="px-6 py-4 text-slate-600 text-xs">
                                        <span title="<?php echo htmlspecialchars($apt['purpose']); ?>">
                                            <?php echo htmlspecialchars(substr($apt['purpose'] ?? '', 0, 30)); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $status_color; ?>">
                                            <?php echo ucfirst($apt['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <button onclick="markNoShow(<?php echo $apt['appointment_id']; ?>, '<?php echo htmlspecialchars(addslashes($apt['patient_name'])); ?>')" 
                                                class="px-3 py-2 bg-red-500 hover:bg-red-600 text-white text-xs font-semibold rounded transition-colors">
                                            <i class="fas fa-times mr-1"></i> No Show
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!
             RECORD CONSULTATION FORM WITH PATIENT DROPDOWN
             ==================================================================== 
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!
            <div class="lg:col-span-2">
                <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
                    <h2 class="text-xl font-heading font-bold text-slate-900 mb-6 flex items-center gap-2">
                        <i class="fas fa-stethoscope text-[#454B1B]"></i>
                        Record Consultation
                    </h2>

                    <form id="consultationForm" class="space-y-6">
                        <!
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Select Approved Patient <span class="text-red-500">*</span>
                            </label>
                            <select id="patientDropdown" onchange="handlePatientSelection()" 
                                    class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent" 
                                    required>
                                <option value="">
                                <?php foreach ($approved_patients as $patient): ?>
                                <option value="<?php echo $patient['appointment_id']; ?>" 
                                        data-resident-id="<?php echo $patient['resident_id']; ?>"
                                        data-patient-name="<?php echo htmlspecialchars($patient['patient_name']); ?>"
                                        data-dob="<?php echo $patient['date_of_birth']; ?>"
                                        data-sex="<?php echo $patient['sex']; ?>"
                                        data-contact="<?php echo htmlspecialchars($patient['contact_number']); ?>"
                                        data-email="<?php echo htmlspecialchars($patient['email']); ?>"
                                        data-appointment-date="<?php echo $patient['appointment_date']; ?>"
                                        data-appointment-time="<?php echo $patient['appointment_time']; ?>"
                                        data-purpose="<?php echo htmlspecialchars($patient['purpose']); ?>">
                                    <?php echo htmlspecialchars($patient['patient_name']) . ' (' . date('M d, Y h:i A', strtotime($patient['appointment_date'] . ' ' . $patient['appointment_time'])) . ')'; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!
                        <div id="patientDetailsPanel" class="bg-slate-50 border border-slate-200 rounded-lg p-4" style="display: none;">
                            <h3 class="font-semibold text-slate-900 mb-3">Patient Information</h3>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-slate-600 font-medium">Full Name</p>
                                    <p id="patientName" class="text-slate-900 font-semibold"></p>
                                </div>
                                <div>
                                    <p class="text-slate-600 font-medium">Date of Birth</p>
                                    <p id="patientDOB" class="text-slate-900 font-semibold"></p>
                                </div>
                                <div>
                                    <p class="text-slate-600 font-medium">Sex</p>
                                    <p id="patientSex" class="text-slate-900 font-semibold"></p>
                                </div>
                                <div>
                                    <p class="text-slate-600 font-medium">Contact</p>
                                    <p id="patientContact" class="text-slate-900 font-semibold"></p>
                                </div>
                            </div>
                        </div>

                        <!
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Diagnosis <span class="text-red-500">*</span>
                            </label>
                            <textarea id="diagnosisField" 
                                      placeholder="Enter clinical findings and diagnosis..." 
                                      rows="4"
                                      class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent resize-none disabled:bg-slate-100 disabled:cursor-not-allowed"
                                      disabled></textarea>
                            <p class="text-xs text-slate-500 mt-1">Include symptoms, examination findings, and diagnostic conclusion</p>
                        </div>

                        <!
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Treatment Plan <span class="text-red-500">*</span>
                            </label>
                            <textarea id="treatmentField" 
                                      placeholder="Prescribed treatment and medication..." 
                                      rows="4"
                                      class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent resize-none disabled:bg-slate-100 disabled:cursor-not-allowed"
                                      disabled></textarea>
                            <p class="text-xs text-slate-500 mt-1">Include medications, dosages, frequency, and duration</p>
                        </div>

                        <!
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Clinical Notes</label>
                            <textarea id="notesField" 
                                      placeholder="Additional observations or follow-up instructions..." 
                                      rows="3"
                                      class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent resize-none disabled:bg-slate-100 disabled:cursor-not-allowed"
                                      disabled></textarea>
                        </div>

                        <!
                        <div class="border-t border-slate-200 pt-6">
                            <button type="submit" id="consultationSubmitBtn"
                                    style="background-color: #454B1B;" 
                                    class="w-full hover:opacity-95 text-white px-6 py-3 rounded-lg font-semibold transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                    disabled>
                                <i class="fas fa-save mr-2"></i> Save Consultation Record
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!
            <div class="lg:col-span-1">
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-6">
                    <h3 class="text-lg font-heading font-bold text-emerald-900 mb-6 flex items-center gap-2">
                        <i class="fas fa-pills text-emerald-600"></i> Dispense Free Medicine
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-emerald-900 mb-2">Select Medicine</label>
                            <select id="medicineSelect" 
                                    class="w-full px-4 py-2 border border-emerald-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                                    disabled>
                                <option value="">
                                <option value="paracetamol_500">Paracetamol 500mg</option>
                                <option value="amoxicillin_500">Amoxicillin 500mg</option>
                                <option value="ibuprofen_400">Ibuprofen 400mg</option>
                                <option value="cetirizine_10">Cetirizine 10mg</option>
                                <option value="metformin_500">Metformin 500mg</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-emerald-900 mb-2">Quantity</label>
                            <input type="number" id="medicineQuantity" min="1" max="100" value="1" 
                                   class="w-full px-4 py-2 border border-emerald-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                                   disabled>
                        </div>

                        <button onclick="addMedicineToDispensing()" id="addMedicineBtn"
                                style="background-color: #454B1B;" 
                                class="w-full hover:opacity-95 text-white px-4 py-2 rounded-lg font-semibold text-sm transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                disabled>
                            <i class="fas fa-plus mr-2"></i> Add to Dispensing
                        </button>

                        <div class="border-t border-emerald-200 pt-4 mt-4">
                            <p class="text-xs font-bold text-emerald-700 uppercase tracking-wider mb-3">Medicines to Dispense</p>
                            <div id="dispensingList" class="space-y-2">
                                <p class="text-xs text-emerald-600 italic">Select a patient to add medicines</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <!
         JAVASCRIPT: DYNAMIC PATIENT SELECTION & NO SHOW FUNCTIONALITY
         ======================================================================== 
    <script>
    let selectedPatientId = null;

    
    function handlePatientSelection() {
        const dropdown = document.getElementById('patientDropdown');
        const selectedOption = dropdown.options[dropdown.selectedIndex];
        const detailsPanel = document.getElementById('patientDetailsPanel');
        
        if (!selectedOption.value) {
            
            detailsPanel.style.display = 'none';
            disableConsultationFields();
            selectedPatientId = null;
            return;
        }

        
        selectedPatientId = selectedOption.value;
        const patientData = {
            name: selectedOption.dataset.patientName,
            dob: selectedOption.dataset.dob,
            sex: selectedOption.dataset.sex,
            contact: selectedOption.dataset.contact,
            appointmentDate: selectedOption.dataset.appointmentDate,
            appointmentTime: selectedOption.dataset.appointmentTime,
            purpose: selectedOption.dataset.purpose
        };

        
        document.getElementById('patientName').textContent = patientData.name;
        document.getElementById('patientDOB').textContent = patientData.dob ? new Date(patientData.dob).toLocaleDateString() : 'N/A';
        document.getElementById('patientSex').textContent = patientData.sex || 'N/A';
        document.getElementById('patientContact').textContent = patientData.contact;
        
        detailsPanel.style.display = 'block';
        
        
        enableConsultationFields();
    }

    
    function enableConsultationFields() {
        document.getElementById('diagnosisField').disabled = false;
        document.getElementById('treatmentField').disabled = false;
        document.getElementById('notesField').disabled = false;
        document.getElementById('consultationSubmitBtn').disabled = false;
        document.getElementById('medicineSelect').disabled = false;
        document.getElementById('medicineQuantity').disabled = false;
        document.getElementById('addMedicineBtn').disabled = false;
    }

    
    function disableConsultationFields() {
        document.getElementById('diagnosisField').disabled = true;
        document.getElementById('treatmentField').disabled = true;
        document.getElementById('notesField').disabled = true;
        document.getElementById('consultationSubmitBtn').disabled = true;
        document.getElementById('medicineSelect').disabled = true;
        document.getElementById('medicineQuantity').disabled = true;
        document.getElementById('addMedicineBtn').disabled = true;
        document.getElementById('dispensingList').innerHTML = '<p class="text-xs text-emerald-600 italic">Select a patient to add medicines</p>';
    }

    
    function markNoShow(appointmentId, patientName) {
        if (!confirm(`Mark ${patientName} as No Show?`)) {
            return;
        }

        const button = event.target.closest('button');
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Processing...';

        fetch('mark_no_show.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'appointment_id=' + appointmentId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                
                const row = document.getElementById('apt-row-' + appointmentId);
                if (row) {
                    row.classList.add('fade-out');
                    setTimeout(() => row.remove(), 400);
                }

                
                showNotification(`${patientName} marked as No Show by ${data.updated_by}`, 'success');
            } else {
                showNotification('Error: ' + (data.message || 'Unknown error'), 'error');
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-times mr-1"></i> No Show';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Network error occurred', 'error');
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-times mr-1"></i> No Show';
        });
    }

    
    function addMedicineToDispensing() {
        const medicineSelect = document.getElementById('medicineSelect');
        const quantity = document.getElementById('medicineQuantity').value;
        
        if (!medicineSelect.value || !quantity) {
            showNotification('Please select medicine and quantity', 'error');
            return;
        }

        const medicineText = medicineSelect.options[medicineSelect.selectedIndex].text;
        const dispensingList = document.getElementById('dispensingList');
        
        
        if (dispensingList.innerHTML.includes('Select a patient')) {
            dispensingList.innerHTML = '';
        }

        
        const item = document.createElement('div');
        item.className = 'flex items-center justify-between bg-white p-3 rounded-lg border border-emerald-200';
        item.innerHTML = `
            <span class="text-sm font-medium text-emerald-900">${medicineText} × ${quantity}</span>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800">
                <i class="fas fa-trash-alt"></i>
            </button>
        `;

        dispensingList.appendChild(item);
        medicineSelect.value = '';
        document.getElementById('medicineQuantity').value = '1';
        showNotification('Medicine added to dispensing list', 'success');
    }

    
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg text-white font-semibold ${
            type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500'
        } shadow-lg z-50 animate-pulse`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => notification.remove(), 4000);
    }
    </script>

</body>
</html>

