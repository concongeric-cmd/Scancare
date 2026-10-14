<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'db_config.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$qr_scan = isset($_GET['qr_scan']) && $_GET['qr_scan'] === '1';
$requested_appointment_id = (int) ($_GET['appointment_id'] ?? 0);

// Get patient identifier from URL parameter. Support both legacy resident IDs and new patient codes.
$requested_identifier = null;
if (!empty($_GET['code'])) {
    $requested_identifier = trim($_GET['code']);
} elseif (!empty($_GET['id'])) {
    $requested_identifier = trim($_GET['id']);
}

if ($requested_identifier === null || $requested_identifier === '') {
    error_log("Invalid view_record access - Missing patient identifier parameter");
    $_SESSION['error_message'] = 'Invalid patient identifier. Please try again.';
    header('Location: login.php');
    exit;
}

$requested_patient_id = null;
$requested_patient_code = null;
if (!empty($_GET['code'])) {
    $requested_patient_code = $requested_identifier;
} elseif (filter_var($requested_identifier, FILTER_VALIDATE_INT) !== false) {
    $requested_patient_id = intval($requested_identifier);
} else {
    $requested_patient_code = $requested_identifier;
}

$is_qr_access = $qr_scan ? true : false;
$is_resident_view = ($_SESSION['user_type'] ?? '') === 'resident';
$back_url = $is_resident_view
    ? 'dashboard.php'
    : 'dashboard_bhw.php?module=patient-profile&patient_id=' . (int) $requested_patient_id;
$back_label = $is_resident_view ? '← Back to Dashboard' : '← Back to Profile';

// Authorization check: Allow QR scans OR authenticated users
if (!isset($_SESSION['user_type']) || !isset($_SESSION['user_id'])) {
    error_log("Unauthorized view_record access - No session found");
    $_SESSION['error_message'] = 'Please log in to access medical records.';
    header('Location: login.php');
    exit;
}
















$user_type = $_SESSION['user_type'] ?? null;
$user_role = $_SESSION['role'] ?? $user_type; 
$current_user_id = $_SESSION['user_id'] ?? null;
$current_resident_id = $_SESSION['resident_id'] ?? null;


$is_authorized = false;
$can_view = false;
$can_edit = false;
$access_level = 'NONE';





$raw_role = $_SESSION['worker_role'] ?? $_SESSION['user_role'] ?? $_SESSION['role'] ?? $_SESSION['user_type'] ?? '';
$role_normal = strtolower(trim((string)$raw_role));
$session_user_type = strtolower(trim((string)($_SESSION['user_type'] ?? '')));
$valid_bhw_worker_roles = ['bhw', 'worker', 'health_worker'];
$has_valid_worker_session = in_array($session_user_type, ['health_worker', 'bhw', 'worker'], true)
    || in_array($role_normal, $valid_bhw_worker_roles, true);
$is_bhw_editing = $has_valid_worker_session;

// If QR access granted and RBAC marked the session as authorized for BHW, ensure editing is enabled
if ($is_qr_access && !empty($access_level)) {
    if (stripos($access_level, 'BHW') !== false || stripos($access_level, 'HEALTH_WORKER') !== false) {
        $is_bhw_editing = true;
    }
}

$pdo = getDatabaseConnection();
if (!$pdo) {
    error_log('Database connection failed for view_record.php');
    $_SESSION['error_message'] = 'Unable to load medical record. Please try again later.';
    header('Location: dashboard.php');
    exit;
}

$patient = null;

