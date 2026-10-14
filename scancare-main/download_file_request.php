<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_config.php';

$requestId = (int) ($_GET['request_id'] ?? 0);
$residentId = isset($_SESSION['resident_id']) && ($_SESSION['user_type'] ?? '') === 'resident' ? (int) $_SESSION['resident_id'] : 0;
$isWorker = isset($_SESSION['worker_id']) && ($_SESSION['user_type'] ?? '') === 'health_worker';
if ($requestId <= 0 || (!$residentId && !$isWorker)) {
    http_response_code(403);
    exit('Access denied.');
}

$pdo = getDatabaseConnection();
if (!$pdo) {
    http_response_code(500);
    exit('Database connection unavailable.');
}
ensureFileRequestsTable($pdo);
$where = $residentId ? 'fr.request_id = ? AND fr.resident_id = ?' : 'fr.request_id = ?';
$params = $residentId ? [$requestId, $residentId] : [$requestId];
$stmt = $pdo->prepare("SELECT fr.*, r.full_name, r.patient_code, r.date_of_birth, r.sex, r.address, r.contact_number, r.email FROM file_requests fr JOIN resident r ON r.resident_id = fr.resident_id WHERE $where LIMIT 1");
$stmt->execute($params);
$request = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$request || $request['status'] !== 'accepted') {
    http_response_code(404);
    exit('File is not available.');
}

$reportCode = $request['patient_code'] ?: str_pad((string) $request['resident_id'], 6, '0', STR_PAD_LEFT);
$reportUrl = 'view_record.php?code=' . urlencode($reportCode) . '&qr_scan=1&print=1';
$forceDownload = ($_GET['download'] ?? '') === '1';
if (!$forceDownload) {
    // Preserve the desktop print-friendly flow unless a mobile download was requested.
    header('Location: ' . $reportUrl);
    exit;
}

$lines = [
    'SCANCARE HEALTH FILE',
    'Requested file: ' . $request['requested_file'],
    'Generated: ' . date('F j, Y g:i A'),
    '',
    'RESIDENT INFORMATION',
    'Name: ' . $request['full_name'],
    'Patient ID: ' . ($request['patient_code'] ?: $request['resident_id']),
    'Date of birth: ' . ($request['date_of_birth'] ?: 'Not recorded'),
    'Sex: ' . ($request['sex'] ?: 'Not recorded'),
    'Address: ' . ($request['address'] ?: 'Not recorded'),
    'Contact: ' . ($request['contact_number'] ?: ($request['email'] ?: 'Not recorded')),
    '',
    'MEDICAL RECORDS'
];

try {
    $recordCount = 0;
    $records = $pdo->prepare('SELECT visit_date, diagnosis, treatment, notes FROM patient_record WHERE resident_id = ? ORDER BY visit_date DESC LIMIT 50');
    $records->execute([(int) $request['resident_id']]);
    foreach ($records->fetchAll(PDO::FETCH_ASSOC) as $record) {
        $recordCount++;
        $lines[] = date('M j, Y', strtotime($record['visit_date'])) . ' | Diagnosis: ' . ($record['diagnosis'] ?: 'Not recorded');
        if (!empty($record['treatment'])) $lines[] = 'Treatment: ' . $record['treatment'];
        if (!empty($record['notes'])) $lines[] = 'Notes: ' . $record['notes'];
    }
} catch (Exception $e) {
    error_log('File request patient record export error: ' . $e->getMessage());
}

if (($recordCount ?? 0) === 0) $lines[] = 'No patient records are available.';

try {
    $historyStmt = $pdo->prepare('SELECT visit_date, diagnosis, treatment, notes FROM medical_history WHERE user_id = ? ORDER BY visit_date DESC LIMIT 1');
    $historyStmt->execute([(int) $request['resident_id']]);
    $history = $historyStmt->fetch(PDO::FETCH_ASSOC);
    if ($history) {
        $clinicalData = is_string($history['treatment'] ?? null) ? json_decode($history['treatment'], true) : [];
        $clinicalData = is_array($clinicalData) ? $clinicalData : [];
        $lines[] = '';
        $lines[] = 'LATEST CLINICAL EXAMINATION';
        $lines[] = 'Visit date: ' . ($history['visit_date'] ?: 'Not recorded');
        $lines[] = 'Height: ' . ($clinicalData['height'] ?? 'Not recorded') . ' cm';
        $lines[] = 'Weight: ' . ($clinicalData['weight'] ?? 'Not recorded') . ' kg';
        $lines[] = 'Blood pressure: ' . ($clinicalData['bp'] ?? 'Not recorded');
        $lines[] = 'Pulse rate: ' . ($clinicalData['pr'] ?? 'Not recorded');
        $lines[] = 'Temperature: ' . ($clinicalData['temp'] ?? 'Not recorded');
        $lines[] = 'Head: ' . ($clinicalData['head'] ?? 'Not recorded');
        $lines[] = 'Chest/Lungs: ' . ($clinicalData['chest_lungs'] ?? 'Not recorded');
        $lines[] = 'Heart: ' . ($clinicalData['heart'] ?? 'Not recorded');
        $lines[] = 'Abdomen: ' . ($clinicalData['abdomen'] ?? 'Not recorded');
        $lines[] = 'Extremities: ' . ($clinicalData['extremities'] ?? 'Not recorded');
        $lines[] = 'Remarks: ' . ($clinicalData['remarks'] ?? 'Not recorded');
        $lines[] = 'Recommendations: ' . ($clinicalData['recommendations'] ?? 'Not recorded');
        $lines[] = 'Treatment plan: ' . ($clinicalData['treatment_plan'] ?? 'Not recorded');
        $lines[] = 'Diagnosis: ' . ($history['diagnosis'] ?: 'Not recorded');
        $lines[] = 'Notes: ' . ($history['notes'] ?: 'Not recorded');
    }
} catch (Exception $e) {
    error_log('File request clinical export error: ' . $e->getMessage());
}

require_once __DIR__ . '/vendor/autoload.php';

function reportHtml($value) {
    $value = trim((string) $value);
    if ($value === '') return 'Not recorded';
    $value = wordwrap($value, 90, "\n", true);
    return nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
}

function reportImage($path) {
    $contents = @file_get_contents(__DIR__ . '/' . $path);
    if ($contents === false) return '';
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mime = $extension === 'png' ? 'image/png' : 'image/jpeg';
    return 'data:' . $mime . ';base64,' . base64_encode($contents);
}

$clinicalData = [];
$history = $history ?? [];
if (!empty($history['treatment'])) {
    $decodedClinicalData = json_decode((string) $history['treatment'], true);
    $clinicalData = is_array($decodedClinicalData) ? $decodedClinicalData : [];
}

$recordRows = '';
try {
    $recordStmt = $pdo->prepare('SELECT visit_date, diagnosis, treatment, notes FROM patient_record WHERE resident_id = ? ORDER BY visit_date DESC LIMIT 50');
    $recordStmt->execute([(int) $request['resident_id']]);
    foreach ($recordStmt->fetchAll(PDO::FETCH_ASSOC) as $record) {
        $recordRows .= '<tr><td>' . reportHtml(date('M j, Y', strtotime($record['visit_date']))) . '</td><td>' . reportHtml($record['diagnosis']) . '</td><td>' . reportHtml($record['treatment']) . '</td><td>' . reportHtml($record['notes']) . '</td></tr>';
    }
} catch (Exception $e) {
    error_log('File request patient record PDF error: ' . $e->getMessage());
}
if ($recordRows === '') $recordRows = '<tr><td colspan="4" class="muted">No patient records are available.</td></tr>';

$historyLabels = ['asthma' => 'Asthma', 'epilepsy' => 'Epilepsy', 'diabetes' => 'Diabetes', 'hypertension' => 'Hypertension', 'allergies' => 'Allergies', 'medications' => 'On Medications', 'hospitalization' => 'Hospitalization / Surgery'];
$selectedHistory = is_array($clinicalData['medical_history'] ?? null) ? $clinicalData['medical_history'] : [];
$historyHtml = '';
foreach ($historyLabels as $key => $label) {
    $historyHtml .= '<span class="history-pill ' . (in_array($key, $selectedHistory, true) ? 'selected' : '') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
}