if (!empty($requested_patient_id)) {
    $stmt = $pdo->prepare('SELECT * FROM resident WHERE resident_id = ? LIMIT 1');
    $stmt->execute([$requested_patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$patient && !empty($requested_patient_code)) {
    $stmt = $pdo->prepare('SELECT * FROM resident WHERE patient_code = ? LIMIT 1');
    $stmt->execute([$requested_patient_code]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$patient && !empty($requested_patient_id)) {
    $stmt = $pdo->prepare('SELECT * FROM resident WHERE patient_code = ? LIMIT 1');
    $stmt->execute([str_pad($requested_patient_id, 6, '0', STR_PAD_LEFT)]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$patient) {
    error_log('Resident not found: ' . $requested_identifier);
    $_SESSION['error_message'] = 'Medical record not found.';
    header('Location: dashboard.php');
    exit;
}

$patient_id = intval($patient['resident_id']);
if (!$is_resident_view) {
    $back_url = 'dashboard_bhw.php?module=patient-profile&patient_id=' . $patient_id;
}
if ($qr_scan) {
    $_SESSION['qr_access_resident_id'] = $patient_id;
    error_log("QR Code Scan: Set qr_access_resident_id = {$patient_id}");
}

// If a resident is logged in and this is a QR view, ensure they only access their own record.
if ($is_qr_access && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'resident') {
    if ($current_resident_id === null || intval($current_resident_id) !== $patient_id) {
        $_SESSION['error_message'] = 'You do not own this QR code.';
        error_log("RBAC DENY: Resident [{$current_user_id}] attempted unauthorized QR access to patient [{$patient_id}]");
        header('Location: dashboard.php');
        exit;
    }
}

// If a health worker is logged in on QR access, allow edit.
if ($is_qr_access && isset($_SESSION['user_type']) && in_array(strtolower((string)($_SESSION['user_type'] ?? '')), ['health_worker', 'bhw', 'worker'], true)) {
    $is_bhw_editing = true;
    $access_level = 'FULL_ACCESS_BHW_VIA_QR';
}

$can_edit = $is_bhw_editing && $has_valid_worker_session;
$appointment_context = null;
if ($requested_appointment_id > 0) {
    if (!$can_edit || !ensureMedicalHistoryAppointmentColumns($pdo)) {
        http_response_code(403);
        exit('Appointment-linked clinical editing is unavailable.');
    }

    $appointmentContextStmt = $pdo->prepare('SELECT appointment_id, resident_id, appointment_date, appointment_time, purpose FROM appointment WHERE appointment_id = ? AND resident_id = ? LIMIT 1');
    $appointmentContextStmt->execute([$requested_appointment_id, $patient_id]);
    $appointment_context = $appointmentContextStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if (!$appointment_context) {
        http_response_code(404);
        exit('Appointment not found for this resident.');
    }
    $back_url .= '&appointment_id=' . $requested_appointment_id;
}
$new_visit_context = $can_edit && !$appointment_context && empty($_GET['request_id']) && ($_GET['edit_existing'] ?? '') !== '1';
$file_request_id = (int) ($_GET['request_id'] ?? 0);
$file_request_context = null;
if ($can_edit && $file_request_id > 0) {
    ensureFileRequestsTable($pdo);
    $fileRequestStmt = $pdo->prepare('SELECT request_id, status, requested_file FROM file_requests WHERE request_id = ? AND resident_id = ? LIMIT 1');
    $fileRequestStmt->execute([$file_request_id, $patient_id]);
    $file_request_context = $fileRequestStmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// Normalize patient fields with common fallback column names
$patient['email'] = $patient['email'] ?? $patient['resident_email'] ?? $patient['email_address'] ?? '';
$patient['address'] = $patient['address'] ?? $patient['resident_address'] ?? $patient['address_line'] ?? $patient['street_address'] ?? $patient['patient_address'] ?? '';
$patient['contact_number'] = $patient['contact_number'] ?? $patient['phone_number'] ?? $patient['patient_phone'] ?? $patient['contact_no'] ?? $patient['phone'] ?? $patient['mobile'] ?? $patient['telephone'] ?? '';
$patient['date_of_birth'] = $patient['date_of_birth'] ?? $patient['dob'] ?? $patient['birth_date'] ?? $patient['birthday'] ?? '';

$patient['full_name'] = $patient['full_name'] ?? trim((($patient['first_name'] ?? '') . ' ' . ($patient['middle_initial'] ?? '') . ' ' . ($patient['last_name'] ?? '')));
if ($patient['full_name'] === '') {
    $patient['full_name'] = $patient['name'] ?? $patient['resident_name'] ?? 'Unknown Patient';
}

// Ensure patient_code is available for display and QR compatibility.
if (empty($patient['patient_code']) && !empty($patient['resident_id'])) {
    $patient['patient_code'] = ensureResidentPatientCode($pdo, intval($patient['resident_id']));
}

$patient_id_display = !empty($patient['patient_code']) ? $patient['patient_code'] : str_pad(intval($patient['resident_id']), 6, '0', STR_PAD_LEFT);

// Treat invalid or placeholder DOB values as empty for display
if (!empty($patient['date_of_birth']) && $patient['date_of_birth'] !== '0000-00-00') {
    // keep as-is
} else {
    $patient['date_of_birth'] = '';
}

$patient['email'] = trim((string)$patient['email']);
$patient['address'] = trim((string)$patient['address']);
$patient['contact_number'] = trim((string)$patient['contact_number']);
$patient['date_of_birth'] = trim((string)$patient['date_of_birth']);

$patient_email_display = $patient['email'] !== '' ? $patient['email'] : 'N/A';
$patient_address_display = $patient['address'] !== '' ? $patient['address'] : 'N/A';
$patient_dob_display = $patient['date_of_birth'] !== '' ? $patient['date_of_birth'] : 'N/A';

$age = '';
if (!empty($patient['date_of_birth'])) {
    $dob = DateTime::createFromFormat('Y-m-d', $patient['date_of_birth']);
    if ($dob) {
        $age = $dob->diff(new DateTime())->y;
    }
}
$patient_age_display = $age !== '' ? $age : 'N/A';
$patient_contact_display = $patient['contact_number'] !== '' ? $patient['contact_number'] : 'N/A';

$current_medical_record = [
    'visit_date' => $appointment_context
        ? $appointment_context['appointment_date'] . ' ' . $appointment_context['appointment_time']
        : date('Y-m-d'),
    'visit_type' => $appointment_context['purpose'] ?? '',
    'diagnosis' => ''
];
$has_stored_medical_history_options = false;
$clinical_data = [
    'medical_history' => [],
    'medical_history_options' => [],
    'history_notes' => '',
    'treatment_plan' => '',
    'height' => '',
    'weight' => '',
    'bp' => '',
    'pr' => '',
    'temp' => '',
    'head' => '',
    'chest_lungs' => '',
    'heart' => '',
    'abdomen' => '',
    'extremities' => '',
    'remarks' => '',
    'recommendations' => ''
];
$defaultMedicalHistoryOptions = [
    'asthma' => 'Asthma',
    'epilepsy' => 'Epilepsy',
    'headache' => 'Sakit sa Ulo',
    'diabetes' => 'Diabetes',
    'hypertension' => 'Hypertension',
    'allergies' => 'Allergies',
    'medications' => 'On Medications',
    'hospitalization' => 'Hospitalization / Surgery'
];
$allowedMedicalHistoryFlags = array_keys($defaultMedicalHistoryOptions);

$record = null;
if ($appointment_context) {
    $recordStmt = $pdo->prepare('SELECT * FROM medical_history WHERE user_id = ? AND appointment_id = ? ORDER BY visit_date DESC LIMIT 1');
    $recordStmt->execute([$patient_id, $requested_appointment_id]);
    $record = $recordStmt->fetch(PDO::FETCH_ASSOC) ?: null;
} elseif (!$new_visit_context) {
    $recordStmt = $pdo->prepare('SELECT * FROM medical_history WHERE user_id = ? ORDER BY visit_date DESC, history_id DESC LIMIT 1');
    $recordStmt->execute([$patient_id]);
    $record = $recordStmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$clinicalPrefillRecords = [];
if ($appointment_context || $new_visit_context) {
    if ($appointment_context) {
        $previousClinicalStmt = $pdo->prepare(
              'SELECT diagnosis, treatment, notes, visit_date, COALESCE(created_at, visit_date) AS saved_at
             FROM medical_history
             WHERE user_id = ? AND (appointment_id IS NULL OR appointment_id <> ?)
               ORDER BY COALESCE(created_at, visit_date) DESC, history_id DESC
             LIMIT 50'
        );
        $previousClinicalStmt->execute([$patient_id, $requested_appointment_id]);
    } else {
        $previousClinicalStmt = $pdo->prepare(
              'SELECT diagnosis, treatment, notes, visit_date, COALESCE(created_at, visit_date) AS saved_at
             FROM medical_history
             WHERE user_id = ?
               ORDER BY COALESCE(created_at, visit_date) DESC, history_id DESC
             LIMIT 50'
        );
        $previousClinicalStmt->execute([$patient_id]);
    }
    $clinicalPrefillRecords = $previousClinicalStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    try {
        $patientPrefillStmt = $pdo->prepare(
            'SELECT diagnosis, treatment, notes, visit_date, COALESCE(created_at, visit_date) AS saved_at
             FROM patient_record
             WHERE resident_id = ?
             ORDER BY COALESCE(created_at, visit_date) DESC, record_id DESC
             LIMIT 50'
        );
        $patientPrefillStmt->execute([$patient_id]);
        $clinicalPrefillRecords = array_merge($clinicalPrefillRecords, $patientPrefillStmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    } catch (Throwable $patientPrefillError) {
        error_log('Previous patient record prefill lookup failed: ' . $patientPrefillError->getMessage());
    }

    usort($clinicalPrefillRecords, static function ($left, $right) {
        return (strtotime((string) ($right['saved_at'] ?? '')) ?: 0)
            <=> (strtotime((string) ($left['saved_at'] ?? '')) ?: 0);
    });
}

$clinicalPrefillRecord = $clinicalPrefillRecords[0] ?? null;
$clinicalPrefillData = null;
foreach ($clinicalPrefillRecords as $candidateRecord) {
    $candidateData = json_decode((string) ($candidateRecord['treatment'] ?? ''), true);
    if (is_array($candidateData) && array_key_exists('medical_history', $candidateData)) {
        $clinicalPrefillRecord = $candidateRecord;
        $clinicalPrefillData = $candidateData;
        break;
    }
}

if ($record) {
    $current_medical_record = array_merge($current_medical_record, $record);
    $currentRecordData = json_decode((string) ($record['treatment'] ?? ''), true);
    if (is_array($currentRecordData) && ($_GET['saved'] ?? '') === '1'
        && array_key_exists('medical_history', $currentRecordData)) {
        $clinicalPrefillRecord = $record;
        $clinicalPrefillData = $currentRecordData;
    } elseif (is_array($currentRecordData) && !is_array($clinicalPrefillData)) {
        $clinicalPrefillRecord = $record;
        $clinicalPrefillData = $currentRecordData;
    } elseif (is_array($currentRecordData) && is_array($clinicalPrefillData)) {
        foreach ($currentRecordData as $clinicalField => $clinicalValue) {
            if ($clinicalValue !== '' && $clinicalValue !== null && $clinicalValue !== []) {
                $clinicalPrefillData[$clinicalField] = $clinicalValue;
            }
        }
    }
}

if ($clinicalPrefillRecord) {
    if (trim((string) ($current_medical_record['diagnosis'] ?? '')) === '') {
        $current_medical_record['diagnosis'] = $clinicalPrefillRecord['diagnosis'] ?? '';
    }
    if (is_array($clinicalPrefillData)) {
        $clinical_data = array_merge($clinical_data, $clinicalPrefillData);
        $has_stored_medical_history_options = array_key_exists('medical_history_options', $clinicalPrefillData)
            && is_array($clinicalPrefillData['medical_history_options']);
    } elseif (!empty($clinicalPrefillRecord['treatment'])) {
        $clinical_data['treatment_plan'] = (string) $clinicalPrefillRecord['treatment'];
    }
    if (empty($clinical_data['history_notes']) && !empty($clinicalPrefillRecord['notes'])) {
        $clinical_data['history_notes'] = (string) $clinicalPrefillRecord['notes'];
    }
}

$storedMedicalHistoryOptions = (array) ($clinical_data['medical_history_options'] ?? []);
$medicalHistoryOptions = $has_stored_medical_history_options ? [] : $defaultMedicalHistoryOptions;
foreach ($storedMedicalHistoryOptions as $optionId => $optionLabel) {
    $optionId = trim((string) $optionId);
    $optionLabel = trim((string) $optionLabel);
    if ((in_array($optionId, $allowedMedicalHistoryFlags, true) || preg_match('/^custom_[a-zA-Z0-9_-]{1,40}$/', $optionId)) && $optionLabel !== '') {
        $medicalHistoryOptions[$optionId] = mb_substr($optionLabel, 0, 80);
    }
}
foreach ((array) ($clinical_data['medical_history'] ?? []) as $selectedOptionId) {
    $selectedOptionId = trim((string) $selectedOptionId);
    if ($selectedOptionId !== '' && !isset($medicalHistoryOptions[$selectedOptionId])) {
        $medicalHistoryOptions[$selectedOptionId] = ucwords(str_replace(['_', '-'], ' ', $selectedOptionId));
    }
}

$save_message = isset($_GET['saved']) ? 'Clinical record saved successfully.' : '';
$is_offline_sync = ($_SERVER['HTTP_X_SCANCARE_OFFLINE_SYNC'] ?? '') === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_edit) {
    $submittedLabels = (array) ($_POST['medical_history_labels'] ?? []);
    $submittedOptions = [];
    foreach ($submittedLabels as $optionId => $optionLabel) {
        $optionId = trim((string) $optionId);
        $optionLabel = mb_substr(trim((string) $optionLabel), 0, 80);
        $isKnownOption = in_array($optionId, $allowedMedicalHistoryFlags, true);
        $isCustomOption = preg_match('/^custom_[a-zA-Z0-9_-]{1,80}$/', $optionId) === 1;
        if (($isKnownOption || $isCustomOption) && $optionLabel !== '') {
            $submittedOptions[$optionId] = $optionLabel;
        }
    }

    $submittedMedicalHistory = [];
    foreach ((array) ($_POST['medical_history'] ?? []) as $flag) {
        $flag = trim((string) $flag);
        if ($flag !== '' && array_key_exists($flag, $submittedOptions)) {
            $submittedMedicalHistory[] = $flag;
        }
    }
    $submittedMedicalHistory = array_values(array_unique($submittedMedicalHistory));
    $clinicalData = [
        'medical_history' => $submittedMedicalHistory,
        'medical_history_options' => $submittedOptions,
        'history_notes' => trim($_POST['history_notes'] ?? ''),
        'treatment_plan' => trim($_POST['treatment_plan'] ?? ''),
        'height' => trim($_POST['height'] ?? ''),
        'weight' => trim($_POST['weight'] ?? ''),
        'bp' => trim($_POST['bp'] ?? ''),
        'pr' => trim($_POST['pr'] ?? ''),
        'temp' => trim($_POST['temp'] ?? ''),
        'head' => trim($_POST['head'] ?? ''),
        'chest_lungs' => trim($_POST['chest_lungs'] ?? ''),
        'heart' => trim($_POST['heart'] ?? ''),
        'abdomen' => trim($_POST['abdomen'] ?? ''),
        'extremities' => trim($_POST['extremities'] ?? ''),
        'remarks' => trim($_POST['remarks'] ?? ''),
        'recommendations' => trim($_POST['recommendations'] ?? '')
    ];
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $notes = trim($_POST['history_notes'] ?? '');
    $treatment = json_encode($clinicalData, JSON_UNESCAPED_UNICODE);
    $bhwName = $_SESSION['worker_name'] ?? $_SESSION['user_name'] ?? 'Health Worker';

    $save_error = '';
    $savedAppointmentId = $requested_appointment_id;
    try {
        if ($new_visit_context && !ensureMedicalHistoryAppointmentColumns($pdo)) {
            throw new RuntimeException('Appointment-linked clinical records are unavailable.');
        }

        $pdo->beginTransaction();
        if ($new_visit_context) {
            $workerId = (int) ($_SESSION['worker_id'] ?? $_SESSION['user_id'] ?? 0);
            $walkInAppointmentStmt = $pdo->prepare("INSERT INTO appointment (resident_id, health_worker_id, appointment_date, appointment_time, purpose, status, notes, created_at, updated_at) VALUES (?, ?, CURDATE(), CURTIME(), 'Walk-in Consultation', 'completed', 'Walk-in consultation', NOW(), NOW())");
            $walkInAppointmentStmt->execute([$patient_id, $workerId > 0 ? $workerId : null]);
            $savedAppointmentId = (int) $pdo->lastInsertId();
            if ($savedAppointmentId <= 0) {
                throw new RuntimeException('Unable to create the Walk-in appointment entry.');
            }
        }

        if ($record) {
            if ($appointment_context) {
                $saveStmt = $pdo->prepare('UPDATE medical_history SET diagnosis = ?, treatment = ?, notes = ?, bhw_name = ?, consulted_at = NOW(), updated_at = NOW() WHERE history_id = ? AND user_id = ? AND appointment_id = ?');
                $saveStmt->execute([$diagnosis, $treatment, $notes, $bhwName, $record['history_id'], $patient_id, $requested_appointment_id]);
            } else {
                $saveStmt = $pdo->prepare('UPDATE medical_history SET diagnosis = ?, treatment = ?, notes = ?, bhw_name = ? WHERE history_id = ?');
                $saveStmt->execute([$diagnosis, $treatment, $notes, $bhwName, $record['history_id']]);
            }
        } elseif ($appointment_context || $new_visit_context) {
            $visitDate = $appointment_context
                ? $appointment_context['appointment_date'] . ' ' . $appointment_context['appointment_time']
                : date('Y-m-d H:i:s');
            $visitType = $appointment_context
                ? (trim((string) ($appointment_context['purpose'] ?? '')) ?: 'Consultation')
                : 'Walk-in Consultation';
            $saveStmt = $pdo->prepare('INSERT INTO medical_history (user_id, appointment_id, visit_date, consulted_at, visit_type, bhw_name, diagnosis, treatment, notes, created_at, updated_at) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, NOW(), NOW())');
            $saveStmt->execute([$patient_id, $savedAppointmentId, $visitDate, $visitType, $bhwName, $diagnosis, $treatment, $notes]);
        } else {
            $saveStmt = $pdo->prepare("INSERT INTO medical_history (user_id, visit_date, visit_type, bhw_name, diagnosis, treatment, notes, created_at) VALUES (?, NOW(), 'Medical Examination', ?, ?, ?, ?, NOW())");
            $saveStmt->execute([$patient_id, $bhwName, $diagnosis, $treatment, $notes]);
        }

        if ($savedAppointmentId > 0) {
            $completeAppointmentStmt = $pdo->prepare("UPDATE appointment SET status = 'completed', health_worker_id = ?, updated_at = NOW() WHERE appointment_id = ? AND resident_id = ?");
            $completeAppointmentStmt->execute([
                (int) ($_SESSION['worker_id'] ?? $_SESSION['user_id'] ?? 0) ?: null,
                $savedAppointmentId,
                $patient_id
            ]);
        }

        $pdo->commit();
    } catch (Throwable $saveException) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('CLINICAL RECORD SAVE ERROR: ' . $saveException->getMessage());
        $save_error = 'Unable to save the clinical record. Please try again.';
    }

    if ($is_offline_sync) {
        header('Content-Type: application/json; charset=utf-8');
        if ($save_error !== '') {
            http_response_code(400);
        }
        echo json_encode([
            'success' => $save_error === '',
            'message' => $save_error !== '' ? $save_error : 'Clinical record saved successfully.',
            'appointment_id' => $savedAppointmentId ?: null
        ]);
        exit;
    }

    if ($save_error === '') {
        $redirectQuery = !empty($_GET['code']) ? 'code=' . urlencode($_GET['code']) : 'id=' . urlencode((string) $patient_id);
        if ($savedAppointmentId > 0) {
            $redirectQuery .= '&appointment_id=' . $savedAppointmentId;
        }
        if ($file_request_id > 0) {
            $redirectQuery .= '&qr_scan=1&request_id=' . $file_request_id;
        }
        header('Location: view_record.php?' . $redirectQuery . '&saved=1');
        exit;
    }
}

?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title>Medical Record</title>
                <script src="pwa-offline.js?v=3"></script>
                <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
                <style>
                    @page { size: A4 portrait; margin: 0.3in; }

                    html, body {
                        box-sizing: border-box;
                        margin: 0;
                        padding: 0;
                        font-family: Arial, Helvetica, sans-serif;
                        font-size: 10px;
                        line-height: 1.2;
                        color: #000;
                        background: #fff;
                    }

                    .medical-record-card {
                        width: 100%;
                        max-width: calc(210mm - 0.6in);
                        margin: 0 auto;
                        padding: 6mm 8mm;
                        background: #fff;
                        box-shadow: none;
                        border: none;
                        page-break-after: avoid;
                    }

                    .print-header, .header-container { display:flex; align-items:center; justify-content:center; gap:25px; width:100%; margin-bottom:20px; }
                    .doc-branding-row { display:flex; align-items:center; justify-content:center; gap:18px; width:auto; }
                    .letterhead-logo-left img { height:65px; width:auto; display:block; }
                    .letterhead-logo-right img { height:80px; width:auto; display:block; }
                    .print-header-text { text-align:center; font-weight:700; font-size:11px; line-height:1.1; }
                    .print-header-text p { margin:0; font-size:10px; font-weight:700; }

                    .document-meta { width:100%; margin-bottom:8px; display:grid; gap:10px; }
                    .meta-row { display:grid; grid-template-columns: repeat(3, minmax(0, 1fr)); align-items:start; gap:10px; }
                    .patient-info-grid { display:grid; grid-template-columns: repeat(3, minmax(0, 1fr)); align-items:start; gap:12px 18px; width:100%; margin-bottom:12px; }
                    .patient-info-column { display:grid; gap:6px; }
                    .meta-item { display:flex; min-width:0; flex-direction:column; gap:1px; }
                    .meta-label { font-size:8px; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; }
                    .meta-value { min-width:0; overflow-wrap:anywhere; font-size:9px; }

                    .patient-info-container { display:block; }

                    .section-block { margin-bottom: 8px; }
                    .section-header { font-size: 10px; font-weight: 700; margin-bottom: 4px; letter-spacing: 0.1em; text-transform: uppercase; }

                    .history-grid { display:grid; grid-template-columns: repeat(4, 1fr); gap:8px; margin:10px 0 4px; }
                    .history-item { display:flex; align-items:center; gap:6px; justify-content:flex-start; }
                    .history-item input { width: 16px; height: 16px; margin: 0; }
                    .history-item label { display:inline-flex; align-items:center; min-height:24px; padding:4px 10px; border:1px solid #111; border-radius:999px; font-size:9px; white-space:nowrap; }
                    .history-note { margin-top: 6px; }
                    .history-note label { display:block; font-size:8px; margin-bottom:2px; text-transform:uppercase; letter-spacing:0.08em; }
                    .history-note textarea { width:100%; min-height:26px; max-height:28px; padding:5px 8px; font-size:9px; border-radius:8px; border:1px solid #111; resize:none; }

                    .clinical-table { width:100%; border-collapse:collapse; margin-top:6px; }
                    .clinical-table th, .clinical-table td { border:1px solid #111; padding:5px 6px; font-size:9px; }
                    .clinical-table th { background:transparent; font-weight:700; text-transform:uppercase; }
                    .clinical-table td input, .clinical-table td textarea, .clinical-textarea, .form-textarea { width:100%; border:1px solid #111; border-radius:8px; padding:5px 6px; font-size:9px; background:transparent; resize:none; }
                    .clinical-table td input { height: 24px; }
                    .clinical-textarea { min-height:28px; max-height:32px; }
                    .form-textarea { min-height:28px; max-height:32px; }

                    .signature-row { display:flex; justify-content:space-between; gap:10px; margin-top:10px; }
                    .signature-block { width:48%; }
                    .signature-line { border-top:1px solid #111; margin-top:22px; }
                    .signature-title { font-size:9px; font-weight:700; margin-top:4px; letter-spacing:0.08em; text-transform:uppercase; }

                    .page-header, .action-buttons, nav, .alert { display:none !important; }

                    * { box-sizing:border-box; }
                    html, body { min-height: 100vh; height: auto; overflow-x: hidden; overflow-y: auto; }

                        @media print {
                            .print-hide { display:none !important; }
                            html, body { max-height: none; height: auto; overflow: visible; font-size:10px; margin:0; padding:0; }
                            .medical-record-card { padding:4mm 6mm; }
                            .print-header, .header-container { margin-bottom:7px; }
                            .document-meta { margin-bottom:5px; gap:5px; }
                            .meta-row { gap:8px; }
                            .patient-info-grid { gap:7px 12px; margin-bottom:6px; }
                            .patient-info-column { gap:3px; }
                            .history-grid { gap:6px; margin:6px 0 3px; }
                            .history-item label { min-height:21px; padding:3px 7px; font-size:8px; }
                            .section-block { margin-bottom:6px; }
                            .clinical-table th, .clinical-table td { padding:4px 5px; }
                            .clinical-table td input { height:22px; }
                            .clinical-textarea, .form-textarea { min-height:24px; max-height:27px; padding:4px 5px; }
                            .signature-section { margin-top:20px !important; }
                            .history-note textarea, .clinical-textarea, .form-textarea { -webkit-print-color-adjust:exact; }
                            .no-print { display:none !important; }
                            .action-buttons, .page-header, nav, .alert { display:none !important; }
                        }
                        @media screen {
                            .no-print {
                                position: fixed;
                                top: 40px;
                                left: 30px;
                                display: flex;
                                flex-direction: column;
                                gap: 12px;
                                z-index: 9999;
                            }
                            .no-print button, .no-print a {
                                width: 140px;
                                text-align: center;
                                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                                transition: all 0.2s ease;
                            }
                        }
                        @media print { .no-print { display: none !important; } }
                        @media screen and (max-width: 767px) {
                            .no-print {
                                top: 12px;
                                right: 12px;
                                left: auto;
                                margin: 0 !important;
                                align-items: flex-end;
                                gap: 8px;
                                z-index: 10000;
                            }
                            .no-print .mobile-record-menu-toggle {
                                display: inline-flex;
                                align-items: center;
                                justify-content: center;
                                width: 32px !important;
                                min-width: 32px !important;
                                height: 42px;
                                padding: 0;
                                border: 0;
                                border-radius: 10px;
                                background: #454B1B;
                                color: #fff;
                                font-size: 18px;
                                cursor: pointer;
                                box-shadow: 0 4px 12px rgba(15, 23, 42, 0.2);
                            }
                            .mobile-record-menu {
                                display: none;
                                flex-direction: column;
                                align-items: stretch;
                                gap: 8px;
                                width: min(180px, calc(100vw - 24px));
                                padding: 10px;
                                border: 1px solid #e2e8f0;
                                border-radius: 12px;
                                background: #fff;
                                box-shadow: 0 12px 28px rgba(15, 23, 42, 0.18);
                            }
                            .no-print.is-open .mobile-record-menu {
                                display: flex;
                            }
                            .mobile-record-menu a,
                            .mobile-record-menu button,
                            .mobile-record-menu form {
                                width: 100% !important;
                                margin: 0 !important;
                            }
                            .action-buttons {
                                display: none !important;
                            }
                        }
                        @media screen and (min-width: 768px) {
                            .mobile-record-menu-toggle {
                                display: none;
                            }
                            .mobile-record-menu {
                                display: contents;
                            }
                        }
                    </style>
</head>
<body>
    <div class="no-print" style="margin: 20px 0; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <button type="button" class="mobile-record-menu-toggle" aria-label="Toggle record actions" aria-expanded="false" onclick="toggleMobileRecordMenu(this)"><i class="fas fa-bars"></i></button>
        <div class="mobile-record-menu">
        <a href="<?php echo htmlspecialchars($back_url, ENT_QUOTES, 'UTF-8'); ?>" style="background: #475569; color: white; padding: 10px 20px; border-radius: 8px; font-weight: bold; text-decoration: none;"><?php echo htmlspecialchars($back_label, ENT_QUOTES, 'UTF-8'); ?></a>
        <button onclick="window.print();" style="background: #10b981; color: white; padding: 10px 20px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">Download as PDF</button>
        <?php if ($can_edit): ?><button type="submit" form="medical-record-form" style="background: #454B1B; color: white; padding: 10px 20px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">Save Clinical Record</button><?php endif; ?>
        <?php if ($file_request_context && $file_request_context['status'] === 'pending'): ?><form method="post" action="manage_file_request.php" style="display: inline;"><input type="hidden" name="request_id" value="<?php echo $file_request_id; ?>"><input type="hidden" name="request_action" value="accept"><button type="submit" style="background: #059669; color: white; padding: 10px 20px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">Confirm &amp; Send PDF</button></form><form method="post" action="manage_file_request.php" style="display: inline;"><input type="hidden" name="request_id" value="<?php echo $file_request_id; ?>"><input type="hidden" name="request_action" value="decline"><button type="submit" style="background: #dc2626; color: white; padding: 10px 20px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">Decline Request</button></form><?php endif; ?>
        </div>
    </div>
    <main class="medical-record-card">
        <div class="print-header header-container">
            <div class="doc-branding-row">
                <div class="letterhead-logo-left">
                    <img src="images/SClogo.png" alt="ScanCare Logo">
                </div>
                <div class="print-header-text">
                    <p>REPUBLIC OF THE PHILIPPINES</p>
                    <p>PROVINCE OF PAMPANGA BARANGAY GUTAD HEALTH SERVICES</p>
                    <p>RURAL HEALTH UNIT II</p>
                </div>
                <div class="letterhead-logo-right">
                    <img src="images/RHU LOGO.png" alt="RHU Logo">
                </div>
            </div>
        </div>

        <div class="document-meta">
            <div class="meta-row">
                <div class="meta-item">
                    <span class="meta-label">EMAIL</span>
                    <span class="meta-value"><?php echo htmlspecialchars($patient_email_display); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">ADDRESS</span>
                    <span class="meta-value"><?php echo htmlspecialchars($patient_address_display); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">CONTACT NUMBER</span>
                    <span class="meta-value"><?php echo htmlspecialchars($patient_contact_display); ?></span>
                </div>
            </div>

            <div class="patient-info-grid">
                <div class="patient-info-column">
                    <div class="meta-item">
                        <span class="meta-label">Patient Name</span>
                        <span class="meta-value"><?php echo htmlspecialchars($patient['full_name']); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Patient ID</span>
                        <span class="meta-value"><?php echo htmlspecialchars($patient_id_display); ?></span>
                    </div>
                </div>
                <div class="patient-info-column">
                    <div class="meta-item">
                        <span class="meta-label">Date of Birth</span>
                        <span class="meta-value"><?php echo htmlspecialchars($patient_dob_display); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Age</span>
                        <span class="meta-value"><?php echo htmlspecialchars($patient_age_display); ?></span>
                    </div>
                </div>
                <div class="patient-info-column">
                    <div class="meta-item">
                        <span class="meta-label">Visit Date</span>
                        <span class="meta-value"><?php echo htmlspecialchars($current_medical_record['visit_date'] ?? date('Y-m-d')); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Record Mode</span>
                        <span class="meta-value"><?php echo $is_bhw_editing ? 'BHW / Edit Access' : 'Resident / View Only'; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($save_message)): ?>
        <div class="alert alert-success print-hide">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($save_message); ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($save_error)): ?>
        <div class="alert alert-error print-hide">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($save_error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="medical-record-form" class="<?php echo !$can_edit ? 'read-only-mode' : ''; ?>" <?php echo !$can_edit ? 'data-access-level="VIEW_ONLY"' : ''; ?>>
            <input type="hidden" name="action" value="save_medical_record">

            <div class="section-block">
                <div class="section-header">Medical History</div>
                <div class="history-grid" id="medical-history-options">
                    <?php foreach ($medicalHistoryOptions as $optionId => $optionLabel): ?>
                    <div class="history-item" data-history-option="<?php echo htmlspecialchars($optionId, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="checkbox" id="history-<?php echo htmlspecialchars($optionId, ENT_QUOTES, 'UTF-8'); ?>" name="medical_history[]" value="<?php echo htmlspecialchars($optionId, ENT_QUOTES, 'UTF-8'); ?>" <?php echo !$can_edit ? 'disabled' : ''; ?> <?php echo in_array($optionId, $clinical_data['medical_history'] ?? [], true) ? 'checked' : ''; ?>>
                        <input type="text" name="medical_history_labels[<?php echo htmlspecialchars($optionId, ENT_QUOTES, 'UTF-8'); ?>]" value="<?php echo htmlspecialchars($optionLabel, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Medical history option name" <?php echo !$can_edit ? 'disabled' : ''; ?> style="min-width: 0; flex: 1; border: 0; border-bottom: 1px dashed #64748b; background: transparent; padding: 2px 4px; font: inherit;">
                        <?php if ($can_edit): ?><button type="button" class="remove-history-option" aria-label="Remove <?php echo htmlspecialchars($optionLabel, ENT_QUOTES, 'UTF-8'); ?>" title="Remove checklist item" style="border:0; background:transparent; color:#b91c1c; cursor:pointer;">&times;</button><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($can_edit): ?><button type="button" id="add-medical-history-option" class="print-hide" style="margin-top: 8px; border: 1px dashed #454B1B; background: transparent; color: #454B1B; border-radius: 8px; padding: 6px 10px; font-size: 12px; font-weight: 700; cursor: pointer;"><i class="fas fa-plus"></i> Add checklist item</button><?php endif; ?>
                <div class="history-note">
                    <label for="history_notes">History Notes</label>
                    <textarea id="history_notes" name="history_notes" class="form-textarea" <?php echo !$is_bhw_editing ? 'disabled' : ''; ?>><?php echo htmlspecialchars($clinical_data['history_notes'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="section-block">
                <div class="section-header">Clinical Examination</div>
                <table class="clinical-table">
                    <thead>
                        <tr>
                            <th>Height (cm)</th>
                            <th>Weight (kg)</th>
                            <th>BMI</th>
                            <th>BP (mmHg)</th>
                            <th>PR (bpm)</th>
                            <th>Temp (Â°C)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><input type="number" step="0.1" name="height" value="<?php echo htmlspecialchars($clinical_data['height'] ?? ''); ?>" placeholder="0" <?php echo !$is_bhw_editing ? 'disabled' : ''; ?>></td>
                            <td><input type="number" step="0.1" name="weight" value="<?php echo htmlspecialchars($clinical_data['weight'] ?? ''); ?>" placeholder="0" <?php echo !$is_bhw_editing ? 'disabled' : ''; ?>></td>
                            <td><input type="number" step="0.1" name="bmi" value="" placeholder="Auto" disabled></td>
                            <td><input type="text" name="bp" value="<?php echo htmlspecialchars($clinical_data['bp'] ?? ''); ?>" placeholder="120/80" <?php echo !$is_bhw_editing ? 'disabled' : ''; ?>></td>
                            <td><input type="number" name="pr" value="<?php echo htmlspecialchars($clinical_data['pr'] ?? ''); ?>" placeholder="0" <?php echo !$is_bhw_editing ? 'disabled' : ''; ?>></td>
                            <td><input type="number" step="0.1" name="temp" value="<?php echo htmlspecialchars($clinical_data['temp'] ?? ''); ?>" placeholder="0" <?php echo !$is_bhw_editing ? 'disabled' : ''; ?>></td>
                        </tr>
                    </tbody>
                </table>
                <table class="clinical-table">
                    <tr><th colspan="6">HEENT / Head</th></tr>
                    <tr><td colspan="6"><textarea name="head" class="clinical-textarea" <?php echo !$is_bhw_editing ? 'disabled' : ''; ?>><?php echo htmlspecialchars($clinical_data['head'] ?? ''); ?></textarea></td></tr>
                    <tr><th colspan="6">Chest / Lungs</th></tr>
                    <tr><td colspan="6"><textarea name="chest_lungs" class="clinical-textarea" <?php echo !$is_bhw_editing ? 'disabled' : ''; ?>><?php echo htmlspecialchars($clinical_data['chest_lungs'] ?? ''); ?></textarea></td></tr>
                    <tr><th colspan="6">Heart</th></tr>
                    <tr><td colspan="6"><textarea name="heart" class="clinical-textarea" <?php echo !$is_bhw_editing ? 'disabled' : ''; ?>><?php echo htmlspecialchars($clinical_data['heart'] ?? ''); ?></textarea></td></tr>
                    <tr><th colspan="6">Abdomen</th></tr>
                    <tr><td colspan="6"><textarea name="abdomen" class="clinical-textarea" <?php echo !$is_bhw_editing ? 'disabled' : ''; ?>><?php echo htmlspecialchars($clinical_data['abdomen'] ?? ''); ?></textarea></td></tr>
                    <tr><th colspan="6">Extremities</th></tr>
                    <tr><td colspan="6"><textarea name="extremities" class="clinical-textarea" <?php echo !$is_bhw_editing ? 'disabled' : ''; ?>><?php echo htmlspecialchars($clinical_data['extremities'] ?? ''); ?></textarea></td></tr>
                    <tr><th colspan="6">Diagnosis / Treatment / Others</th></tr>
                    <tr><td colspan="6"><textarea name="diagnosis" class="clinical-textarea" placeholder="Diagnosis details..." <?php echo !$is_bhw_editing ? 'disabled' : ''; ?>><?php echo htmlspecialchars($current_medical_record['diagnosis'] ?? ''); ?></textarea></td></tr>
                    <tr><th colspan="6">Treatment Plan</th></tr>
                    <tr><td colspan="6"><textarea name="treatment_plan" class="clinical-textarea" placeholder="Treatment plan..." <?php echo !$is_bhw_editing ? 'disabled' : ''; ?>><?php echo htmlspecialchars($clinical_data['treatment_plan'] ?? ''); ?></textarea></td></tr>
                </table>
            </div>

            <div class="section-block">
                <div class="section-header">Remarks & Recommendations</div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-textarea" placeholder="Clinical remarks..." <?php echo !$is_bhw_editing ? 'disabled' : ''; ?>><?php echo htmlspecialchars($clinical_data['remarks'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Recommendations</label>
                        <textarea name="recommendations" class="form-textarea" placeholder="Follow-up care recommendations..." <?php echo !$is_bhw_editing ? 'disabled' : ''; ?>><?php echo htmlspecialchars($clinical_data['recommendations'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="signature-section" style="margin-top: 40px; display: flex; justify-content: space-between; width: 100%; gap: 50px;">
                <div style="flex: 1; text-align: center; display: flex; flex-direction: column; align-items: center;">
                    <input type="text" name="patient_signature_name" style="width: 85%; border: none; border-bottom: 1px solid #000; text-align: center; font-family: inherit; font-size: 14px; padding-bottom: 5px; outline: none; background: transparent; font-weight: bold;" <?php echo !$is_bhw_editing ? 'disabled' : ''; ?> />
                    <span style="font-size: 11px; font-weight: bold; margin-top: 5px; text-transform: uppercase; letter-spacing: 0.5px;">Patient's Signature / Parent or Guardian</span>
                </div>
                <div style="flex: 1; text-align: center; display: flex; flex-direction: column; align-items: center;">
                    <input type="text" name="attending_officer_name" style="width: 85%; border: none; border-bottom: 1px solid #000; text-align: center; font-family: inherit; font-size: 14px; padding-bottom: 5px; outline: none; background: transparent; font-weight: bold;" <?php echo !$is_bhw_editing ? 'disabled' : ''; ?> />
                    <span style="font-size: 11px; font-weight: bold; margin-top: 5px; text-transform: uppercase; letter-spacing: 0.5px;">Attending BHW / Nurse / Medical Officer</span>
                </div>
            </div>
        </form>
        <?php if ($can_edit): ?>
        <div id="removeHistoryOptionModal" style="display: none; position: fixed; inset: 0; z-index: 50000;" class="items-center justify-center bg-black/45 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="removeHistoryOptionTitle" onclick="if (event.target === this) closeRemoveHistoryOptionModal()">
            <div class="w-full max-w-md rounded-xl border border-slate-300 bg-slate-100 p-6 shadow-2xl dark:border-slate-600 dark:bg-slate-800" onclick="event.stopPropagation()">
                <div class="mb-4 flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600"><i class="fas fa-trash-alt"></i></span>
                    <div>
                        <h2 id="removeHistoryOptionTitle" class="text-lg font-bold text-slate-900">Remove checklist item?</h2>
                        <p id="removeHistoryOptionMessage" class="mt-1 text-sm text-slate-600">This checked item will be removed from the record when you save.</p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-200 pt-4">
                    <button type="button" id="cancelRemoveHistoryOption" class="rounded-lg border border-slate-300 px-4 py-2 font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="button" id="confirmRemoveHistoryOption" class="rounded-lg bg-red-600 px-4 py-2 font-semibold text-white hover:bg-red-700">Remove</button>
                </div>
            </div>
        </div>
        <script>
            (function () {
                const options = document.getElementById('medical-history-options');
                const addButton = document.getElementById('add-medical-history-option');
                if (!options || !addButton) return;

                const removeModal = document.getElementById('removeHistoryOptionModal');
                const removeMessage = document.getElementById('removeHistoryOptionMessage');
                const confirmRemoveButton = document.getElementById('confirmRemoveHistoryOption');
                let pendingRemovalItem = null;

                document.body.appendChild(removeModal);

                const closeRemoveModal = () => {
                    removeModal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                    const recordContent = document.querySelector('main.medical-record-card');
                    if (recordContent) recordContent.style.filter = '';
                    pendingRemovalItem = null;
                };

                window.closeRemoveHistoryOptionModal = closeRemoveModal;

                const removeOption = (button) => {
                    const item = button.closest('[data-history-option]');
                    const checkbox = item?.querySelector('input[type="checkbox"]');
                    if (!item) return;
                    if (checkbox?.checked) {
                        const label = item.querySelector('input[name^="medical_history_labels["]')?.value.trim();
                        removeMessage.textContent = label
                            ? `Remove "${label}" from this record? It will be deleted when you save.`
                            : 'Remove this checked item from the record? It will be deleted when you save.';
                        pendingRemovalItem = item;
                        removeModal.style.position = 'fixed';
                        removeModal.style.inset = '0';
                        removeModal.style.zIndex = '50000';
                        removeModal.style.display = 'flex';
                        removeModal.style.alignItems = 'center';
                        removeModal.style.justifyContent = 'center';
                        const recordContent = document.querySelector('main.medical-record-card');
                        if (recordContent) recordContent.style.filter = 'blur(5px)';
                        document.body.style.overflow = 'hidden';
                        return;
                    }
                    item.remove();
                };

                confirmRemoveButton.addEventListener('click', () => {
                    pendingRemovalItem?.remove();
                    closeRemoveModal();
                });
                document.getElementById('cancelRemoveHistoryOption').addEventListener('click', closeRemoveModal);
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && removeModal.style.display === 'flex') closeRemoveModal();
                });

                options.querySelectorAll('.remove-history-option').forEach((button) => {
                    button.addEventListener('click', () => removeOption(button));
                });

                addButton.addEventListener('click', () => {
                    const optionId = `custom_${Date.now()}_${Math.random().toString(36).slice(2, 10)}`;
                    const item = document.createElement('div');
                    item.className = 'history-item';
                    item.dataset.historyOption = optionId;

                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.id = `history-${optionId}`;
                    checkbox.name = 'medical_history[]';
                    checkbox.value = optionId;
                    checkbox.checked = true;

                    const labelInput = document.createElement('input');
                    labelInput.type = 'text';
                    labelInput.name = `medical_history_labels[${optionId}]`;
                    labelInput.value = 'New checklist item';
                    labelInput.setAttribute('aria-label', 'Medical history option name');
                    labelInput.style.cssText = 'min-width:0;flex:1;border:0;border-bottom:1px dashed #64748b;background:transparent;padding:2px 4px;font:inherit;';

                    const removeButton = document.createElement('button');
                    removeButton.type = 'button';
                    removeButton.className = 'remove-history-option';
                    removeButton.setAttribute('aria-label', 'Remove checklist item');
                    removeButton.style.cssText = 'border:0;background:transparent;color:#b91c1c;cursor:pointer;';
                    removeButton.innerHTML = '&times;';
                    removeButton.addEventListener('click', () => removeOption(removeButton));

                    item.append(checkbox, labelInput, removeButton);
                    options.appendChild(item);
                    labelInput.focus();
                    labelInput.select();
                });
            }());
        </script>
        <?php endif; ?>
    <div class="action-buttons print-hide"><a href="<?php echo htmlspecialchars($back_url, ENT_QUOTES, 'UTF-8'); ?>" class="btn-secondary" title="<?php echo $is_resident_view ? 'Back to dashboard' : 'Back to patient profile'; ?>">
            <i class="fas fa-arrow-left"></i>
            <span><?php echo $is_resident_view ? 'BACK TO DASHBOARD' : 'BACK TO PROFILE'; ?></span>
        </a><button type="button" onclick="downloadMedicalRecord()" class="btn-primary" title="Download as PDF">
            <i class="fas fa-download"></i>
            <span>PDF</span>
        </button><?php if ($can_edit): ?>
        <button type="submit" form="medical-record-form" class="btn-primary" title="Save changes to medical record">
            <i class="fas fa-save"></i>
            <span>SAVE</span>
        </button>
        <?php else: ?><div style="padding: 12px 16px; background: #fef3c7; border: 1px solid #fcd34d; border-radius: 8px; font-size: 12px; color: #92400e; font-weight: 500;">
            <i class="fas fa-lock-open" style="margin-right: 6px;"></i>View-only access - Contact a health worker to edit
        </div>
        <?php endif; ?><button type="button" onclick="printMedicalRecord()" class="btn-primary" title="Print medical record">
            <i class="fas fa-print"></i>
            <span>PRINT</span>
        </button>
    </div>
    </main>

    <script>

        function toggleMobileRecordMenu(button) {
            const menu = button.closest('.no-print');
            if (!menu) return;
            const isOpen = menu.classList.toggle('is-open');
            button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            button.innerHTML = isOpen ? '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
        }
        
        const heightInput = document.querySelector('input[name="height"]');
        const weightInput = document.querySelector('input[name="weight"]');
        const bmiInput = document.querySelector('input[name="bmi"]');
        
        function calculateBMI() {
            const height = parseFloat(heightInput?.value) || 0;
            const weight = parseFloat(weightInput?.value) || 0;
            if (height > 0 && weight > 0) {
                const heightM = height / 100;
                const bmi = (weight / (heightM * heightM)).toFixed(1);
                if (bmiInput) bmiInput.value = bmi;
            }
        }
        
        if (heightInput && weightInput) {
            heightInput.addEventListener('input', calculateBMI);
            weightInput.addEventListener('input', calculateBMI);
            calculateBMI();
        }
        
        
        function downloadMedicalRecord() {
            const patientName = '<?php echo htmlspecialchars(addslashes($patient['full_name'])); ?>';
            const patientId = '<?php echo htmlspecialchars(addslashes($patient_id_display)); ?>';
            const originalTitle = document.title;
            
            document.title = `MedicalRecord_${patientId}_${patientName.replace(/\s+/g, '_')}.pdf`;
            window.print();
            
            setTimeout(() => {
                document.title = originalTitle;
            }, 500);
        }
        
        
        function printMedicalRecord() {
            window.print();
        }

        if (new URLSearchParams(window.location.search).get('print') === '1') {
            window.addEventListener('load', function() {
                setTimeout(function() {
                    window.print();
                }, 150);
            });
        }
        
        
        
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('medical-record-form');
            const canEdit = <?php echo json_encode($can_edit); ?>;
            const accessLevel = '<?php echo htmlspecialchars($access_level); ?>';

            const syncClinicalRecordQueue = async function() {
                if (!navigator.onLine) return;
                let queue = [];
                try {
                    queue = JSON.parse(localStorage.getItem('scancare_bhw_sync_queue_v1') || '[]');
                } catch (error) {
                    return;
                }

                const clinicalItems = queue.filter(item => item.type === 'save_clinical_record');
                if (!clinicalItems.length) return;
                const remaining = queue.filter(item => item.type !== 'save_clinical_record');

                for (const item of clinicalItems) {
                    try {
                        const formData = new FormData();
                        Object.entries(item.payload || {}).forEach(([key, value]) => {
                            if (Array.isArray(value)) {
                                value.forEach(entry => formData.append(key, String(entry)));
                            } else {
                                formData.append(key, String(value ?? ''));
                            }
                        });
                        const response = await fetch(item.endpoint, {
                            method: 'POST',
                            body: formData,
                            credentials: 'same-origin',
                            headers: { 'X-ScanCare-Offline-Sync': '1' }
                        });
                        const result = await response.json();
                        if (!response.ok || !result.success) throw new Error(result.message || 'Clinical record sync failed.');
                    } catch (error) {
                        remaining.push(item);
                    }
                }

                localStorage.setItem('scancare_bhw_sync_queue_v1', JSON.stringify(remaining));
            };

            window.addEventListener('online', syncClinicalRecordQueue);
            syncClinicalRecordQueue();
            
            if (form) {
                
                if (!canEdit) {
                    
                    const inputs = form.querySelectorAll('input, textarea, select, button[type="submit"]');
                    inputs.forEach(input => {
                        input.disabled = true;
                    });
                    
                    
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        alert('This medical record is read-only. You cannot make changes. Contact a health worker for editing.');
                        return false;
                    });
                    
                    
                    console.warn('RBAC VIEW-ONLY MODE: User has read-only access (Access Level: ' + accessLevel + ')');
                } else {
                    form.addEventListener('submit', async function(e) {
                        e.preventDefault();

                        const payload = {};
                        new FormData(form).forEach(function(value, key) {
                            if (key.endsWith('[]')) {
                                if (!Array.isArray(payload[key])) payload[key] = [];
                                payload[key].push(value);
                            } else {
                                payload[key] = value;
                            }
                        });

                        const endpoint = 'view_record.php' + window.location.search;
                        const queueEdit = function(message) {
                            const queue = JSON.parse(localStorage.getItem('scancare_bhw_sync_queue_v1') || '[]');
                            queue.push({
                                id: `clinical-record-${Date.now()}-${Math.random().toString(16).slice(2)}`,
                                type: 'save_clinical_record',
                                endpoint,
                                payload,
                                queuedAt: new Date().toISOString()
                            });
                            localStorage.setItem('scancare_bhw_sync_queue_v1', JSON.stringify(queue));
                            alert(message);
                        };

                        if (!navigator.onLine) {
                            queueEdit('📶 Clinical record saved locally. It will sync when the connection is restored.');
                            return;
                        }

                        const submitButton = document.querySelector('button[form="medical-record-form"]');
                        if (submitButton) submitButton.disabled = true;
                        try {
                            const response = await fetch(endpoint, {
                                method: 'POST',
                                body: new FormData(form),
                                credentials: 'same-origin'
                            });
                            if (!response.ok) throw new Error(`HTTP ${response.status}`);
                            window.location.href = endpoint + '&saved=1';
                        } catch (error) {
                            queueEdit('📶 The connection was lost. Your clinical record was saved locally and will sync when you reconnect.');
                        } finally {
                            if (submitButton) submitButton.disabled = false;
                        }
                    });
                }
            }
        });
    </script>

</body>
</html>