$scancareLogo = reportImage('images/SClogo.png');
$rhuLogo = reportImage('images/RHU LOGO.png');
$patientId = $request['patient_code'] ?: $request['resident_id'];
$html = '<style>
@page { size: A4 portrait; margin: 10mm 12mm; } * { box-sizing: border-box; }
body { font-family: Arial, sans-serif; color: #111; font-size: 9px; line-height: 1.25; }
.letterhead { width: 100%; text-align: center; border-bottom: 1px solid #111; padding-bottom: 7px; margin-bottom: 9px; }
.letterhead table { width: 100%; border-collapse: collapse; } .letterhead td { border: 0; vertical-align: middle; }
.logo-left { width: 18%; text-align: right; } .logo-right { width: 18%; text-align: left; } .logo { width: 55px; height: 55px; } .logo.rhu { width: 65px; height: 65px; }
.heading { font-weight: bold; font-size: 10px; line-height: 1.25; }
.meta-grid { width: 100%; border-collapse: collapse; margin-bottom: 9px; } .meta-grid td { border: 0; padding: 2px 8px 3px 0; vertical-align: top; }
.label { display: block; font-size: 7px; font-weight: bold; text-transform: uppercase; letter-spacing: .06em; } .value { font-size: 9px; }
.section-title { font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: .08em; border-bottom: 1px solid #111; padding-bottom: 3px; margin: 8px 0 5px; }
.history { margin-bottom: 5px; } .history-pill { display: inline-block; border: 1px solid #777; border-radius: 10px; padding: 3px 7px; margin: 0 3px 3px 0; font-size: 8px; } .history-pill.selected { border-color: #111; font-weight: bold; }
.note { border: 1px solid #111; padding: 5px; min-height: 20px; margin-bottom: 7px; }
table.report-table { width: 100%; border-collapse: collapse; margin: 4px 0 8px; } .report-table th, .report-table td { border: 1px solid #111; padding: 4px 5px; vertical-align: top; } .report-table th { font-weight: bold; text-transform: uppercase; font-size: 8px; } .report-table td { font-size: 8px; }
.clinical-table td { height: 22px; } .muted { color: #555; text-align: center; }
.signature { width: 100%; margin-top: 14px; } .signature td { width: 50%; text-align: center; border: 0; padding: 22px 20px 0; border-top: 1px solid #111; font-weight: bold; font-size: 8px; }
td, th, div, span { word-wrap: break-word; }
</style>
<div class="letterhead"><table><tr><td class="logo-left">' . ($scancareLogo ? '<img class="logo" src="' . $scancareLogo . '">' : '') . '</td><td class="heading">REPUBLIC OF THE PHILIPPINES<br>PROVINCE OF PAMPANGA BARANGAY GUTAD HEALTH SERVICES<br>RURAL HEALTH UNIT II</td><td class="logo-right">' . ($rhuLogo ? '<img class="logo rhu" src="' . $rhuLogo . '">' : '') . '</td></tr></table></div>
<table class="meta-grid"><tr><td><span class="label">Patient Name</span><span class="value">' . reportHtml($request['full_name']) . '</span></td><td><span class="label">Patient ID</span><span class="value">' . reportHtml($patientId) . '</span></td><td><span class="label">Sex</span><span class="value">' . reportHtml($request['sex']) . '</span></td></tr><tr><td><span class="label">Date of Birth</span><span class="value">' . reportHtml($request['date_of_birth']) . '</span></td><td><span class="label">Contact Number</span><span class="value">' . reportHtml($request['contact_number'] ?: $request['email']) . '</span></td><td><span class="label">Visit Date</span><span class="value">' . reportHtml($history['visit_date'] ?? date('Y-m-d')) . '</span></td></tr><tr><td colspan="3"><span class="label">Address</span><span class="value">' . reportHtml($request['address']) . '</span></td></tr></table>
<div class="section-title">Medical History</div><div class="history">' . $historyHtml . '</div><div class="label">History Notes</div><div class="note">' . reportHtml($clinicalData['history_notes'] ?? '') . '</div>
<div class="section-title">Clinical Examination</div><table class="report-table clinical-table"><tr><th>Height (cm)</th><th>Weight (kg)</th><th>BP (mmHg)</th><th>PR (bpm)</th><th>Temp (C)</th></tr><tr><td>' . reportHtml($clinicalData['height'] ?? '') . '</td><td>' . reportHtml($clinicalData['weight'] ?? '') . '</td><td>' . reportHtml($clinicalData['bp'] ?? '') . '</td><td>' . reportHtml($clinicalData['pr'] ?? '') . '</td><td>' . reportHtml($clinicalData['temp'] ?? '') . '</td></tr></table><table class="report-table"><tr><th>HEENT / Head</th><td>' . reportHtml($clinicalData['head'] ?? '') . '</td></tr><tr><th>Chest / Lungs</th><td>' . reportHtml($clinicalData['chest_lungs'] ?? '') . '</td></tr><tr><th>Heart</th><td>' . reportHtml($clinicalData['heart'] ?? '') . '</td></tr><tr><th>Abdomen</th><td>' . reportHtml($clinicalData['abdomen'] ?? '') . '</td></tr><tr><th>Extremities</th><td>' . reportHtml($clinicalData['extremities'] ?? '') . '</td></tr><tr><th>Diagnosis</th><td>' . reportHtml($history['diagnosis'] ?? '') . '</td></tr><tr><th>Treatment Plan</th><td>' . reportHtml($clinicalData['treatment_plan'] ?? '') . '</td></tr></table>
<div class="section-title">Medical Records</div><table class="report-table"><tr><th>Visit Date</th><th>Diagnosis</th><th>Treatment</th><th>Notes</th></tr>' . $recordRows . '</table><div class="section-title">Remarks &amp; Recommendations</div><table class="report-table"><tr><th>Remarks</th><td>' . reportHtml($clinicalData['remarks'] ?? ($history['notes'] ?? '')) . '</td><th>Recommendations</th><td>' . reportHtml($clinicalData['recommendations'] ?? '') . '</td></tr></table><table class="signature"><tr><td>Patient / Guardian Signature</td><td>Attending BHW / Nurse / Medical Officer</td></tr></table>';

$mpdf = new \Mpdf\Mpdf(['format' => 'A4', 'margin_top' => 10, 'margin_right' => 12, 'margin_bottom' => 10, 'margin_left' => 12]);
$mpdf->SetTitle('ScanCare Medical Report - ' . $request['full_name']);
$mpdf->WriteHTML($html);
ob_end_clean();
$mpdf->Output('scancare-medical-report-' . $requestId . '.pdf', \Mpdf\Output\Destination::DOWNLOAD);
exit;
