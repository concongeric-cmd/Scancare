<?php
ob_start();
// ============================================================================
// BARANGAY HEALTH WORKER (BHW) DASHBOARD - STANDALONE FILE
// ============================================================================
// Complete self-contained dashboard for BHW portal in ScanCare application
// Features: Patient identification, consultations, inventory, appointments, SMS
// ============================================================================

session_start();
require_once 'db_config.php';

function ensureBhwMedicineArchiveColumn(PDO $pdo) {
    if (!hasTableColumn($pdo, 'medicine', 'is_deleted')) {
        $pdo->exec('ALTER TABLE medicine ADD COLUMN is_deleted TINYINT(1) NOT NULL DEFAULT 0');
    }
}

// Temporary POST debugger removed to ensure AJAX endpoints return clean JSON.
// Log POST submissions to error log instead of printing HTML (prevents JSON parse errors).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST received on dashboard_bhw.php: " . print_r($_POST, true));
}

// --- SEMAPHORE SMS HELPER (temporary placement) ---
function sendScanCareAlert($number, $message) {
    $apiKey = getEnvironmentValue('SEMAPHORE_API_KEY');
    $sender = getEnvironmentValue('SEMAPHORE_SENDER', 'SCANCARE');
    if ($apiKey === '') {
        return ['output' => null, 'error' => 'Semaphore is not configured', 'http_code' => 0];
    }

    $ch = curl_init();
    $parameters = array(
        'apikey' => $apiKey,
        'number' => $number,
        'message' => $message,
        'sendername' => $sender
    );
    curl_setopt($ch, CURLOPT_URL, 'https://api.semaphore.co/api/v4/messages');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    $caFile = getEnvironmentValue('CURL_CA_BUNDLE', ini_get('curl.cainfo'));
    if ($caFile === '' || !is_file($caFile)) {
        foreach (['/etc/ssl/certs/ca-certificates.crt', '/etc/pki/tls/certs/ca-bundle.crt', 'C:/php/extras/ssl/cacert.pem'] as $candidate) {
            if (is_file($candidate)) {
                $caFile = $candidate;
                break;
            }
        }
    }
    if ($caFile !== '' && is_file($caFile)) {
        curl_setopt($ch, CURLOPT_CAINFO, $caFile);
    }

    $output = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    return ['output' => $output, 'error' => $err, 'http_code' => $httpCode];
}

// Residents render a read-only community surveillance widget from their
// dashboard. Handle that request before the worker-only dashboard guard so a
// resident request cannot destroy the resident's authenticated session.
if ($_SERVER['REQUEST_METHOD'] === 'GET'
    && ($_GET['action'] ?? '') === 'get_surveillance_metrics'
    && ($_SESSION['user_type'] ?? '') === 'resident') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $residentMetricsPdo = getDatabaseConnection();
        if ($residentMetricsPdo === false) {
            throw new RuntimeException('Database connection failed.');
        }

        ensureSurveillanceMetricsSchema($residentMetricsPdo);
        $residentMetrics = getResidentSurveillanceMetrics($residentMetricsPdo);

        ob_clean();
        echo json_encode($residentMetrics ?: new stdClass(), JSON_UNESCAPED_SLASHES);
    } catch (Throwable $e) {
        error_log('RESIDENT SURVEILLANCE METRICS ERROR: ' . $e->getMessage());
        http_response_code(500);
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Unable to load surveillance metrics.']);
    }
    exit;
}

// Trigger a live alert when the Publish Updates button was clicked
// Log the Semaphore response instead of printing HTML so JSON responses remain valid.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish_updates'])) {
    $res = sendScanCareAlert('09984026995', 'Hi this is RHU II and your appointment is getting close to time');
    error_log("Semaphore send result: " . print_r($res, true));
}

// ============================================================================
// SESSION PROTECTION - BHW AUTHENTICATION GUARD
// ============================================================================
// Verify BHW is logged in with proper session variables
// Redirect unauthorized users cleanly to login.php
// ============================================================================

$sessionWorkerRole = strtolower($_SESSION['worker_role'] ?? $_SESSION['user_role'] ?? $_SESSION['user_type'] ?? 'guest');
$validBhwRoles = ['bhw', 'worker', 'health_worker'];
$sessionWorkerId = $_SESSION['worker_id'] ?? $_SESSION['user_id'] ?? null;

if ($sessionWorkerId === null ||
    ($_SESSION['user_type'] ?? '') !== 'health_worker' ||
    !in_array($sessionWorkerRole, $validBhwRoles, true)) {
    header('Location: login.php');
    exit;
}

// Retrieve worker identity from session
$worker_id = intval($sessionWorkerId);
$worker_name = $_SESSION['user_name'] ?? 'Health Worker';
$worker_email = $_SESSION['email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_reschedule_times') {
    header('Content-Type: application/json; charset=utf-8');
    $appointmentId = (int) ($_GET['appointment_id'] ?? 0);
    $date = trim((string) ($_GET['date'] ?? ''));

    if ($appointmentId <= 0 || $date === '') {
        echo json_encode(['success' => false, 'message' => 'Appointment ID and date are required.']);
        exit;
    }

    try {
        $pdo = getDatabaseConnection();
        if ($pdo === false) {
            throw new RuntimeException('Database connection failed');
        }

        $currentAppointmentStmt = $pdo->prepare(
            "SELECT resident_id, appointment_date, TIME_FORMAT(appointment_time, '%H:%i') AS appointment_time
             FROM appointment
             WHERE appointment_id = ?
             LIMIT 1"
        );
        $currentAppointmentStmt->execute([$appointmentId]);
        $currentAppointment = $currentAppointmentStmt->fetch(PDO::FETCH_ASSOC);
        $sameHourTime = $currentAppointment && $currentAppointment['appointment_date'] === $date
            ? $currentAppointment['appointment_time']
            : null;

                $stmt = $pdo->prepare(
                         "SELECT TIME_FORMAT(appointment_time, '%H:%i') AS appointment_time, COUNT(*) AS booked_count
             FROM appointment
             WHERE appointment_date = ?
               AND appointment_id != ?
                             AND status != 'cancelled'
                         GROUP BY appointment_time
                         "
        );
        $stmt->execute([$date, $appointmentId]);
        $occupiedTimes = [];
        $slotCounts = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $record) {
            $time = $record['appointment_time'];
            $remaining = max(0, 5 - (int) $record['booked_count']);
            $slotCounts[$time] = $remaining;
            if ($remaining === 0) {
                $occupiedTimes[] = $time;
            }
        }

        $residentConflictTimes = [];
        if ($currentAppointment) {
            $residentConflictStmt = $pdo->prepare(
                "SELECT DISTINCT TIME_FORMAT(appointment_time, '%H:%i') AS appointment_time
                 FROM appointment
                 WHERE resident_id = ?
                   AND appointment_date = ?
                   AND appointment_id != ?
                   AND status != 'cancelled'"
            );
            $residentConflictStmt->execute([
                $currentAppointment['resident_id'],
                $date,
                $appointmentId
            ]);
            $residentConflictTimes = array_column($residentConflictStmt->fetchAll(PDO::FETCH_ASSOC), 'appointment_time');
        }

        echo json_encode([
            'success' => true,
            'occupied_times' => $occupiedTimes,
            'slot_counts' => $slotCounts,
            'resident_conflict_times' => $residentConflictTimes,
            'same_hour_time' => $sameHourTime
        ]);
        exit;
    } catch (Throwable $e) {
        error_log('RESCHEDULE TIME AVAILABILITY ERROR: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Unable to load appointment times.']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_last_consultation') {
    header('Content-Type: application/json; charset=utf-8');
    $residentId = (int) ($_GET['resident_id'] ?? 0);
    if ($residentId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'A valid resident ID is required.']);
        exit;
    }

    try {
        $historyPdo = getDatabaseConnection();
        $historyStmt = $historyPdo->prepare(
            'SELECT diagnosis, treatment, notes, COALESCE(created_at, visit_date) AS saved_at
             FROM medical_history
             WHERE user_id = ?
             ORDER BY COALESCE(created_at, visit_date) DESC, history_id DESC
             LIMIT 1'
        );
        $historyStmt->execute([$residentId]);
        $savedRecords = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

        try {
            $patientRecordStmt = $historyPdo->prepare(
                'SELECT diagnosis, treatment, notes, COALESCE(created_at, visit_date) AS saved_at
                 FROM patient_record
                 WHERE resident_id = ?
                 ORDER BY COALESCE(created_at, visit_date) DESC, record_id DESC
                 LIMIT 1'
            );
            $patientRecordStmt->execute([$residentId]);
            $patientRecord = $patientRecordStmt->fetch(PDO::FETCH_ASSOC);
            if ($patientRecord) {
                $savedRecords[] = $patientRecord;
            }
        } catch (Throwable $patientRecordError) {
            error_log('LAST CONSULTATION PATIENT_RECORD LOOKUP ERROR: ' . $patientRecordError->getMessage());
        }

        usort($savedRecords, static function ($left, $right) {
            return (strtotime((string) ($left['saved_at'] ?? '')) ?: 0)
                <=> (strtotime((string) ($right['saved_at'] ?? '')) ?: 0);
        });

        $previousVisit = null;
        foreach ($savedRecords as $savedRecord) {
            $treatmentData = json_decode((string) ($savedRecord['treatment'] ?? ''), true);
            $treatmentPlan = is_array($treatmentData)
                ? trim((string) ($treatmentData['treatment_plan'] ?? ''))
                : trim((string) ($savedRecord['treatment'] ?? ''));
            $diagnosis = trim((string) ($savedRecord['diagnosis'] ?? ''));
            $clinicalNotes = trim((string) ($savedRecord['notes'] ?? ''));

            if ($diagnosis !== '' || $treatmentPlan !== '' || $clinicalNotes !== '') {
                $previousVisit = $previousVisit ?? [
                    'diagnosis' => '',
                    'treatment_plan' => '',
                    'clinical_notes' => ''
                ];
                if ($diagnosis !== '') {
                    $previousVisit['diagnosis'] = $diagnosis;
                }
                if ($treatmentPlan !== '') {
                    $previousVisit['treatment_plan'] = $treatmentPlan;
                }
                if ($clinicalNotes !== '') {
                    $previousVisit['clinical_notes'] = $clinicalNotes;
                }
            }
        }

        echo json_encode([
            'success' => true,
            'previous_visit' => $previousVisit
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        error_log('LAST CONSULTATION LOAD ERROR: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Unable to load the previous consultation.']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_consultation') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $consultationResidentId = (int) ($_POST['resident_id'] ?? 0);
        $consultationAppointmentId = (int) ($_POST['appointment_id'] ?? 0);
        $consultationIsWalkIn = ($_POST['walk_in'] ?? '') === '1';
        if ($consultationResidentId <= 0) {
            throw new RuntimeException('Please select a patient first.');
        }

        $consultationPdo = getDatabaseConnection();
        if (!($consultationPdo instanceof PDO) || !ensureMedicalHistoryAppointmentColumns($consultationPdo)) {
            throw new RuntimeException('Medical history storage is unavailable.');
        }

        $consultationPdo->beginTransaction();
        if ($consultationIsWalkIn) {
            $consultationAppointmentId = 0;
            $walkInAppointmentStmt = $consultationPdo->prepare("INSERT INTO appointment (resident_id, health_worker_id, appointment_date, appointment_time, purpose, status, notes, created_at, updated_at) VALUES (?, ?, CURDATE(), CURTIME(), 'Walk-in Consultation', 'approved', 'Walk-in consultation', NOW(), NOW())");
            $walkInAppointmentStmt->execute([$consultationResidentId, $worker_id]);
            $consultationAppointmentId = (int) $consultationPdo->lastInsertId();
        }

        $existingRecord = null;
        $visitDate = date('Y-m-d H:i:s');
        $visitType = 'Consultation';
        $appointmentRecord = null;

        if ($consultationAppointmentId > 0) {
            $appointmentStmt = $consultationPdo->prepare('SELECT resident_id, appointment_date, appointment_time, purpose, status FROM appointment WHERE appointment_id = ? LIMIT 1 FOR UPDATE');
            $appointmentStmt->execute([$consultationAppointmentId]);
            $appointmentRecord = $appointmentStmt->fetch(PDO::FETCH_ASSOC);
            if (!$appointmentRecord || (int) $appointmentRecord['resident_id'] !== $consultationResidentId) {
                throw new RuntimeException('The selected appointment does not belong to this patient.');
            }
            if (in_array(strtolower((string) $appointmentRecord['status']), ['no_show', 'cancelled'], true)) {
                throw new RuntimeException('This appointment can no longer receive a consultation record.');
            }

            $visitDate = $appointmentRecord['appointment_date'] . ' ' . $appointmentRecord['appointment_time'];
            $visitType = trim((string) ($appointmentRecord['purpose'] ?? '')) ?: 'Consultation';

            $existingStmt = $consultationPdo->prepare('SELECT history_id, treatment FROM medical_history WHERE appointment_id = ? AND user_id = ? ORDER BY history_id DESC LIMIT 1 FOR UPDATE');
            $existingStmt->execute([$consultationAppointmentId, $consultationResidentId]);
            $existingRecord = $existingStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        $clinicalData = $existingRecord && is_string($existingRecord['treatment'] ?? null) ? json_decode($existingRecord['treatment'], true) : [];
        $clinicalData = is_array($clinicalData) ? $clinicalData : [];
        $clinicalData['treatment_plan'] = trim($_POST['treatment_plan'] ?? '');
        $diagnosis = trim($_POST['diagnosis'] ?? '');
        $clinicalNotes = trim($_POST['clinical_notes'] ?? '');
        $consultedAt = (string) $consultationPdo->query('SELECT NOW()')->fetchColumn();

        if ($existingRecord) {
            $saveConsultation = $consultationPdo->prepare('UPDATE medical_history SET visit_date = ?, consulted_at = ?, visit_type = ?, diagnosis = ?, treatment = ?, notes = ?, bhw_name = ?, status = ?, updated_at = NOW() WHERE history_id = ? AND user_id = ? AND appointment_id = ?');
            $saveConsultation->execute([$visitDate, $consultedAt, $visitType, $diagnosis, json_encode($clinicalData, JSON_UNESCAPED_UNICODE), $clinicalNotes, $worker_name, 'completed', $existingRecord['history_id'], $consultationResidentId, $consultationAppointmentId]);
        } else {
            $saveConsultation = $consultationPdo->prepare('INSERT INTO medical_history (user_id, appointment_id, visit_date, consulted_at, visit_type, bhw_name, diagnosis, treatment, notes, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
            $saveConsultation->execute([$consultationResidentId, $consultationAppointmentId > 0 ? $consultationAppointmentId : null, $visitDate, $consultedAt, $visitType, $worker_name, $diagnosis, json_encode($clinicalData, JSON_UNESCAPED_UNICODE), $clinicalNotes, 'completed']);
        }

        if ($consultationAppointmentId > 0) {
            $completeAppointment = $consultationPdo->prepare("UPDATE appointment SET status = 'completed', health_worker_id = ?, updated_at = NOW() WHERE appointment_id = ? AND resident_id = ?");
            $completeAppointment->execute([$worker_id, $consultationAppointmentId, $consultationResidentId]);
        }

        $consultationPdo->commit();
        echo json_encode([
            'success' => true,
            'message' => $consultationIsWalkIn
                ? 'Walk-in consultation saved as a new appointment and marked completed.'
                : ($consultationAppointmentId > 0 ? 'Consultation saved and appointment marked completed.' : 'Consultation saved successfully.'),
            'appointment_id' => $consultationAppointmentId ?: null,
            'walk_in' => $consultationIsWalkIn,
            'visit_date' => $visitDate,
            'consulted_at' => $consultedAt
        ]);
    } catch (Throwable $e) {
        if (isset($consultationPdo) && $consultationPdo instanceof PDO && $consultationPdo->inTransaction()) {
            $consultationPdo->rollBack();
        }
        error_log('CONSULTATION SAVE ERROR: ' . $e->getMessage());
        $isRequestError = $e instanceof RuntimeException;
        http_response_code($isRequestError ? 400 : 500);
        echo json_encode([
            'success' => false,
            'message' => $isRequestError
                ? $e->getMessage()
                : 'A server error prevented the consultation from being saved.'
        ]);
    }
    exit;
}

$announcementStatus = $_GET['announcement_status'] ?? '';
$requestedAnnouncementTab = $_GET['announcement_tab'] ?? 'create';
$requestedAnnouncementState = $_GET['announcement_state'] ?? 'upcoming';
$announcementTab = in_array($requestedAnnouncementTab, ['create', 'manage'], true) ? $requestedAnnouncementTab : 'create';
$announcementState = in_array($requestedAnnouncementState, ['upcoming', 'now', 'done'], true) ? $requestedAnnouncementState : 'upcoming';
$manual_search_token_expires = time() + 3600;
$manual_search_token_payload = $worker_id . '|' . $manual_search_token_expires;
$manual_search_token = base64_encode($manual_search_token_payload . '|' . hash_hmac('sha256', $manual_search_token_payload, DB_NAME . '|' . DB_USER . '|' . DB_PASS));

// A resident QR scanned by a BHW is the QR equivalent of selecting a result
// from Manual Entry.  Resolve its patient code here and open the BHW profile
// directly; do not send the worker through resident_qr.php.
$scannedPatientCode = trim((string) ($_GET['qr_code'] ?? ''));
if ($scannedPatientCode !== '') {
    try {
        $scanPdo = getDatabaseConnection();
        $scanStmt = $scanPdo->prepare('SELECT resident_id FROM resident WHERE patient_code = ? LIMIT 1');
        $scanStmt->execute([$scannedPatientCode]);
        $scannedResidentId = (int) ($scanStmt->fetchColumn() ?: 0);

        if ($scannedResidentId > 0) {
            $_SESSION['qr_access_resident_id'] = $scannedResidentId;
            $_SESSION['qr_access_timestamp'] = time();
            header('Location: dashboard_bhw.php?module=patient-profile&patient_id=' . $scannedResidentId . '&qr_scan=1');
            exit;
        }
    } catch (Throwable $e) {
        error_log('BHW QR patient lookup error: ' . $e->getMessage());
    }

    header('Location: dashboard_bhw.php?module=identify-patient&qr_error=not_found');
    exit;
}

try {
    $announcementPdo = getDatabaseConnection();
    if ($announcementPdo !== false) {
        $announcementPdo->exec("CREATE TABLE IF NOT EXISTS health_announcements (
            announcement_id INT AUTO_INCREMENT PRIMARY KEY,
            worker_id INT NOT NULL,
            title VARCHAR(150) NOT NULL,
            message TEXT NOT NULL,
            service_date DATE DEFAULT NULL,
            location VARCHAR(150) DEFAULT NULL,
            state VARCHAR(20) NOT NULL DEFAULT 'upcoming',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_active_date (is_active, service_date, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        if (!hasTableColumn($announcementPdo, 'health_announcements', 'state')) {
            $announcementPdo->exec("ALTER TABLE health_announcements ADD COLUMN state VARCHAR(20) NOT NULL DEFAULT 'upcoming'");
        }

        $today = date('Y-m-d');
        $announcementPdo->prepare("UPDATE health_announcements
            SET state = CASE
                WHEN service_date < ? THEN 'done'
                WHEN service_date = ? THEN 'now'
                ELSE 'upcoming'
            END
            WHERE service_date IS NOT NULL")->execute([$today, $today]);
    }
} catch (Exception $e) {
    error_log('ANNOUNCEMENT TABLE SETUP ERROR: ' . $e->getMessage());
}

function resolveAnnouncementStateByDate($serviceDate, $selectedState) {
    if ($serviceDate === '') {
        return $selectedState;
    }

    $parsedDate = DateTime::createFromFormat('!Y-m-d', $serviceDate);
    if (!$parsedDate || $parsedDate->format('Y-m-d') !== $serviceDate) {
        return $selectedState;
    }

    $today = date('Y-m-d');
    if ($serviceDate < $today) {
        return 'done';
    }
    if ($serviceDate === $today) {
        return 'now';
    }
    return 'upcoming';
}

// Initialize module states for tab navigation
$active_module = $_GET['module'] ?? 'appointments';
$consultation_resident_id = (int) ($_GET['resident_id'] ?? 0);
$consultation_appointment_id = (int) ($_GET['appointment_id'] ?? 0);
$consultation_walk_in = ($_GET['walk_in'] ?? '') === '1';
$patient_identified = false;
$patient_data = null;

// ================================================================
// LOAD PATIENT PROFILE DATA IF PATIENT_ID IS PROVIDED
// ================================================================
if (isset($_GET['patient_id']) && $active_module === 'patient-profile') {
    $patient_id = intval($_GET['patient_id']);
    
    if ($patient_id > 0) {
        try {
            $pdo = getDatabaseConnection();
            $medicalHistoryLinkColumnsReady = $pdo instanceof PDO && ensureMedicalHistoryAppointmentColumns($pdo);
            error_log("DEBUG: Loading patient profile for resident_id = {$patient_id}");
            
            // Fetch patient basic information
            $patient_query = "
                SELECT 
                    resident_id,
                    patient_code,
                    full_name,
                    email,
                    contact_number,
                    date_of_birth,
                    address
                FROM resident
                WHERE resident_id = ?
                LIMIT 1
            ";
            
            $stmt = $pdo->prepare($patient_query);
            if (!$stmt) {
                throw new Exception("Failed to prepare resident query: " . implode(", ", $pdo->errorInfo()));
            }
            
            if (!$stmt->execute([$patient_id])) {
                throw new Exception("Failed to execute resident query: " . implode(", ", $stmt->errorInfo()));
            }
            
            $resident = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("DEBUG: Resident query result: " . ($resident ? "Found resident" : "No resident found"));
            
            if ($resident) {
                $resident['patient_code'] = ensureResidentPatientCode($pdo, (int)$resident['resident_id']);
                $patient_identified = true;
                $patient_data = $resident;
                error_log("DEBUG: Patient identified - {$resident['full_name']}");
                
                // Fetch medical history (uses user_id foreign key)
                $medicalHistoryLinkFields = $medicalHistoryLinkColumnsReady
                    ? 'appointment_id, consulted_at,'
                    : 'NULL AS appointment_id, NULL AS consulted_at,';
                $history_query = "
                    SELECT 
                        history_id,
                        user_id,
                        visit_date,
                        visit_type,
                        {$medicalHistoryLinkFields}
                        bhw_name,
                        diagnosis,
                        treatment,
                        notes,
                        created_at
                    FROM medical_history
                    WHERE user_id = ?
                    ORDER BY created_at DESC
                    LIMIT 10
                ";
                
                $stmt = $pdo->prepare($history_query);
                if (!$stmt) {
                    throw new Exception("Failed to prepare medical_history query: " . implode(", ", $pdo->errorInfo()));
                }
                
                if (!$stmt->execute([$patient_id])) {
                    throw new Exception("Failed to execute medical_history query: " . implode(", ", $stmt->errorInfo()));
                }
                
                $medical_history = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                $patient_data['medical_history'] = $medical_history;
                error_log("DEBUG: Medical history records found: " . count($medical_history));
                
                // Fetch follow-up check-ups (flagged appointments)
                $followup_query = "
                    SELECT 
                        appointment_id,
                        resident_id,
                        appointment_date,
                        appointment_time,
                        purpose,
                        status,
                        updated_at
                    FROM appointment
                    WHERE resident_id = ? AND status = 'flagged'
                    ORDER BY appointment_date ASC, appointment_time ASC
                    LIMIT 10
                ";
                
                $stmt = $pdo->prepare($followup_query);
                if (!$stmt) {
                    throw new Exception("Failed to prepare follow-up query: " . implode(", ", $pdo->errorInfo()));
                }
                
                if (!$stmt->execute([$patient_id])) {
                    throw new Exception("Failed to execute follow-up query: " . implode(", ", $stmt->errorInfo()));
                }
                
                $followup_checkups = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                $patient_data['followup_checkups'] = $followup_checkups;
                error_log("DEBUG: Follow-up check-up records found: " . count($followup_checkups));
                
                // Fetch appointments
                $appointment_query = "
                    SELECT 
                        appointment_id,
                        resident_id,
                        appointment_date,
                        appointment_time,
                        purpose,
                        status,
                        updated_at
                    FROM appointment
                    WHERE resident_id = ?
                    ORDER BY appointment_date DESC, appointment_time DESC
                    LIMIT 10
                ";
                
                $stmt = $pdo->prepare($appointment_query);
                if (!$stmt) {
                    throw new Exception("Failed to prepare appointment query: " . implode(", ", $pdo->errorInfo()));
                }
                
                if (!$stmt->execute([$patient_id])) {
                    throw new Exception("Failed to execute appointment query: " . implode(", ", $stmt->errorInfo()));
                }
                
                $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                $patient_data['appointments'] = $appointments;
                error_log("DEBUG: Appointment records found: " . count($appointments));
                
                error_log("✓ Patient profile loaded successfully for ID: {$patient_id}");
            } else {
                error_log("DEBUG: Resident not found for ID: {$patient_id}");
                $patient_identified = false;
                $patient_data = null;
            }
        } catch (Exception $e) {
            error_log("❌ Error loading patient profile: " . $e->getMessage());
            error_log("Exception trace: " . $e->getTraceAsString());
            $patient_identified = false;
            $patient_data = null;
        }
    }
}

// Determine connectivity state (frontend simulation - hardcoded)
$is_online = true; // In production, detect via JavaScript or backend logic

// ================================================================
// FETCH APPROVED PATIENTS FOR CONSULTATION DROPDOWN
// ================================================================
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

    if ($consultation_appointment_id > 0) {
        $requestedAppointmentStmt = $pdo->prepare("SELECT a.appointment_id, a.resident_id, r.full_name AS patient_name, r.date_of_birth, r.sex, r.contact_number, r.email, a.appointment_date, a.appointment_time, a.purpose FROM appointment a INNER JOIN resident r ON a.resident_id = r.resident_id WHERE a.appointment_id = ? LIMIT 1");
        $requestedAppointmentStmt->execute([$consultation_appointment_id]);
        $requestedAppointment = $requestedAppointmentStmt->fetch(PDO::FETCH_ASSOC);
        if ($requestedAppointment) {
            $consultation_resident_id = (int) $requestedAppointment['resident_id'];
            $appointmentAlreadyListed = false;
            foreach ($approved_patients as $approvedPatient) {
                if ((int) ($approvedPatient['appointment_id'] ?? 0) === $consultation_appointment_id) {
                    $appointmentAlreadyListed = true;
                    break;
                }
            }
            if (!$appointmentAlreadyListed) {
                array_unshift($approved_patients, $requestedAppointment);
            }
        }
    }

    if ($consultation_resident_id > 0) {
        $alreadyListed = false;
        foreach ($approved_patients as $approvedPatient) {
            if ((int) ($approvedPatient['resident_id'] ?? 0) === $consultation_resident_id) {
                $alreadyListed = true;
                break;
            }
        }

        if (!$alreadyListed || $consultation_walk_in) {
            $directPatientStmt = $pdo->prepare('SELECT resident_id, full_name AS patient_name, date_of_birth, sex, contact_number, email FROM resident WHERE resident_id = ? LIMIT 1');
            $directPatientStmt->execute([$consultation_resident_id]);
            $directPatient = $directPatientStmt->fetch(PDO::FETCH_ASSOC);
            if ($directPatient) {
                $approved_patients[] = array_merge($directPatient, [
                    'appointment_id' => ($consultation_walk_in ? 'walkin-' : 'resident-') . $consultation_resident_id,
                    'appointment_date' => date('Y-m-d'),
                    'appointment_time' => date('H:i:s'),
                    'purpose' => $consultation_walk_in ? 'Walk-in Consultation' : 'Direct patient consultation',
                    'is_walk_in' => $consultation_walk_in
                ]);
            }
        }
    }

    if ($active_module === 'consultation' && empty($approved_patients)) {
        $residentStmt = $pdo->query(
            "SELECT resident_id, full_name AS patient_name, date_of_birth, sex, contact_number, email
             FROM resident
             ORDER BY full_name ASC
             LIMIT 500"
        );
        foreach ($residentStmt->fetchAll(PDO::FETCH_ASSOC) as $resident) {
            $approved_patients[] = array_merge($resident, [
                'appointment_id' => 'walkin-' . (int) $resident['resident_id'],
                'appointment_date' => date('Y-m-d'),
                'appointment_time' => date('H:i:s'),
                'purpose' => 'Walk-in Consultation',
                'is_walk_in' => true
            ]);
        }
    }
} catch (Exception $e) {
    error_log("APPROVED PATIENTS QUERY ERROR: " . $e->getMessage());
    $approved_patients = [];
}

// ================================================================
// LOAD SAVED SURVEILLANCE METRICS
// ================================================================
$surveillance_metrics = [];
$highest_surveillance_metric = null;
try {
    $pdo = getDatabaseConnection();
    if ($pdo !== false) {
        ensureSurveillanceMetricsSchema($pdo);
        $pdo->exec("CREATE TABLE IF NOT EXISTS surveillance_metrics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            worker_id INT NOT NULL,
            reporting_period VARCHAR(255) DEFAULT NULL,
            disease_name VARCHAR(255) DEFAULT NULL,
            case_count INT DEFAULT 0,
            trend_status VARCHAR(255) DEFAULT NULL,
            meta_notes TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("ALTER TABLE surveillance_metrics MODIFY disease_name VARCHAR(255) DEFAULT NULL");

        $stmt = $pdo->prepare("SELECT * FROM surveillance_metrics WHERE worker_id = ? AND case_count > 0 AND disease_name IS NOT NULL AND TRIM(disease_name) <> '' AND LOWER(TRIM(disease_name)) <> 'record entry' ORDER BY case_count DESC, created_at DESC");
        $stmt->execute([$worker_id]);
        $surveillance_metrics = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $surveillance_metrics = array_values(array_filter($surveillance_metrics, static function ($metric) {
            $diseaseName = trim((string)($metric['disease_name'] ?? ''));
            return $diseaseName !== '' && strcasecmp($diseaseName, 'Record Entry') !== 0;
        }));
        $highest_surveillance_metric = $surveillance_metrics[0] ?? null;
    }
} catch (Exception $e) {
    error_log("SURVEILLANCE METRICS LOAD ERROR: " . $e->getMessage());
    $surveillance_metrics = [];
}

// ================================================================
// GET: Return latest surveillance metrics as JSON (simple API)
// ================================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (isset($_GET['action']) && $_GET['action'] === 'get_surveillance_metrics')) {
    header('Content-Type: application/json');
    try {
        $pdoApi = getDatabaseConnection();
        if ($pdoApi === false) {
            echo json_encode(['success' => false, 'message' => 'Database connection failed']);
            exit;
        }

        $stmtApi = $pdoApi->prepare("SELECT * FROM surveillance_metrics WHERE worker_id = ? AND case_count > 0 AND disease_name IS NOT NULL AND TRIM(disease_name) <> '' AND LOWER(TRIM(disease_name)) <> 'record entry' ORDER BY case_count DESC, created_at DESC");
        $stmtApi->execute([$worker_id]);
        echo json_encode($stmtApi->fetchAll(PDO::FETCH_ASSOC) ?: []);
        exit;
    } catch (Throwable $e) {
        error_log('GET SURVEILLANCE METRICS ERROR: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Unable to load surveillance metrics.']);
        exit;
    }
}

// ================================================================
// HANDLE POST ACTIONS
// ================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $isOfflineSync = ($_SERVER['HTTP_X_SCANCARE_OFFLINE_SYNC'] ?? '') === '1';

    if ($action === 'create_announcement') {
        $title = trim((string)($_POST['title'] ?? ''));
        $message = trim((string)($_POST['message'] ?? ''));
        $serviceDate = trim((string)($_POST['service_date'] ?? ''));
        $location = trim((string)($_POST['location'] ?? ''));
        $state = trim((string)($_POST['state'] ?? 'upcoming'));

        if ($title === '' || $message === '' || !in_array($state, ['upcoming', 'now', 'done'], true)) {
            if ($isOfflineSync) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Announcement fields are incomplete.']);
                exit;
            }
            header('Location: dashboard_bhw.php?module=announcements&announcement_status=missing');
            exit;
        }

        $state = resolveAnnouncementStateByDate($serviceDate, $state);

        try {
            $announcementPdo = getDatabaseConnection();
            $announcementStmt = $announcementPdo->prepare(
                'INSERT INTO health_announcements (worker_id, title, message, service_date, location, state) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $announcementStmt->execute([$worker_id, $title, $message, $serviceDate !== '' ? $serviceDate : null, $location !== '' ? $location : null, $state]);
            if ($isOfflineSync) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true, 'message' => 'Announcement published.']);
                exit;
            }
            header('Location: dashboard_bhw.php?module=announcements&announcement_status=published');
            exit;
        } catch (Exception $e) {
            error_log('CREATE ANNOUNCEMENT ERROR: ' . $e->getMessage());
            if ($isOfflineSync) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Announcement could not be published.']);
                exit;
            }
            header('Location: dashboard_bhw.php?module=announcements&announcement_status=error');
            exit;
        }
    }

    if ($action === 'update_announcement') {
        $announcementId = intval($_POST['announcement_id'] ?? 0);
        $title = trim((string)($_POST['title'] ?? ''));
        $message = trim((string)($_POST['message'] ?? ''));
        $serviceDate = trim((string)($_POST['service_date'] ?? ''));
        $location = trim((string)($_POST['location'] ?? ''));
        $state = trim((string)($_POST['state'] ?? 'upcoming'));

        if ($announcementId <= 0 || $title === '' || $message === '' || !in_array($state, ['upcoming', 'now', 'done'], true)) {
            if ($isOfflineSync) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Announcement fields are incomplete.']);
                exit;
            }
            header('Location: dashboard_bhw.php?module=announcements&announcement_tab=manage&announcement_status=missing');
            exit;
        }

        $state = resolveAnnouncementStateByDate($serviceDate, $state);

        try {
            $announcementPdo = getDatabaseConnection();
            $announcementStmt = $announcementPdo->prepare(
                'UPDATE health_announcements SET title = ?, message = ?, service_date = ?, location = ?, state = ? WHERE announcement_id = ?'
            );
            $announcementStmt->execute([$title, $message, $serviceDate !== '' ? $serviceDate : null, $location !== '' ? $location : null, $state, $announcementId]);
            if ($isOfflineSync) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true, 'message' => 'Announcement updated.']);
                exit;
            }
            header('Location: dashboard_bhw.php?module=announcements&announcement_tab=manage&announcement_state=' . urlencode($state) . '&announcement_status=updated');
            exit;
        } catch (Exception $e) {
            error_log('UPDATE ANNOUNCEMENT ERROR: ' . $e->getMessage());
            if ($isOfflineSync) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Announcement could not be updated.']);
                exit;
            }
            header('Location: dashboard_bhw.php?module=announcements&announcement_tab=manage&announcement_status=error');
            exit;
        }
    }

    if ($action === 'delete_announcement') {
        header('Content-Type: application/json; charset=utf-8');
        $announcementId = intval($_POST['announcement_id'] ?? 0);
        if ($announcementId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid announcement ID.']);
            exit;
        }

        try {
            $announcementPdo = getDatabaseConnection();
            if ($announcementPdo === false) {
                throw new RuntimeException('Database connection failed.');
            }

            $deleteStmt = $announcementPdo->prepare(
                'DELETE FROM health_announcements WHERE announcement_id = ?'
            );
            $deleteStmt->execute([$announcementId]);
            if ($deleteStmt->rowCount() === 0) {
                echo json_encode(['success' => false, 'message' => 'Announcement not found.']);
                exit;
            }

            echo json_encode(['success' => true, 'message' => 'Announcement permanently deleted.']);
        } catch (Throwable $e) {
            error_log('DELETE ANNOUNCEMENT ERROR: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Announcement could not be deleted.']);
        }
        exit;
    }

    if ($action === 'delete_metric') {
        header('Content-Type: application/json; charset=utf-8');
        $metricIdInput = $_POST['id'] ?? ($_POST['disease_id'] ?? ($_POST['metric_id'] ?? null));
        $metric_id = is_scalar($metricIdInput) ? intval($metricIdInput) : 0;
        $disease_name = isset($_POST['disease_name']) && is_string($_POST['disease_name']) ? trim($_POST['disease_name']) : '';
        if ($metric_id <= 0 && $disease_name === '') {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid disease record ID.']);
            exit;
        }

        try {
            $pdo = getDatabaseConnection();
            if ($pdo === false) {
                ensureSurveillanceMetricsSchema($pdo);
                throw new RuntimeException('Database connection failed.');
            }
            if ($metric_id > 0) {
                $deleteStmt = $pdo->prepare('DELETE FROM surveillance_metrics WHERE id = ? AND worker_id = ?');
                $deleteStmt->execute([$metric_id, $worker_id]);
            } else {
                $deleteStmt = $pdo->prepare('DELETE FROM surveillance_metrics WHERE disease_name = ? AND worker_id = ?');
                $deleteStmt->execute([$disease_name, $worker_id]);
            }
            ob_clean();
            echo json_encode(['success' => true, 'deleted_id' => $metric_id, 'disease_name' => $disease_name]);
        } catch (Exception $e) {
            error_log('DELETE SURVEILLANCE METRIC ERROR: ' . $e->getMessage());
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'search_patient') {
        header('Content-Type: application/json; charset=utf-8');
        $searchTerm = trim($_POST['search_term'] ?? $_POST['search'] ?? '');

        if ($searchTerm === '') {
            echo json_encode(['success' => false, 'message' => 'Please enter a name, email, phone number, or resident ID.']);
            exit;
        }

        try {
            $searchPdo = getDatabaseConnection();
            if ($searchPdo === false) {
                throw new RuntimeException('Database connection unavailable.');
            }
            ensureResidentPatientCodeColumn($searchPdo);
            $searchLike = '%' . $searchTerm . '%';
            $phoneLike = '%' . str_replace([' ', '-', '+'], '', $searchTerm) . '%';
            $searchStmt = $searchPdo->prepare(
                "SELECT resident_id, patient_code, full_name, email, contact_number, date_of_birth
                 FROM resident
                 WHERE LOWER(full_name) LIKE LOWER(?)
                    OR LOWER(email) LIKE LOWER(?)
                    OR REPLACE(REPLACE(REPLACE(contact_number, ' ', ''), '-', ''), '+', '') LIKE ?
                    OR patient_code LIKE ?
                    OR CAST(resident_id AS CHAR) LIKE ?
                 LIMIT 10"
            );
            $searchStmt->execute([$searchLike, $searchLike, $phoneLike, $searchLike, $searchLike]);
            $patients = [];

            foreach ($searchStmt->fetchAll(PDO::FETCH_ASSOC) as $patient) {
                $patientCode = ensureResidentPatientCode($searchPdo, (int)$patient['resident_id']);
                $dob = 'N/A';
                if (!empty($patient['date_of_birth'])) {
                    $dobDate = DateTime::createFromFormat('Y-m-d', $patient['date_of_birth']);
                    $dob = $dobDate ? $dobDate->format('M d, Y') : (string)$patient['date_of_birth'];
                }
                $patients[] = [
                    'id' => (int)$patient['resident_id'],
                    'patient_code' => $patientCode ?: str_pad((string)$patient['resident_id'], 6, '0', STR_PAD_LEFT),
                    'name' => htmlspecialchars($patient['full_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
                    'email' => htmlspecialchars($patient['email'] ?? 'N/A', ENT_QUOTES, 'UTF-8'),
                    'phone' => htmlspecialchars($patient['contact_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8'),
                    'dob' => $dob
                ];
            }

            echo json_encode([
                'success' => !empty($patients),
                'patients' => $patients,
                'data' => $patients,
                'message' => !empty($patients) ? count($patients) . ' patient result(s) found.' : 'No patient found matching that ID or details.'
            ]);
        } catch (Throwable $e) {
            error_log('DASHBOARD PATIENT SEARCH ERROR: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Patient search failed. Please try again.']);
        }
        exit;
    }
    
    // ================================================================
    // HANDLE UPDATE APPOINTMENT STATUS
    // ================================================================
    if ($action === 'update_appointment_status') {
        header('Content-Type: application/json');
        $appointment_id = intval($_POST['appointment_id'] ?? 0);
        $resident_id = intval($_POST['resident_id'] ?? 0);
        $new_status = trim($_POST['status'] ?? '');
        
        if ($appointment_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
            exit;
        }
        
        if (empty($new_status)) {
            echo json_encode(['success' => false, 'message' => 'Status is required']);
            exit;
        }
        
        try {
            $pdo = getDatabaseConnection();
            
            if ($pdo === false) {
                echo json_encode(['success' => false, 'message' => 'Database connection failed']);
                exit;
            }

            $pdo->beginTransaction();
            $appointmentStmt = $pdo->prepare('SELECT resident_id, appointment_date, appointment_time, purpose, status FROM appointment WHERE appointment_id = ? LIMIT 1 FOR UPDATE');
            $appointmentStmt->execute([$appointment_id]);
            $appointmentRecord = $appointmentStmt->fetch(PDO::FETCH_ASSOC);
            if (!$appointmentRecord) {
                throw new RuntimeException('Appointment not found');
            }
            if ($new_status === 'completed') {
                throw new RuntimeException('Save the consultation record before completing this appointment.');
            }
            if ($new_status === 'completed' && $appointmentRecord['status'] === 'completed') {
                throw new RuntimeException('This appointment has already been completed');
            }
            $resident_id = (int) $appointmentRecord['resident_id'];
            $appointmentPurpose = $appointmentRecord['purpose'] ?? 'General Consultation';
            
            // ========================================================================
            // CRITICAL: If status is 'completed' (Rendered), CREATE MEDICAL RECORD
            // ========================================================================
            if ($new_status === 'completed' && $resident_id > 0) {
                try {
                    $existingHistoryId = null;
                    if (hasTableColumn($pdo, 'medical_history', 'user_id')
                        && hasTableColumn($pdo, 'medical_history', 'visit_date')
                        && hasTableColumn($pdo, 'medical_history', 'visit_type')
                        && hasTableColumn($pdo, 'medical_history', 'diagnosis')
                        && hasTableColumn($pdo, 'medical_history', 'history_id')) {
                        $existingHistoryStmt = $pdo->prepare("SELECT history_id FROM medical_history WHERE user_id = ? AND DATE(visit_date) = ? AND (TRIM(visit_type) = TRIM(?) OR TRIM(COALESCE(diagnosis, '')) = TRIM(?)) ORDER BY history_id DESC LIMIT 1 FOR UPDATE");
                        $existingHistoryStmt->execute([$resident_id, $appointmentRecord['appointment_date'], $appointmentPurpose, $appointmentPurpose]);
                        $existingHistoryId = $existingHistoryStmt->fetchColumn();
                    }

                    if ($existingHistoryId) {
                        $completeHistoryStmt = $pdo->prepare("UPDATE medical_history SET status = 'completed', notes = CONCAT(COALESCE(notes, ''), CASE WHEN COALESCE(notes, '') = '' THEN '' ELSE CHAR(10) END, ?), updated_at = NOW() WHERE history_id = ?");
                        $completeHistoryStmt->execute(['Visit completed on ' . date('Y-m-d H:i:s'), $existingHistoryId]);
                        error_log("✓ Existing medical history completed for appointment_id=$appointment_id");
                    } else {
                        // Try to create PATIENT_RECORD entry first (NEW ERD schema)
                        try {
                            $insert_stmt = $pdo->prepare("
                                INSERT INTO patient_record (
                                    resident_id,
                                    health_worker_id,
                                    visit_date,
                                    diagnosis,
                                    treatment,
                                    notes,
                                    created_at
                                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
                            ");
                            
                            $insert_stmt->execute([
                                $resident_id,
                                $worker_id,
                                $appointmentRecord['appointment_date'],
                                $appointmentPurpose,  // Use appointment purpose as diagnosis
                                'Medical visit completed',     // Default treatment
                                'Visit completed on ' . date('Y-m-d H:i:s'),  // Log note
                            ]);
                            
                            error_log("✓ PATIENT_RECORD created for resident_id=$resident_id from appointment_id=$appointment_id");
                        } catch (Exception $patient_record_err) {
                            // FALLBACK: If PATIENT_RECORD table doesn't exist, try medical_history table
                            error_log("PATIENT_RECORD insertion failed, attempting medical_history fallback: " . $patient_record_err->getMessage());
                            
                            try {
                                $insert_history = $pdo->prepare("
                                    INSERT INTO medical_history (
                                        user_id,
                                        visit_date,
                                        visit_type,
                                        bhw_name,
                                        diagnosis,
                                        treatment,
                                        notes,
                                        created_at
                                    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                                ");
                                
                                $bhw_name = $worker_id > 0 ? "BHW ID: $worker_id" : 'Health Worker';
                                
                                $insert_history->execute([
                                    $resident_id,
                                    $appointmentRecord['appointment_date'],
                                    'Consultation',
                                    $bhw_name,
                                    $appointmentPurpose,
                                    'Medical visit completed',
                                    'Visit completed on ' . date('Y-m-d H:i:s'),
                                ]);
                                
                                error_log("✓ FALLBACK: medical_history record created for resident_id=$resident_id from appointment_id=$appointment_id");
                            } catch (Exception $fallback_err) {
                                error_log("WARNING: Both PATIENT_RECORD and medical_history creation failed: " . $fallback_err->getMessage());
                            }
                        }
                    }
                } catch (Exception $record_err) {
                    error_log("WARNING: Error during medical record creation: " . $record_err->getMessage());
                    // Don't fail the appointment update if record creation fails
                }
            }
            
            // Update appointment status
            $stmt = $pdo->prepare("
                UPDATE appointment
                SET status = ?, health_worker_id = ?
                WHERE appointment_id = ?
            ");
            
            if (!$stmt->execute([$new_status, $worker_id, $appointment_id])) {
                throw new RuntimeException('Failed to update appointment status');
            }
            $pdo->commit();
                // Log the activity
                $status_display = ucfirst(str_replace('_', ' ', $new_status));
                $resident_name = "Resident ID: {$resident_id}";
                
                // Try to get resident name
                try {
                    $res_stmt = $pdo->prepare("SELECT full_name FROM resident WHERE resident_id = ? LIMIT 1");
                    $res_stmt->execute([$resident_id]);
                    $resident = $res_stmt->fetch(PDO::FETCH_ASSOC);
                    if ($resident) {
                        $resident_name = $resident['full_name'];
                    }
                } catch (Exception $e) {
                    error_log("Warning: Could not fetch resident name: " . $e->getMessage());
                }
                
                // Log activity using logActivity function
                try {
                    logActivity(
                        $pdo,
                        $worker_id,
                        'bhw',
                        'appointment_rendered',
                        "Rendered appointment for {$resident_name} - Status changed to: {$status_display}"
                    );
                } catch (Exception $log_err) {
                    error_log("Warning: Activity logging failed: " . $log_err->getMessage());
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Appointment status updated successfully',
                    'new_status' => $new_status
                ]);
                exit;
        } catch (Exception $e) {
            if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("UPDATE APPOINTMENT STATUS ERROR: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Unable to update the appointment.']);
            exit;
        }
    }

    // ================================================================
    // HANDLE RESCHEDULE APPOINTMENT
    // ================================================================
    $isFollowupSchedule = $action === 'schedule_followup';
    if ($action === 'reschedule_appointment' || $isFollowupSchedule) {
        header('Content-Type: application/json; charset=utf-8');
        $appointmentId = (int) ($_POST['appointment_id'] ?? 0);
        $newDate = trim((string) ($_POST['appointment_date'] ?? ''));
        $newTime = trim((string) ($_POST['appointment_time'] ?? ''));

        if ($appointmentId <= 0 || $newDate === '' || $newTime === '') {
            echo json_encode(['success' => false, 'message' => 'Appointment ID, date, and time are required.']);
            exit;
        }

        if (!preg_match('/^(0?[1-9]|1[0-2]):([0-5][0-9])\s*(AM|PM)$/i', $newTime, $timeMatch)) {
            echo json_encode(['success' => false, 'message' => 'Please select a valid appointment time.']);
            exit;
        }

        $selectedHour = (int) $timeMatch[1];
        if (strcasecmp($timeMatch[3], 'PM') === 0 && $selectedHour !== 12) {
            $selectedHour += 12;
        } elseif (strcasecmp($timeMatch[3], 'AM') === 0 && $selectedHour === 12) {
            $selectedHour = 0;
        }
        $normalizedTime = sprintf('%02d:%s', $selectedHour, $timeMatch[2]);
        $dateTime = DateTime::createFromFormat('!Y-m-d H:i', $newDate . ' ' . $normalizedTime);
        if (!$dateTime || $dateTime->format('Y-m-d H:i') !== $newDate . ' ' . $normalizedTime) {
            echo json_encode(['success' => false, 'message' => 'Please select a valid appointment date and time.']);
            exit;
        }

        if ((int) $dateTime->format('N') >= 6) {
            echo json_encode(['success' => false, 'message' => 'Appointments can only be scheduled Monday through Friday.']);
            exit;
        }

        if ($dateTime < new DateTime('now')) {
            echo json_encode(['success' => false, 'message' => 'The new appointment date and time must be in the future.']);
            exit;
        }

        try {
            $pdo = getDatabaseConnection();
            if ($pdo === false) {
                throw new RuntimeException('Database connection failed');
            }

            $pdo->beginTransaction();
            $conflictStmt = $pdo->prepare(
                "SELECT COUNT(*) FROM appointment
                 WHERE appointment_date = ?
                   AND TIME_FORMAT(appointment_time, '%H:%i') = ?
                   AND appointment_id != ?
                   AND status != 'cancelled'
                 HAVING COUNT(*) >= 5"
            );
            $conflictStmt->execute([$newDate, $normalizedTime, $appointmentId]);
            if ((int) $conflictStmt->fetchColumn() >= 5) {
                throw new RuntimeException('That time has reached its limit of 5 appointments. Please select another time.');
            }

            $appointmentStmt = $pdo->prepare(
                'SELECT a.resident_id, a.appointment_date, a.appointment_time, a.purpose, r.full_name, r.contact_number
                 FROM appointment a
                 LEFT JOIN resident r ON r.resident_id = a.resident_id
                 WHERE a.appointment_id = ? FOR UPDATE'
            );
            $appointmentStmt->execute([$appointmentId]);
            $appointment = $appointmentStmt->fetch(PDO::FETCH_ASSOC);
            if (!$appointment) {
                throw new RuntimeException('Appointment not found');
            }

            $residentConflictStmt = $pdo->prepare(
                "SELECT COUNT(*) FROM appointment
                 WHERE resident_id = ?
                   AND appointment_date = ?
                   AND TIME_FORMAT(appointment_time, '%H:%i') = ?
                   AND appointment_id != ?
                   AND status != 'cancelled'"
            );
            $residentConflictStmt->execute([$appointment['resident_id'], $newDate, $normalizedTime, $appointmentId]);
            if ((int) $residentConflictStmt->fetchColumn() > 0) {
                throw new RuntimeException('This resident already has an appointment during that hour. Please choose another time.');
            }

            if ($newDate === $appointment['appointment_date']
                && date('H', strtotime((string) $appointment['appointment_time'])) === sprintf('%02d', $selectedHour)) {
                throw new RuntimeException('Please choose a different hour from the current appointment schedule.');
            }

            $oldSchedule = date('M d, Y g:i A', strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']));
            $newSchedule = date('M d, Y g:i A', $dateTime->getTimestamp());
            $notes = $isFollowupSchedule
                ? 'Follow-up scheduled from ' . $oldSchedule . ' to ' . $newSchedule
                : 'Appointment rescheduled from ' . $oldSchedule . ' to ' . $newSchedule;
            if ($isFollowupSchedule) {
                $updateStmt = $pdo->prepare(
                    "UPDATE appointment SET appointment_date = ?, appointment_time = ?, purpose = 'Follow-up Check-up', status = 'flagged', notes = ?, updated_at = NOW() WHERE appointment_id = ?"
                );
                $updateStmt->execute([$newDate, $normalizedTime, $notes, $appointmentId]);
            } else {
                $updateStmt = $pdo->prepare(
                    'UPDATE appointment SET appointment_date = ?, appointment_time = ?, status = ?, notes = ?, updated_at = NOW() WHERE appointment_id = ?'
                );
                $updateStmt->execute([$newDate, $normalizedTime, 'rescheduled', $notes, $appointmentId]);
            }
            $pdo->commit();

            $residentName = $appointment['full_name'] ?? 'Resident';
            $smsSent = false;
            if (!$isFollowupSchedule) {
                $contactNumber = trim((string) ($appointment['contact_number'] ?? ''));
                $smsMessage = "Hi {$residentName}, your appointment has been rescheduled to {$newSchedule}. Please arrive 10 minutes early. ScanCare";
                $smsResult = sendScanCareAlert($contactNumber, $smsMessage);
                $smsSent = $smsResult['error'] === '';
                if (!$smsSent) {
                    error_log('Appointment reschedule SMS failed for resident_id=' . $appointment['resident_id'] . ': ' . $smsResult['error']);
                } else {
                    error_log('Appointment reschedule SMS sent to resident_id=' . $appointment['resident_id'] . ' for appointment_id=' . $appointmentId);
                }
            }

            $activityType = $isFollowupSchedule ? 'scheduled_followup' : 'rescheduled_appointment';
            $activityMessage = $isFollowupSchedule
                ? "Scheduled follow-up for appointment {$appointmentId} for {$residentName} at {$newSchedule}"
                : "Rescheduled appointment {$appointmentId} for {$residentName} to {$newSchedule}";
            logActivity($pdo, $worker_id, 'bhw', $activityType, $activityMessage);
            echo json_encode([
                'success' => true,
                'message' => $isFollowupSchedule
                    ? 'Follow-up scheduled and added to the Follow-up tab.'
                    : 'Appointment rescheduled successfully and resident notified.',
                'new_date' => $newDate,
                'new_time' => $normalizedTime,
                'new_status' => $isFollowupSchedule ? 'flagged' : 'rescheduled',
                'sms_sent' => $smsSent
            ]);
            exit;
        } catch (Throwable $e) {
            if ($pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('RESCHEDULE APPOINTMENT ERROR: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Unable to reschedule the appointment.']);
            exit;
        }
    }
    
    // ================================================================
    // HANDLE SAVE SURVEILLANCE METRICS
    // ================================================================
    if ($action === 'save_surveillance_metrics') {
        header('Content-Type: application/json; charset=utf-8');
        $reportingPeriodInput = $_POST['reporting_period'] ?? null;
        $diseaseNameInput = $_POST['disease_name'] ?? null;
        $caseCountInput = $_POST['case_count'] ?? ($_POST['quantity_count'] ?? null);
        $metaNotesInput = $_POST['meta_notes'] ?? ($_POST['disease_meta'] ?? null);

        $reporting_period = is_string($reportingPeriodInput) && trim($reportingPeriodInput) !== '' ? trim($reportingPeriodInput) : 'Current 4 Weeks';
        $disease_name = is_string($diseaseNameInput) ? trim($diseaseNameInput) : '';
        $case_count = is_scalar($caseCountInput) ? intval($caseCountInput) : 0;
        if ($case_count <= 10) {
            $trend_status = 'Baseline';
        } elseif ($case_count <= 20) {
            $trend_status = 'Watch';
        } elseif ($case_count <= 30) {
            $trend_status = 'Alert';
        } else {
            $trend_status = 'Emergency';
        }
        $meta_notes = is_string($metaNotesInput) ? trim($metaNotesInput) : '';

        if ($disease_name === '') {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Disease name is required.']);
            exit;
        }
        
        try {
            $pdo = getDatabaseConnection();
            if ($pdo === false) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Database connection failed']);
                exit;
            }

            ensureSurveillanceMetricsSchema($pdo);
            $pdo->exec("CREATE TABLE IF NOT EXISTS surveillance_metrics (
                id INT AUTO_INCREMENT PRIMARY KEY,
                worker_id INT NOT NULL,
                reporting_period VARCHAR(255) DEFAULT NULL,
                disease_name VARCHAR(255) DEFAULT NULL,
                case_count INT DEFAULT 0,
                trend_status VARCHAR(255) DEFAULT NULL,
                meta_notes TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $pdo->exec("ALTER TABLE surveillance_metrics MODIFY disease_name VARCHAR(255) DEFAULT NULL");

                $metric_id = isset($_POST['metric_id']) && is_scalar($_POST['metric_id']) ? intval($_POST['metric_id']) : 0;
                if ($case_count <= 0 && $metric_id > 0) {
                    $deleteStmt = $pdo->prepare("DELETE FROM surveillance_metrics WHERE id = ? AND worker_id = ?");
                    $deleteStmt->execute([$metric_id, $worker_id]);
                } elseif ($case_count > 0 && $metric_id > 0) {
                    $updateStmt = $pdo->prepare("UPDATE surveillance_metrics
                        SET case_count = ?, trend_status = ?, updated_at = CURRENT_TIMESTAMP
                        WHERE id = ? AND worker_id = ?");
                    $updateStmt->execute([$case_count, $trend_status, $metric_id, $worker_id]);
                } elseif ($case_count > 0) {
                    $insertStmt = $pdo->prepare("INSERT INTO surveillance_metrics
                        (worker_id, reporting_period, disease_name, case_count, trend_status, meta_notes)
                        VALUES (?, ?, ?, ?, ?, ?)");
                    $insertStmt->execute([$worker_id, $reporting_period, $disease_name, $case_count, $trend_status, $meta_notes]);
                        $created_metric_id = (int)$pdo->lastInsertId();
                }

                $cleanupStmt = $pdo->prepare("DELETE FROM surveillance_metrics
                    WHERE worker_id = ? AND (case_count <= 0 OR disease_name IS NULL OR TRIM(disease_name) = '' OR LOWER(TRIM(disease_name)) = 'record entry')");
                $cleanupStmt->execute([$worker_id]);

            // Fetch the freshly saved/updated metrics for this worker so frontend can reflect exact DB state
            try {
                $fetchStmt = $pdo->prepare("SELECT * FROM surveillance_metrics WHERE worker_id = ? AND case_count > 0 AND disease_name IS NOT NULL AND TRIM(disease_name) <> '' AND LOWER(TRIM(disease_name)) <> 'record entry' ORDER BY case_count DESC, created_at DESC");
                $fetchStmt->execute([$worker_id]);
                $saved = $fetchStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Exception $fetchErr) {
                error_log("FETCH SAVED SURVEILLANCE METRICS ERROR: " . $fetchErr->getMessage());
                $saved = [];
            }

            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Surveillance metrics saved successfully',
                'id' => $created_metric_id ?? ($metric_id > 0 ? $metric_id : null),
                'created_id' => $created_metric_id ?? null,
                'disease_name' => $disease_name,
                'case_count' => $case_count,
                'metrics' => $saved
            ]);
            exit;
        } catch (Exception $e) {
            error_log("SAVE SURVEILLANCE METRICS ERROR: " . $e->getMessage());
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
    }
    
    // ================================================================
    // HANDLE UPDATE FOLLOW-UP TIME
    // ================================================================
    if ($action === 'update_followup_time') {
        header('Content-Type: application/json');
        $appointment_id = intval($_POST['appointment_id'] ?? 0);
        $follow_time = trim($_POST['follow_time'] ?? '');
        
        if ($appointment_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
            exit;
        }
        
        if (empty($follow_time)) {
            echo json_encode(['success' => false, 'message' => 'Time is required']);
            exit;
        }
        
        try {
            $pdo = getDatabaseConnection();
            
            if ($pdo === false) {
                echo json_encode(['success' => false, 'message' => 'Database connection failed']);
                exit;
            }
            

            ensureSurveillanceMetricsSchema($pdo);
            // Update appointment time (follow-up will be at the new time)
            $stmt = $pdo->prepare("
                UPDATE appointment
                SET appointment_time = ?
                WHERE appointment_id = ?
            ");
            
            if ($stmt->execute([$follow_time, $appointment_id])) {
                // Log activity
                try {
                    // Get appointment and resident details
                    $apt_stmt = $pdo->prepare("SELECT resident_id, purpose FROM appointment WHERE appointment_id = ? LIMIT 1");
                    $apt_stmt->execute([$appointment_id]);
                    $apt = $apt_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $resident_name = "Resident ID: " . ($apt['resident_id'] ?? $appointment_id);
                    if ($apt && isset($apt['resident_id'])) {
                        $res_stmt = $pdo->prepare("SELECT full_name FROM resident WHERE resident_id = ? LIMIT 1");
                        $res_stmt->execute([$apt['resident_id']]);
                        $resident = $res_stmt->fetch(PDO::FETCH_ASSOC);
                        if ($resident) {
                            $resident_name = $resident['full_name'];
                        }
                    }
                    
                    logActivity(
                        $pdo,
                        $worker_id,
                        'bhw',
                        'rescheduled_appointment',
                        "Rescheduled appointment for {$resident_name} - New time: {$follow_time}"
                    );
                } catch (Exception $log_err) {
                    error_log("Warning: Activity logging failed: " . $log_err->getMessage());
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Follow-up time updated successfully'
                ]);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update time']);
                exit;
            }
        } catch (Exception $e) {
            error_log("UPDATE FOLLOWUP TIME ERROR: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Unable to update the follow-up schedule.']);
            exit;
        }
    }
    
    if ($action === 'add_medicine') {
        header('Content-Type: application/json');
        $medicine_name = trim($_POST['medicine_name'] ?? '');
        $dosage = trim($_POST['dosage'] ?? '');
        $available = filter_var($_POST['available'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        
        // Debug logging
        error_log("ADD MEDICINE DEBUG - Name: '$medicine_name', Dosage: '$dosage', Available: " . var_export($available, true));
        
        try {
            $pdo = getDatabaseConnection();
            
            if ($pdo === false) {
                error_log("ADD MEDICINE ERROR: Database connection failed");
                echo json_encode(['success' => false, 'message' => 'Database connection failed']);
                exit;
            }
            
            if (empty($medicine_name)) {
                error_log("ADD MEDICINE ERROR: Medicine name is empty");
                echo json_encode(['success' => false, 'message' => 'Medicine name is required']);
                exit;
            }
            
            if ($available === false) {
                error_log("ADD MEDICINE ERROR: Available quantity must be a non-negative integer");
                echo json_encode(['success' => false, 'message' => 'Available quantity must be a non-negative whole number.']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO medicine (name, dosage, stock_quantity, available, dispensed, status) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $status = ($available > 5) ? 'Available' : (($available > 0) ? 'Low Stock' : 'Out of Stock');
            $clean_name = $medicine_name;
            $clean_dosage = $dosage !== '' ? $dosage : '-';
            
            error_log("ADD MEDICINE: Executing insert with - Name: '$clean_name', Dosage: '$clean_dosage', Available: $available, Status: '$status'");
            
            // stock_quantity is retained for existing reports, but is always
            // derived from the two inventory figures shown to staff.
            if ($stmt->execute([$clean_name, $clean_dosage, $available, $available, 0, $status])) {
                error_log("ADD MEDICINE SUCCESS: Medicine inserted successfully");
                
                // Log activity
                try {
                    logActivity(
                        $pdo,
                        $worker_id,
                        'bhw',
                        'added_medicine',
                        "Added medicine: {$clean_name} ({$clean_dosage}) with {$available} available"
                    );
                } catch (Exception $log_err) {
                    error_log("Warning: Activity logging failed: " . $log_err->getMessage());
                }
                
                echo json_encode(['success' => true, 'message' => 'Medicine added successfully']);
                exit;
            } else {
                error_log("ADD MEDICINE ERROR: Execute returned false");
                echo json_encode(['success' => false, 'message' => 'Failed to execute database insert']);
                exit;
            }
            
        } catch (Exception $e) {
            $error_msg = $e->getMessage();
            error_log("ADD MEDICINE ERROR: {$error_msg}");
            echo json_encode([
                'success' => false,
                'message' => 'Unable to add medicine: ' . $error_msg,
                'error' => $error_msg
            ]);
            exit;
        }
    }
    
    if ($action === 'dispense_free_medicine') {
        header('Content-Type: application/json; charset=utf-8');
        $residentId = (int) ($_POST['resident_id'] ?? 0);
        $medicines = json_decode((string) ($_POST['medicines'] ?? '[]'), true);

        try {
            if ($residentId <= 0 || !is_array($medicines) || empty($medicines)) {
                throw new RuntimeException('Select a patient and at least one medicine.');
            }

            $pdo = getDatabaseConnection();
            if ($pdo === false) {
                throw new RuntimeException('Database connection unavailable.');
            }

            ensureBhwMedicineArchiveColumn($pdo);

            $residentStmt = $pdo->prepare('SELECT resident_id FROM resident WHERE resident_id = ? LIMIT 1');
            $residentStmt->execute([$residentId]);
            if (!$residentStmt->fetchColumn()) {
                throw new RuntimeException('Selected patient was not found.');
            }

            $pdo->beginTransaction();
            $selectMedicine = $pdo->prepare('SELECT medicine_id, name, available, dispensed FROM medicine WHERE medicine_id = ? AND is_deleted = 0 FOR UPDATE');
            $insertDispensing = $pdo->prepare('INSERT INTO medicine_dispensing (resident_id, medicine_id, health_worker_id, dispense_date, quantity_given) VALUES (?, ?, ?, NOW(), ?)');
            // Dispensing subtracts from available and adds the same amount to
            // dispensed; the internal total therefore remains derived.
            $updateMedicine = $pdo->prepare(
                "UPDATE medicine
                 SET available = available - ?,
                     dispensed = dispensed + ?,
                     status = CASE
                         WHEN available - ? > 5 THEN 'Available'
                         WHEN available - ? > 0 THEN 'Low Stock'
                         ELSE 'Out of Stock'
                     END
                 WHERE medicine_id = ? AND available >= ?"
            );
            $dispensedItems = [];

            foreach ($medicines as $medicine) {
                $medicineId = (int) ($medicine['medicine_id'] ?? 0);
                $quantity = (int) ($medicine['quantity'] ?? 0);
                if ($medicineId <= 0 || $quantity <= 0) {
                    throw new RuntimeException('Each medicine must have a valid quantity.');
                }

                $selectMedicine->execute([$medicineId]);
                $inventory = $selectMedicine->fetch(PDO::FETCH_ASSOC);
                if (!$inventory) {
                    throw new RuntimeException('One of the selected medicines no longer exists.');
                }
                $available = (int) $inventory['available'];
                if ($quantity > $available) {
                    throw new RuntimeException($inventory['name'] . ' has only ' . $available . ' unit(s) remaining.');
                }

                $insertDispensing->execute([$residentId, $medicineId, $worker_id ?: null, $quantity]);
                $updateMedicine->execute([$quantity, $quantity, $quantity, $quantity, $medicineId, $quantity]);
                if ($updateMedicine->rowCount() !== 1) {
                    throw new RuntimeException('Unable to update the available stock for ' . $inventory['name'] . '.');
                }
                $dispensedItems[] = $inventory['name'] . ' x ' . $quantity;
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Dispensing recorded: ' . implode(', ', $dispensedItems) . '.']);
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('FREE MEDICINE DISPENSING ERROR: ' . $e->getMessage());
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'update_medicine') {
        header('Content-Type: application/json');
        $medicine_id = intval($_POST['medicine_id'] ?? 0);
        $name = trim($_POST['medicine_name'] ?? '');
        $dosage = trim($_POST['dosage'] ?? '');
        $available = filter_var($_POST['available'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $dispensed = filter_var($_POST['dispensed'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        
        try {
            $pdo = getDatabaseConnection();
            if ($pdo !== false && $medicine_id > 0 && $name !== '' && $available !== false && $dispensed !== false) {
                $stock = $available + $dispensed;
                $status = ($available > 5) ? 'Available' : (($available > 0) ? 'Low Stock' : 'Out of Stock');
                $stmt = $pdo->prepare("
                    UPDATE medicine 
                    SET name = ?, dosage = ?, stock_quantity = ?, available = ?, dispensed = ?, status = ? 
                    WHERE medicine_id = ? AND is_deleted = 0
                ");
                if ($stmt->execute([htmlspecialchars($name), htmlspecialchars($dosage), $stock, $available, $dispensed, $status, $medicine_id])) {
                    // Log activity
                    try {
                        $clean_name = htmlspecialchars($name);
                        $clean_dosage = htmlspecialchars($dosage);
                        logActivity(
                            $pdo,
                            $worker_id,
                            'bhw',
                            'edited_medicine',
                        "Edited medicine: {$clean_name} ({$clean_dosage}) - Available: {$available}, dispensed: {$dispensed}"
                        );
                    } catch (Exception $log_err) {
                        error_log("Warning: Activity logging failed: " . $log_err->getMessage());
                    }
                    
                    echo json_encode(['success' => true, 'message' => 'Medicine updated successfully']);
                    exit;
                }
            }
        } catch (Exception $e) {
            error_log("Medicine update error: " . $e->getMessage());
        }
        echo json_encode(['success' => false, 'message' => 'Error updating medicine']);
        exit;
    }

    if ($action === 'delete_medicine') {
        header('Content-Type: application/json');
        $medicine_id = intval($_POST['medicine_id'] ?? 0);

        if ($medicine_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid medicine ID']);
            exit;
        }

        try {
            $pdo = getDatabaseConnection();
            if ($pdo !== false) {
                ensureBhwMedicineArchiveColumn($pdo);
                $deleteStmt = $pdo->prepare('UPDATE medicine SET is_deleted = 1 WHERE medicine_id = ? AND is_deleted = 0');
                if ($deleteStmt->execute([$medicine_id]) && $deleteStmt->rowCount() > 0) {
                    try {
                        logActivity(
                            $pdo,
                            $worker_id,
                            'bhw',
                            'deleted_medicine',
                            "Archived medicine ID: {$medicine_id}"
                        );
                    } catch (Exception $log_err) {
                        error_log("Warning: Activity logging failed: " . $log_err->getMessage());
                    }

                    echo json_encode(['success' => true, 'message' => 'Medicine deleted successfully']);
                    exit;
                }
            }
        } catch (Exception $e) {
            error_log("Medicine delete error: " . $e->getMessage());
        }

        echo json_encode(['success' => false, 'message' => 'Error deleting medicine']);
        exit;
    }
    
    // ================================================================
    // HANDLE CLEAR MEDICAL HISTORY
    // ================================================================
    if ($action === 'clear_medical_history') {
        header('Content-Type: application/json');
        $patient_id = intval($_POST['patient_id'] ?? 0);
        
        if ($patient_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid patient ID']);
            exit;
        }
        
        try {
            $pdo = getDatabaseConnection();
            
            if ($pdo === false) {
                echo json_encode(['success' => false, 'message' => 'Database connection failed']);
                exit;
            }
            
            // Get record count before deletion for confirmation
            $count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM medical_history WHERE user_id = ?");
            $count_stmt->execute([$patient_id]);
            $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
            $deleted_count = $count_result['count'] ?? 0;
            
            // Delete all medical history records for the patient
            $delete_stmt = $pdo->prepare("DELETE FROM medical_history WHERE user_id = ?");
            
            if ($delete_stmt->execute([$patient_id])) {
                error_log("✓ Medical history cleared for patient_id=$patient_id. Deleted $deleted_count records.");
                echo json_encode([
                    'success' => true,
                    'message' => "Medical history cleared successfully. Deleted $deleted_count record(s).",
                    'deleted_count' => $deleted_count
                ]);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to clear medical history']);
                exit;
            }
        } catch (Exception $e) {
            error_log("CLEAR MEDICAL HISTORY ERROR: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Unable to clear medical history.']);
            exit;
        }
    }
    
    // ================================================================
    // HANDLE CLEAR FOLLOW-UP CHECK-UPS
    // ================================================================
    if ($action === 'clear_followup_checkups') {
        header('Content-Type: application/json');
        $patient_id = intval($_POST['patient_id'] ?? 0);
        
        if ($patient_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid patient ID']);
            exit;
        }
        
        try {
            $pdo = getDatabaseConnection();
            
            if ($pdo === false) {
                echo json_encode(['success' => false, 'message' => 'Database connection failed']);
                exit;
            }
            
            // Get record count before deletion for confirmation
            $count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM appointment WHERE resident_id = ? AND status = 'flagged'");
            $count_stmt->execute([$patient_id]);
            $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
            $deleted_count = $count_result['count'] ?? 0;
            
            // Delete all follow-up check-up records (flagged appointments)
            $delete_stmt = $pdo->prepare("DELETE FROM appointment WHERE resident_id = ? AND status = 'flagged'");
            
            if ($delete_stmt->execute([$patient_id])) {
                error_log("✓ Follow-up check-ups cleared for patient_id=$patient_id. Deleted $deleted_count records.");
                echo json_encode([
                    'success' => true,
                    'message' => "Follow-up check-ups cleared successfully. Deleted $deleted_count record(s).",
                    'deleted_count' => $deleted_count
                ]);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to clear follow-up check-ups']);
                exit;
            }
        } catch (Exception $e) {
            error_log("CLEAR FOLLOWUP CHECKUPS ERROR: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Unable to clear follow-up checkups.']);
            exit;
        }
    }
}

// ================================================================
// FETCH REAL APPOINTMENT DATA FROM DATABASE
// ================================================================
$total_appointments = 0;
$pending_consultations = 0;
$medicine_count = 0;
$medicine_available = 0;
$pending_reminders = 0;
$sms_sent_today = 0;
$sms_failed_today = 0;
$upcoming_appointments = [];
$appointment_history = [];
$medicine_list = [];
$dispensed_medicine_list = [];
$dispensing_residents = [];
$file_requests = [];

try {
    $pdo = getDatabaseConnection();
    
    if ($pdo !== false) {
        ensureBhwMedicineArchiveColumn($pdo);

        // Get total appointments (all active/upcoming appointments)
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM appointment WHERE status IN ('scheduled', 'pending', 'flagged', 'approved')");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_appointments = $result['count'] ?? 0;
        
        // Get pending consultations (scheduled or pending, future dates only)
        $today = date('Y-m-d');
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count FROM appointment
            WHERE status IN ('scheduled', 'pending') 
            AND appointment_date >= ?
        ");
        $stmt->execute([$today]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $pending_consultations = $result['count'] ?? 0;
        
        // Get upcoming appointments with resident details
        // NOTE: Include ALL statuses (pending, completed, no_show, etc.) so we can display them
        // in their respective tabs (Today's Queue, Follow-Up, and Appointment History)
        $stmt = $pdo->prepare("
            SELECT 
                a.appointment_id,
                a.resident_id,
                a.appointment_date,
                a.appointment_time,
                a.purpose,
                a.status,
                r.full_name as resident_name,
                r.contact_number
            FROM appointment a
            LEFT JOIN resident r ON a.resident_id = r.resident_id
            WHERE a.status IN ('scheduled', 'pending', 'flagged', 'approved', 'completed', 'no_show', 'rescheduled')
            AND a.appointment_date >= ?
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
            LIMIT 100
        ");
        $stmt->execute([$today]);
        $upcoming_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $historyStmt = $pdo->query("SELECT a.appointment_id, a.resident_id, a.appointment_date, a.appointment_time, a.purpose, a.status, r.full_name AS resident_name, r.contact_number FROM appointment a LEFT JOIN resident r ON r.resident_id = a.resident_id WHERE a.status IN ('completed', 'no_show') ORDER BY a.appointment_date DESC, a.appointment_time DESC");
        $appointment_history = $historyStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        
        // Get medicine inventory count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM medicine WHERE is_deleted = 0");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $medicine_count = $result['count'] ?? 0;
        
        // Get available medicines count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM medicine WHERE is_deleted = 0 AND available > 0");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $medicine_available = $result['count'] ?? 0;
        
        // Get all medicines for inventory table with all 7 columns
        $stmt = $pdo->query("SELECT medicine_id, name, dosage, stock_quantity, available, dispensed, status FROM medicine WHERE is_deleted = 0 ORDER BY name ASC");
        $medicine_list = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];

        $dispensingStmt = $pdo->query("SELECT md.dispense_id, md.quantity_given, md.dispense_date, r.full_name AS resident_name, m.name AS medicine_name, m.dosage, hw.full_name AS worker_name FROM medicine_dispensing md INNER JOIN resident r ON r.resident_id = md.resident_id INNER JOIN medicine m ON m.medicine_id = md.medicine_id LEFT JOIN health_worker hw ON hw.worker_id = md.health_worker_id ORDER BY md.dispense_date DESC, md.dispense_id DESC LIMIT 200");
        $dispensed_medicine_list = $dispensingStmt->fetchAll(PDO::FETCH_ASSOC) ?? [];

        try {
            $smsLogColumns = $pdo->query('SHOW COLUMNS FROM sms_log')->fetchAll(PDO::FETCH_COLUMN);
            $smsTimestampColumn = in_array('created_at', $smsLogColumns, true) ? 'created_at' : 'sent_at';
            $smsStatsStmt = $pdo->query("SELECT
                SUM(CASE WHEN status IN ('sent', 'queued', 'accepted', 'submitted', 'processing', 'pending', 'success') THEN 1 ELSE 0 END) AS sent_today,
                SUM(CASE WHEN status IN ('failed', 'error', 'rejected') THEN 1 ELSE 0 END) AS failed_today
                FROM sms_log
                WHERE DATE({$smsTimestampColumn}) = CURRENT_DATE");
            $smsStats = $smsStatsStmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $sms_sent_today = (int) ($smsStats['sent_today'] ?? 0);
            $sms_failed_today = (int) ($smsStats['failed_today'] ?? 0);
        } catch (Throwable $smsStatsError) {
            error_log('SMS STATS LOAD ERROR: ' . $smsStatsError->getMessage());
        }

    }
} catch (Throwable $e) {
    error_log("BHW Dashboard Error: " . $e->getMessage());
}

try {
    $residentListPdo = getDatabaseConnection();
    if ($residentListPdo !== false) {
        $residentsStmt = $residentListPdo->query("SELECT resident_id, full_name FROM resident ORDER BY full_name ASC LIMIT 500");
        $dispensing_residents = $residentsStmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
    }
} catch (Exception $e) {
    error_log('BHW dispensing resident list error: ' . $e->getMessage());
}

if ($active_module === 'file-requests') {
    try {
        $requestPdo = getDatabaseConnection();
        if ($requestPdo !== false && ensureFileRequestsTable($requestPdo)) {
            $requestStmt = $requestPdo->query("SELECT fr.request_id, fr.resident_id, fr.requested_file, fr.status, fr.response_note, fr.created_at, r.full_name, r.patient_code FROM file_requests fr JOIN resident r ON r.resident_id = fr.resident_id ORDER BY FIELD(fr.status, 'pending', 'accepted', 'declined'), fr.created_at DESC LIMIT 100");
            $file_requests = $requestStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
    } catch (Exception $e) {
        error_log('BHW file request load error: ' . $e->getMessage());
    }
}

$managedAnnouncements = [];
$announcementCounts = ['upcoming' => 0, 'now' => 0, 'done' => 0];
if ($active_module === 'announcements') {
    try {
        $announcementPdo = getDatabaseConnection();
        $announcementStmt = $announcementPdo->query(
            'SELECT a.announcement_id, a.worker_id, a.title, a.message, a.service_date, a.location, a.state, a.created_at,
                hw.full_name AS author_name
             FROM health_announcements a
             LEFT JOIN health_worker hw ON hw.worker_id = a.worker_id
             ORDER BY a.created_at DESC'
        );
        $managedAnnouncements = $announcementStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($managedAnnouncements as $managedAnnouncement) {
            if (isset($announcementCounts[$managedAnnouncement['state']])) {
                $announcementCounts[$managedAnnouncement['state']]++;
            }
        }
    } catch (Exception $e) {
        error_log('MANAGE ANNOUNCEMENTS LOAD ERROR: ' . $e->getMessage());
    }
}

// Helper function for active tab styling
function isModuleActive($module) {
    global $active_module;
    return $active_module === $module;
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BHW Dashboard — ScanCare</title>
    <link rel="icon" type="image/png" href="images/SClogo.png?v=2">
    <link rel="apple-touch-icon" href="images/SClogo.png?v=2">
    <script src="theme-state.js?v=2"></script>
    <script src="pwa-offline.js?v=3" defer></script>
    <link rel="stylesheet" href="global-theme.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' };</script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap');
        
        .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-body { font-family: 'Inter', sans-serif; }

        /* Tab Navigation Active State */
        .tab-button {
            position: relative;
            padding: 12px 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
            color: #64748b;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
        }

        .tab-button:hover {
            color: #2d5016;
            background-color: rgba(79, 119, 38, 0.08);
        }

        .tab-button.active {
            color: #1b5e20;
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.1), rgba(56, 142, 60, 0.05));
            font-weight: 600;
        }

        .tab-button.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60%;
            height: 3px;
            background: linear-gradient(90deg, #4f7726, #66bb6a);
            border-radius: 2px;
            box-shadow: 0 2px 8px rgba(79, 119, 38, 0.2);
        }

        /* Keep the button clickable without placing it above the fixed header */
        #saveSurveillanceBtn {
            position: relative !important;
            z-index: 1 !important;
            pointer-events: auto !important;
        }

        .scancare-offline-disabled {
            opacity: 0.45 !important;
            filter: grayscale(1);
            pointer-events: none !important;
            cursor: not-allowed !important;
        }

        .surveillance-status-pill {
            display: inline-flex;
            align-items: center;
            border-radius: 9999px;
            padding: 0.25rem 0.6rem;
            font-size: 0.7rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .surveillance-status-pill.status-baseline {
            background: #d1fae5;
            color: #047857;
        }

        .surveillance-status-pill.status-watch {
            background: #fef3c7;
            color: #b45309;
        }

        .surveillance-status-pill.status-alert {
            background: #ffedd5;
            color: #c2410c;
        }

        .surveillance-status-pill.status-emergency {
            background: #ffe4e6;
            color: #be123c;
        }

        /* Sidebar Layout */
        html,
        body {
            overflow-x: hidden;
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        .sidebar {
            position: fixed;
            top: 80px;
            left: 0;
            bottom: 0;
            width: 5rem;
            padding: 1rem 0.5rem;
            background: transparent;
            border-right: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
            z-index: 40;
            overflow-y: auto;
            overflow-x: hidden;
            height: calc(100% - 80px);
            transition: width 0.2s ease, padding 0.2s ease;
        }

        .main-with-sidebar {
            width: calc(100% - 5rem);
            margin-left: 5rem;
            min-width: 0;
            overflow-x: hidden;
            transition: width 0.2s ease, margin-left 0.2s ease;
        }

        .main-with-sidebar > div {
            min-width: 0;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 34px;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .sidebar:hover .sidebar-header {
            opacity: 1;
            pointer-events: auto;
        }

        .sidebar-logo {
            width: 44px;
            height: 44px;
            display: grid;
            place-items: center;
            border-radius: 16px;
            background: linear-gradient(135deg, #c8e6c9 0%, #a5d6a7 100%);
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.15);
            flex-shrink: 0;
        }

        .sidebar-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: #111827;
            line-height: 1.2;
            white-space: nowrap;
        }

        .sidebar-subtitle {
            font-size: 0.82rem;
            color: #6b7280;
            margin-top: 2px;
            white-space: nowrap;
        }

        .sidebar-nav {
            display: grid;
            gap: 5px;
        }

        .sidebar-section-label {
            overflow: hidden;
            max-height: 0;
            margin: 0;
            padding: 0;
            opacity: 0;
            transition: opacity 0.15s ease, max-height 0.2s ease, margin 0.2s ease;
        }

        .sidebar-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 16px;
            color: #475569;
            font-weight: 600;
            font-size: 0.92rem;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid transparent;
            justify-content: flex-start;
            position: relative;
            white-space: normal;
            overflow: visible;
        }

        .sidebar:hover .sidebar-item {
            padding: 10px 12px;
            justify-content: flex-start;
        }

        .sidebar .sidebar-item-text {
            display: inline-block;
            opacity: 0;
            max-width: 0;
            overflow: hidden;
            vertical-align: middle;
            transform: none;
            line-height: 1.25;
            white-space: nowrap;
            transition: opacity 0.15s ease, max-width 0.2s ease;
        }

        .dashboard-footer {
            width: calc(100% - 5rem);
            margin-left: 5rem;
            transition: width 0.2s ease, margin-left 0.2s ease;
        }

        .sidebar:hover {
            width: 16rem;
            padding: 1rem;
        }

        .sidebar:hover .sidebar-section-label {
            max-height: 2rem;
            margin-bottom: 0.75rem;
            opacity: 1;
        }

        .sidebar:hover .sidebar-item-text {
            max-width: 13rem;
            opacity: 1;
        }

        .sidebar-item:hover {
            background: linear-gradient(135deg, #f6fbf6, #f0f8f2);
            color: #111827;
            border-color: #c8e6c9;
        }

        .sidebar-item.active {
            background: #3d4a1e;
            color: #ffffff;
            border-color: #3d4a1e;
            box-shadow: 0 2px 8px rgba(61, 74, 30, 0.16);
        }

        .sidebar-item i {
            width: 30px;
            height: 30px;
            display: grid;
            place-items: center;
            border-radius: 14px;
            background: linear-gradient(135deg, #e8f5e9, #d4e9db);
            color: #2d5016;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .sidebar-item:hover i {
            background: linear-gradient(135deg, #c8e6c9, #a5d6a7);
            color: #1b5e20;
        }

        .dashboard-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 26px 0;
        }

        .dashboard-topbar-title {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 800;
            color: #111827;
        }

        .dashboard-topbar-subtitle {
            margin: 0;
            color: #6b7280;
            font-size: 0.95rem;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            border: 1px solid #a5d6a7;
            color: #1b5e20;
            font-weight: 600;
            font-size: 0.9rem;
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.1);
        }

        .status-pill span {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: linear-gradient(135deg, #66bb6a, #43a047);
            box-shadow: 0 0 6px rgba(76, 175, 80, 0.4);
        }

        .summary-card {
            background: linear-gradient(135deg, #ffffff 0%, #f9faf8 100%);
            border: 1px solid #e5e7eb;
            border-radius: 24px;
            padding: 24px;
            box-shadow: 0 2px 12px rgba(15, 23, 42, 0.06), 0 8px 24px rgba(15, 23, 42, 0.04);
            transition: all 0.3s ease;
        }

        .summary-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.12), 0 12px 32px rgba(15, 23, 42, 0.08);
            border-color: #d1fae5;
        }

        .summary-card-icon {
            width: 46px;
            height: 46px;
            display: grid;
            place-items: center;
            border-radius: 16px;
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.12), rgba(56, 142, 60, 0.08));
            color: #2d5016;
            box-shadow: inset 0 1px 3px rgba(76, 175, 80, 0.1);
        }

        .summary-card-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .summary-card-badge.today { background: #eff6ff; color: #2563eb; }
        .summary-card-badge.pending { background: #fffbeb; color: #92400e; }
        .summary-card-badge.stock { background: #ecfdf5; color: #166534; }
        .summary-card-badge.queue { background: #f5f3ff; color: #7c3aed; }
        .summary-card-badge.surveillance { background: #fefce8; color: #b45309; }

        .section-card {
            background: linear-gradient(135deg, #ffffff 0%, #fafbf8 100%);
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 2px 16px rgba(15, 23, 42, 0.05), 0 8px 20px rgba(15, 23, 42, 0.03);
        }

        .section-card h3 {
            margin-top: 0;
            margin-bottom: 16px;
            font-size: 1.1rem;
            font-weight: 700;
            color: #111827;
        }

        .scan-card {
            background: linear-gradient(135deg, #f5f9f1 0%, #eef4e9 100%);
            border: 1px solid #d4e9db;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .scan-card:hover {
            border-color: #a5d6a7;
            box-shadow: 0 4px 16px rgba(76, 175, 80, 0.1);
        }

        .manual-card {
            background: linear-gradient(135deg, #ffffff 0%, #f9faf8 100%);
            border: 1px solid #c8e6c9;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .manual-card:hover {
            border-color: #81c784;
            box-shadow: 0 4px 16px rgba(76, 175, 80, 0.12);
        }

        .search-input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 14px;
            border: 1px solid #d1d5db;
            font-size: 0.95rem;
            color: #111827;
            background: linear-gradient(to bottom, #ffffff, #f9faf8);
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .search-input:focus {
            outline: none;
            border-color: #4f7726;
            box-shadow: 0 0 0 3px rgba(79, 119, 38, 0.1), 0 2px 8px rgba(79, 119, 38, 0.12);
        }

        .manual-search-dropdown {
            position: relative;
            width: 100%;
            z-index: 10;
        }

        .manual-search-dropdown .dropdown-panel {
            width: 100%;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
            border: 1px solid #d1d5db;
            background: #ffffff;
            border-radius: 20px;
            max-height: 360px;
            overflow-y: auto;
        }

        .manual-search-option {
            width: 100%;
            text-align: left;
            padding: 1rem 1rem;
            border-bottom: 1px solid #e5e7eb;
            background: #ffffff;
            border-radius: 16px;
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            transition: background 0.2s ease, transform 0.2s ease;
            cursor: pointer;
        }

        .manual-search-option:last-child {
            border-bottom: none;
        }

        .manual-search-option:hover {
            background: #f8fafc;
            transform: translateX(2px);
        }

        .manual-search-option .patient-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            font-size: 0.8rem;
            color: #64748b;
        }

        .manual-search-option .patient-meta span {
            background: #f1f5f9;
            padding: 0.25rem 0.5rem;
            border-radius: 999px;
        }

        .primary-button {
            background: linear-gradient(135deg, #4f7726 0%, #3d5a1d 100%);
            color: white;
            border-radius: 14px;
            padding: 14px 20px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(69, 75, 27, 0.2);
            border: none;
            cursor: pointer;
        }

        .primary-button:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #5a8a2e 0%, #475920 100%);
            box-shadow: 0 8px 20px rgba(69, 75, 27, 0.3);
        }

        .header-actions {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: nowrap;
            min-width: 0;
        }

        .header-actions .text-right {
            min-width: 0;
            flex: 1 1 auto;
        }

        .header-actions .text-right > div {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .mobile-nav-toggle {
            display: none;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 36px;
            gap: 0;
            padding: 4px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            color: #1f2937;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .mobile-nav-toggle:hover,
        .mobile-nav-toggle.active {
            background: #f3f4f6;
            border-color: #cbd5e1;
        }

        .mobile-nav-icon {
            display: grid;
            gap: 3px;
            width: 14px;
            height: 14px;
        }

        .mobile-nav-icon span {
            display: block;
            width: 14px;
            height: 2px;
            background: #111827;
            border-radius: 999px;
        }

        @media (max-width: 1023px) {
            .mobile-nav-toggle {
                display: inline-flex;
                flex-shrink: 0;
                order: 0;
                margin-right: 0;
            }

            .header-actions {
                width: 100%;
                justify-content: space-between;
            }

            .header-actions .text-right {
                order: 1;
                flex: 1 1 auto;
                min-width: 0;
                text-align: right;
            }

            .header-actions .profile-dropdown-container {
                order: 2;
                flex-shrink: 0;
            }

            .sidebar {
                position: fixed;
                top: 80px;
                left: 0;
                right: 0;
                width: auto;
                height: calc(100vh - 80px);
                max-height: calc(100vh - 80px);
                padding: 24px 18px;
                border-right: none;
                box-shadow: none;
                background: white;
                border-bottom: 0;
                overflow-x: hidden;
                overflow-y: auto;
                overscroll-behavior: contain;
                -webkit-overflow-scrolling: touch;
                z-index: 9999;
                display: none;
            }

            .sidebar.active {
                display: block;
            }

            .sidebar:hover {
                width: auto;
                padding: 24px 18px;
            }

            .sidebar .sidebar-section-label,
            .sidebar:hover .sidebar-section-label {
                max-height: 2rem;
                margin-bottom: 0.75rem;
                opacity: 1;
            }

            .sidebar .sidebar-item-text,
            .sidebar:hover .sidebar-item-text {
                max-width: none;
                opacity: 1;
                white-space: normal;
            }

            .main-with-sidebar {
                width: 100%;
                margin-left: 0;
            }

            .dashboard-footer {
                width: 100%;
                margin-left: 0;
            }

            .dashboard-topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 14px;
                padding-bottom: 10px;
            }

            nav.border-b > div {
                flex-wrap: wrap;
                justify-content: space-between;
                gap: 12px;
                height: auto;
            }

            nav.border-b .text-right {
                min-width: 0;
                flex: 1 1 auto;
            }

            nav.border-b .profile-dropdown-container {
                flex-shrink: 0;
            }

            nav.border-b > div.h-20 {
                height: auto;
                min-height: auto;
                padding-top: 16px;
                padding-bottom: 16px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                padding: 18px 14px;
            }

            .sidebar-nav {
                display: grid;
                gap: 10px;
            }

            .sidebar-item {
                padding: 12px 14px;
                font-size: 0.95rem;
                border-radius: 18px;
                min-height: auto;
                white-space: normal;
                justify-content: flex-start;
            }

            .sidebar-item i {
                width: 30px;
                height: 30px;
            }

            .dashboard-topbar-title {
                font-size: 1.85rem;
            }

            .dashboard-topbar-subtitle {
                font-size: 0.95rem;
            }
        }

        @media (max-width: 768px) {
            nav.border-b > div {
                flex-wrap: wrap;
                justify-content: space-between;
                align-items: center;
                gap: 10px;
            }

            nav.border-b .header-actions {
                justify-content: flex-end;
                flex-wrap: wrap;
            }

            nav.border-b .text-right {
                text-align: right;
            }

            .sidebar {
                padding: 18px 14px;
                background: #ffffff;
            }

            .sidebar-nav {
                gap: 10px;
            }

            .sidebar-item {
                gap: 10px;
                padding: 12px 14px;
                font-size: 0.95rem;
                border-radius: 18px;
                min-height: auto;
                white-space: normal;
                justify-content: flex-start;
            }

            .sidebar-item i {
                width: 30px;
                height: 30px;
            }

            .dashboard-topbar {
                width: 100%;
                padding: 16px 0;
                gap: 10px;
            }

            .dashboard-topbar-title {
                font-size: 1.85rem;
            }

            .dashboard-topbar-subtitle {
                font-size: 0.95rem;
            }

            .main-with-sidebar {
                padding-bottom: 20px;
            }

            .summary-card,
            .section-card,
            .scan-card {
                padding: 18px;
            }

            .hover\:scale-105:hover,
            .hover\:shadow-lg:hover {
                transform: none !important;
                box-shadow: none !important;
            }

            .group:hover .group-hover\:opacity-100 {
                opacity: 1 !important;
            }

            .dashboard-topbar .profile-dropdown-container {
                width: auto;
            }

            .dashboard-topbar .text-right {
                width: 100%;
                text-align: left;
            }

            nav.border-b > div {
                flex-wrap: wrap;
                justify-content: space-between;
                gap: 12px;
            }
        }

        @media (max-width: 600px) {
            .sidebar {
                padding: 14px 12px;
            }

            .sidebar-nav {
                gap: 8px;
            }

            .sidebar-item {
                gap: 10px;
                padding: 10px 12px;
                font-size: 0.92rem;
                border-radius: 16px;
            }

            .sidebar-item i {
                width: 28px;
                height: 28px;
            }

            .dashboard-topbar {
                padding: 14px 0;
            }

            .dashboard-topbar-title {
                font-size: 1.6rem;
            }

            .dashboard-topbar-subtitle {
                font-size: 0.9rem;
            }

            .main-with-sidebar {
                padding-bottom: 20px;
            }

            .summary-card,
            .section-card,
            .scan-card {
                padding: 16px;
            }

            .bg-white.border.border-slate-200.rounded-xl,
            .border.border-slate-200.rounded-\[28px\],
            .rounded-\[32px\] {
                border-radius: 18px !important;
            }

            .dashboard-topbar .profile-dropdown-container {
                width: auto;
            }
        }

        @media (max-width: 480px) {
            .dashboard-topbar-title {
                font-size: 1.35rem;
            }

            .dashboard-topbar-subtitle {
                font-size: 0.88rem;
            }

            .sidebar-item {
                font-size: 0.88rem;
                padding: 10px 10px;
            }

            .sidebar-item i {
                width: 26px;
                height: 26px;
            }

            .sidebar {
                padding: 12px 12px;
            }

            .sidebar-nav {
                gap: 6px;
            }

            .main-with-sidebar {
                padding-bottom: 16px;
            }

            .grid.grid-cols-1.sm\:grid-cols-2.md\:grid-cols-3.lg\:grid-cols-5 {
                gap: 14px;
            }

            .module-content > div {
                padding: 0;
            }

            .bg-white.border.border-slate-200.rounded-xl,
            .border.border-slate-200.rounded-\[28px\],
            .rounded-\[32px\] {
                border-radius: 14px !important;
            }

            .dashboard-topbar .text-right {
                width: 100%;
            }
        }

        /* Module Content Animations */
        .module-content {
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Sync Status Badge Animation */
        .sync-pulse {
            animation: syncPulse 2s infinite;
        }

        @keyframes syncPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }

        /* Status Color Scheme */
        .status-scheduled { color: #2563eb; }
        .status-pending { color: #f59e0b; }
        .status-completed { color: #10b981; }
        .status-no-show { color: #ef4444; }
        .status-void { color: #6b7280; }

        /* ====================================================================
           QR SCANNER STYLES - html5-qrcode Integration
           ==================================================================== */
        #qrScannerContainer {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            z-index: 30000;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        #qrScannerContainer.active {
            display: flex;
        }

        .qr-scanner-box {
            background: white;
            border-radius: 16px;
            padding: 24px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .qr-scanner-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid #e2e8f0;
        }

        .qr-scanner-header h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: #454B1B;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .qr-scanner-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #64748b;
            transition: color 0.3s ease;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qr-scanner-close:hover {
            color: #454B1B;
        }

        #html5-qrcode {
            width: 100% !important;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 16px;
        }

        .qr-scanner-status {
            text-align: center;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 16px;
        }

        .qr-scanner-status.scanning {
            background-color: #dbeafe;
            color: #1e40af;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .qr-scanner-status.success {
            background-color: #dcfce7;
            color: #166534;
        }

        .qr-scanner-status.error {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .qr-scanner-spinner {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 2px solid #1e40af;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .qr-scanner-instructions {
            text-align: center;
            color: #64748b;
            font-size: 13px;
            margin-bottom: 16px;
            line-height: 1.5;
        }

        .qr-scanner-buttons {
            display: flex;
            gap: 12px;
        }

        .qr-scanner-button {
            flex: 1;
            padding: 12px 16px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }

        .qr-scanner-button-primary {
            background: linear-gradient(135deg, #4f7726 0%, #3d5a1d 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(69, 75, 27, 0.15);
        }

        .qr-scanner-button-primary:hover:not(:disabled) {
            background: linear-gradient(135deg, #5a8a2e 0%, #475920 100%);
            box-shadow: 0 6px 16px rgba(69, 75, 27, 0.25);
            transform: translateY(-1px);
        }

        .qr-scanner-button-secondary {
            background: linear-gradient(135deg, #e2e8f0, #d4d8e5);
            color: #334155;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
        }

        .qr-scanner-button-secondary:hover {
            background: linear-gradient(135deg, #cbd5e1, #bfc4d6);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transform: translateY(-1px);
        }

        .qr-scanner-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        html.dark .qr-scanner-box,
        body.dark-mode .qr-scanner-box {
            background: #111827;
            border: 1px solid #334155;
            color: #e2e8f0;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.55);
        }

        html.dark .qr-scanner-header,
        body.dark-mode .qr-scanner-header {
            border-bottom-color: #334155;
        }

        html.dark .qr-scanner-header h2,
        body.dark-mode .qr-scanner-header h2 {
            color: #d9f99d;
        }

        html.dark .qr-scanner-close,
        body.dark-mode .qr-scanner-close {
            color: #94a3b8;
        }

        html.dark .qr-scanner-close:hover,
        body.dark-mode .qr-scanner-close:hover {
            color: #d9f99d;
        }

        html.dark #html5-qrcode,
        body.dark-mode #html5-qrcode {
            background: #020617;
            border: 1px solid #334155;
        }

        html.dark .qr-scanner-status.scanning,
        body.dark-mode .qr-scanner-status.scanning {
            background-color: #172554;
            color: #bfdbfe;
        }

        html.dark .qr-scanner-status.success,
        body.dark-mode .qr-scanner-status.success {
            background-color: #052e16;
            color: #bbf7d0;
        }

        html.dark .qr-scanner-status.error,
        body.dark-mode .qr-scanner-status.error {
            background-color: #450a0a;
            color: #fecaca;
        }

        html.dark .qr-scanner-instructions,
        body.dark-mode .qr-scanner-instructions {
            color: #94a3b8;
        }

        html.dark .qr-scanner-button-secondary,
        body.dark-mode .qr-scanner-button-secondary {
            background: linear-gradient(135deg, #334155, #1e293b);
            color: #e2e8f0;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.25);
        }

        html.dark .qr-scanner-button-secondary:hover,
        body.dark-mode .qr-scanner-button-secondary:hover {
            background: linear-gradient(135deg, #475569, #334155);
        }

        /* Mobile responsiveness */
        body.dark-mode #rescheduleAppointmentDate {
            color-scheme: dark;
        }

        body.dark-mode #rescheduleAppointmentDate::-webkit-calendar-picker-indicator {
            filter: brightness(0) invert(1);
            opacity: 0.9;
        }

        @media (max-width: 600px) {
            .qr-scanner-box {
                padding: 16px;
            }

            .qr-scanner-header h2 {
                font-size: 18px;
            }
        }

        /* ====================================================================
           PROFILE PICTURE UPLOAD STYLES
           ==================================================================== */
        .profile-upload-input {
            display: none;
        }

        .profile-pic-navbar {
            position: relative;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #454B1B 0%, #6B7D28 100%);
            border: 2px solid white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            overflow: hidden;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }

        .profile-pic-navbar:hover {
            transform: scale(1.08);
            box-shadow: 0 2px 8px rgba(69, 75, 27, 0.3);
        }

        .profile-pic-navbar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-pic-navbar-text {
            font-weight: 700;
            color: white;
            font-size: 14px;
            letter-spacing: 0.5px;
        }

        .profile-pic-overlay-navbar {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .profile-pic-navbar:hover .profile-pic-overlay-navbar {
            opacity: 1;
        }

        .profile-pic-overlay-navbar i {
            color: white;
            font-size: 16px;
        }

        .profile-upload-status {
            border-radius: 8px;
            padding: 12px 16px;
            font-weight: 500;
            display: none;
            animation: slideInRight 0.3s ease;
        }

        .profile-upload-status.success {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .profile-upload-status.error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* ====================================================================
           PROFILE DROPDOWN MENU STYLES
           ==================================================================== */
        .profile-dropdown-container {
            position: relative;
            display: inline-block;
        }

        .profile-dropdown-btn {
            position: relative;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid white;
            background: linear-gradient(135deg, #454B1B 0%, #2a2d0f 100%);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .profile-dropdown-btn:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .profile-dropdown-btn.active {
            box-shadow: 0 4px 12px rgba(69, 75, 27, 0.3);
        }

        .profile-dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 8px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            min-width: 260px;
            z-index: 1000;
            border: 1px solid #e2e8f0;
            animation: slideDown 0.2s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .profile-dropdown-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
        }

        .dropdown-profile-img,
        .dropdown-profile-initials {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            background: linear-gradient(135deg, #454B1B 0%, #2a2d0f 100%);
            flex-shrink: 0;
            font-size: 14px;
        }

        .dropdown-profile-img {
            object-fit: cover;
        }

        .dropdown-name {
            font-weight: 600;
            color: #1e293b;
            font-size: 14px;
        }

        .dropdown-role {
            font-size: 12px;
            color: #64748b;
            margin-top: 2px;
        }

        .profile-dropdown-divider {
            height: 1px;
            background-color: #e2e8f0;
            margin: 8px 0;
        }

        .dropdown-menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #1e293b;
            text-decoration: none;
            transition: all 0.2s ease;
            font-size: 14px;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }

        .dropdown-menu-item:hover {
            background-color: #f8fafc;
            color: #454B1B;
        }

        .dropdown-menu-item i {
            width: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #454B1B;
        }

        .dropdown-menu-item.logout-item {
            color: #dc2626;
            border-top: 1px solid #e2e8f0;
            margin-top: 8px;
        }

        .dropdown-menu-item.logout-item:hover {
            background-color: #fee2e2;
        }

        .dropdown-menu-item.logout-item i {
            color: #dc2626;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* ================================================================
           DARK MODE - BHW DASHBOARD (COMPLETE FROM RESIDENT)
           ================================================================ */
        
        html,
        body {
            background-color: #f8fafc !important;
            color: #0f172a !important;
        }

        html.dark,
        html.dark body,
        body.dark-mode {
            background-color: #0d1310 !important;
            color: #f8fafc !important;
        }

        body.dark-mode h1,
        body.dark-mode h2,
        body.dark-mode h3,
        body.dark-mode h4,
        body.dark-mode h5,
        body.dark-mode h6,
        body.dark-mode p,
        body.dark-mode span,
        body.dark-mode label,
        body.dark-mode a,
        body.dark-mode button,
        body.dark-mode div {
            color: #cbd5e1 !important;
        }

        body.dark-mode nav {
            background: #0f172a !important;
            border-color: #334155 !important;
        }

        body.dark-mode .profile-dropdown-menu {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;
            border: 1px solid #334155 !important;
        }

        body.dark-mode .profile-dropdown-header,
        body.dark-mode #settingsModal > div > div,
        body.dark-mode #bhwLogoutConfirmModal > div {
            background: #1e293b !important;
            border-color: #334155 !important;
        }

        body.dark-mode .dropdown-menu-item:hover {
            background-color: rgba(69, 75, 27, 0.15) !important;
            color: #454B1B !important;
        }

        body.dark-mode input,
        body.dark-mode select,
        body.dark-mode textarea {
            background: #0f172a !important;
            border-color: #334155 !important;
            color: #cbd5e1 !important;
        }

        body.dark-mode input[type="checkbox"],
        body.dark-mode input[type="radio"] {
            accent-color: #454B1B !important;
        }

        /* Dark theme dashboard polish */
        body.dark-mode nav {
            background: #020617 !important;
            border-color: #111827 !important;
        }

        body.dark-mode .sidebar {
            background: #020617 !important;
            border-color: #111827 !important;
        }

        body.dark-mode .sidebar-item {
            background: transparent !important;
            color: #cbd5e1 !important;
            border-color: transparent !important;
        }

        body.dark-mode .sidebar-item:hover {
            background: rgba(148, 163, 184, 0.08) !important;
            color: #ffffff !important;
            border-color: #334155 !important;
        }

        body.dark-mode .sidebar-item.active {
            background: rgba(56, 189, 248, 0.12) !important;
            color: #f8fafc !important;
            border-color: rgba(56, 189, 248, 0.32) !important;
            box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.12) !important;
        }

        body.dark-mode .summary-card,
        body.dark-mode .section-card,
        body.dark-mode .scan-card,
        body.dark-mode .manual-card,
        body.dark-mode .bg-white,
        body.dark-mode .bg-slate-50,
        body.dark-mode .bg-slate-100,
        body.dark-mode .bg-blue-50,
        body.dark-mode .bg-emerald-50,
        body.dark-mode .bg-amber-50,
        body.dark-mode .bg-purple-50,
        body.dark-mode .bg-[#F8FAF6] {
            background-color: #0f172a !important;
            border-color: #111827 !important;
            color: #e2e8f0 !important;
        }

        body.dark-mode .summary-card:hover {
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.38) !important;
        }

        body.dark-mode .search-input {
            background: #0f172a !important;
            border-color: #334155 !important;
            color: #e2e8f0 !important;
        }

        body.dark-mode .search-input::placeholder {
            color: #94a3b8 !important;
        }

        body.dark-mode .primary-button {
            background-color: #38bdf8 !important;
            color: #0f172a !important;
        }

        body.dark-mode .primary-button:hover {
            background-color: #0ea5e9 !important;
        }

        body.dark-mode h1,
        body.dark-mode h2,
        body.dark-mode h3,
        body.dark-mode h4,
        body.dark-mode h5,
        body.dark-mode h6 {
            color: #cbd5e1 !important;
        }

        body.dark-mode p,
        body.dark-mode span,
        body.dark-mode label,
        body.dark-mode small {
            color: #cbd5e1 !important;
        }

        /* Tables - DARK MODE */
        body.dark-mode table {
            background-color: #1e293b !important;
            border-color: #334155 !important;
        }

        body.dark-mode thead {
            background-color: #0f172a !important;
            border-color: #334155 !important;
        }

        body.dark-mode thead th {
            background-color: #0f172a !important;
            color: #cbd5e1 !important;
            border-color: #334155 !important;
        }

        body.dark-mode tbody tr {
            background-color: #1e293b !important;
            border-color: #334155 !important;
        }

        body.dark-mode tbody tr:hover {
            background-color: rgba(69, 75, 27, 0.1) !important;
        }

        body.dark-mode tbody td {
            background-color: #1e293b !important;
            color: #cbd5e1 !important;
            border-color: #334155 !important;
        }

        body.dark-mode th,
        body.dark-mode td {
            color: #cbd5e1 !important;
            border-color: #334155 !important;
        }

        /* Override Tailwind classes for dark mode */
        body.dark-mode.bg-\[#F8FAF6\] {
            background-color: #0d1310 !important;
        }

        body.dark-mode {
            background-color: #0d1310 !important;
        }

        body.dark-mode [class*="bg-white"],
        body.dark-mode [class*="bg-slate"],
        body.dark-mode [class*="bg-gray"] {
            background-color: #1e293b !important;
        }

        body.dark-mode [class*="text-slate"],
        body.dark-mode [class*="text-gray"],
        body.dark-mode [class*="text-black"] {
            color: #cbd5e1 !important;
        }

        body.dark-mode .bg-white {
            background-color: #1e293b !important;
        }

        body.dark-mode .text-slate-800,
        body.dark-mode .text-gray-800,
        body.dark-mode .text-black {
            color: #cbd5e1 !important;
        }

        body.dark-mode .border-\[#454B1B\]\/10 {
            border-color: #334155 !important;
        }

        /* ACTION BUTTONS - Rendered, No Show, Follow-up */
        /* Rendered (Green) button */
        body.dark-mode button[onclick*="rendered"] {
            background: linear-gradient(to right, #10b981 0%, #059669 100%) !important;
            background-image: linear-gradient(to right, #10b981 0%, #059669 100%) !important;
            color: white !important;
        }

        body.dark-mode button[onclick*="rendered"]:hover {
            background: linear-gradient(to right, #34d399 0%, #10b981 100%) !important;
            background-image: linear-gradient(to right, #34d399 0%, #10b981 100%) !important;
        }

        /* No Show (Red) button */
        body.dark-mode button[onclick*="no-show"],
        body.dark-mode button[title="Mark as No Show"] {
            background: linear-gradient(to right, #ef4444 0%, #dc2626 100%) !important;
            background-image: linear-gradient(to right, #ef4444 0%, #dc2626 100%) !important;
            color: white !important;
        }

        body.dark-mode button[onclick*="no-show"]:hover,
        body.dark-mode button[title="Mark as No Show"]:hover {
            background: linear-gradient(to right, #f87171 0%, #ef4444 100%) !important;
            background-image: linear-gradient(to right, #f87171 0%, #ef4444 100%) !important;
        }

        /* Follow-up (Yellow) button */
        body.dark-mode button[onclick*="followup"],
        body.dark-mode button[title="Schedule Follow-up"] {
            background: linear-gradient(to right, #fbbf24 0%, #f59e0b 100%) !important;
            background-image: linear-gradient(to right, #fbbf24 0%, #f59e0b 100%) !important;
            color: #1f2937 !important;
        }

        body.dark-mode button[onclick*="followup"]:hover,
        body.dark-mode button[title="Schedule Follow-up"]:hover {
            background: linear-gradient(to right, #fcd34d 0%, #fbbf24 100%) !important;
            background-image: linear-gradient(to right, #fcd34d 0%, #fbbf24 100%) !important;
        }

        #bhwLogoutConfirmModal {
            z-index: 20000 !important;
        }

        /* Other gradients in dark mode */
        body.dark-mode .bg-gradient-to-br,
        body.dark-mode .bg-gradient-to-b,
        body.dark-mode .bg-gradient-to-l {
            background-image: none !important;
            background: #1e293b !important;
        }

        body.dark-mode section[class*="bg-gradient"] {
            background: #1e293b !important;
        }

        body.dark-mode div[class*="bg-gradient"]:not([onclick*="rendered"]):not([onclick*="no-show"]):not([onclick*="followup"]):not([title*="Mark"]):not([title*="Schedule"]) {
            background: #1e293b !important;
        }

        /* Ensure all text labels are visible in dark mode */
        body.dark-mode .text-slate-400,
        body.dark-mode .text-slate-500,
        body.dark-mode .text-gray-400,
        body.dark-mode .text-gray-500,
        body.dark-mode .text-slate-300 {
            color: #cbd5e1 !important;
        }

        body.dark-mode .font-body,
        body.dark-mode .font-heading {
            color: #cbd5e1 !important;
        }

        body.dark-mode [class*="text-"] {
            color: #cbd5e1 !important;
        }

        /* Cards and their content in dark mode */
        body.dark-mode [class*="rounded-lg"],
        body.dark-mode [class*="rounded-md"],
        body.dark-mode [class*="rounded"] {
            background-color: #1e293b !important;
            color: #cbd5e1 !important;
        }

        /* Small labels and secondary text */
        body.dark-mode small,
        body.dark-mode .text-sm,
        body.dark-mode [class*="text-sm"] {
            color: #cbd5e1 !important;
        }

        /* Ensure inline elements are readable */
        body.dark-mode i {
            color: #cbd5e1 !important;
        }

        /* ================================================================
           COMPREHENSIVE DARK MODE ICON COLORS - ALL ICONS
           ================================================================ */
        
        /* Default icon color */
        body.dark-mode i,
        body.dark-mode .fa,
        body.dark-mode [class*="fa-"] {
            color: #cbd5e1 !important;
        }

        /* Calendar & Date Icons */
        body.dark-mode .fa-calendar,
        body.dark-mode .fa-calendar-check,
        body.dark-mode .fa-calendar-plus,
        body.dark-mode .fa-calendar-alt,
        body.dark-mode .fa-calendar-days {
            color: #fbbf24 !important;
        }

        /* Time & Clock Icons */
        body.dark-mode .fa-clock,
        body.dark-mode .fa-hourglass,
        body.dark-mode .fa-hourglass-end,
        body.dark-mode .fa-watch,
        body.dark-mode .fa-timer {
            color: #60a5fa !important;
        }

        /* User & Profile Icons */
        body.dark-mode .fa-user,
        body.dark-mode .fa-user-circle,
        body.dark-mode .fa-user-alt,
        body.dark-mode .fa-user-check,
        body.dark-mode .fa-user-md,
        body.dark-mode .fa-id-card,
        body.dark-mode .fa-address-card,
        body.dark-mode .fa-people,
        body.dark-mode .fa-users,
        body.dark-mode .fa-user-group {
            color: #a78bfa !important;
        }

        /* History & Records Icons */
        body.dark-mode .fa-history,
        body.dark-mode .fa-list,
        body.dark-mode .fa-list-check,
        body.dark-mode .fa-file,
        body.dark-mode .fa-file-medical,
        body.dark-mode .fa-book,
        body.dark-mode .fa-scroll,
        body.dark-mode .fa-archive,
        body.dark-mode .fa-database {
            color: #34d399 !important;
        }

        /* Success & Check Icons */
        body.dark-mode .fa-check,
        body.dark-mode .fa-check-circle,
        body.dark-mode .fa-check-double,
        body.dark-mode .fa-check-square,
        body.dark-mode .fa-circle-check,
        body.dark-mode .fa-thumbs-up,
        body.dark-mode .fa-checkmark {
            color: #4ade80 !important;
        }

        /* Error & Delete Icons */
        body.dark-mode .fa-times,
        body.dark-mode .fa-times-circle,
        body.dark-mode .fa-xmark,
        body.dark-mode .fa-x,
        body.dark-mode .fa-circle-xmark,
        body.dark-mode .fa-ban,
        body.dark-mode .fa-trash,
        body.dark-mode .fa-trash-alt,
        body.dark-mode .fa-delete,
        body.dark-mode .fa-close {
            color: #f87171 !important;
        }

        /* Notification & Bell Icons */
        body.dark-mode .fa-bell,
        body.dark-mode .fa-bell-alt,
        body.dark-mode .fa-bell-slash {
            color: #fbbf24 !important;
        }

        /* Security & Lock Icons */
        body.dark-mode .fa-lock,
        body.dark-mode .fa-lock-alt,
        body.dark-mode .fa-padlock,
        body.dark-mode .fa-key,
        body.dark-mode .fa-password,
        body.dark-mode .fa-shield,
        body.dark-mode .fa-shield-check {
            color: #60a5fa !important;
        }

        /* Settings & Configuration Icons */
        body.dark-mode .fa-cog,
        body.dark-mode .fa-gear,
        body.dark-mode .fa-sliders,
        body.dark-mode .fa-sliders-h,
        body.dark-mode .fa-wrench,
        body.dark-mode .fa-tools,
        body.dark-mode .fa-screwdriver {
            color: #34d399 !important;
        }

        /* Theme & Appearance Icons */
        body.dark-mode .fa-moon,
        body.dark-mode .fa-sun,
        body.dark-mode .fa-star,
        body.dark-mode .fa-palette {
            color: #fbbf24 !important;
        }

        /* Logout & Exit Icons */
        body.dark-mode .fa-sign-out,
        body.dark-mode .fa-sign-out-alt,
        body.dark-mode .fa-door-open,
        body.dark-mode .fa-right-from-bracket,
        body.dark-mode .fa-arrow-right-from-bracket {
            color: #f87171 !important;
        }

        /* Warning & Alert Icons */
        body.dark-mode .fa-exclamation,
        body.dark-mode .fa-exclamation-circle,
        body.dark-mode .fa-exclamation-triangle,
        body.dark-mode .fa-triangle-exclamation,
        body.dark-mode .fa-warning,
        body.dark-mode .fa-alert {
            color: #fbbf24 !important;
        }

        /* Medical & Health Icons */
        body.dark-mode .fa-stethoscope,
        body.dark-mode .fa-heart,
        body.dark-mode .fa-heart-pulse,
        body.dark-mode .fa-pulse,
        body.dark-mode .fa-hospital,
        body.dark-mode .fa-hospital-alt,
        body.dark-mode .fa-clinic-medical,
        body.dark-mode .fa-pills,
        body.dark-mode .fa-capsules,
        body.dark-mode .fa-prescription,
        body.dark-mode .fa-medicine,
        body.dark-mode .fa-bottle,
        body.dark-mode .fa-syringe,
        body.dark-mode .fa-bandage,
        body.dark-mode .fa-microscope,
        body.dark-mode .fa-dna {
            color: #ef4444 !important;
        }

        /* Action & Navigation Icons */
        body.dark-mode .fa-arrow-right,
        body.dark-mode .fa-arrow-left,
        body.dark-mode .fa-arrow-up,
        body.dark-mode .fa-arrow-down,
        body.dark-mode .fa-arrow-right-long,
        body.dark-mode .fa-arrow-left-long,
        body.dark-mode .fa-chevron-right,
        body.dark-mode .fa-chevron-left,
        body.dark-mode .fa-chevron-down,
        body.dark-mode .fa-chevron-up,
        body.dark-mode .fa-angles-right,
        body.dark-mode .fa-angles-left,
        body.dark-mode .fa-play,
        body.dark-mode .fa-pause,
        body.dark-mode .fa-next {
            color: #60a5fa !important;
        }

        /* Plus & Add Icons */
        body.dark-mode .fa-plus,
        body.dark-mode .fa-plus-circle,
        body.dark-mode .fa-circle-plus,
        body.dark-mode .fa-add {
            color: #4ade80 !important;
        }

        /* Minus & Remove Icons */
        body.dark-mode .fa-minus,
        body.dark-mode .fa-minus-circle,
        body.dark-mode .fa-circle-minus {
            color: #f87171 !important;
        }

        /* Search & Find Icons */
        body.dark-mode .fa-search,
        body.dark-mode .fa-magnifying-glass,
        body.dark-mode .fa-zoom-in,
        body.dark-mode .fa-binoculars {
            color: #a78bfa !important;
        }

        /* Download & Upload Icons */
        body.dark-mode .fa-download,
        body.dark-mode .fa-upload,
        body.dark-mode .fa-cloud-download,
        body.dark-mode .fa-cloud-upload {
            color: #60a5fa !important;
        }

        /* Edit & Edit Icons */
        body.dark-mode .fa-edit,
        body.dark-mode .fa-pencil,
        body.dark-mode .fa-pencil-alt,
        body.dark-mode .fa-pen,
        body.dark-mode .fa-pen-to-square {
            color: #fbbf24 !important;
        }

        /* Copy & Duplicate Icons */
        body.dark-mode .fa-copy,
        body.dark-mode .fa-clone,
        body.dark-mode .fa-duplicate {
            color: #34d399 !important;
        }

        /* Filter & Sort Icons */
        body.dark-mode .fa-filter,
        body.dark-mode .fa-funnel,
        body.dark-mode .fa-sort,
        body.dark-mode .fa-sort-down,
        body.dark-mode .fa-sort-up {
            color: #a78bfa !important;
        }

        /* Print & Save Icons */
        body.dark-mode .fa-print,
        body.dark-mode .fa-save,
        body.dark-mode .fa-floppy-disk,
        body.dark-mode .fa-download {
            color: #60a5fa !important;
        }

        /* Phone & Communication Icons */
        body.dark-mode .fa-phone,
        body.dark-mode .fa-phone-alt,
        body.dark-mode .fa-mobile,
        body.dark-mode .fa-mobile-alt,
        body.dark-mode .fa-phone-square,
        body.dark-mode .fa-envelope,
        body.dark-mode .fa-envelope-open,
        body.dark-mode .fa-envelope-square,
        body.dark-mode .fa-mail,
        body.dark-mode .fa-sms,
        body.dark-mode .fa-message {
            color: #4ade80 !important;
        }

        /* Location & Map Icons */
        body.dark-mode .fa-location,
        body.dark-mode .fa-location-dot,
        body.dark-mode .fa-map,
        body.dark-mode .fa-map-location,
        body.dark-mode .fa-compass,
        body.dark-mode .fa-location-pin {
            color: #ef4444 !important;
        }

        /* Document & Clipboard Icons */
        body.dark-mode .fa-clipboard,
        body.dark-mode .fa-clipboard-check,
        body.dark-mode .fa-clipboard-list,
        body.dark-mode .fa-document,
        body.dark-mode .fa-file-text {
            color: #a78bfa !important;
        }

        /* Barcode & QR Icons */
        body.dark-mode .fa-barcode,
        body.dark-mode .fa-qrcode,
        body.dark-mode .fa-barcode-read,
        body.dark-mode .fa-scanner {
            color: #4ade80 !important;
        }

        /* Dashboard & View Icons */
        body.dark-mode .fa-chart,
        body.dark-mode .fa-chart-bar,
        body.dark-mode .fa-chart-line,
        body.dark-mode .fa-chart-pie,
        body.dark-mode .fa-dashboard,
        body.dark-mode .fa-eye,
        body.dark-mode .fa-eye-alt {
            color: #60a5fa !important;
        }

        /* ================================================================
           LOGOUT BUTTON - DARK MODE STYLING
           ================================================================ */
        
        /* Logout button with border in dark mode */
        body.dark-mode button[onclick="confirmBHWLogout()"],
        body.dark-mode button[onclick*="confirmBHWLogout"] {
            border: 2px solid #f87171 !important;
            background-color: #dc2626 !important;
            color: white !important;
        }

        body.dark-mode button[onclick="confirmBHWLogout()"]:hover,
        body.dark-mode button[onclick*="confirmBHWLogout"]:hover {
            border-color: #fca5a5 !important;
            background-color: #b91c1c !important;
        }

        /* Modal background in dark mode */
        body.dark-mode #bhwLogoutConfirmModal {
            background-color: rgba(0, 0, 0, 0.7) !important;
        }

        /* Modal dialog in dark mode */
        body.dark-mode #bhwLogoutConfirmModal .bg-white {
            background-color: #1e293b !important;
            border-color: #334155 !important;
        }

        /* Modal header border in dark mode */
        body.dark-mode #bhwLogoutConfirmModal [class*="border-b"] {
            border-color: #334155 !important;
        }

        /* Modal content text in dark mode */
        body.dark-mode #bhwLogoutConfirmModal .text-slate-900 {
            color: #f1f5f9 !important;
        }

        body.dark-mode #bhwLogoutConfirmModal .text-slate-600 {
            color: #cbd5e1 !important;
        }

        /* Cancel button in dark mode */
        body.dark-mode button[onclick="closeBHWLogoutConfirmModal()"] {
            background-color: #334155 !important;
            color: #e2e8f0 !important;
            border-color: #475569 !important;
        }

        body.dark-mode button[onclick="closeBHWLogoutConfirmModal()"]:hover {
            background-color: #475569 !important;
            color: #f1f5f9 !important;
        }

        /* ================================================================
           SETTINGS MODAL BUTTONS - DARK MODE STYLING
           ================================================================ */
        
        /* Settings modal background in dark mode */
        body.dark-mode #settingsModal {
            background-color: rgba(0, 0, 0, 0.7) !important;
        }

        /* Settings modal dialog in dark mode */
        body.dark-mode #settingsModal .bg-white {
            background-color: #1e293b !important;
        }

        /* Settings modal header in dark mode */
        body.dark-mode #settingsModal [class*="border-b"] {
            border-color: #334155 !important;
        }

        body.dark-mode #settingsModal .text-slate-900 {
            color: #f1f5f9 !important;
        }

        body.dark-mode #settingsModal .text-slate-500 {
            color: #cbd5e1 !important;
        }

        body.dark-mode #settingsModal .text-slate-700 {
            color: #e2e8f0 !important;
        }

        /* Settings modal section headings in dark mode */
        body.dark-mode #settingsModal h4 {
            color: #f1f5f9 !important;
        }

        /* Close button in settings modal (dark mode) */
        body.dark-mode button[onclick="closeSettingsModal()"] {
            background-color: #334155 !important;
            color: #cbd5e1 !important;
            border-color: #475569 !important;
        }

        body.dark-mode button[onclick="closeSettingsModal()"]:hover {
            background-color: #475569 !important;
            color: #f1f5f9 !important;
        }

        /* Save Settings button in settings modal (dark mode) */
        body.dark-mode button[onclick="saveSettings()"] {
            background-color: #454B1B !important;
            color: white !important;
            border: 2px solid #6b7c2f !important;
        }

        body.dark-mode button[onclick="saveSettings()"]:hover {
            background-color: #5a6324 !important;
            border-color: #8ba83f !important;
        }

        /* Change Password button in settings modal (dark mode) */
        body.dark-mode button[onclick="changePassword()"] {
            background-color: #3b82f6 !important;
            color: white !important;
            border: 1px solid #60a5fa !important;
        }

        body.dark-mode button[onclick="changePassword()"]:hover {
            background-color: #2563eb !important;
            border-color: #93c5fd !important;
        }

        /* Settings modal footer border in dark mode */
        body.dark-mode #settingsModal [class*="border-t"] {
            border-color: #334155 !important;
        }

        /* Bright colored icons in dark mode */
        body.dark-mode .fa-calendar {
            color: #fbbf24 !important;
        }

        body.dark-mode .fa-clock {
            color: #60a5fa !important;
        }

        body.dark-mode .fa-user,
        body.dark-mode .fa-user-circle {
            color: #a78bfa !important;
        }

        body.dark-mode .fa-history {
            color: #34d399 !important;
        }

        body.dark-mode .fa-check {
            color: #4ade80 !important;
        }

        body.dark-mode .fa-times {
            color: #f87171 !important;
        }

        body.dark-mode .fa-bell {
            color: #fbbf24 !important;
        }

        body.dark-mode .fa-lock {
            color: #a78bfa !important;
        }

        body.dark-mode .fa-moon {
            color: #fbbf24 !important;
        }

        body.dark-mode .fa-key {
            color: #60a5fa !important;
        }

        body.dark-mode .fa-cog,
        body.dark-mode .fa-gear {
            color: #34d399 !important;
        }

        body.dark-mode .fa-sign-out-alt {
            color: #f87171 !important;
        }

        body.dark-mode .fa-exclamation-circle {
            color: #fbbf24 !important;
        }

        /* Light color text for all labels - admin panel style */
        body.dark-mode label,
        body.dark-mode span,
        body.dark-mode .text-sm,
        body.dark-mode [class*="text-sm"],
        body.dark-mode small,
        body.dark-mode h2,
        body.dark-mode h3,
        body.dark-mode h4 {
            color: #cbd5e1 !important;
        }

        /* Stat cards with gradient backgrounds in dark mode */
        body.dark-mode .bg-blue-50 {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%) !important;
        }

        body.dark-mode .bg-green-50 {
            background: linear-gradient(135deg, #15803d 0%, #16a34a 100%) !important;
        }

        body.dark-mode .bg-orange-50 {
            background: linear-gradient(135deg, #92400e 0%, #b45309 100%) !important;
        }

        body.dark-mode .bg-red-50 {
            background: linear-gradient(135deg, #7f1d1d 0%, #991b1b 100%) !important;
        }

        body.dark-mode .bg-purple-50 {
            background: linear-gradient(135deg, #5b21b6 0%, #7c3aed 100%) !important;
        }

        /* Ensure text is visible on gradient backgrounds */
        body.dark-mode .text-blue-700 {
            color: #bfdbfe !important;
        }

        body.dark-mode .text-green-700 {
            color: #86efac !important;
        }

        body.dark-mode .text-orange-700 {
            color: #fed7aa !important;
        }

        body.dark-mode .text-red-700 {
            color: #fca5a5 !important;
        }

        body.dark-mode .text-purple-700 {
            color: #d8b4fe !important;
        }

        /* Secondary text colors */
        body.dark-mode .text-blue-600 {
            color: #93c5fd !important;
        }

        body.dark-mode .text-green-600 {
            color: #6ee7b7 !important;
        }

        body.dark-mode .text-orange-600 {
            color: #fdba74 !important;
        }

        body.dark-mode .text-red-600 {
            color: #f87171 !important;
        }

        body.dark-mode .text-purple-600 {
            color: #c084fc !important;
        }

        /* Dashboard navy layout theme */
        html.dark main,
        body.dark-mode main,
        html.dark .main-content,
        body.dark-mode .main-content,
        html.dark .dashboard-container,
        body.dark-mode .dashboard-container {
            background-color: #0b1329 !important;
        }

        html.dark > body,
        body.dark-mode {
            background-color: #0b1329 !important;
        }

        html.dark body > nav,
        body.dark-mode > nav,
        html.dark nav.border-b,
        body.dark-mode nav.border-b {
            background-color: #111a36 !important;
            border-color: rgba(51, 65, 85, 0.5) !important;
        }

        html.dark .sidebar,
        body.dark-mode .sidebar,
        html.dark .sidebar-nav,
        body.dark-mode .sidebar-nav {
            background-color: #111a36 !important;
            border-color: rgba(51, 65, 85, 0.5) !important;
        }

        html.dark main .summary-card,
        body.dark-mode main .summary-card,
        html.dark main .section-card,
        body.dark-mode main .section-card,
        html.dark main .scan-card,
        body.dark-mode main .scan-card,
        html.dark main .manual-card,
        body.dark-mode main .manual-card,
        html.dark main [class*="rounded-2xl"][class*="bg-white"],
        body.dark-mode main [class*="rounded-2xl"][class*="bg-white"],
        html.dark main [class*="rounded-[28px]"],
        body.dark-mode main [class*="rounded-[28px]"] {
            background: #1a254b !important;
            border-color: rgba(51, 65, 85, 0.5) !important;
        }

        html.dark .sidebar-item.active,
        body.dark-mode .sidebar-item.active,
        html.dark main button[class*="bg-[#3D4A1E]"],
        body.dark-mode main button[class*="bg-[#3D4A1E]"] {
            background: linear-gradient(to right, #10b981, #0d9488) !important;
            border-color: rgba(16, 185, 129, 0.65) !important;
            box-shadow: 0 0 18px rgba(16, 185, 129, 0.18) !important;
        }

        html.dark main .border-slate-200,
        body.dark-mode main .border-slate-200,
        html.dark main [class*="border-slate-200"],
        body.dark-mode main [class*="border-slate-200"] {
            border-color: rgba(51, 65, 85, 0.5) !important;
        }

        /* BHW mobile layout: keep the dashboard at the viewport width instead
           of carrying desktop header, sidebar, card, and table dimensions. */
        @media (max-width: 640px) {
            /* The header is fixed. Reserve a little extra space below it so
               module actions (including Surveillance's Save Updates button)
               never sit underneath the header after a mobile navigation. */
            body {
                padding-top: calc(4.5rem + env(safe-area-inset-top, 0px)) !important;
            }

            #bhwHeader {
                height: 4rem !important;
            }

            #bhwHeader > div {
                height: 4rem !important;
                gap: 0.75rem !important;
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }

            #bhwHeader .mobile-nav-toggle {
                width: 2.5rem;
                height: 2.5rem;
            }

            #bhwHeader img[src="images/SClogo.png"] {
                width: 2.25rem;
                height: 2.25rem;
            }

            #bhwHeader .font-heading {
                font-size: 1.125rem;
            }

            #bhwHeader .profile-dropdown-container {
                gap: 0;
            }

            #addMedicineModal,
            #editMedicineModal {
                z-index: 11000 !important;
                align-items: flex-start !important;
                padding-top: calc(4.75rem + env(safe-area-inset-top, 0px)) !important;
                padding-bottom: 0.75rem !important;
                overflow-y: auto !important;
            }

            #addMedicineModal > div,
            #editMedicineModal > div {
                width: 100% !important;
                max-height: calc(100dvh - 5.5rem) !important;
                margin-bottom: 0 !important;
            }

            .sidebar {
                top: 4rem !important;
                bottom: 0 !important;
                height: calc(100dvh - 4rem) !important;
                max-height: calc(100dvh - 4rem) !important;
                padding: 0.875rem !important;
                background: #ffffff !important;
                border-color: #e2e8f0 !important;
                box-shadow: 0 18px 38px rgba(15, 23, 42, 0.16) !important;
            }

            html.dark .sidebar,
            body.dark-mode .sidebar {
                background: #111a36 !important;
                border-color: rgba(51, 65, 85, 0.75) !important;
            }

            .main-with-sidebar {
                width: 100% !important;
                margin-left: 0 !important;
                overflow-x: clip;
            }

            .main-with-sidebar > .mx-auto {
                padding: 1.5rem 1rem 2rem !important;
            }

            .main-with-sidebar h1 {
                font-size: 1.5rem;
                line-height: 1.25;
            }

            .main-with-sidebar h2 {
                font-size: 1.25rem;
                line-height: 1.3;
            }

            .main-with-sidebar h3 {
                line-height: 1.35;
            }

            .main-with-sidebar .rounded-2xl.p-5,
            .main-with-sidebar .rounded-2xl.p-6,
            .main-with-sidebar .rounded-xl.p-6,
            .main-with-sidebar .border.rounded-\[28px\].p-5,
            .main-with-sidebar .border.rounded-\[28px\].p-6 {
                padding: 1rem !important;
            }

            .main-with-sidebar input,
            .main-with-sidebar select,
            .main-with-sidebar textarea,
            .main-with-sidebar button {
                min-height: 2.75rem;
            }

            .main-with-sidebar .inline-flex.items-center.gap-2.self-start {
                max-width: 100%;
            }

            /* Keep consultation and medicine controls compact on narrow screens. */
            #consultationModule select,
            #inventoryModule select {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                min-height: 2.5rem !important;
                padding: 0.5rem 0.75rem !important;
                font-size: 0.875rem !important;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            /* The modules themselves must be allowed to shrink.  Without this,
               long option labels and the inventory header can expand the page. */
            #consultationModule,
            #inventoryModule,
            #consultationModule form,
            #inventoryModule > .bg-white {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                box-sizing: border-box;
            }

            #consultationModule #patientDetailsPanel .grid {
                grid-template-columns: 1fr;
            }

            #consultationModule #patientDetailsPanel p {
                overflow-wrap: anywhere;
            }

            #consultationModule textarea,
            #inventoryModule input,
            #inventoryModule button {
                font-size: 0.875rem !important;
            }

            #consultationModule .bg-white.border,
            #inventoryModule .bg-white.border {
                padding: 1rem !important;
            }

            #inventoryModule .overflow-x-auto {
                margin-left: -1rem;
                margin-right: -1rem;
                max-width: calc(100% + 2rem);
            }

            #inventoryModule > .bg-white > .flex.items-center.justify-between {
                flex-direction: column;
                align-items: stretch;
                gap: 0.75rem;
                padding: 1rem;
            }

            #inventoryModule > .bg-white > .flex.items-center.justify-between button {
                width: 100%;
            }

            #inventoryModule [role="tablist"] {
                flex-direction: column;
                gap: 0.25rem;
                padding: 0.75rem 1rem 0;
            }

            #inventoryModule [role="tablist"] button {
                width: 100%;
                border-radius: 0.5rem 0.5rem 0 0;
            }

            /* Data grids stay usable on a narrow phone: their wrapper scrolls,
               while the cells and action buttons use a compact touch layout. */
            .main-with-sidebar .overflow-x-auto {
                -webkit-overflow-scrolling: touch;
            }

            .main-with-sidebar table {
                min-width: 36rem;
                font-size: 0.75rem !important;
            }

            .main-with-sidebar table th,
            .main-with-sidebar table td {
                padding: 0.625rem 0.75rem !important;
                white-space: nowrap;
            }

            .main-with-sidebar table td .flex {
                min-width: 8rem;
            }

            #qrScannerContainer {
                align-items: stretch;
                justify-content: flex-start;
                overflow-y: auto;
                padding: 0.75rem;
            }

            .qr-scanner-box {
                width: 100%;
                max-width: none;
                margin: auto 0;
                padding: 1rem;
            }

            #html5-qrcode {
                height: min(72vw, 19rem) !important;
            }

            .qr-scanner-buttons {
                flex-direction: column;
                gap: 0.5rem;
            }

            .qr-scanner-button {
                width: 100%;
                min-height: 2.75rem;
            }
        }
    </style>

    <!-- ====================================================================
         HTML5-QRCODE LIBRARY - Live Webcam QR Scanning
         Source: https://unpkg.com/html5-qrcode
         ==================================================================== -->
    <script src="https://unpkg.com/html5-qrcode"></script>
</head>
<body class="min-h-screen bg-[#f8faf6] font-body text-slate-800 antialiased selection:bg-[#3D4A1E] selection:text-white dark:bg-[#0b1329] dark:text-slate-100">

    <!-- ========================================================================
         HEADER & NAVIGATION BAR (included from components/header_bhw.php)
         ======================================================================== -->
    <?php
    // Render the saved avatar on the first response. This prevents the initials
    // from flashing while the client-side profile-picture request completes.
    $profile_picture = $_SESSION['profile_picture'] ?? $_SESSION['profile_pic'] ?? $_SESSION['avatar'] ?? null;
    if (empty($profile_picture)) {
        try {
            $profilePicturePdo = getDatabaseConnection();
            $profilePictureStmt = $profilePicturePdo->prepare(
                'SELECT profile_picture FROM health_worker WHERE worker_id = ? LIMIT 1'
            );
            $profilePictureStmt->execute([$worker_id]);
            $profile_picture = $profilePictureStmt->fetchColumn() ?: null;
            if ($profile_picture) {
                $_SESSION['profile_picture'] = $profile_picture;
                $_SESSION['profile_pic'] = $profile_picture;
                $_SESSION['avatar'] = $profile_picture;
            }
        } catch (Throwable $profilePictureError) {
            error_log('BHW PROFILE PICTURE LOAD ERROR: ' . $profilePictureError->getMessage());
        }
    }
    include __DIR__ . '/components/header_bhw_new.php';
    ?>

    <?php include __DIR__ . '/components/approval_poll.php'; ?>

    <aside class="sidebar !bottom-0 !overflow-y-auto !overflow-x-hidden bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900 lg:block">
        <nav id="mobileSidebarNav" class="sidebar-nav" aria-label="Main navigation">
            <p class="sidebar-section-label px-3 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Workspace</p>
            <a href="?module=appointments" class="sidebar-item <?php echo isModuleActive('appointments') ? 'active !bg-[#3D4A1E] !text-white' : 'dark:!text-slate-300'; ?>">
                <i class="fas fa-calendar-alt"></i> <span class="sidebar-item-text">Appointments</span>
            </a>
            <a href="?module=identify-patient" class="sidebar-item <?php echo isModuleActive('identify-patient') ? 'active !bg-[#3D4A1E] !text-white' : 'dark:!text-slate-300'; ?>">
                <i class="fas fa-user-check"></i> <span class="sidebar-item-text">Walk-in</span>
            </a>
            <?php if (false): ?>
            <a href="?module=consultation" class="sidebar-item <?php echo isModuleActive('consultation') ? 'active !bg-[#3D4A1E] !text-white' : 'dark:!text-slate-300'; ?>">
                <i class="fas fa-stethoscope"></i> <span class="sidebar-item-text">Consultations</span>
            </a>
            <?php endif; ?>
            <a href="?module=inventory" class="sidebar-item <?php echo isModuleActive('inventory') ? 'active !bg-[#3D4A1E] !text-white' : 'dark:!text-slate-300'; ?>">
                <i class="fas fa-pills"></i> <span class="sidebar-item-text">Medicine Inventory</span>
            </a>
            <?php if (false): ?>
            <a href="?module=surveillance" class="sidebar-item <?php echo isModuleActive('surveillance') ? 'active !bg-[#3D4A1E] !text-white' : 'dark:!text-slate-300'; ?>">
                <i class="fas fa-shield-virus"></i> <span class="sidebar-item-text">Surveillance</span>
            </a>
            <?php endif; ?>
            <p class="sidebar-section-label mt-8 px-3 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Operations</p>
            <a href="?module=file-requests" data-offline-control title="File Requests are unavailable while offline" class="sidebar-item <?php echo isModuleActive('file-requests') ? 'active !bg-[#3D4A1E] !text-white' : 'dark:!text-slate-300'; ?>">
                <i class="fas fa-file-arrow-down"></i> <span class="sidebar-item-text">File Requests</span>
            </a>
            <a href="?module=sms" class="sidebar-item <?php echo isModuleActive('sms') ? 'active !bg-[#3D4A1E] !text-white' : 'dark:!text-slate-300'; ?>">
                <i class="fas fa-sms"></i> <span class="sidebar-item-text">SMS Communications</span>
            </a>
            <a href="?module=announcements" class="sidebar-item <?php echo isModuleActive('announcements') ? 'active !bg-[#3D4A1E] !text-white' : 'dark:!text-slate-300'; ?>">
                <i class="fas fa-bullhorn"></i> <span class="sidebar-item-text">Announcements</span>
            </a>
        </nav>
    </aside>

    <!-- ========================================================================
         MAIN DASHBOARD CONTAINER
         ======================================================================== -->
    <main class="bg-dark-grid main-with-sidebar bg-[#f8fafc] dark:bg-[#0b1329] flex-1">
        <div class="mx-auto w-full max-w-7xl px-4 pb-12 pt-8 sm:px-6 lg:px-8">

            <section class="mb-8">
                <div class="mb-5 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p class="mb-2 text-sm font-semibold uppercase tracking-[0.18em] text-[#3D4A1E] dark:text-emerald-300">BHW workspace</p>
                        <h1 class="font-heading text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">Good day, <?php echo htmlspecialchars($worker_name); ?></h1>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Manage community care from one focused dashboard.</p>
                    </div>
                    <div class="inline-flex items-center gap-2 self-start rounded-full border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300 sm:self-auto">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span> System online
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <div class="mb-4 flex items-center justify-between"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-300"><i class="fas fa-calendar-check"></i></span><span class="text-xs font-semibold text-slate-400">Today</span></div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Appointments</p><p class="mt-1 text-3xl font-bold text-slate-900 dark:text-white"><?php echo intval($total_appointments); ?></p>
                    </div>
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <div class="mb-4 flex items-center justify-between"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-300"><i class="fas fa-clock"></i></span><span class="text-xs font-semibold text-slate-400">Queue</span></div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Pending Consultations</p><p class="mt-1 text-3xl font-bold text-slate-900 dark:text-white"><?php echo intval($pending_consultations); ?></p>
                    </div>
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <div class="mb-4 flex items-center justify-between"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-300"><i class="fas fa-pills"></i></span><span class="text-xs font-semibold text-slate-400">In stock</span></div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Available Medicine</p><p class="mt-1 text-3xl font-bold text-slate-900 dark:text-white"><?php echo intval($medicine_available); ?></p>
                    </div>
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <div class="mb-4 flex items-center justify-between"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-200"><i class="fas fa-boxes-stacked"></i></span><span class="text-xs font-semibold text-slate-400">Catalog</span></div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Items</p><p class="mt-1 text-3xl font-bold text-slate-900 dark:text-white"><?php echo intval($medicine_count); ?></p>
                    </div>
                </div>
            </section>

            <!-- ====================================================================
                 TABBED MODULE NAVIGATION
                 ==================================================================== -->
            <div class="mb-8 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <?php if (isModuleActive('identify-patient')): ?>
                <div class="flex items-center gap-3 border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200">
                        <i class="fas fa-user-plus text-lg"></i>
                    </div>
                    <div>
                        <p class="text-sm uppercase tracking-[0.3em] text-slate-400 mb-1">ScanCare</p>
                        <h2 class="m-0 text-xl font-heading font-bold text-slate-900 dark:text-white">Walk-in</h2>
                    </div>
                </div>
                <?php endif; ?>

                <!-- MODULE CONTENT AREA -->
                <div class="p-4 module-content">

                    <!-- ============================================================
                         MODULE: IDENTIFY PATIENT
                         ============================================================ -->
                    <?php if (isModuleActive('identify-patient')): ?>
                    <div>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                            <!-- QR Code Scan Option -->
                            <div class="border border-slate-200 rounded-[28px] p-5 text-center bg-slate-50 shadow-sm">
                                <div class="w-16 h-16 bg-slate-900/10 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-qrcode text-3xl text-slate-900"></i>
                                </div>
                                <h3 class="text-lg font-heading font-bold text-slate-900 mb-2">Scan Patient QR Code</h3>
                                <p class="text-sm text-slate-600 mb-6">Point your camera at the patient's QR ID card for instant identification.</p>
                                <button type="button" data-offline-control onclick="initiateCameraCapture()" class="w-full rounded-2xl bg-[#3D4A1E] px-4 py-3 text-sm font-semibold text-white transition-all hover:bg-[#303a18] inline-flex items-center justify-center gap-2">
                                    <i class="fas fa-camera"></i> Start QR Scanner
                                </button>
                            </div>

                            <!-- ====================================================================
                                 QR SCANNER CONTAINER - Hidden by default, shown on button click
                                 ==================================================================== -->
                            <div id="qrScannerContainer">
                                <div class="qr-scanner-box">
                                    <!-- Header -->
                                    <div class="qr-scanner-header">
                                        <h2>Patient QR Scanner</h2>
                                        <button class="qr-scanner-close" onclick="stopQRScanner()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>

                                    <!-- Camera Feed Container (html5-qrcode will inject here) -->
                                    <div id="html5-qrcode" style="width: 100%; height: 300px;"></div>

                                    <!-- Status Message -->
                                    <div id="qrScannerStatus" class="qr-scanner-status scanning">
                                        <div class="qr-scanner-spinner"></div>
                                        <span>Initializing camera...</span>
                                    </div>

                                    <!-- Instructions -->
                                    <div class="qr-scanner-instructions">
                                        <i class="fas fa-phone-alt" style="color: #454B1B; margin-right: 8px;"></i>
                                        Point your phone's camera at the patient's QR code
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="qr-scanner-buttons">
                                        <!-- Fallback: upload QR image when camera unavailable -->
                                        <input type="file" id="qrImageFile" accept="image/*" style="display:none" />
                                        <button type="button" data-offline-control onclick="document.getElementById('qrImageFile').click()" class="qr-scanner-button qr-scanner-button-secondary" style="margin-right:8px;">
                                            <i class="fas fa-upload" style="margin-right:6px;"></i> Upload QR Image
                                        </button>
                                        <button type="button" data-offline-control onclick="testCamera()" class="qr-scanner-button qr-scanner-button-primary" style="margin-right:8px; background:#2b6a30; color:#fff;">
                                            <i class="fas fa-video" style="margin-right:6px;"></i> Test Camera
                                        </button>
                                        <button onclick="stopQRScanner()" class="qr-scanner-button qr-scanner-button-secondary">
                                            <i class="fas fa-times" style="margin-right: 6px;"></i> Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Manual Entry Option -->
                            <div class="border border-emerald-200 rounded-[28px] p-6 bg-emerald-50 shadow-sm">
                                <div class="flex items-center gap-2 mb-4">
                                    <div class="w-16 h-16 bg-emerald-100 rounded-2xl flex items-center justify-center">
                                        <i class="fas fa-keyboard text-3xl text-emerald-600"></i>
                                    </div>
                                    <span class="inline-block px-3 py-1 bg-emerald-200 text-emerald-800 text-xs font-bold rounded-full">No QR Available</span>
                                </div>
                                <h3 class="text-lg font-heading font-bold text-slate-900 mb-2">Manual Entry</h3>
                                <p class="text-sm text-slate-600 mb-6">Search by name, email, phone number, or resident ID</p>
                                
                                <!-- Error Banner -->
                                <div id="manualSearchErrorBanner" style="display: none;" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3">
                                    <div class="flex-shrink-0 mt-0.5">
                                        <i class="fas fa-exclamation-circle text-red-600 text-lg"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p id="manualSearchErrorText" class="text-sm font-semibold text-red-800">Error message will appear here</p>
                                    </div>
                                    <button type="button" onclick="closeManualSearchError()" class="flex-shrink-0 text-red-400 hover:text-red-600 transition-colors">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                
                                <!-- Search Form -->
                                <form id="manualEntryForm" class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-1">Search Patient</label>
                                        <input type="text" name="search_term" placeholder="e.g., Maria, maria@email.com, 09171234567, or ID 27" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B]" required autocomplete="off" oninput="handleManualSearchInput(event)">
                                        <p class="text-xs text-slate-500 mt-1">Name • Email • Phone • ID</p>
                                    </div>
                                    <button type="submit" class="w-full rounded-2xl bg-[#3D4A1E] px-4 py-3 text-sm font-semibold text-white transition-all hover:bg-[#303a18] inline-flex items-center justify-center gap-2">
                                        <i class="fas fa-search"></i> Search Patient
                                    </button>
                                </form>

                                <div id="manualSearchResults" class="mt-4 hidden manual-search-dropdown">
                                    <div class="dropdown-panel">
                                        <div class="rounded-2xl bg-white p-4">
                                            <div class="mb-3 flex items-center justify-between">
                                                <div>
                                                    <h4 class="text-sm font-semibold text-slate-800">Select a matching patient</h4>
                                                    <p class="text-xs text-slate-500">Choose the correct person from the list below.</p>
                                                </div>
                                                <button type="button" onclick="clearManualSearchResults()" class="text-slate-400 hover:text-slate-600 text-sm">Clear</button>
                                            </div>
                                        </div>
                                        <div id="manualSearchResultsList" class="space-y-2 p-4"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ============================================================
                         MODULE: PATIENT PROFILE VIEW
                         ============================================================ -->
                    <?php if (isModuleActive('patient-profile')): ?>
                    <div>
                        <h2 class="text-2xl font-heading font-bold text-slate-900 mb-6">Patient Profile</h2>
                        
                        <?php if ($patient_identified && $patient_data): ?>
                            <!-- PATIENT PROFILE LOADED - Display Full Profile -->
                            <div class="space-y-6">
                                <!-- Complete Profile Card -->
                                <div class="bg-white border border-slate-200 rounded-xl p-8">
                                    <h3 class="text-lg font-heading font-bold text-slate-900 mb-6 flex items-center gap-2">
                                        <i class="fas fa-user text-[#454B1B]"></i> Complete Profile
                                    </h3>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                                        <!-- Full Name -->
                                        <div class="space-y-2">
                                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Full Name</p>
                                            <p class="text-lg font-semibold text-slate-900"><?php echo htmlspecialchars($patient_data['full_name'] ?? 'N/A'); ?></p>
                                        </div>
                                        
                                        <!-- Email -->
                                        <div class="space-y-2">
                                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Email Address</p>
                                            <p class="text-slate-900"><a href="mailto:<?php echo htmlspecialchars($patient_data['email'] ?? ''); ?>" class="text-[#454B1B] hover:underline"><?php echo htmlspecialchars($patient_data['email'] ?? 'N/A'); ?></a></p>
                                        </div>
                                        
                                        <!-- Phone -->
                                        <div class="space-y-2">
                                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Phone Number</p>
                                            <p class="text-slate-900"><a href="tel:<?php echo htmlspecialchars($patient_data['contact_number'] ?? ''); ?>" class="text-[#454B1B] hover:underline"><?php echo htmlspecialchars($patient_data['contact_number'] ?? 'N/A'); ?></a></p>
                                        </div>
                                        
                                        <!-- Resident ID -->
                                        <div class="space-y-2">
                                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Resident ID</p>
                                            <p class="text-slate-900 font-mono text-lg"><?php echo htmlspecialchars($patient_data['patient_code'] ?? str_pad((string)($patient_data['resident_id'] ?? 0), 6, '0', STR_PAD_LEFT)); ?></p>
                                        </div>
                                        
                                        <!-- Sex -->
                                        <div class="space-y-2">
                                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Sex</p>
                                            <p class="text-slate-900"><?php echo htmlspecialchars($patient_data['sex'] ?? 'N/A'); ?></p>
                                        </div>
                                        
                                        <!-- Date of Birth -->
                                        <div class="space-y-2">
                                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Date of Birth</p>
                                            <p class="text-slate-900">
                                                <?php 
                                                if (!empty($patient_data['date_of_birth'])) {
                                                    echo date('M d, Y', strtotime($patient_data['date_of_birth']));
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <!-- Address Section -->
                                    <div class="mt-8 pt-8 border-t border-slate-200">
                                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">Address</p>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <?php 
                                                // Parse address string if separate fields don't exist
                                                $street_address = $patient_data['address'] ?? 'N/A';
                                                $barangay = $patient_data['barangay'] ?? 'N/A';
                                                $city = $patient_data['city'] ?? 'N/A';
                                                $province_postal = ($patient_data['province'] ?? '') . ' ' . ($patient_data['postal_code'] ?? '');
                                                
                                                // If all separate fields are N/A, try to parse from single address string
                                                if (($patient_data['address'] ?? '') && $barangay === 'N/A' && $city === 'N/A') {
                                                    $address_parts = array_map('trim', explode(',', $patient_data['address']));
                                                    $street_address = $address_parts[0] ?? 'N/A';
                                                    $barangay = $address_parts[1] ?? 'N/A';
                                                    $city = $address_parts[2] ?? 'N/A';
                                                    $province_postal = $address_parts[3] ?? 'N/A';
                                                }
                                            ?>
                                            <div>
                                                <p class="text-sm text-slate-600 mb-1">Street Address</p>
                                                <p class="font-semibold text-slate-900"><?php echo htmlspecialchars($street_address); ?></p>
                                            </div>
                                            <div>
                                                <p class="text-sm text-slate-600 mb-1">Barangay</p>
                                                <p class="font-semibold text-slate-900"><?php echo htmlspecialchars($barangay); ?></p>
                                            </div>
                                            <div>
                                                <p class="text-sm text-slate-600 mb-1">City</p>
                                                <p class="font-semibold text-slate-900"><?php echo htmlspecialchars($city); ?></p>
                                            </div>
                                            <div>
                                                <p class="text-sm text-slate-600 mb-1">Province / Postal Code</p>
                                                <p class="font-semibold text-slate-900"><?php echo htmlspecialchars(trim($province_postal)); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Medical History Card -->
                                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-8">
                                    <div class="flex items-center justify-between mb-6">
                                        <h3 class="text-lg font-heading font-bold text-emerald-900 flex items-center gap-2">
                                            <i class="fas fa-file-medical text-emerald-600"></i> Medical Visit History
                                        </h3>
                                        <?php if (!empty($patient_data['medical_history']) && count($patient_data['medical_history']) > 0): ?>
                                            <button type="button" id="clearHistoryBtn_<?php echo $patient_id; ?>" onclick="clearMedicalHistory(<?php echo $patient_id; ?>, this)" class="px-3 py-1 bg-red-100 text-red-700 text-xs font-semibold rounded-lg hover:bg-red-200 transition-colors duration-200 flex items-center gap-1">
                                                <i class="fas fa-trash-alt"></i> Clear History
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($patient_data['medical_history']) && count($patient_data['medical_history']) > 0): ?>
                                        <div class="mb-4 p-3 bg-emerald-100 border border-emerald-300 rounded-lg text-sm text-emerald-800">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            <strong><?php echo count($patient_data['medical_history']); ?></strong> medical record(s) found
                                        </div>
                                        <div class="space-y-4 max-h-96 overflow-y-auto">
                                            <?php foreach ($patient_data['medical_history'] as $record):
                                                $clinicalData = json_decode($record['treatment'] ?? '', true);
                                                $clinicalData = is_array($clinicalData) ? $clinicalData : [];
                                                $height = trim((string) ($clinicalData['height'] ?? ''));
                                                $weight = trim((string) ($clinicalData['weight'] ?? ''));
                                                $bloodPressure = trim((string) ($clinicalData['bp'] ?? ''));
                                                $pulse = trim((string) ($clinicalData['pr'] ?? ''));
                                                $temperature = trim((string) ($clinicalData['temp'] ?? ''));
                                                $historyLabels = [
                                                    'asthma' => 'Asthma',
                                                    'epilepsy' => 'Epilepsy',
                                                    'headache' => 'Sakit sa Ulo',
                                                    'diabetes' => 'Diabetes',
                                                    'hypertension' => 'Hypertension',
                                                    'allergies' => 'Allergies',
                                                    'medications' => 'On Medications',
                                                    'hospitalization' => 'Hospitalization / Surgery'
                                                ];
                                                $customHistoryLabels = array_filter((array) ($clinicalData['medical_history_options'] ?? []), 'is_string');
                                                $historyFlags = array_map(function ($item) use ($historyLabels, $customHistoryLabels) {
                                                    return $customHistoryLabels[$item] ?? $historyLabels[$item] ?? $item;
                                                }, (array) ($clinicalData['medical_history'] ?? []));
                                            ?>
                                                <div class="bg-white border-l-4 border-emerald-500 rounded-lg p-4">
                                                    <div class="flex items-start justify-between mb-2">
                                                        <div>
                                                            <span class="inline-block px-2 py-1 bg-emerald-100 text-emerald-800 text-xs font-semibold rounded mb-2">Medical Visit</span>
                                                            <h4 class="font-semibold text-slate-900"><?php echo htmlspecialchars($record['diagnosis'] ?: 'Clinical Examination'); ?></h4>
                                                        </div>
                                                        <span class="text-xs text-slate-500">
                                                            <?php echo !empty($record['visit_date']) ? date('M d, Y', strtotime($record['visit_date'])) : 'Date not recorded'; ?>
                                                        </span>
                                                    </div>
                                                    <?php if (!empty($record['appointment_id'])): ?>
                                                        <p class="mb-2 text-xs font-semibold text-emerald-700">Linked to appointment #<?php echo (int) $record['appointment_id']; ?><?php if (!empty($record['consulted_at'])): ?> · Consultation saved <?php echo date('M d, Y g:i A', strtotime($record['consulted_at'])); ?><?php endif; ?></p>
                                                    <?php endif; ?>
                                                    <?php if (!empty($historyFlags)): ?><p class="mb-3 text-sm text-slate-700"><strong>Medical history:</strong> <?php echo htmlspecialchars(implode(', ', $historyFlags)); ?></p><?php endif; ?>
                                                    <?php if (!empty($clinicalData['history_notes'])): ?><p class="mb-3 text-sm text-slate-700"><strong>History notes:</strong> <?php echo htmlspecialchars($clinicalData['history_notes']); ?></p><?php endif; ?>
                                                    <div class="grid grid-cols-2 gap-3 rounded-lg bg-slate-50 p-3 text-sm sm:grid-cols-3">
                                                        <div><span class="block text-xs font-semibold uppercase text-slate-500">Height</span><span class="font-medium text-slate-900"><?php echo htmlspecialchars($height !== '' ? $height . ' cm' : 'Not recorded'); ?></span></div>
                                                        <div><span class="block text-xs font-semibold uppercase text-slate-500">Weight</span><span class="font-medium text-slate-900"><?php echo htmlspecialchars($weight !== '' ? $weight . ' kg' : 'Not recorded'); ?></span></div>
                                                        <div><span class="block text-xs font-semibold uppercase text-slate-500">Blood pressure</span><span class="font-medium text-slate-900"><?php echo htmlspecialchars($bloodPressure !== '' ? $bloodPressure : 'Not recorded'); ?></span></div>
                                                        <div><span class="block text-xs font-semibold uppercase text-slate-500">Pulse</span><span class="font-medium text-slate-900"><?php echo htmlspecialchars($pulse !== '' ? $pulse : 'Not recorded'); ?></span></div>
                                                        <div><span class="block text-xs font-semibold uppercase text-slate-500">Temperature</span><span class="font-medium text-slate-900"><?php echo htmlspecialchars($temperature !== '' ? $temperature . ' °C' : 'Not recorded'); ?></span></div>
                                                    </div>
                                                    <div class="mt-3 grid grid-cols-1 gap-2 text-sm text-slate-700 sm:grid-cols-2">
                                                        <?php foreach (['head' => 'Head', 'chest_lungs' => 'Chest / Lungs', 'heart' => 'Heart', 'abdomen' => 'Abdomen', 'extremities' => 'Extremities'] as $clinicalKey => $clinicalLabel): ?>
                                                            <?php if (!empty($clinicalData[$clinicalKey])): ?><p><strong><?php echo $clinicalLabel; ?>:</strong> <?php echo htmlspecialchars($clinicalData[$clinicalKey]); ?></p><?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <?php if (!empty($clinicalData['remarks'])): ?><p class="mt-3 text-sm text-slate-700"><strong>Remarks:</strong> <?php echo htmlspecialchars($clinicalData['remarks']); ?></p><?php endif; ?>
                                                    <?php if (!empty($clinicalData['recommendations'])): ?><p class="mt-1 text-sm text-slate-700"><strong>Recommendations:</strong> <?php echo htmlspecialchars($clinicalData['recommendations']); ?></p><?php endif; ?>
                                                    <?php if (!empty($clinicalData['treatment_plan'])): ?><p class="mt-1 text-sm text-slate-700"><strong>Treatment plan:</strong> <?php echo htmlspecialchars($clinicalData['treatment_plan']); ?></p><?php endif; ?>
                                                    <?php if (!empty($record['notes'])): ?>
                                                        <p class="text-xs text-slate-600 italic">
                                                            <strong>Notes:</strong> <?php echo htmlspecialchars($record['notes']); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-8">
                                            <i class="fas fa-inbox text-4xl text-emerald-200 mb-3 block"></i>
                                            <p class="text-emerald-800">No medical visit records found for this patient.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Follow-Up Check-Up Card -->
                                <div class="bg-blue-50 border border-blue-200 rounded-xl p-8">
                                    <div class="flex items-center justify-between mb-6">
                                        <h3 class="text-lg font-heading font-bold text-blue-900 flex items-center gap-2">
                                            <i class="fas fa-calendar-check text-blue-600"></i> Follow Up Check Up
                                        </h3>
                                        <?php if (!empty($patient_data['followup_checkups']) && count($patient_data['followup_checkups']) > 0): ?>
                                            <button type="button" onclick="clearFollowupCheckups(<?php echo $patient_id; ?>, this)" class="px-3 py-1 bg-red-100 text-red-700 text-xs font-semibold rounded-lg hover:bg-red-200 transition-colors duration-200 flex items-center gap-1">
                                                <i class="fas fa-trash-alt"></i> Clear Followups
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($patient_data['followup_checkups']) && count($patient_data['followup_checkups']) > 0): ?>
                                        <div class="mb-4 p-3 bg-blue-100 border border-blue-300 rounded-lg text-sm text-blue-800">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            <strong><?php echo count($patient_data['followup_checkups']); ?></strong> follow-up check-up(s) scheduled
                                        </div>
                                        <div class="space-y-4 max-h-96 overflow-y-auto">
                                            <?php foreach ($patient_data['followup_checkups'] as $followup): ?>
                                                <div class="bg-white border-l-4 border-blue-500 rounded-lg p-4">
                                                    <div class="flex items-start justify-between mb-3">
                                                        <div>
                                                            <span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded mb-2">Follow Up Check Up</span>
                                                            <p class="font-semibold text-slate-900"><?php echo htmlspecialchars($followup['purpose'] ?? 'Follow-up Consultation'); ?></p>
                                                            <p class="text-sm text-slate-600 mt-1">
                                                                <i class="fas fa-calendar-alt mr-2"></i>
                                                                <?php echo date('M d, Y', strtotime($followup['appointment_date'])); ?> @ 
                                                                <?php echo htmlspecialchars($followup['appointment_time']); ?>
                                                            </p>
                                                        </div>
                                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold whitespace-nowrap bg-blue-100 text-blue-800">
                                                            <i class="fas fa-circle text-xs"></i> Scheduled
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-8">
                                            <i class="fas fa-check-circle text-4xl text-blue-200 mb-3 block"></i>
                                            <p class="text-blue-800">No follow-up check-ups scheduled for this patient.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Appointment Details Card -->
                                <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-8">
                                    <div class="flex items-center justify-between mb-6">
                                        <h3 class="text-lg font-heading font-bold text-indigo-900 flex items-center gap-2">
                                            <i class="fas fa-calendar-check text-indigo-600"></i> Appointment Details
                                        </h3>
                                        <?php if (!empty($patient_data['appointments']) && count($patient_data['appointments']) > 0): ?>
                                            <button type="button" id="clearAppointmentDetailsBtn_<?php echo $patient_id; ?>" onclick="clearAppointmentDetails(<?php echo $patient_id; ?>, this)" class="px-3 py-1 bg-red-100 text-red-700 text-xs font-semibold rounded-lg hover:bg-red-200 transition-colors duration-200 flex items-center gap-1">
                                                <i class="fas fa-trash-alt"></i> Clear All
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($patient_data['appointments']) && count($patient_data['appointments']) > 0): ?>
                                        <div class="space-y-4 max-h-96 overflow-y-auto" id="appointmentsList_<?php echo $patient_id; ?>">
                                            <?php foreach ($patient_data['appointments'] as $apt): ?>
                                                <div class="bg-white border-l-4 border-indigo-500 rounded-lg p-4">
                                                    <div class="flex items-start justify-between mb-3">
                                                        <div>
                                                            <h4 class="font-semibold text-slate-900"><?php echo htmlspecialchars($apt['purpose'] ?? 'General Appointment'); ?></h4>
                                                            <p class="text-sm text-slate-600 mt-1">
                                                                <i class="fas fa-calendar-alt mr-2"></i>
                                                                <?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?> @ 
                                                                <?php echo htmlspecialchars($apt['appointment_time']); ?>
                                                            </p>
                                                        </div>
                                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold whitespace-nowrap
                                                            <?php 
                                                            $status = strtolower($apt['status'] ?? '');
                                                            if ($status === 'approved' || $status === 'completed') {
                                                                echo 'bg-emerald-100 text-emerald-800';
                                                            } elseif ($status === 'pending' || $status === 'scheduled') {
                                                                echo 'bg-amber-100 text-amber-800';
                                                            } else {
                                                                echo 'bg-slate-100 text-slate-800';
                                                            }
                                                            ?>
                                                        ">
                                                            <?php echo htmlspecialchars(ucfirst($status)); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-8">
                                            <i class="fas fa-calendar-times text-4xl text-blue-200 mb-3 block"></i>
                                            <p class="text-blue-800">No appointment records found for this patient.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="flex flex-col sm:flex-row gap-4">
                                    <a href="view_record.php?code=<?php echo urlencode($patient_data['patient_code'] ?? str_pad((string) ($patient_data['resident_id'] ?? 0), 6, '0', STR_PAD_LEFT)); ?>&qr_scan=1<?php echo $consultation_appointment_id > 0 ? '&appointment_id=' . $consultation_appointment_id : '&new_visit=1'; ?>" class="rounded-lg bg-emerald-600 px-6 py-3 text-sm font-semibold text-white transition-all hover:bg-emerald-700 inline-flex items-center justify-center gap-2">
                                        <i class="fas fa-file-medical"></i> Open Clinical Record
                                    </a>
                                    <a href="?module=identify-patient" class="rounded-lg bg-[#3D4A1E] px-6 py-3 text-sm font-semibold text-white transition-all hover:bg-[#303a18] inline-flex items-center justify-center gap-2">
                                        <i class="fas fa-arrow-left"></i> Back to Identify Patient
                                    </a>
                                    <a href="?module=consultation&resident_id=<?php echo (int) ($patient_data['resident_id'] ?? 0); ?><?php echo $consultation_appointment_id > 0 ? '&appointment_id=' . $consultation_appointment_id : '&walk_in=1'; ?>" class="rounded-lg bg-[#3D4A1E] px-6 py-3 text-sm font-semibold text-white transition-all hover:bg-[#303a18] inline-flex items-center justify-center gap-2">
                                        <i class="fas fa-stethoscope"></i> Record Consultation
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- NO PATIENT LOADED - Show Placeholder -->
                            <div class="border-2 border-dashed border-slate-300 rounded-xl p-12 text-center bg-slate-50">
                                <div class="w-16 h-16 bg-slate-200 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-user-circle text-4xl text-slate-400"></i>
                                </div>
                                <h3 class="text-lg font-heading font-semibold text-slate-700 mb-2">Load Patient Profile</h3>
                                <p class="text-slate-600 max-w-md mx-auto mb-6">
                                    Identify a patient using the <strong>QR Scanner</strong> or <strong>Manual Entry</strong> to view their complete profile, medical history, and appointment details.
                                </p>
                                <a href="?module=identify-patient" class="rounded-lg bg-[#3D4A1E] px-6 py-2 text-sm font-semibold text-white transition-all hover:bg-[#303a18] inline-flex items-center gap-2">
                                    <i class="fas fa-arrow-left"></i> Back to Identify Patient
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- ============================================================
                         MODULE: CONSULTATION LOG & MEDICINE DISPENSING
                         ============================================================ -->
                    <?php if (isModuleActive('consultation')): ?>
                    <div id="consultationModule">
                        <h2 class="text-2xl font-heading font-bold text-slate-900 mb-6">Record Consultation</h2>
                        
                        <div class="grid grid-cols-1 gap-8">
                            <!-- Consultation Form -->
                            <div class="w-full">
                                <div class="bg-white border border-slate-200 rounded-xl p-6">
                                    <h3 class="text-lg font-heading font-bold text-slate-900 mb-6 flex items-center gap-2">
                                        <i class="fas fa-stethoscope text-[#454B1B]"></i> Consultation Notes & Diagnosis
                                    </h3>

                                    <form id="consultationForm" onsubmit="handleConsultationSubmit(event)" class="space-y-6">
                                        <!-- DYNAMIC APPROVED PATIENT DROPDOWN -->
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                                Select Patient <span class="text-red-500">*</span>
                                            </label>
                                            <select id="patientDropdown" onchange="handlePatientSelection()" 
                                                    class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent" 
                                                    required>
                                                <option value="">-- Choose an approved patient --</option>
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
                                                        data-purpose="<?php echo htmlspecialchars($patient['purpose']); ?>"
                                                        data-walk-in="<?php echo !empty($patient['is_walk_in']) ? 'true' : 'false'; ?>">
                                                    <?php echo htmlspecialchars($patient['patient_name']) . (!empty($patient['appointment_date']) ? ' (' . date('M d, Y h:i A', strtotime($patient['appointment_date'] . ' ' . $patient['appointment_time'])) . ')' : ''); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- PATIENT DETAILS (Auto-filled) -->
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
                                            <div id="consultationAppointmentSummary" class="mt-4 hidden rounded-lg border border-[#454B1B]/20 bg-white p-3">
                                                <p class="text-xs font-semibold uppercase text-slate-500">Selected appointment</p>
                                                <p id="consultationAppointmentDetails" class="mt-1 text-sm font-semibold text-slate-900"></p>
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 mb-2">Diagnosis <span class="text-red-500">*</span></label>
                                            <textarea id="diagnosisField"
                                                placeholder="Enter clinical findings and diagnosis..." 
                                                rows="4"
                                                class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent resize-none disabled:bg-slate-100 disabled:cursor-not-allowed"
                                                disabled
                                                required></textarea>
                                            <p class="text-xs text-slate-500 mt-1">Include symptoms, examination findings, and diagnostic conclusion</p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 mb-2">Treatment Plan <span class="text-red-500">*</span></label>
                                            <textarea id="treatmentField"
                                                placeholder="Prescribed treatment and medication..." 
                                                rows="4"
                                                class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent resize-none disabled:bg-slate-100 disabled:cursor-not-allowed"
                                                disabled
                                                required></textarea>
                                            <p class="text-xs text-slate-500 mt-1">Include medications, dosages, frequency, and duration</p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 mb-2">Clinical Notes</label>
                                            <textarea id="notesField"
                                                placeholder="Additional observations or follow-up instructions..." 
                                                rows="3"
                                                class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent resize-none disabled:bg-slate-100 disabled:cursor-not-allowed"
                                                disabled></textarea>
                                        </div>

                                        <div class="border-t border-slate-200 pt-6">
                                            <button type="submit" id="consultationSubmitBtn" class="w-full rounded-lg bg-[#3D4A1E] px-6 py-3 font-semibold text-white transition-all hover:bg-[#303a18] disabled:cursor-not-allowed disabled:opacity-50">
                                                <i class="fas fa-save mr-2"></i> Save Consultation Record
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ============================================================
                         MODULE: MEDICINE INVENTORY MANAGEMENT
                         ============================================================ -->
                    <?php if (isModuleActive('inventory')): ?>
                    <div id="inventoryModule">
                        <h2 class="text-2xl font-heading font-bold text-slate-900 mb-6">Medicine Inventory Management</h2>
                        
                        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                                <div>
                                    <h3 class="font-heading font-bold text-slate-900 flex items-center gap-2">
                                        <i class="fas fa-flask-vial text-[#454B1B]"></i> Stock of Free Medicine
                                    </h3>
                                    <p class="text-sm text-slate-600 mt-1">Track inventory and dispensing history</p>
                                </div>
                                <button onclick="showAddMedicineForm()" class="rounded-lg bg-[#3D4A1E] px-4 py-2 text-sm font-semibold text-white transition-all hover:bg-[#303a18]">
                                    <i class="fas fa-plus mr-2"></i> Add Medicine
                                </button>
                            </div>

                            <div class="flex gap-2 border-b border-slate-200 px-6 pt-4" role="tablist" aria-label="Medicine views">
                                <button type="button" data-medicine-tab="inventory" onclick="switchMedicineTab('inventory')" class="rounded-t-lg border-b-2 border-[#3D4A1E] px-4 py-2 text-sm font-semibold text-[#3D4A1E]">Stock of Free Medicine</button>
                                <button type="button" data-medicine-tab="dispensed" data-offline-control onclick="switchMedicineTab('dispensed')" class="rounded-t-lg border-b-2 border-transparent px-4 py-2 text-sm font-semibold text-slate-500 hover:text-[#3D4A1E]">Dispensed to Residents</button>
                            </div>

                            <div id="medicine-inventory-panel">
                            <div class="max-h-[65vh] overflow-auto overscroll-contain">
                                <table class="w-full text-sm">
                                    <thead class="sticky top-0 z-10 bg-slate-50 border-b border-slate-200">
                                        <tr>
                                            <th class="px-6 py-3 text-left font-semibold text-slate-700">Medicine Name</th>
                                            <th class="px-6 py-3 text-left font-semibold text-slate-700">Dosage</th>
                                            <th class="px-6 py-3 text-center font-semibold text-slate-700">Available</th>
                                            <th class="px-6 py-3 text-center font-semibold text-slate-700">Dispensed</th>
                                            <th class="px-6 py-3 text-left font-semibold text-slate-700">Status</th>
                                            <th class="px-6 py-3 text-center font-semibold text-slate-700">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200">
                                        <?php if (empty($medicine_list)): ?>
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                                <i class="fas fa-inbox text-4xl text-slate-300 mb-3 block"></i>
                                                No medicines in inventory
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($medicine_list as $medicine): ?>
                                        <tr class="hover:bg-slate-50 transition-colors" data-medicine-id="<?php echo $medicine['medicine_id']; ?>">
                                            <td class="px-6 py-4 font-medium text-slate-900"><?php echo htmlspecialchars($medicine['name']); ?></td>
                                            <td class="px-6 py-4 text-slate-600 font-medium"><?php echo htmlspecialchars($medicine['dosage']); ?></td>
                                            <td class="px-6 py-4 text-center"><span class="font-bold text-emerald-600"><?php echo intval($medicine['available']); ?></span></td>
                                            <td class="px-6 py-4 text-center"><span class="text-slate-600 font-medium"><?php echo intval($medicine['dispensed']); ?></span></td>
                                            <td class="px-6 py-4">
                                                <?php 
                                                $status_class = 'text-slate-700 bg-slate-100';
                                                $status_text = 'Out of Stock';
                                                $status_value = strtolower($medicine['status']);
                                                
                                                if ($status_value === 'available') {
                                                    $status_class = 'text-green-700 bg-green-50';
                                                    $status_text = 'Available';
                                                } elseif ($status_value === 'low stock') {
                                                    $status_class = 'text-orange-700 bg-orange-50';
                                                    $status_text = 'Low Stock';
                                                } elseif ($status_value === 'out of stock') {
                                                    $status_class = 'text-slate-700 bg-slate-100';
                                                    $status_text = 'Out of Stock';
                                                }
                                                ?>
                                                <span class="inline-flex items-center gap-1 px-2 py-1 <?php echo $status_class; ?> text-xs font-bold rounded-full">
                                                    <span class="w-1.5 h-1.5 rounded-full <?php echo str_replace('text-', 'bg-', str_replace(' ', ' bg-', substr($status_class, 5))); ?>"></span> <?php echo htmlspecialchars($medicine['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <button onclick="editMedicine(this)" class="text-[#454B1B] hover:opacity-70 text-sm font-semibold">Edit</button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 text-sm text-slate-600">
                                <strong>Note:</strong> Available stock decreases and dispensed stock increases whenever medicine is issued. Click "Edit" to correct either figure.
                            </div>
                            </div>

                            <div id="medicine-dispensed-panel" data-offline-control class="hidden p-6">
                                <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-6">
                                    <h3 class="mb-6 flex items-center gap-2 font-heading text-lg font-bold text-emerald-900">
                                        <i class="fas fa-pills text-emerald-600"></i> Dispense Free Medicine
                                    </h3>
                                    <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_160px_auto] md:items-end">
                                        <div class="md:col-span-3">
                                            <label class="mb-2 block text-sm font-semibold text-emerald-900" for="dispensingResidentSelect">Resident receiving medicine</label>
                                            <select id="dispensingResidentSelect" class="w-full rounded-lg border border-emerald-300 bg-white px-4 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                                <option value="">-- Choose resident --</option>
                                                <?php foreach ($dispensing_residents as $resident): ?>
                                                    <option value="<?php echo (int) $resident['resident_id']; ?>"><?php echo htmlspecialchars($resident['full_name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-sm font-semibold text-emerald-900" for="inventoryMedicineSelect">Select Medicine</label>
                                            <select id="inventoryMedicineSelect" class="w-full rounded-lg border border-emerald-300 bg-white px-4 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                                <option value="">-- Choose Medicine --</option>
                                                <?php foreach ($medicine_list as $medicine): ?>
                                                    <?php if (intval($medicine['available']) > 0): ?><option value="<?php echo (int) $medicine['medicine_id']; ?>" data-medicine-name="<?php echo htmlspecialchars($medicine['name'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($medicine['name'] . ' ' . $medicine['dosage']); ?> (<?php echo (int) $medicine['available']; ?> available)</option><?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-sm font-semibold text-emerald-900" for="inventoryMedicineQuantity">Quantity</label>
                                            <input id="inventoryMedicineQuantity" type="number" min="1" value="1" class="w-full rounded-lg border border-emerald-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                        </div>
                                        <button type="button" onclick="dispenseMedicine()" class="rounded-lg bg-[#3D4A1E] px-4 py-2 text-sm font-semibold text-white transition-all hover:bg-[#303a18]">
                                            <i class="fas fa-plus mr-2"></i> Add to Dispensing
                                        </button>
                                    </div>
                                    <p id="inventoryDispensingPatient" class="mt-3 text-xs text-emerald-700">Choose the resident who will receive the selected medicine.</p>
                                    <div class="mt-4 border-t border-emerald-200 pt-4">
                                        <p class="mb-3 text-xs font-bold uppercase tracking-wider text-emerald-700">Medicines to Dispense</p>
                                        <div id="inventoryDispensingList" class="space-y-2">
                                            <p class="text-xs italic text-emerald-600">Select a medicine to add it to the dispensing list.</p>
                                        </div>
                                        <button type="button" onclick="recordFreeMedicineDispensing()" class="mt-4 rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white transition-all hover:bg-emerald-800">
                                            <i class="fas fa-check mr-2"></i> Record Dispensing
                                        </button>
                                    </div>
                                </div>
                                <div class="mb-4 flex items-center justify-between gap-3">
                                    <div>
                                        <h3 class="font-heading font-bold text-slate-900">Dispensed Free Medicine</h3>
                                        <p class="text-sm text-slate-600 mt-1">Track medicines given to residents.</p>
                                    </div>
                                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700"><?php echo count($dispensed_medicine_list); ?> records</span>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead class="bg-slate-50 border-b border-slate-200">
                                            <tr>
                                                <th class="px-4 py-3 text-left font-semibold text-slate-700">Resident</th>
                                                <th class="px-4 py-3 text-left font-semibold text-slate-700">Medicine</th>
                                                <th class="px-4 py-3 text-center font-semibold text-slate-700">Quantity</th>
                                                <th class="px-4 py-3 text-left font-semibold text-slate-700">Date</th>
                                                <th class="px-4 py-3 text-left font-semibold text-slate-700">Recorded By</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200">
                                            <?php if (empty($dispensed_medicine_list)): ?>
                                                <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">No medicines have been dispensed yet.</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($dispensed_medicine_list as $dispensedMedicine): ?>
                                                    <tr class="hover:bg-slate-50">
                                                        <td class="px-4 py-3 font-medium text-slate-900"><?php echo htmlspecialchars($dispensedMedicine['resident_name']); ?></td>
                                                        <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars(trim($dispensedMedicine['medicine_name'] . ' ' . ($dispensedMedicine['dosage'] ?? ''))); ?></td>
                                                        <td class="px-4 py-3 text-center font-bold text-emerald-700"><?php echo (int) $dispensedMedicine['quantity_given']; ?></td>
                                                        <td class="px-4 py-3 text-slate-600"><?php echo date('M d, Y g:i A', strtotime($dispensedMedicine['dispense_date'])); ?></td>
                                                        <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($dispensedMedicine['worker_name'] ?? 'Health Worker'); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ============================================================
                         MODULE: APPOINTMENT MANAGEMENT
                         ============================================================ -->
                    <?php if (isModuleActive('appointments')): ?>
                    <div>
                        <h2 class="text-2xl font-heading font-bold text-slate-900 mb-6">Manage Appointment Schedule</h2>
                        
                        <!-- Appointments Sub-Tab Navigation -->
                        <div class="flex items-center gap-1 border-b border-slate-200 px-6 py-3 overflow-x-auto bg-white mb-4 rounded-t-xl">
                            <a href="?module=appointments&sub=today" class="tab-button <?php echo (($_GET['sub'] ?? 'today') === 'today') ? 'active' : ''; ?>" style="font-size: 0.875rem;">
                                <i class="fas fa-calendar-check mr-1"></i>Today's Queue
                            </a>
                            <a href="?module=appointments&sub=followup" class="tab-button <?php echo (($_GET['sub'] ?? 'today') === 'followup') ? 'active' : ''; ?>" style="font-size: 0.875rem;">
                                <i class="fas fa-stethoscope mr-1"></i>Follow-up Check-up
                            </a>
                            <a href="?module=appointments&sub=history" class="tab-button <?php echo (($_GET['sub'] ?? 'today') === 'history') ? 'active' : ''; ?>" style="font-size: 0.875rem;">
                                <i class="fas fa-history mr-1"></i>Appointment History
                            </a>
                        </div>

                        <!-- TODAY'S QUEUE TAB -->
                        <?php if (!isset($_GET['sub']) || $_GET['sub'] === 'today'): ?>
                        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-200">
                                <h3 class="font-heading font-bold text-slate-900 flex items-center gap-2 mb-4">
                                    <i class="fas fa-calendar-check text-[#454B1B]"></i> Upcoming Appointments Queue
                                </h3>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-50 border-b border-slate-200">
                                        <tr>
                                            <th class="px-6 py-3 text-left font-semibold text-slate-700">Resident Name</th>
                                            <th class="px-6 py-3 text-left font-semibold text-slate-700">Date</th>
                                            <th class="px-6 py-3 text-left font-semibold text-slate-700">Time</th>
                                            <th class="px-6 py-3 text-left font-semibold text-slate-700">Purpose</th>
                                            <th class="px-6 py-3 text-left font-semibold text-slate-700">Status</th>
                                            <th class="px-6 py-3 text-center font-semibold text-slate-700">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200">
                                   <?php 
                                        // Show ALL upcoming appointments (today and future), sorted by date
                                        // These are appointments waiting for BHW processing or already approved
                                        $today_apts = array_filter($upcoming_appointments, function($apt) {
                                            $status = strtolower($apt['status']);
                                            return in_array($status, ['scheduled', 'pending', 'approved', 'rescheduled']);
                                        });
                                        // Show the latest scheduled appointments first.
                                        usort($today_apts, static function ($leftAppointment, $rightAppointment) {
                                            $leftTimestamp = strtotime(($leftAppointment['appointment_date'] ?? '') . ' ' . ($leftAppointment['appointment_time'] ?? '00:00:00')) ?: 0;
                                            $rightTimestamp = strtotime(($rightAppointment['appointment_date'] ?? '') . ' ' . ($rightAppointment['appointment_time'] ?? '00:00:00')) ?: 0;
                                            return $rightTimestamp <=> $leftTimestamp;
                                        });
                                        if (empty($today_apts)): 
                                        ?>
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td colspan="6" class="px-6 py-12 text-center">
                                                <div class="flex flex-col items-center gap-3">
                                                    <i class="fas fa-inbox text-4xl text-slate-300"></i>
                                                    <p class="text-slate-600 font-medium">No upcoming appointments in the queue</p>
                                                    <p class="text-xs text-slate-500">Patients waiting to be processed will appear here</p>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($today_apts as $apt): ?>
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td class="px-6 py-4 font-medium text-slate-900"><?php echo htmlspecialchars($apt['resident_name'] ?? 'Unknown'); ?></td>
                                            <td class="px-6 py-4 text-slate-600"><?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?></td>
                                            <td class="px-6 py-4 text-slate-600"><?php echo htmlspecialchars($apt['appointment_time'] ?? 'N/A'); ?></td>
                                            <td class="px-6 py-4 text-slate-600"><?php echo htmlspecialchars($apt['purpose'] ?? 'General Consultation'); ?></td>
                                            <td class="px-6 py-4">
                                                <?php 
                                                $status = strtolower($apt['status']);
                                                if ($status === 'scheduled') {
                                                    echo '<span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-50 rounded-full text-xs font-bold text-blue-700"><span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Scheduled</span>';
                                                } elseif ($status === 'pending') {
                                                    echo '<span class="inline-flex items-center gap-1 px-2 py-1 bg-yellow-50 rounded-full text-xs font-bold text-yellow-700"><span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span> Pending</span>';
                                                } elseif ($status === 'approved') {
                                                    echo '<span class="inline-flex items-center gap-1 px-2 py-1 bg-purple-50 rounded-full text-xs font-bold text-purple-700"><span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span> Approved</span>';
                                                } elseif ($status === 'rescheduled') {
                                                    echo '<span class="inline-flex items-center gap-1 px-2 py-1 bg-violet-100 rounded-full text-xs font-bold text-violet-700"><span class="w-1.5 h-1.5 rounded-full bg-violet-500"></span> Rescheduled</span>';
                                                }
                                                ?>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <?php 
                                                $apt_id = intval($apt['appointment_id'] ?? 0);
                                                $res_id = intval($apt['resident_id'] ?? 0);
                                                ?>
                                                <div class="flex flex-nowrap items-center justify-center gap-1.5">
                                                    <button onclick="changeAppointmentStatus(this, 'rendered', <?php echo $apt_id; ?>)" data-appointment-id="<?php echo $apt_id; ?>" data-resident-id="<?php echo $res_id; ?>" class="inline-flex min-h-8 shrink-0 items-center justify-center gap-1 whitespace-nowrap bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-2.5 py-1.5 rounded text-[11px] font-medium transition-all" title="Mark as Completed">
                                                        <i class="fas fa-check"></i> Process
                                                    </button>
                                                    <button onclick="changeAppointmentStatus(this, 'no-show', <?php echo $apt_id; ?>)" class="inline-flex min-h-8 shrink-0 items-center justify-center gap-1 whitespace-nowrap bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white px-2.5 py-1.5 rounded text-[11px] font-medium transition-all" title="Mark as No Show">
                                                        <i class="fas fa-times"></i> No Show
                                                    </button>
                                                    <button onclick="showFollowupScheduleModal(<?php echo $res_id; ?>, '<?php echo htmlspecialchars($apt['resident_name']); ?>', <?php echo $apt_id; ?>)" class="inline-flex min-h-8 shrink-0 items-center justify-center gap-1 whitespace-nowrap bg-gradient-to-r from-yellow-400 to-yellow-500 hover:from-yellow-500 hover:to-yellow-600 text-slate-900 px-2.5 py-1.5 rounded text-[11px] font-medium transition-all" title="Schedule Follow-up">
                                                        <i class="fas fa-plus"></i> Follow-up
                                                    </button>
                                                    <button type="button" onclick="openRescheduleAppointmentModal(this)" data-appointment-id="<?php echo $apt_id; ?>" data-resident-id="<?php echo $res_id; ?>" data-resident-name="<?php echo htmlspecialchars($apt['resident_name'] ?? 'Resident', ENT_QUOTES, 'UTF-8'); ?>" class="inline-flex min-h-8 shrink-0 items-center justify-center gap-1 whitespace-nowrap bg-gradient-to-r from-violet-500 to-violet-600 hover:from-violet-600 hover:to-violet-700 text-white px-2.5 py-1.5 rounded text-[11px] font-medium transition-all" title="Reschedule this appointment">
                                                        <i class="fas fa-calendar-day"></i> Reschedule
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Reschedule modal is intentionally positioned beside the Today's Queue actions -->
                        <div id="rescheduleAppointmentModal" style="display: none;" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[12000] p-4">
                            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full animate-in fade-in zoom-in duration-300">
                                <div class="px-6 py-5 border-b border-slate-200 flex items-center justify-between">
                                    <div>
                                        <h3 id="appointmentScheduleModalTitle" class="text-lg font-bold text-slate-900 flex items-center gap-2">
                                            <i class="fas fa-calendar-day text-violet-600"></i> Reschedule Appointment
                                        </h3>
                                        <p class="text-xs text-slate-500 mt-1">Resident: <span id="rescheduleResidentName"></span></p>
                                    </div>
                                    <button type="button" onclick="closeRescheduleAppointmentModal()" class="text-slate-400 hover:text-slate-600 transition-colors text-2xl leading-none" aria-label="Close modal">&times;</button>
                                </div>

                                <form id="rescheduleAppointmentForm" onsubmit="submitRescheduleAppointment(event)" class="p-6 space-y-5">
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <label class="block text-sm font-medium text-slate-700">
                                            New Date
                                            <input id="rescheduleAppointmentDate" type="date" required class="mt-1 block w-full rounded-lg border-slate-300 px-3 py-2 shadow-sm focus:border-violet-500 focus:ring-violet-500">
                                        </label>
                                        <label class="block text-sm font-medium text-slate-700">
                                            New Time
                                            <select id="rescheduleAppointmentTime" required class="mt-1 block w-full rounded-lg border-slate-300 px-3 py-2 shadow-sm focus:border-violet-500 focus:ring-violet-500">
                                                <option value="">Select time</option>
                                                <option value="08:00 AM">08:00 AM</option>
                                                <option value="09:00 AM">09:00 AM</option>
                                                <option value="10:00 AM">10:00 AM</option>
                                                <option value="11:00 AM">11:00 AM</option>
                                                <option value="01:00 PM">01:00 PM</option>
                                                <option value="02:00 PM">02:00 PM</option>
                                                <option value="03:00 PM">03:00 PM</option>
                                                <option value="04:00 PM">04:00 PM</option>
                                            </select>
                                        </label>
                                    </div>
                                    <p id="appointmentScheduleModalNotice" class="text-xs text-slate-500">The resident will receive an SMS notification after the appointment is updated.</p>
                                    <div class="flex justify-end gap-3 border-t border-slate-200 pt-4">
                                        <button type="button" onclick="closeRescheduleAppointmentModal()" class="px-4 py-2 rounded-lg border border-slate-300 font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                                        <button id="submitRescheduleAppointmentBtn" type="submit" class="px-4 py-2 rounded-lg bg-violet-600 font-semibold text-white hover:bg-violet-700">
                                            <i class="fas fa-calendar-day"></i> Reschedule Appointment
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- FOLLOW-UP CHECK-UP TAB -->
                        <?php if (isset($_GET['sub']) && $_GET['sub'] === 'followup'): ?>
                        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-200">
                                <h3 class="font-heading font-bold text-slate-900 flex items-center gap-2 mb-4">
                                    <i class="fas fa-stethoscope text-[#454B1B]"></i> Flagged Patients for Follow-up Check-up
                                </h3>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-50 border-b border-slate-200">
                                        <tr>
                                            <th class="px-6 py-3 text-left font-semibold text-slate-700">Resident Name</th>
                                            <th class="px-6 py-3 text-left font-semibold text-slate-700">Follow-up Date</th>
                                            <th class="px-6 py-3 text-left font-semibold text-slate-700">Follow-up Time</th>
                                            <th class="px-6 py-3 text-center font-semibold text-slate-700">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200">
                                        <?php 
                                        $today = date('Y-m-d');
                                        $followup_apts = array_filter($upcoming_appointments, function($apt) use ($today) {
                                            $apt_date = date('Y-m-d', strtotime($apt['appointment_date']));
                                            $status = strtolower($apt['status']);
                                            // Follow-up Check-up: ONLY show appointments explicitly flagged for follow-up (status='flagged')
                                            // These are patients the BHW clicked the yellow 'Follow-up Check-up' button for
                                            return $status === 'flagged';
                                        });
                                        if (empty($followup_apts)): 
                                        ?>
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td colspan="4" class="px-6 py-12 text-center">
                                                <div class="flex flex-col items-center gap-3">
                                                    <i class="fas fa-calendar-times text-4xl text-slate-300"></i>
                                                    <p class="text-slate-600 font-medium">No follow-up appointments flagged</p>
                                                    <p class="text-xs text-slate-500">Use the yellow "Follow-up Check-up" button in Today's Queue to schedule returns</p>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($followup_apts as $apt): ?>
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td class="px-6 py-4 font-medium text-slate-900"><?php echo htmlspecialchars($apt['resident_name'] ?? 'Unknown'); ?></td>
                                            <td class="px-6 py-4 text-slate-600"><?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?></td>
                                            <td class="px-6 py-4 text-slate-600"><?php echo date('g:i A', strtotime($apt['appointment_time'])); ?></td>
                                            <td class="px-6 py-4 text-center">
                                                <?php 
                                                $apt_id = intval($apt['appointment_id'] ?? 0);
                                                $res_id = intval($apt['resident_id'] ?? 0);
                                                ?>
                                                <button onclick="changeAppointmentStatus(this, 'rendered', <?php echo $apt_id; ?>)" data-appointment-id="<?php echo $apt_id; ?>" data-resident-id="<?php echo $res_id; ?>" class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-3 py-1.5 rounded text-xs font-medium transition-all" title="Mark as Completed">
                                                    <i class="fas fa-check"></i> Process
                                                </button>
                                                <button onclick="changeAppointmentStatus(this, 'no-show', <?php echo $apt_id; ?>)" data-appointment-id="<?php echo $apt_id; ?>" data-resident-id="<?php echo $res_id; ?>" class="bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white px-3 py-1.5 rounded text-xs font-medium transition-all ml-1" title="Mark as No Show">
                                                    <i class="fas fa-times"></i> No Show
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- APPOINTMENT HISTORY TAB -->
                        <?php if (isset($_GET['sub']) && $_GET['sub'] === 'history'): ?>
                        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-200">
                                <h3 class="font-heading font-bold text-slate-900 flex items-center gap-2 mb-4">
                                    <i class="fas fa-history text-[#454B1B]"></i> Appointment History (Completed Records)
                                </h3>
                            </div>

                            <div class="max-h-[440px] overflow-auto overscroll-contain">
                                <table class="w-full text-sm">
                                    <thead class="sticky top-0 z-10 bg-slate-50 border-b border-slate-200">
                                        <tr>
                                            <th class="px-6 py-3 text-left font-semibold text-slate-700">Resident Name</th>
                                            <th class="px-6 py-3 text-left font-semibold text-slate-700">Date</th>
                                            <th class="px-6 py-3 text-left font-semibold text-slate-700">Time</th>
                                            <th class="px-6 py-3 text-left font-semibold text-slate-700">Purpose</th>
                                            <th class="px-6 py-3 text-left font-semibold text-slate-700">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200">
                                        <?php 
                                        // Appointment History: Show completed and no-show appointments (finalized records)
                                        // This includes appointments the BHW already processed
                                        $history_apts = $appointment_history;
                                        if (empty($history_apts)): 
                                        ?>
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td colspan="5" class="px-6 py-12 text-center">
                                                <div class="flex flex-col items-center gap-3">
                                                    <i class="fas fa-archive text-4xl text-slate-300"></i>
                                                    <p class="text-slate-600 font-medium">No completed appointments yet</p>
                                                    <p class="text-xs text-slate-500">Historical records will appear here after appointments are marked as completed</p>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($history_apts as $apt): ?>
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td class="px-6 py-4 font-medium text-slate-900"><?php echo htmlspecialchars($apt['resident_name'] ?? 'Unknown'); ?></td>
                                            <td class="px-6 py-4 text-slate-600"><?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?></td>
                                            <td class="px-6 py-4 text-slate-600"><?php echo htmlspecialchars($apt['appointment_time'] ?? 'N/A'); ?></td>
                                            <td class="px-6 py-4 text-slate-600"><?php echo htmlspecialchars($apt['purpose'] ?? 'Consultation'); ?></td>
                                            <td class="px-6 py-4">
                                                <?php 
                                                $status = strtolower($apt['status'] ?? '');
                                                if ($status === 'completed') {
                                                    echo '<span class="inline-flex items-center gap-1 px-2 py-1 bg-green-50 rounded-full text-xs font-bold text-green-700"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Completed</span>';
                                                } elseif ($status === 'no_show') {
                                                    echo '<span class="inline-flex items-center gap-1 px-2 py-1 bg-red-50 rounded-full text-xs font-bold text-red-700"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> No Show</span>';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>

                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ============================================================
                         MODULE: FILE REQUESTS
                         ============================================================ -->
                    <?php if (isModuleActive('file-requests')): ?>
                    <div>
                        <div class="mb-6">
                            <h2 class="text-2xl font-heading font-bold text-slate-900">Resident File Requests</h2>
                            <p class="mt-1 text-sm text-slate-600">Review requests submitted from the resident portal, approve or decline them, then residents can download their PDF.</p>
                        </div>
                        <?php if (isset($_GET['request_status'])): ?><div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">Request <?php echo htmlspecialchars($_GET['request_status']); ?>.</div><?php endif; ?>
                        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                            <table class="w-full text-sm"><thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left">Resident</th><th class="px-4 py-3 text-left">File</th><th class="px-4 py-3 text-left">Submitted</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3 text-center">Action</th></tr></thead><tbody class="divide-y divide-slate-200">
                            <?php if (empty($file_requests)): ?><tr><td colspan="5" class="px-4 py-12 text-center text-slate-500">No resident file requests yet.</td></tr><?php else: ?>
                                <?php foreach ($file_requests as $fileRequest): $requestStatus = strtolower($fileRequest['status']); $requestBadge = $requestStatus === 'accepted' ? 'bg-green-100 text-green-800' : ($requestStatus === 'declined' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800'); ?>
                                <tr>
                                    <td class="px-4 py-4"><p class="font-semibold text-slate-900"><?php echo htmlspecialchars($fileRequest['full_name']); ?></p><p class="text-xs text-slate-500">ID: <?php echo htmlspecialchars($fileRequest['patient_code'] ?: 'Not assigned'); ?></p></td>
                                    <td class="px-4 py-4 text-slate-600"><?php echo htmlspecialchars($fileRequest['requested_file']); ?></td>
                                    <td class="px-4 py-4 text-slate-600"><?php echo date('M d, Y g:i A', strtotime($fileRequest['created_at'])); ?></td>
                                    <td class="px-4 py-4"><span class="rounded-full px-2 py-1 text-xs font-bold capitalize <?php echo $requestBadge; ?>"><?php echo htmlspecialchars($requestStatus); ?></span></td>
                                    <td class="min-w-[368px] px-4 py-4 align-middle">
                                        <div class="flex min-h-9 items-center justify-center gap-2 whitespace-nowrap">
                                            <?php if ($requestStatus === 'pending'): ?>
                                                <a href="view_record.php?code=<?php echo urlencode($fileRequest['patient_code']); ?>&qr_scan=1&request_id=<?php echo (int) $fileRequest['request_id']; ?>" class="inline-flex h-9 w-28 shrink-0 items-center justify-center gap-1 rounded-lg bg-[#454B1B] px-2 text-xs font-semibold text-white hover:opacity-90"><i class="fas fa-file-medical"></i>Review &amp; Send</a>
                                                <form method="post" action="manage_file_request.php" class="m-0 flex h-9 w-28 shrink-0"><input type="hidden" name="request_id" value="<?php echo (int) $fileRequest['request_id']; ?>"><input type="hidden" name="request_action" value="decline"><button class="inline-flex h-9 w-full items-center justify-center gap-1 rounded-lg bg-red-600 px-2 text-xs font-semibold text-white hover:bg-red-700"><i class="fas fa-times"></i>Decline</button></form>
                                            <?php elseif ($requestStatus === 'accepted'): ?>
                                                <a href="download_file_request.php?request_id=<?php echo (int) $fileRequest['request_id']; ?>" class="inline-flex h-9 w-28 shrink-0 items-center justify-center gap-1 rounded-lg border border-[#454B1B] px-2 text-xs font-semibold text-[#454B1B] hover:bg-[#454B1B]/5"><i class="fas fa-download"></i>Download PDF</a>
                                            <?php else: ?>
                                                <span class="inline-flex h-9 w-28 shrink-0 items-center justify-center rounded-lg bg-slate-50 px-2 text-xs text-slate-400">Closed</span>
                                            <?php endif; ?>
                                            <form method="post" action="delete_file_request.php" class="m-0 flex h-9 w-28 shrink-0" onsubmit="const requestForm = this; event.preventDefault(); window.openCenteredConfirmation({title: 'Delete File Request', message: 'Delete this file request from history?', confirmLabel: 'Delete Request', onConfirm: () => requestForm.submit()});"><input type="hidden" name="request_id" value="<?php echo (int) $fileRequest['request_id']; ?>"><button type="submit" class="inline-flex h-9 w-full items-center justify-center gap-1 rounded-lg border border-red-200 px-2 text-xs font-semibold text-red-700 hover:bg-red-50"><i class="fas fa-trash"></i>Delete</button></form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?></tbody></table>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ============================================================
                         MODULE: SURVEILLANCE MANAGEMENT
                         ============================================================ -->
                    <?php if (isModuleActive('surveillance')): ?>
                    <div>
                        <h2 class="text-2xl font-heading font-bold text-slate-900 mb-6">Surveillance Management</h2>
                        <p class="text-sm text-slate-600 mb-8">Update the Barangay health surveillance metrics and publish a live resident preview within the BHW dashboard.</p>

                        <div class="space-y-6">
                            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm dark:bg-slate-800 dark:border-slate-700">
                                <div class="flex items-center justify-between mb-6">
                                    <div>
                                        <h3 class="text-lg font-heading font-bold text-slate-900">BHW Metrics Editor</h3>
                                        <p class="text-sm text-slate-500">Enter the latest surveillance figures and community observations.</p>
                                    </div>
                                </div>

                                <form id="surveillanceMetricsForm" method="POST" class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end" onsubmit="handleSurveillanceSubmit(event)" novalidate>
                                    <input type="hidden" name="action" value="save_surveillance_metrics" />
                                    <div class="md:col-span-2"><label for="input_disease_name" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">Target Disease Name</label><input id="input_disease_name" name="disease_name" type="text" placeholder="Dengue" class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"></div>
                                    <div><label for="input_quantity_count" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">Quantity / Cases</label><input id="input_quantity_count" name="case_count" type="number" min="0" placeholder="0" required class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"></div>
                                    <div><label for="reportingPeriod" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">Reporting Period</label><input id="reportingPeriod" name="reporting_period" type="text" placeholder="Current 4 Weeks" class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"></div>
                                    <div class="md:col-span-3"><label for="input_disease_meta" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">Surveillance Metadata / Notes</label><input id="input_disease_meta" name="meta_notes" type="text" placeholder="Add a short community observation" class="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"></div>
                                    <div class="md:col-span-2"><button id="saveSurveillanceBtn" name="publish_updates" type="submit" class="w-full rounded-lg bg-[#3D4A1E] px-6 py-3 font-semibold text-white transition-all hover:bg-[#303a18] disabled:cursor-not-allowed disabled:opacity-60"><i class="fas fa-upload mr-2"></i> Save Updates</button></div>
                                    <div id="survSaveMessage" class="hidden self-center text-sm font-semibold text-emerald-700 dark:text-emerald-300"><i class="fas fa-check-circle mr-1"></i> Metrics saved successfully</div>
                                </form>
                            </div>

                            <section class="rounded-2xl border border-[#3D4A1E]/20 bg-[#3D4A1E] p-6 text-white shadow-sm"><p class="text-xs font-semibold uppercase tracking-[0.2em] text-lime-200">Highest Incidence</p><div class="mt-2 flex flex-wrap items-end justify-between gap-3"><h3 id="highest-incidence-name" class="text-2xl font-heading font-bold"><?php echo htmlspecialchars($highest_surveillance_metric['disease_name'] ?? 'None', ENT_QUOTES, 'UTF-8'); ?></h3><p><span id="highest-incidence-count" class="text-4xl font-bold"><?php echo intval($highest_surveillance_metric['case_count'] ?? 0); ?></span> <span class="text-sm text-lime-100">total cases</span></p></div></section>

                            <div class="surveillance-preview-panel rounded-2xl border border-slate-200 bg-slate-50 p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900"><div class="mb-5 flex flex-wrap items-end justify-between gap-2"><div><h3 class="text-lg font-heading font-bold text-slate-900 dark:text-white">Surveillance Preview</h3><p class="text-sm text-slate-600 dark:text-slate-400">All recorded disease entries.</p></div><p id="survReportingPeriod" class="text-sm text-slate-500 dark:text-slate-400">Reporting Period: Current 4 Weeks</p></div><div id="surveillanceRecordsList" class="grid grid-cols-1 gap-4 md:grid-cols-3"></div></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ============================================================
                         MODULE: SMS COMMUNICATIONS
                         ============================================================ -->
                    <?php if (isModuleActive('sms')): ?>
                    <div>
                        <h2 class="text-2xl font-heading font-bold text-slate-900 mb-6">Send SMS Reminders to Residents</h2>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                            <!-- SMS Composition Form -->
                            <div class="lg:col-span-2">
                                <div class="bg-white border border-slate-200 rounded-xl p-6">
                                    <h3 class="text-lg font-heading font-bold text-slate-900 mb-6 flex items-center gap-2">
                                        <i class="fas fa-sms text-[#454B1B]"></i> Compose Message
                                    </h3>

                                    <form id="smsForm" onsubmit="handleSMSSubmit(event)" class="space-y-6">
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 mb-2">Recipient Type</label>
                                            <div class="space-y-2">
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="radio" name="recipient_type" value="all" checked onchange="toggleCustomNumbers()" class="w-4 h-4">
                                                    <span class="text-sm font-medium text-slate-700">All Residents</span>
                                                </label>
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="radio" name="recipient_type" value="appointment" onchange="toggleCustomNumbers()" class="w-4 h-4">
                                                    <span class="text-sm font-medium text-slate-700">Upcoming Appointment Reminders</span>
                                                </label>
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="radio" name="recipient_type" value="custom" onchange="toggleCustomNumbers()" class="w-4 h-4">
                                                    <span class="text-sm font-medium text-slate-700">Custom Contact List</span>
                                                </label>
                                            </div>
                                        </div>

                                        <div id="customNumbersSection" class="hidden">
                                            <label class="block text-sm font-semibold text-slate-700 mb-2">Phone Numbers <span class="text-red-500">*</span></label>
                                            <textarea 
                                                id="customNumbers"
                                                placeholder="Enter phone numbers separated by commas&#10;Example: +639927174709, 09123456789, 639123456789" 
                                                rows="3"
                                                class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent resize-none font-mono text-sm"></textarea>
                                            <p class="text-xs text-slate-500 mt-2">Comma-separated phone numbers (supports multiple formats)</p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 mb-2">Message Content <span class="text-red-500">*</span></label>
                                            <textarea 
                                                id="smsContent"
                                                placeholder="Type your SMS message (max 160 characters)..." 
                                                rows="4"
                                                maxlength="160"
                                                class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent resize-none font-mono text-sm"
                                                required
                                                oninput="updateCharCount()"></textarea>
                                            <div class="flex items-center justify-between mt-2">
                                                <p class="text-xs text-slate-500">Recommended for SMS delivery</p>
                                                <p class="text-xs font-semibold text-slate-700"><span id="charCount">0</span>/160 characters</p>
                                            </div>
                                        </div>

                                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                            <p class="text-xs font-bold text-blue-700 uppercase tracking-wider mb-2">Message Preview</p>
                                            <p id="smsPreview" class="text-sm text-blue-900 font-medium">Your message will appear here...</p>
                                        </div>

                                        <div class="border-t border-slate-200 pt-6">
                                            <button type="submit" class="w-full rounded-lg bg-[#3D4A1E] px-6 py-3 font-semibold text-white transition-all hover:bg-[#303a18]">
                                                <i class="fas fa-paper-plane mr-2"></i> Send SMS Reminders
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- SMS Templates & Stats -->
                            <div class="space-y-6">
                                <div class="bg-white border border-slate-200 rounded-xl p-6">
                                    <h3 class="text-lg font-heading font-bold text-slate-900 mb-4 flex items-center gap-2">
                                        <i class="fas fa-bookmark text-[#454B1B]"></i> Quick Templates
                                    </h3>
                                    <div class="space-y-2">
                                        <button onclick="insertTemplate('Your appointment is tomorrow at 9:00 AM. Please arrive 10 minutes early. ScanCare')" class="w-full text-left px-4 py-3 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors font-medium text-sm text-slate-900">
                                            <i class="fas fa-calendar-check text-[#454B1B] mr-2"></i> Appointment Reminder
                                        </button>
                                        <button onclick="insertTemplate('The Rural Health Unit will open tomorrow. Visit us for free checkup and vaccination services. ScanCare')" class="w-full text-left px-4 py-3 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors font-medium text-sm text-slate-900">
                                            <i class="fas fa-hospital text-[#454B1B] mr-2"></i> Clinic Announcement
                                        </button>
                                        <button onclick="insertTemplate('Follow-up medication reminder. Take as prescribed. For concerns, visit the RHU. ScanCare')" class="w-full text-left px-4 py-3 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors font-medium text-sm text-slate-900">
                                            <i class="fas fa-pills text-[#454B1B] mr-2"></i> Medication Reminder
                                        </button>
                                    </div>
                                </div>

                                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-6">
                                    <h3 class="text-lg font-heading font-bold text-emerald-900 mb-4 flex items-center gap-2">
                                        <i class="fas fa-chart-bar text-emerald-600"></i> SMS Statistics
                                    </h3>
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-emerald-800 font-medium">Sent Today</span>
                                            <span class="text-2xl font-bold text-emerald-700"><?php echo $sms_sent_today; ?></span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-emerald-800 font-medium">Pending</span>
                                            <span class="text-2xl font-bold text-emerald-700"><?php echo $pending_reminders; ?></span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-emerald-800 font-medium">Failed</span>
                                            <span class="text-2xl font-bold text-emerald-700"><?php echo $sms_failed_today; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ============================================================
                         MODULE: COMMUNITY ANNOUNCEMENTS
                         ============================================================ -->
                    <?php if (isModuleActive('announcements')): ?>
                    <div>
                        <h2 class="text-2xl font-heading font-bold text-slate-900 mb-2">Community Announcements</h2>
                        <p class="text-sm text-slate-600 mb-8">Create and manage health updates for residents to see on their dashboard.</p>

                        <?php if ($announcementStatus === 'published'): ?>
                            <div id="announcementStatusToast" class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">Announcement published and visible to residents.</div>
                        <?php elseif ($announcementStatus === 'missing'): ?>
                            <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">Title and message are required.</div>
                        <?php elseif ($announcementStatus === 'error'): ?>
                            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">The announcement could not be published.</div>
                        <?php elseif ($announcementStatus === 'updated'): ?>
                            <div id="announcementStatusToast" class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">Announcement updated successfully.</div>
                        <?php endif; ?>

                        <div class="mb-6 flex flex-wrap gap-2 border-b border-slate-200 pb-3">
                            <a href="?module=announcements&announcement_tab=create" data-announcement-tab-link data-announcement-primary-tab data-announcement-mode="create" class="rounded-lg px-4 py-2 text-sm font-semibold <?php echo $announcementTab === 'create' ? 'bg-[#3D4A1E] text-white' : 'text-slate-600 hover:bg-slate-100'; ?>">Create Announcement</a>
                            <a href="?module=announcements&announcement_tab=manage&announcement_state=<?php echo htmlspecialchars($announcementState); ?>" data-announcement-tab-link data-announcement-primary-tab data-announcement-mode="manage" class="rounded-lg px-4 py-2 text-sm font-semibold <?php echo $announcementTab === 'manage' ? 'bg-[#3D4A1E] text-white' : 'text-slate-600 hover:bg-slate-100'; ?>">Manage Announcement</a>
                        </div>

                        <div data-announcement-panel="create" <?php echo $announcementTab === 'create' ? '' : 'hidden'; ?>>
                            <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                                <form method="POST" class="space-y-5">
                                    <input type="hidden" name="action" value="create_announcement">
                                    <div>
                                        <label for="announcementTitle" class="mb-2 block text-sm font-semibold text-slate-700">Title</label>
                                        <input id="announcementTitle" name="title" type="text" maxlength="150" required placeholder="Free blood pressure check-up" class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-[#3D4A1E] focus:outline-none focus:ring-2 focus:ring-[#3D4A1E]/20">
                                    </div>
                                    <div>
                                        <label for="announcementCreatedDate" class="mb-2 block text-sm font-semibold text-slate-700">Date created</label>
                                        <input id="announcementCreatedDate" type="text" value="<?php echo date('M d, Y'); ?>" readonly class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                                    </div>
                                    <div>
                                        <label for="announcementMessage" class="mb-2 block text-sm font-semibold text-slate-700">Body</label>
                                        <textarea id="announcementMessage" name="message" rows="5" required placeholder="Tell residents what is available and who can attend." class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-[#3D4A1E] focus:outline-none focus:ring-2 focus:ring-[#3D4A1E]/20"></textarea>
                                    </div>
                                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                        <div>
                                            <label for="announcementState" class="mb-2 block text-sm font-semibold text-slate-700">State</label>
                                            <select id="announcementState" name="state" class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-[#3D4A1E] focus:outline-none focus:ring-2 focus:ring-[#3D4A1E]/20">
                                                <option value="upcoming">Upcoming</option>
                                                <option value="now">Now</option>
                                                <option value="done">Done</option>
                                            </select>
                                            <p class="mt-1 text-xs text-slate-500">When a service date is set, the state follows that date.</p>
                                        </div>
                                        <div>
                                            <label for="announcementDate" class="mb-2 block text-sm font-semibold text-slate-700">Service date <span class="font-normal text-slate-400">(optional)</span></label>
                                            <input id="announcementDate" name="service_date" type="date" class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-[#3D4A1E] focus:outline-none focus:ring-2 focus:ring-[#3D4A1E]/20">
                                        </div>
                                    </div>
                                    <div>
                                        <label for="announcementLocation" class="mb-2 block text-sm font-semibold text-slate-700">Location <span class="font-normal text-slate-400">(optional)</span></label>
                                        <input id="announcementLocation" name="location" type="text" maxlength="150" placeholder="RHU II - Gutad" class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-[#3D4A1E] focus:outline-none focus:ring-2 focus:ring-[#3D4A1E]/20">
                                    </div>
                                    <button type="submit" class="rounded-lg bg-[#3D4A1E] px-5 py-3 font-semibold text-white transition hover:bg-[#303a18]"><i class="fas fa-bullhorn mr-2"></i> Create Announcement</button>
                                </form>
                            </div>
                        </div>

                        <div data-announcement-panel="manage" <?php echo $announcementTab === 'manage' ? '' : 'hidden'; ?>>
                            <div class="mb-5 flex flex-wrap gap-2">
                                <?php foreach (['upcoming' => 'Upcoming', 'now' => 'Now', 'done' => 'Done'] as $stateKey => $stateLabel): ?>
                                    <a href="?module=announcements&announcement_tab=manage&announcement_state=<?php echo $stateKey; ?>" data-announcement-tab-link data-announcement-mode="manage" data-announcement-state-tab="<?php echo $stateKey; ?>" class="rounded-full border px-4 py-2 text-sm font-semibold <?php echo $announcementState === $stateKey ? 'border-[#3D4A1E] bg-[#3D4A1E] text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-[#3D4A1E]'; ?>"><?php echo $stateLabel; ?></a>
                                <?php endforeach; ?>
                            </div>
                            <div class="space-y-5">
                                <?php foreach ($managedAnnouncements as $announcement): ?>
                                        <form method="POST" data-announcement-item="<?php echo htmlspecialchars($announcement['state']); ?>" class="rounded-2xl border border-slate-200 bg-slate-50 p-6 shadow-sm" <?php echo $announcement['state'] === $announcementState ? '' : 'hidden'; ?> >
                                            <input type="hidden" name="action" value="update_announcement">
                                            <input type="hidden" name="announcement_id" value="<?php echo intval($announcement['announcement_id']); ?>">
                                            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                                                <span data-announcement-state-label class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-slate-600"><?php echo htmlspecialchars($announcement['state']); ?></span>
                                                <span class="text-xs text-slate-400">By <?php echo htmlspecialchars($announcement['author_name'] ?: 'BHW #' . intval($announcement['worker_id'])); ?> · Created <?php echo date('M d, Y', strtotime($announcement['created_at'])); ?></span>
                                            </div>
                                            <div class="grid gap-4 md:grid-cols-2">
                                                <div class="md:col-span-2">
                                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Title</label>
                                                    <input name="title" type="text" maxlength="150" required value="<?php echo htmlspecialchars($announcement['title']); ?>" class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-[#3D4A1E] focus:outline-none focus:ring-2 focus:ring-[#3D4A1E]/20">
                                                </div>
                                                <div class="md:col-span-2">
                                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Body</label>
                                                    <textarea name="message" rows="4" required class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-[#3D4A1E] focus:outline-none focus:ring-2 focus:ring-[#3D4A1E]/20"><?php echo htmlspecialchars($announcement['message']); ?></textarea>
                                                </div>
                                                <div>
                                                    <label class="mb-2 block text-sm font-semibold text-slate-700">State</label>
                                                    <select name="state" class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-[#3D4A1E] focus:outline-none focus:ring-2 focus:ring-[#3D4A1E]/20">
                                                        <?php foreach (['upcoming' => 'Upcoming', 'now' => 'Now', 'done' => 'Done'] as $stateKey => $stateLabel): ?>
                                                            <option value="<?php echo $stateKey; ?>" <?php echo $announcement['state'] === $stateKey ? 'selected' : ''; ?>><?php echo $stateLabel; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <p class="mt-1 text-xs text-slate-500">When a service date is set, the state follows that date.</p>
                                                </div>
                                                <div>
                                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Service date</label>
                                                    <input name="service_date" type="date" value="<?php echo htmlspecialchars($announcement['service_date'] ?? ''); ?>" class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-[#3D4A1E] focus:outline-none focus:ring-2 focus:ring-[#3D4A1E]/20">
                                                </div>
                                                <div class="md:col-span-2">
                                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Location</label>
                                                    <input name="location" type="text" maxlength="150" value="<?php echo htmlspecialchars($announcement['location'] ?? ''); ?>" class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-[#3D4A1E] focus:outline-none focus:ring-2 focus:ring-[#3D4A1E]/20">
                                                </div>
                                            </div>
                                            <button type="submit" class="mt-5 rounded-lg bg-[#3D4A1E] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#303a18]"><i class="fas fa-save mr-2"></i> Save Changes</button>
                                            <button type="button" onclick="handleDeleteAnnouncement(event, <?php echo intval($announcement['announcement_id']); ?>)" class="mt-5 ml-2 rounded-lg border border-red-300 px-5 py-3 text-sm font-semibold text-red-700 transition hover:bg-red-50"><i class="fas fa-trash-alt mr-2"></i> Delete Announcement</button>
                                        </form>
                                <?php endforeach; ?>
                                <?php foreach (['upcoming', 'now', 'done'] as $emptyState): ?>
                                    <div data-announcement-empty="<?php echo $emptyState; ?>" class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500" <?php echo $emptyState === $announcementState && $announcementCounts[$emptyState] === 0 ? '' : 'hidden'; ?>>No <?php echo $emptyState; ?> announcements yet.</div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>

        </div>
    </main>

    <!-- ========================================================================
         FOOTER
         ======================================================================== -->
    <footer class="dashboard-footer bg-slate-900 text-white py-12 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-6 lg:px-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div><h3 class="font-heading font-bold text-lg mb-4">ScanCare</h3><p class="text-slate-400 text-sm">Healthcare made simple for Barangay Gutad</p></div>
                <div><h4 class="font-semibold mb-4">Services</h4><ul class="space-y-2 text-sm text-slate-400"><li><a href="appointments.php" class="hover:text-white transition">Appointments</a></li><li><a href="system-goals.php" class="hover:text-white transition">System Goals</a></li><li><a href="service-areas.php" class="hover:text-white transition">Service Areas</a></li></ul></div>
                <div><h4 class="font-semibold mb-4">Legal</h4><ul class="space-y-2 text-sm text-slate-400"><li><a href="compliance.php" class="hover:text-white transition">Privacy Policy & Terms of Service</a></li></ul></div>
                <div><h4 class="font-semibold mb-4">Contact</h4><p class="text-slate-400 text-sm">Floridablanca RHU II - GUTAD<br>Email: floridablancarhu2@gmail.com<br>Contact: 0948 056 6872</p></div>
            </div>
            <div class="border-t border-slate-800 pt-8 text-center text-slate-400 text-sm"><p>&copy; 2026 ScanCare. All rights reserved. Your data is encrypted and secure.</p></div>
        </div>
    </footer>

    <div id="centeredConfirmModal" class="fixed inset-0 z-[20000] hidden items-center justify-center bg-black/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="centeredConfirmTitle" aria-describedby="centeredConfirmMessage">
        <div class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-white/10 dark:bg-[#111813]">
            <div class="mb-4 flex items-center gap-3">
                <div id="centeredConfirmIcon" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300"><i class="fas fa-exclamation-triangle"></i></div>
                <h2 id="centeredConfirmTitle" class="text-lg font-bold text-slate-900 dark:text-white">Confirm action</h2>
            </div>
            <p id="centeredConfirmMessage" class="mb-6 whitespace-pre-line text-sm leading-6 text-slate-600 dark:text-slate-300"></p>
            <div class="flex justify-end gap-3">
                <button id="centeredConfirmCancel" type="button" onclick="closeCenteredConfirmModal()" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Cancel</button>
                <button id="centeredConfirmAction" type="button" onclick="confirmCenteredConfirmModal()" class="rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700">Confirm</button>
            </div>
        </div>
    </div>

    <!-- ========================================================================
         JAVASCRIPT HANDLERS
         ======================================================================== -->
    <script>
        window.centeredConfirmCallback = null;

        window.openCenteredConfirmation = function({ title, message, confirmLabel = 'Confirm', confirmClass = 'bg-red-600 hover:bg-red-700', onConfirm }) {
            const modal = document.getElementById('centeredConfirmModal');
            const confirmButton = document.getElementById('centeredConfirmAction');
            if (!modal || !confirmButton) return;
            const icon = document.getElementById('centeredConfirmIcon');
            const cancelButton = document.getElementById('centeredConfirmCancel');
            document.getElementById('centeredConfirmTitle').textContent = title;
            document.getElementById('centeredConfirmMessage').textContent = message;
            if (icon) {
                icon.className = 'flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300';
                icon.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
            }
            if (cancelButton) cancelButton.hidden = false;
            confirmButton.textContent = confirmLabel;
            confirmButton.className = `rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition ${confirmClass}`;
            window.centeredConfirmCallback = onConfirm;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
            confirmButton.focus();
        };

        window.showCenteredSuccess = function({ title = 'Success', message, onClose }) {
            const modal = document.getElementById('centeredConfirmModal');
            const confirmButton = document.getElementById('centeredConfirmAction');
            if (!modal || !confirmButton) return;
            const icon = document.getElementById('centeredConfirmIcon');
            const cancelButton = document.getElementById('centeredConfirmCancel');
            document.getElementById('centeredConfirmTitle').textContent = title;
            document.getElementById('centeredConfirmMessage').textContent = message;
            if (icon) {
                icon.className = 'flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300';
                icon.innerHTML = '<i class="fas fa-check-circle"></i>';
            }
            if (cancelButton) cancelButton.hidden = true;
            confirmButton.textContent = 'Done';
            confirmButton.className = 'rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700';
            window.centeredConfirmCallback = onClose;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
            confirmButton.focus();
        };

        window.closeCenteredConfirmModal = function() {
            const modal = document.getElementById('centeredConfirmModal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = 'auto';
            window.centeredConfirmCallback = null;
        };

        window.confirmCenteredConfirmModal = function() {
            const callback = window.centeredConfirmCallback;
            window.closeCenteredConfirmModal();
            if (typeof callback === 'function') callback();
        };

        (function () {
            const BHW_QUEUE_KEY = 'scancare_bhw_sync_queue_v1';

            function readQueue() {
                try {
                    const raw = localStorage.getItem(BHW_QUEUE_KEY);
                    const parsed = raw ? JSON.parse(raw) : [];
                    return Array.isArray(parsed) ? parsed : [];
                } catch (error) {
                    console.warn('Failed to read BHW sync queue:', error);
                    return [];
                }
            }

            function writeQueue(queue) {
                try {
                    localStorage.setItem(BHW_QUEUE_KEY, JSON.stringify(queue));
                } catch (error) {
                    console.warn('Failed to persist BHW sync queue:', error);
                }
            }

            function updateQueueStatus(message, color = 'text-slate-700', visible = true) {
                const status = document.getElementById('bhwSyncQueueStatus');
                if (!status) return;
                status.textContent = message;
                status.className = 'mt-2 text-xs font-medium ' + color;
                const retryButton = document.getElementById('bhwSyncQueueRetry');
                if (retryButton) retryButton.classList.toggle('hidden', !message.startsWith('Needs attention:'));
                const container = document.getElementById('bhwSyncQueueIndicator');
                if (container) container.classList.toggle('hidden', !visible);
            }

            function queueBhwAction(type, payload, endpoint = 'dashboard_bhw.php') {
                const queue = readQueue();
                const item = {
                    id: `${type}-${Date.now()}-${Math.random().toString(16).slice(2)}`,
                    type,
                    payload,
                    endpoint,
                    queuedAt: new Date().toISOString()
                };

                queue.push(item);
                writeQueue(queue);
                const queueCount = queue.length;
                updateQueueStatus(`${queueCount} queued change${queueCount > 1 ? 's' : ''} waiting to sync`, 'text-amber-600');
                return item;
            }

            function removeQueuedAction(id) {
                if (!id) return;
                const queue = readQueue().filter(item => item.id !== id);
                writeQueue(queue);
                const count = queue.length;
                if (count) {
                    updateQueueStatus(`${count} queued change${count > 1 ? 's' : ''} waiting to sync`, 'text-amber-600');
                } else {
                    updateQueueStatus('', 'text-emerald-600', false);
                }
            }

            let bhwSyncInProgress = false;

            async function syncQueuedBhwActions() {
                if (bhwSyncInProgress) {
                    return;
                }

                if (!navigator.onLine) {
                    const queue = readQueue();
                    const blockedConsultation = queue.find(item => item.type === 'save_consultation' && item.blockedReason);
                    if (blockedConsultation) {
                        updateQueueStatus('Needs attention: ' + blockedConsultation.blockedReason, 'text-red-600');
                    } else if (queue.length) {
                        updateQueueStatus(`${queue.length} queued change${queue.length > 1 ? 's' : ''} waiting to sync`, 'text-amber-600');
                    } else {
                        updateQueueStatus('', 'text-emerald-600', false);
                    }
                    return;
                }

                const queue = readQueue();
                if (!queue.length) {
                    updateQueueStatus('', 'text-emerald-600', false);
                    return;
                }

                bhwSyncInProgress = true;
                const remaining = [];
                try {
                    const syncStartedAt = Date.now();
                    updateQueueStatus('Syncing queued changes...', 'text-blue-600');

                    for (const item of queue) {
                        if (item.blockedReason) {
                            remaining.push(item);
                            continue;
                        }

                        try {
                            const endpoint = item.endpoint || (item.type === 'send_sms' ? 'send_sms_handler.php' : 'dashboard_bhw.php');
                            const formData = new FormData();
                            Object.entries(item.payload || {}).forEach(([key, value]) => {
                                if (Array.isArray(value)) {
                                    value.forEach(entry => formData.append(key, String(entry)));
                                } else if (value !== undefined && value !== null) {
                                    formData.append(key, String(value));
                                }
                            });

                            const response = await fetch(endpoint, {
                                method: 'POST',
                                body: formData,
                                credentials: 'same-origin',
                                headers: { 'X-ScanCare-Offline-Sync': '1' }
                            });

                            const raw = await response.text();
                            let result = null;
                            try {
                                result = JSON.parse(raw);
                            } catch (error) {
                                throw new Error(raw.substring(0, 200) || 'Invalid server response');
                            }

                            if (!response.ok || !result || result.success === false) {
                                const syncError = new Error(result && result.message ? result.message : 'Sync failed');
                                syncError.httpStatus = response.status;
                                throw syncError;
                            }
                        } catch (error) {
                            console.warn('Queued BHW action failed to sync:', error);
                            if (item.type === 'save_consultation' && error.httpStatus >= 400 && error.httpStatus < 500) {
                                item.blockedReason = error.message;
                            }
                            remaining.push(item);
                        }
                    }

                    const elapsed = Date.now() - syncStartedAt;
                    const minimumDisplayTime = 700;
                    if (elapsed < minimumDisplayTime) {
                        await new Promise(resolve => setTimeout(resolve, minimumDisplayTime - elapsed));
                    }

                    writeQueue(remaining);
                    const blockedConsultation = remaining.find(item => item.type === 'save_consultation' && item.blockedReason);
                    if (blockedConsultation) {
                        updateQueueStatus('Needs attention: ' + blockedConsultation.blockedReason, 'text-red-600');
                    } else if (remaining.length) {
                        updateQueueStatus(`${remaining.length} queued change${remaining.length > 1 ? 's' : ''} waiting to sync`, 'text-amber-600');
                    } else {
                        updateQueueStatus('', 'text-emerald-600', false);
                    }
                } finally {
                    bhwSyncInProgress = false;
                }
            }

            window.bhwOfflineQueue = {
                readQueue,
                writeQueue,
                queueBhwAction,
                removeQueuedAction,
                syncQueuedBhwActions
            };

            function isBhwOffline() {
                return navigator.onLine === false;
            }

            function updateOfflineOnlyControls() {
                const offline = isBhwOffline();
                const controls = document.querySelectorAll('form#manualEntryForm, a[href*="view_record.php"], form[action*="manage_file_request.php"], form[action*="delete_file_request.php"], [data-offline-control]');
                controls.forEach(control => {
                    control.classList.toggle('scancare-offline-disabled', offline);
                    control.setAttribute('aria-disabled', offline ? 'true' : 'false');
                    if (control.matches('a')) {
                        control.tabIndex = offline ? -1 : 0;
                    }
                    const fields = control.matches('input, select, textarea, button')
                        ? [control]
                        : control.querySelectorAll('input, select, textarea, button');
                    fields.forEach(field => { field.disabled = offline; });
                });
            }

            document.addEventListener('DOMContentLoaded', function () {
                const queueStatus = document.getElementById('bhwSyncQueueStatus');
                if (!queueStatus) {
                    const container = document.createElement('div');
                    container.id = 'bhwSyncQueueIndicator';
                    container.className = 'hidden fixed bottom-16 right-4 z-[10000] inline-flex max-w-[calc(100vw-2rem)] items-center gap-2 rounded-lg border border-slate-200 bg-white/95 px-3 py-2 text-xs font-medium text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-800/95 dark:text-slate-200';
                    container.innerHTML = '<span class="inline-block h-2 w-2 shrink-0 rounded-full bg-emerald-500"></span><span id="bhwSyncQueueStatus" class="min-w-0 whitespace-normal">All changes synced</span><button id="bhwSyncQueueRetry" type="button" class="hidden shrink-0 rounded border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-200">Retry</button>';
                    const target = document.querySelector('[data-sync-status-anchor]') || document.body;
                    if (target && target !== document.body) {
                        target.appendChild(container);
                    } else {
                        document.body.appendChild(container);
                    }
                }

                const retryButton = document.getElementById('bhwSyncQueueRetry');
                if (retryButton) {
                    retryButton.addEventListener('click', function () {
                        const retryQueue = readQueue().map(item => {
                            if (item.type === 'save_consultation') delete item.blockedReason;
                            return item;
                        });
                        writeQueue(retryQueue);
                        syncQueuedBhwActions();
                    });
                }

                const queue = readQueue();
                if (queue.length) {
                    updateQueueStatus(`${queue.length} queued change${queue.length > 1 ? 's' : ''} waiting to sync`, 'text-amber-600');
                } else {
                    updateQueueStatus('', 'text-emerald-600', false);
                }

                if (typeof window !== 'undefined') {
                    document.addEventListener('click', function (event) {
                        const control = event.target.closest('[data-offline-control]');
                        const offline = isBhwOffline();
                        if (control && offline) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                    }, true);
                    window.addEventListener('online', function () {
                        document.documentElement.dataset.network = 'online';
                        updateOfflineOnlyControls();
                        syncQueuedBhwActions();
                    });
                    window.addEventListener('pageshow', updateOfflineOnlyControls);
                    window.addEventListener('focus', updateOfflineOnlyControls);
                    document.addEventListener('visibilitychange', updateOfflineOnlyControls);
                    window.addEventListener('offline', function () {
                        if (readQueue().length) {
                            updateQueueStatus('Offline mode — queued changes will sync when you reconnect', 'text-amber-600');
                        }
                        updateOfflineOnlyControls();
                    });
                }

                updateOfflineOnlyControls();
                window.setInterval(function () {
                    if (navigator.onLine && document.documentElement.dataset.network === 'offline') {
                        document.documentElement.dataset.network = 'online';
                        updateOfflineOnlyControls();
                    }
                    updateOfflineOnlyControls();
                    if (navigator.onLine && readQueue().length) {
                        syncQueuedBhwActions();
                    }
                }, 2000);

                document.querySelectorAll('form').forEach(form => {
                    const action = form.querySelector('input[name="action"]')?.value;
                    if (!['create_announcement', 'update_announcement'].includes(action)) return;
                    form.addEventListener('submit', function (event) {
                        if (!isBhwOffline()) {
                            return;
                        }

                        event.preventDefault();
                        const payload = Object.fromEntries(new FormData(form).entries());

                        const queueAnnouncement = function () {
                            window.bhwOfflineQueue.queueBhwAction(action, payload, 'dashboard_bhw.php');
                            if (action === 'create_announcement') form.reset();
                            updateQueueStatus('Announcement waiting to sync', 'text-amber-600');
                        };

                        queueAnnouncement();
                    });
                });

                syncQueuedBhwActions();
            });
        })();
        // ====================================================================
        // SIDEBAR EXPAND/COLLAPSE ON HOVER
        // ====================================================================
        const sidebar = document.querySelector('.sidebar');
        const mainWithSidebar = document.querySelector('.main-with-sidebar');
        const dashboardFooter = document.querySelector('.dashboard-footer');

        if (sidebar && mainWithSidebar && window.matchMedia('(min-width: 1024px)').matches) {
            sidebar.addEventListener('mouseenter', function() {
                mainWithSidebar.style.width = 'calc(100% - 16rem)';
                mainWithSidebar.style.marginLeft = '16rem';
                if (dashboardFooter) {
                    dashboardFooter.style.width = 'calc(100% - 16rem)';
                    dashboardFooter.style.marginLeft = '16rem';
                }
            });

            sidebar.addEventListener('mouseleave', function() {
                mainWithSidebar.style.width = 'calc(100% - 5rem)';
                mainWithSidebar.style.marginLeft = '5rem';
                if (dashboardFooter) {
                    dashboardFooter.style.width = 'calc(100% - 5rem)';
                    dashboardFooter.style.marginLeft = '5rem';
                }
            });
        }

        // ====================================================================
        // QR SCANNER STATE MANAGEMENT
        // ====================================================================
        let html5QrcodeScanner = null;
        let isScannerActive = false;

        // ====================================================================
        // QR SCANNER INITIALIZATION - Start camera and begin scanning
        // ====================================================================
        async function initiateCameraCapture() {
            if (navigator.onLine === false || document.documentElement.dataset.network === 'offline') {
                alert('QR scanning is unavailable while offline. Reconnect to identify a patient.');
                return;
            }
            const container = document.getElementById('qrScannerContainer');
            container.classList.add('active');
            isScannerActive = true;
            document.body.style.overflow = 'hidden';
            updateScannerStatus('scanning', 'Requesting camera permission...');

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                updateScannerStatus('error', 'Your browser does not support camera access. Use a modern browser.');
                return;
            }

            try {
                const testStream = await navigator.mediaDevices.getUserMedia({ video: true });
                testStream.getTracks().forEach(track => track.stop());
            } catch (err) {
                console.error('Camera permission error:', err);
                let message = 'Unable to access camera. Please check permissions.';
                if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                    message = 'Camera permission denied. Allow access and try again.';
                } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                    message = 'No camera found. Please connect a camera and retry.';
                } else if (!window.isSecureContext && !['localhost', '127.0.0.1'].includes(location.hostname)) {
                    message = 'Camera access requires HTTPS or localhost. Open this app on a secure origin.';
                }
                updateScannerStatus('error', message);
                return;
            }

            if (html5QrcodeScanner) {
                try {
                    await html5QrcodeScanner.clear();
                } catch (ignored) {}
                html5QrcodeScanner = null;
            }

            html5QrcodeScanner = new Html5Qrcode(
                "html5-qrcode",
                {
                    fps: 10,
                    qrbox: { width: 250, height: 250 },
                    supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA],
                    experimentalFeatures: { useBarCodeDetectorIfAvailable: true }
                }
            );

            Html5Qrcode.getCameras()
                .then(devices => {
                    if (!devices || devices.length === 0) {
                        updateScannerStatus('error', 'No camera found. Please check permissions.');
                        return;
                    }

                    let rearCamera = devices[0];
                    const rearCameraId = devices.find(device =>
                        device.label.toLowerCase().includes('back') ||
                        device.label.toLowerCase().includes('rear') ||
                        device.label.toLowerCase().includes('environment')
                    )?.id;

                    if (rearCameraId) {
                        rearCamera = devices.find(device => device.id === rearCameraId) || rearCamera;
                    }

                    updateScannerStatus('scanning', 'Point camera at QR code');

                    html5QrcodeScanner.start(
                        rearCamera.id,
                        {
                            fps: 10,
                            qrbox: { width: 250, height: 250 }
                        },
                        onQRCodeDetected,
                        onQRCodeError
                    ).catch(err => {
                        console.error('Camera start error:', err);
                        updateScannerStatus('error', 'Failed to start camera. Check permissions.');
                    });
                })
                .catch(err => {
                    console.error('Camera detection error:', err);
                    updateScannerStatus('error', 'Unable to access camera.');
                });
        }

        // ====================================================================
        // QR CODE DETECTION HANDLER - Called when QR code is successfully scanned
        // ====================================================================
        function onQRCodeDetected(decodedText, decodedResult) {
            // Resident QR codes contain a ScanCare URL.  Do not treat the QR
            // payload as a file-request link here: resident_qr.php identifies
            // this BHW session and opens the resident profile, while the same
            // QR opened by a resident goes to the file-request confirmation.
            let scannedUrl;
            try {
                scannedUrl = new URL(String(decodedText).trim());
            } catch (error) {
                updateScannerStatus('error', 'Invalid QR code. Please scan a resident QR code.');
                return;
            }

            const scanPath = scannedUrl.pathname.toLowerCase();
            const isResidentQr = scanPath.endsWith('/resident_qr.php') && scannedUrl.searchParams.has('code');
            const isLegacyQr = (scanPath.endsWith('/verify_patient.php') && scannedUrl.searchParams.has('token')) ||
                scanPath.endsWith('/view_record.php');

            if (isResidentQr || isLegacyQr) {
                console.log('QR Code detected:', decodedText);
                updateScannerStatus('success', 'QR Code detected! Redirecting...');

                const redirectToPatient = () => {
                    // Match the Manual Entry result action: a resident QR
                    // opens the BHW's patient profile inside this dashboard.
                    // The resident-facing QR route is intentionally not used.
                    if (isResidentQr) {
                        window.location.href = '?qr_code=' + encodeURIComponent(scannedUrl.searchParams.get('code'));
                        return;
                    }
                    window.location.href = decodedText;
                };

                if (isScannerActive) {
                    stopQRScanner();
                    setTimeout(redirectToPatient, 500);
                } else {
                    redirectToPatient();
                }
            } else {
                console.log('QR code detected but invalid format:', decodedText);
                updateScannerStatus('error', 'Invalid QR code. Please scan a patient QR code.');
            }
        }

        // ====================================================================
        // QR CODE ERROR HANDLER - Called continuously while scanning
        // ====================================================================
        function onQRCodeError(errorMessage) {
            // Silently ignore scanning errors - this is normal during active scanning
            // Only log if scanner is stopped unexpectedly
            if (!isScannerActive) {
                console.log('Scanning stopped');
            }
        }

        // ====================================================================
        // SCANNER STATUS UPDATE HELPER
        // ====================================================================
        function updateScannerStatus(type, message) {
            const statusElement = document.getElementById('qrScannerStatus');
            if (statusElement) {
                statusElement.className = `qr-scanner-status ${type}`;
                const icon = type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'circle-dot';
                if (type === 'scanning') {
                    statusElement.innerHTML = `<div class="qr-scanner-spinner"></div><span>${message}</span>`;
                } else {
                    statusElement.innerHTML = `<i class="fas fa-${icon}" style="margin-right: 8px;"></i>${message}`;
                }
            }
        }

        // ====================================================================
        // STOP SCANNER - Clean up camera and hide container
        // ====================================================================
        function stopQRScanner() {
            isScannerActive = false;

            const container = document.getElementById('qrScannerContainer');
            container.classList.remove('active');
            document.body.style.overflow = 'auto';
            updateScannerStatus('ready', 'Scanner closed. Click Start QR Scanner again to reopen.');

            if (html5QrcodeScanner) {
                html5QrcodeScanner.stop()
                    .then(() => html5QrcodeScanner.clear())
                    .then(() => {
                        console.log('QR Scanner stopped successfully');
                        html5QrcodeScanner = null;
                    })
                    .catch(err => {
                        console.error('Error stopping scanner:', err);
                        html5QrcodeScanner = null;
                    });
            }
        }

        // ====================================================================
        // FALLBACK: Scan uploaded image file containing QR code
        // Uses Html5Qrcode.scanFile if available (minimal change)
        // ====================================================================
        (function registerFileFallback() {
            const fileInput = document.getElementById('qrImageFile');
            if (!fileInput) return;
            fileInput.addEventListener('change', async function(e) {
                const file = e.target.files && e.target.files[0];
                if (!file) return;
                updateScannerStatus('scanning', 'Decoding image...');
                try {
                    if (html5QrcodeScanner && isScannerActive) {
                        await html5QrcodeScanner.stop();
                        isScannerActive = false;
                    }

                    // scanFile is more reliable with a fresh decoder than a
                    // camera instance that was just stopped.  This is the path
                    // used when a BHW uploads a resident's saved QR image.
                    if (html5QrcodeScanner) {
                        try {
                            await html5QrcodeScanner.clear();
                        } catch (ignored) {}
                        html5QrcodeScanner = null;
                    }
                    html5QrcodeScanner = new Html5Qrcode('html5-qrcode');

                    if (typeof html5QrcodeScanner.scanFile === 'function') {
                        const decoded = await html5QrcodeScanner.scanFile(file, true);
                        if (decoded) {
                            onQRCodeDetected(typeof decoded === 'string' ? decoded : decoded.decodedText);
                            return;
                        }
                    }

                    updateScannerStatus('error', 'Unable to decode QR from image. Try a clearer photo.');
                } catch (err) {
                    console.error('Image decode error:', err);
                    updateScannerStatus('error', 'Unable to read that QR image. Use the original downloaded QR image or a clear, uncropped photo.');
                } finally {
                    // clear selection so same file can be re-uploaded
                    fileInput.value = '';
                }
            });
        })();

        // ====================================================================
        // Test camera permissions/devices and surface helpful messages
        // If successful, stop temporary stream and start Html5Qrcode scanner
        // ====================================================================
        async function testCamera() {
            updateScannerStatus('scanning', 'Requesting camera permission...');

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                updateScannerStatus('error', 'Camera API not supported in this browser. Use a modern browser (Chrome/Edge/Firefox).');
                return;
            }

            // Warn about insecure origin
            if (!window.isSecureContext && !['localhost', '127.0.0.1'].includes(location.hostname)) {
                updateScannerStatus('error', 'Camera access requires HTTPS or localhost. Open the app using https:// or http://localhost.');
                return;
            }

            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
                // If we get a stream, immediately stop and proceed to start scanner normally
                stream.getTracks().forEach(t => t.stop());
                updateScannerStatus('scanning', 'Camera available — starting scanner...');
                // small delay so user sees update
                setTimeout(() => {
                    initiateCameraCapture();
                }, 300);
            } catch (err) {
                console.error('Test camera error:', err);
                if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                    updateScannerStatus('error', 'Camera permission denied. Allow camera access and retry.');
                } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                    updateScannerStatus('error', 'No camera found. Connect a webcam and retry.');
                } else if (err.name === 'NotReadableError') {
                    updateScannerStatus('error', 'Camera is busy or cannot be opened. Close other apps using the camera and retry.');
                } else {
                    updateScannerStatus('error', 'Camera error: ' + (err.message || err.name));
                }
            }
        }

        // ====================================================================
        // ESCAPE KEY HANDLER - Close scanner on Escape key
        // ====================================================================
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && isScannerActive) {
                stopQRScanner();
            }
        });

        // ====================================================================
        // ERROR BANNER HELPERS
        // ====================================================================
        function showManualSearchError(message) {
            const banner = document.getElementById('manualSearchErrorBanner');
            const text = document.getElementById('manualSearchErrorText');
            if (banner && text) {
                text.textContent = message;
                banner.style.display = 'block';
                // Auto-hide after 8 seconds
                setTimeout(() => {
                    banner.style.display = 'none';
                }, 8000);
            }
        }
        
        function closeManualSearchError() {
            const banner = document.getElementById('manualSearchErrorBanner');
            if (banner) {
                banner.style.display = 'none';
            }
        }

        // ====================================================================
        // MANUAL PATIENT ENTRY HANDLER - Enhanced Multi-Field Search
        // ====================================================================
        function handleManualEntry(event) {
            if (event && typeof event.preventDefault === 'function') {
                event.preventDefault();
            }

            const formData = new FormData(document.getElementById('manualEntryForm'));
            const searchTerm = formData.get('search_term').trim();

            if (!searchTerm) {
                showManualSearchError('Please enter a search term (name, email, phone, or ID).');
                clearManualSearchResults();
                return false;
            }

            if (searchTerm.length > 100) {
                showManualSearchError('Search term is too long. Please enter a shorter query.');
                return false;
            }

            performManualSearch(searchTerm, { fromSubmit: true });
            return false;
        }

        document.addEventListener('DOMContentLoaded', function () {
            const manualEntryForm = document.getElementById('manualEntryForm');
            if (manualEntryForm) {
                manualEntryForm.addEventListener('submit', handleManualEntry);
            }
        });

        function handleManualSearchInput(event) {
            const searchTerm = event.target.value.trim();
            closeManualSearchError();

            if (!searchTerm) {
                clearManualSearchResults();
            }
        }

        function performManualSearch(searchTerm, { fromSubmit = false } = {}) {
            closeManualSearchError();
            const submitButton = document.querySelector('#manualEntryForm button[type="submit"]') || document.querySelector('#manualEntryForm button');
            const originalText = submitButton ? submitButton.innerHTML : 'Searching...';
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Searching...';
            }

            console.log('🔍 Searching patient accounts for:', searchTerm);

            const manualSearchToken = <?php echo json_encode($manual_search_token); ?>;

            fetch(window.location.href, {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: 'action=search_patient&search_term=' + encodeURIComponent(searchTerm) + '&search=' + encodeURIComponent(searchTerm)
            })
            .then(async response => {
                const text = await response.text();
                let data = null;
                try {
                    data = text ? JSON.parse(text) : null;
                } catch (err) {
                    // Not JSON — include raw text in the error for debugging
                    throw new Error('Invalid server response: ' + text);
                }

                if (!response.ok) {
                    if (response.status === 401) {
                        throw new Error('Your BHW session could not be verified. Please refresh your login session and try again.');
                    }
                    const errMsg = (data && data.message) ? data.message : ('Server returned HTTP ' + response.status + ': ' + text);
                    throw new Error(errMsg);
                }

                return data;
            })
            .then(data => {
                if (!data) {
                    return;
                }

                submitButton.disabled = false;
                submitButton.innerHTML = originalText;

                if (!data.success) {
                    clearManualSearchResults();
                    const errorMsg = data.message || 'An error occurred while searching.';
                    if (fromSubmit) {
                        showManualSearchError(errorMsg);
                    }
                    return;
                }

                if (data.patients && data.patients.length > 0) {
                    renderManualSearchResults(data.patients);
                    return;
                }

                clearManualSearchResults();
                if (fromSubmit) {
                    showManualSearchError(data.message || 'No matching patient accounts were found.');
                }
            })
            .catch(error => {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
                clearManualSearchResults();
                showManualSearchError(error.message || 'Search failed. Please try again.');
            });
        }

        function renderManualSearchResults(patients) {
            const resultsPanel = document.getElementById('manualSearchResults');
            const resultsList = document.getElementById('manualSearchResultsList');

            if (!resultsPanel || !resultsList) {
                return;
            }

            resultsList.innerHTML = '';

            if (!patients || patients.length === 0) {
                const emptyRow = document.createElement('div');
                emptyRow.className = 'manual-search-option';
                emptyRow.innerHTML = '<div class="font-semibold text-slate-700">No matching patients found.</div>';
                resultsList.appendChild(emptyRow);
            } else {
                patients.forEach(patient => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'manual-search-option';
                    button.innerHTML = `
                        <div>
                            <p class="font-semibold text-slate-900">${patient.name}</p>
                            <div class="patient-meta">
                                <span>ID: ${patient.patient_code || String(patient.id).padStart(6, '0')}</span>
                                <span>${patient.email}</span>
                                <span>${patient.phone}</span>
                                <span>${patient.dob}</span>
                            </div>
                        </div>
                        <div class="text-xs font-semibold text-[#454B1B]">Select</div>
                    `;
                    button.addEventListener('click', () => {
                        window.location.href = '?module=patient-profile&patient_id=' + encodeURIComponent(patient.id);
                    });
                    resultsList.appendChild(button);
                });
            }

            resultsPanel.classList.remove('hidden');
            resultsPanel.style.display = 'block';
        }

        function clearManualSearchResults() {
            const resultsPanel = document.getElementById('manualSearchResults');
            const resultsList = document.getElementById('manualSearchResultsList');
            if (resultsList) {
                resultsList.innerHTML = '';
            }
            if (resultsPanel) {
                resultsPanel.classList.add('hidden');
                resultsPanel.style.display = 'none';
            }
        }

        // ====================================================================
        // LOAD PATIENT PROFILE DATA AND DISPLAY
        // ====================================================================
        // ====================================================================
        // CONSULTATION FORM HANDLER
        // ====================================================================
        function handleConsultationSubmit(event) {
            event.preventDefault();
            const dropdown = document.getElementById('patientDropdown');
            const selectedOption = dropdown?.options[dropdown.selectedIndex];
            const submitButton = document.getElementById('consultationSubmitBtn');
            if (!selectedOption?.dataset.residentId) {
                alert('Please select a patient first.');
                return;
            }

            const consultationPayload = {
                action: 'save_consultation',
                resident_id: selectedOption.dataset.residentId,
                appointment_id: selectedOption.dataset.walkIn === 'true' ? '' : selectedOption.value,
                walk_in: selectedOption.dataset.walkIn === 'true' ? '1' : '0',
                appointment_date: selectedOption.dataset.appointmentDate || '',
                diagnosis: document.getElementById('diagnosisField').value,
                treatment_plan: document.getElementById('treatmentField').value,
                clinical_notes: document.getElementById('notesField').value
            };

            if (!navigator.onLine) {
                window.bhwOfflineQueue.queueBhwAction('save_consultation', consultationPayload, 'dashboard_bhw.php');
                alert('📶 Consultation saved locally. It will sync when the connection is restored.');
                return;
            }

            submitButton.disabled = true;
            fetch('dashboard_bhw.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    ...consultationPayload
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) throw new Error(data.message || 'Unable to save consultation.');
                    window.showCenteredSuccess({
                        title: 'Consultation Saved',
                        message: data.message,
                        onClose: () => {
                            window.location.href = data.appointment_id
                                ? '?module=patient-profile&patient_id=' + encodeURIComponent(selectedOption.dataset.residentId)
                                    + '&appointment_id=' + encodeURIComponent(data.appointment_id)
                                : '?module=identify-patient';
                        }
                    });
                })
                .catch(error => {
                    if (window.bhwOfflineQueue && window.bhwOfflineQueue.queueBhwAction) {
                        window.bhwOfflineQueue.queueBhwAction('save_consultation', consultationPayload, 'dashboard_bhw.php');
                        alert('📶 The connection was lost. Your consultation was saved locally and will sync when you reconnect.');
                    } else {
                        alert(error.message);
                    }
                })
                .finally(() => { submitButton.disabled = false; });
        }

        let previousConsultationRequestId = 0;

        async function loadPreviousConsultation(residentId) {
            const requestId = ++previousConsultationRequestId;
            const diagnosisField = document.getElementById('diagnosisField');
            const treatmentField = document.getElementById('treatmentField');
            const notesField = document.getElementById('notesField');
            diagnosisField.value = '';
            treatmentField.value = '';
            notesField.value = '';

            if (!residentId) return;

            try {
                const response = await fetch(
                    'dashboard_bhw.php?action=get_last_consultation&resident_id=' + encodeURIComponent(residentId),
                    { credentials: 'same-origin', cache: 'no-store' }
                );
                const data = await response.json();
                if (requestId !== previousConsultationRequestId || !data.success || !data.previous_visit) return;

                diagnosisField.value = data.previous_visit.diagnosis || '';
                treatmentField.value = data.previous_visit.treatment_plan || '';
                notesField.value = data.previous_visit.clinical_notes || '';
            } catch (error) {
                console.warn('Previous consultation preload skipped:', error);
            }
        }

        // ====================================================================
        // PATIENT DROPDOWN SELECTION HANDLER
        // ====================================================================
        function handlePatientSelection() {
            const dropdown = document.getElementById('patientDropdown');
            const selectedOption = dropdown.options[dropdown.selectedIndex];
            const detailsPanel = document.getElementById('patientDetailsPanel');
            
            if (!selectedOption.value) {
                detailsPanel.style.display = 'none';
                loadPreviousConsultation('');
                disableConsultationFields();
                return;
            }

            const patientData = {
                name: selectedOption.dataset.patientName,
                dob: selectedOption.dataset.dob,
                sex: selectedOption.dataset.sex,
                contact: selectedOption.dataset.contact
            };

            document.getElementById('patientName').textContent = patientData.name;
            document.getElementById('patientDOB').textContent = patientData.dob ? new Date(patientData.dob).toLocaleDateString() : 'N/A';
            document.getElementById('patientSex').textContent = patientData.sex || 'N/A';
            document.getElementById('patientContact').textContent = patientData.contact;

            const appointmentSummary = document.getElementById('consultationAppointmentSummary');
            const appointmentDetails = document.getElementById('consultationAppointmentDetails');
            const hasAppointment = /^\d+$/.test(selectedOption.value) || selectedOption.dataset.walkIn === 'true';
            if (appointmentSummary && appointmentDetails) {
                const appointmentParts = [
                    selectedOption.dataset.appointmentDate,
                    selectedOption.dataset.appointmentTime,
                    selectedOption.dataset.purpose
                ].filter(Boolean);
                appointmentDetails.textContent = appointmentParts.join(' · ');
                appointmentSummary.classList.toggle('hidden', !hasAppointment);
            }
            
            detailsPanel.style.display = 'block';
            enableConsultationFields();
            loadPreviousConsultation(selectedOption.dataset.residentId);
        }

        const requestedConsultationResidentId = <?php echo $consultation_resident_id; ?>;
        const requestedConsultationAppointmentId = <?php echo $consultation_appointment_id; ?>;
        const requestedConsultationWalkIn = <?php echo $consultation_walk_in ? 'true' : 'false'; ?>;

        document.addEventListener('DOMContentLoaded', function() {
            const dropdown = document.getElementById('patientDropdown');
            if (!dropdown) return;

            const matchingAppointment = requestedConsultationAppointmentId
                ? Array.from(dropdown.options).find(option => option.value === String(requestedConsultationAppointmentId))
                : null;
            const matchingWalkIn = requestedConsultationWalkIn
                ? Array.from(dropdown.options).find(option => option.dataset.walkIn === 'true' && option.dataset.residentId === String(requestedConsultationResidentId))
                : null;
            const matchingOption = matchingAppointment || matchingWalkIn || (requestedConsultationResidentId
                ? Array.from(dropdown.options).find(option => option.dataset.residentId === String(requestedConsultationResidentId))
                : null);
            if (matchingOption) {
                dropdown.value = matchingOption.value;
                handlePatientSelection();
            }
        });

        const APPROVED_PATIENTS_CACHE_KEY = 'scancare_bhw_approved_patients_v2';

        function getCachedApprovedPatients() {
            try {
                const cached = JSON.parse(localStorage.getItem(APPROVED_PATIENTS_CACHE_KEY) || '[]');
                return Array.isArray(cached) ? cached : [];
            } catch (error) {
                return [];
            }
        }

        function cacheApprovedPatients(patients) {
            if (!Array.isArray(patients) || !patients.length) return;
            localStorage.setItem(APPROVED_PATIENTS_CACHE_KEY, JSON.stringify(patients));
        }

        function populateApprovedPatients(patients) {
            const dropdown = document.getElementById('patientDropdown');
            if (!dropdown || !Array.isArray(patients)) return;

            const selectedOption = dropdown.selectedOptions[0];
            const selectedResidentId = selectedOption?.dataset.residentId || '';
            const selectedAppointmentId = dropdown.value || String(requestedConsultationAppointmentId || '');
            const pinnedAppointmentOption = requestedConsultationAppointmentId
                ? Array.from(dropdown.options).find(option => option.value === String(requestedConsultationAppointmentId))
                : null;
            const pinnedAppointment = pinnedAppointmentOption ? {
                appointment_id: pinnedAppointmentOption.value,
                resident_id: pinnedAppointmentOption.dataset.residentId,
                patient_name: pinnedAppointmentOption.dataset.patientName,
                date_of_birth: pinnedAppointmentOption.dataset.dob,
                sex: pinnedAppointmentOption.dataset.sex,
                contact_number: pinnedAppointmentOption.dataset.contact,
                email: pinnedAppointmentOption.dataset.email,
                appointment_date: pinnedAppointmentOption.dataset.appointmentDate,
                appointment_time: pinnedAppointmentOption.dataset.appointmentTime,
                purpose: pinnedAppointmentOption.dataset.purpose
            } : null;
            const pinnedDirectPatient = (selectedOption?.dataset.walkIn === 'true' || selectedOption?.value.startsWith('resident-')) ? {
                appointment_id: selectedOption.value,
                resident_id: selectedOption.dataset.residentId,
                patient_name: selectedOption.dataset.patientName,
                date_of_birth: selectedOption.dataset.dob,
                sex: selectedOption.dataset.sex,
                contact_number: selectedOption.dataset.contact,
                email: selectedOption.dataset.email,
                appointment_date: selectedOption.dataset.appointmentDate,
                appointment_time: selectedOption.dataset.appointmentTime,
                purpose: selectedOption.dataset.purpose,
                is_walk_in: selectedOption.dataset.walkIn === 'true'
            } : null;
            const patientsToRender = patients.slice();
            if (pinnedAppointment && !patientsToRender.some(patient => String(patient.appointment_id) === String(requestedConsultationAppointmentId))) {
                patientsToRender.push(pinnedAppointment);
            }
            if (pinnedDirectPatient && !patientsToRender.some(patient => String(patient.appointment_id) === selectedOption.value)) {
                patientsToRender.push(pinnedDirectPatient);
            }

            while (dropdown.options.length > 1) dropdown.remove(1);

            patientsToRender.forEach(patient => {
                const option = document.createElement('option');
                option.value = patient.appointment_id;
                option.dataset.residentId = patient.resident_id;
                option.dataset.patientName = patient.patient_name;
                option.dataset.dob = patient.date_of_birth || '';
                option.dataset.sex = patient.sex || '';
                option.dataset.contact = patient.contact_number || '';
                option.dataset.email = patient.email || '';
                option.dataset.appointmentDate = patient.appointment_date || '';
                option.dataset.appointmentTime = patient.appointment_time || '';
                option.dataset.purpose = patient.purpose || '';
                option.dataset.walkIn = patient.is_walk_in ? 'true' : 'false';
                option.textContent = `${patient.patient_name} (${patient.appointment_date || 'Date not set'} ${patient.appointment_time || ''})`;
                dropdown.appendChild(option);
            });

            const restoredOption = Array.from(dropdown.options).find(option => option.value === selectedAppointmentId)
                || Array.from(dropdown.options).find(option => option.dataset.residentId === selectedResidentId);
            if (restoredOption) {
                dropdown.value = restoredOption.value;
                handlePatientSelection();
            }
        }

        async function initializeConsultationPatients() {
            const dropdown = document.getElementById('patientDropdown');
            if (!dropdown) return;

            const serverPatients = Array.from(dropdown.options).slice(1).map(option => ({
                appointment_id: option.value,
                resident_id: option.dataset.residentId,
                patient_name: option.dataset.patientName,
                date_of_birth: option.dataset.dob,
                sex: option.dataset.sex,
                contact_number: option.dataset.contact,
                email: option.dataset.email,
                appointment_date: option.dataset.appointmentDate,
                appointment_time: option.dataset.appointmentTime,
                purpose: option.dataset.purpose,
                is_walk_in: option.dataset.walkIn === 'true'
            }));
            cacheApprovedPatients(serverPatients);

            if (navigator.onLine && document.documentElement.dataset.network !== 'offline') {
                try {
                    const response = await fetch('get_approved_patients.php?ts=' + Date.now(), { credentials: 'same-origin', cache: 'no-store' });
                    const data = await response.json();
                    if (response.ok && data.success && Array.isArray(data.patients) && data.patients.length) {
                        cacheApprovedPatients(data.patients);
                        populateApprovedPatients(data.patients);
                        return;
                    }
                } catch (error) {
                    console.warn('Approved patient refresh skipped:', error);
                }
            }

            if (!serverPatients.length) {
                populateApprovedPatients(getCachedApprovedPatients());
            }
        }

        document.addEventListener('DOMContentLoaded', initializeConsultationPatients);

        /**
         * Refresh patient dropdown from backend
         * Called after appointment approval to show newly approved patients
         */
        function refreshPatientDropdown() {
            fetch('get_approved_patients.php?ts=' + Date.now(), { credentials: 'same-origin', cache: 'no-store' })
                .then(response => response.json())
                .then(data => {
                    if (data.success && Array.isArray(data.patients) && data.patients.length) {
                        cacheApprovedPatients(data.patients);
                        populateApprovedPatients(data.patients);
                        console.log('Dropdown refreshed with ' + data.patients.length + ' approved patients');
                    }
                })
                .catch(error => console.error('Error refreshing dropdown:', error));
        }

        function enableConsultationFields() {
            document.getElementById('diagnosisField').disabled = false;
            document.getElementById('treatmentField').disabled = false;
            document.getElementById('notesField').disabled = false;
            document.getElementById('consultationSubmitBtn').disabled = false;
        }

        function disableConsultationFields() {
            document.getElementById('diagnosisField').disabled = true;
            document.getElementById('treatmentField').disabled = true;
            document.getElementById('notesField').disabled = true;
            document.getElementById('consultationSubmitBtn').disabled = true;
        }

        // ====================================================================
        // MEDICINE DISPENSING HANDLER
        // ====================================================================
        async function switchMedicineTab(tab) {
            const offline = navigator.onLine === false || document.documentElement.dataset.network === 'offline';
            if (tab === 'dispensed' && offline) {
                alert('Dispensed medicine records are unavailable while offline. Reconnect to view this tab.');
                return;
            }

            if (tab === 'dispensed') {
                try {
                    const probeResponse = await fetch('get_pending_logins.php?offline_probe=' + Date.now(), {
                        method: 'GET',
                        cache: 'no-store',
                        credentials: 'same-origin'
                    });
                    const probeData = await probeResponse.json().catch(() => ({}));
                    if (!probeResponse.ok || probeData.offline === true) {
                        throw new Error('Offline');
                    }
                } catch (error) {
                    document.documentElement.dataset.network = 'offline';
                    updateOfflineOnlyControls();
                    alert('Dispensed medicine records are unavailable while offline. Reconnect to view this tab.');
                    return;
                }
            }

            const inventoryPanel = document.getElementById('medicine-inventory-panel');
            const dispensedPanel = document.getElementById('medicine-dispensed-panel');
            const inventoryTab = document.querySelector('[data-medicine-tab="inventory"]');
            const dispensedTab = document.querySelector('[data-medicine-tab="dispensed"]');
            const showInventory = tab === 'inventory';

            inventoryPanel?.classList.toggle('hidden', !showInventory);
            dispensedPanel?.classList.toggle('hidden', showInventory);
            inventoryTab?.classList.toggle('border-[#3D4A1E]', showInventory);
            inventoryTab?.classList.toggle('text-[#3D4A1E]', showInventory);
            inventoryTab?.classList.toggle('border-transparent', !showInventory);
            inventoryTab?.classList.toggle('text-slate-500', !showInventory);
            dispensedTab?.classList.toggle('border-[#3D4A1E]', !showInventory);
            dispensedTab?.classList.toggle('text-[#3D4A1E]', !showInventory);
            dispensedTab?.classList.toggle('border-transparent', showInventory);
            dispensedTab?.classList.toggle('text-slate-500', showInventory);
        }

        function dispenseMedicine() {
            if (navigator.onLine === false || document.documentElement.dataset.network === 'offline') {
                alert('Medicine dispensing is unavailable while offline. Reconnect before dispensing to a resident.');
                return;
            }
            const medicineSelect = document.getElementById('inventoryMedicineSelect');
            const quantityInput = document.getElementById('inventoryMedicineQuantity');
            const dispensingList = document.getElementById('inventoryDispensingList');
            const medicineOption = medicineSelect?.selectedOptions[0];
            const quantity = Number.parseInt(quantityInput?.value || '0', 10);

            if (!medicineOption?.value || !Number.isInteger(quantity) || quantity < 1) {
                alert('Select a medicine and enter a valid quantity.');
                return;
            }

            const emptyMessage = dispensingList.querySelector('p');
            if (emptyMessage) emptyMessage.remove();

            const item = document.createElement('div');
            item.className = 'flex items-center justify-between rounded-lg border border-emerald-200 bg-white p-3';
            item.dataset.medicineId = medicineOption.value;
            item.dataset.quantity = String(quantity);
            const label = document.createElement('span');
            label.className = 'text-sm font-medium text-emerald-900';
            label.textContent = medicineOption.textContent.split(' (')[0] + ' x ' + quantity;
            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'text-emerald-600 hover:text-emerald-800';
            removeButton.setAttribute('aria-label', 'Remove medicine');
            removeButton.innerHTML = '<i class="fas fa-trash-alt"></i>';
            item.append(label, removeButton);
            removeButton.addEventListener('click', () => item.remove());
            dispensingList.appendChild(item);
        }

        async function recordFreeMedicineDispensing() {
            if (navigator.onLine === false || document.documentElement.dataset.network === 'offline') {
                alert('Medicine dispensing is unavailable while offline. Reconnect before recording dispensing.');
                return;
            }
            const residentDropdown = document.getElementById('dispensingResidentSelect');
            const residentId = residentDropdown?.value;
            const items = Array.from(document.querySelectorAll('#inventoryDispensingList [data-medicine-id]'));
            const medicines = items.map(item => ({
                medicine_id: Number.parseInt(item.dataset.medicineId, 10),
                quantity: Number.parseInt(item.dataset.quantity, 10)
            }));

            if (!residentId) {
                alert('Select the resident receiving the medicine before recording dispensing.');
                return;
            }
            if (!medicines.length) {
                alert('Add at least one medicine to the dispensing list.');
                return;
            }

            try {
                const response = await fetch('dashboard_bhw.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'dispense_free_medicine',
                        resident_id: residentId,
                        medicines: JSON.stringify(medicines)
                    })
                });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'Unable to record dispensing.');
                window.showCenteredSuccess({
                    title: 'Dispensing Recorded',
                    message: result.message,
                    onClose: () => window.location.reload()
                });
            } catch (error) {
                alert(error.message);
            }
        }

        // ====================================================================
        // MEDICINE INVENTORY FUNCTIONS
        // ====================================================================
        
        // Refresh medicine table - reload page to show new medicines
        function refreshMedicineTable() {
            console.log('Refreshing medicine table...');
            // Simple reliable refresh: reload the page
            location.reload();
        }
        
        // ====================================================================
        // MEDICINE INVENTORY - HELPER FUNCTIONS FOR STATUS CALCULATION
        // ====================================================================
        /**
         * Calculate medicine status based on available quantity
         * @param {number} quantity - Current available quantity
         * @returns {object} - {status: string, color: string, icon: string, display: string}
         */
        function calculateMedicineStatus(quantity) {
            const qty = parseInt(quantity) || 0;
            if (qty > 5) {
                return {
                    status: 'Available',
                    color: 'green',
                    display: '<span class="inline-flex items-center gap-1 px-3 py-1 bg-green-50 text-green-700 rounded-full font-medium text-xs"><i class="fas fa-circle text-xs"></i> Available</span>'
                };
            } else if (qty > 0 && qty <= 5) {
                return {
                    status: 'Low Stock',
                    color: 'orange',
                    display: '<span class="inline-flex items-center gap-1 px-3 py-1 bg-orange-50 text-orange-700 rounded-full font-medium text-xs"><i class="fas fa-exclamation-triangle text-xs mr-1"></i> Low Stock</span>'
                };
            } else {
                return {
                    status: 'Out of Stock',
                    color: 'slate',
                    display: '<span class="inline-flex items-center gap-1 px-3 py-1 bg-slate-100 text-slate-700 rounded-full font-medium text-xs"><i class="fas fa-circle text-xs"></i> Out of Stock</span>'
                };
            }
        }
        
        function updateAddMedicineStatus() {
            const availableInput = document.getElementById('addMedicineAvailable');
            const statusDiv = document.getElementById('addMedicineStatusPreview');
            if (availableInput && statusDiv) {
                statusDiv.innerHTML = calculateMedicineStatus(availableInput.value).display;
            }
        }
        
        /**
         * Update status preview in edit modal
         */
        function updateEditMedicineStatus() {
            const availableInput = document.getElementById('editMedicineAvailable');
            const statusDiv = document.getElementById('editMedicineStatusPreview');
            
            if (availableInput && statusDiv) {
                const statusInfo = calculateMedicineStatus(availableInput.value);
                statusDiv.innerHTML = statusInfo.display;
            }
        }
        
        // ====================================================================
        // SHOW ADD MEDICINE MODAL - Enhanced Version
        // ====================================================================
        function showAddMedicineForm() {
            const modal = document.getElementById('addMedicineModal');
            if (!modal) {
                console.error('Add Medicine modal not found');
                alert('Error: Modal not found. Please refresh the page.');
                return;
            }
            
            // Reset form
            const form = document.getElementById('addMedicineForm');
            if (form) {
                form.reset();
            }
            
            // Clear all input fields
            document.getElementById('addMedicineName').value = '';
            document.getElementById('addMedicineDosage').value = '';
            document.getElementById('addMedicineAvailable').value = '0';
            
            // Reset status preview
            const statusPreview = document.getElementById('addMedicineStatusPreview');
            if (statusPreview) {
                statusPreview.innerHTML = '<span class="inline-flex items-center gap-1 px-3 py-1 bg-slate-100 text-slate-700 rounded-full font-medium text-xs"><i class="fas fa-circle text-xs"></i> Out of Stock</span>';
            }
            
            // Enable submit button
            const submitBtn = document.getElementById('addMedicineSubmitBtn');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-plus-circle"></i> Add Medicine';
            }
            
            // Show modal with animation
            modal.style.display = 'flex';
            
            // Focus on first input
            setTimeout(() => {
                document.getElementById('addMedicineName').focus();
            }, 100);
            
            console.log('Add Medicine modal opened');
        }

        function closeAddMedicineModal() {
            const modal = document.getElementById('addMedicineModal');
            if (modal) {
                modal.style.display = 'none';
                console.log('Add Medicine modal closed');
            }
        }

        // ====================================================================
        // PATIENT SELECTION MODAL FUNCTIONS
        // ====================================================================
        // ====================================================================
        // PATIENT SELECTION MODAL - REMOVED (Using Auto-Redirect with First Match)
        // ====================================================================

        /**
         * Handle Add Medicine Form Submission
         * Validates input and sends to backend via AJAX
         */
        function handleAddMedicineSubmit(event) {
            event.preventDefault();
            
            // Get form values
            const medicineName = document.getElementById('addMedicineName').value.trim();
            const dosage = document.getElementById('addMedicineDosage').value.trim();
            const available = document.getElementById('addMedicineAvailable').value.trim();
            
            // Client-side validation
            if (!medicineName) {
                alert('❌ Error: Medicine name is required');
                document.getElementById('addMedicineName').focus();
                return;
            }
            
            if (available === '' || parseInt(available, 10) < 0) {
                alert('❌ Error: Please enter a valid available quantity');
                document.getElementById('addMedicineAvailable').focus();
                return;
            }
            
            // Show loading state
            const submitBtn = document.getElementById('addMedicineSubmitBtn');
            const originalHTML = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            
            console.log('📋 Submitting Add Medicine form:', {
                name: medicineName,
                dosage: dosage,
                available: available
            });
            
            // Prepare form data for backend
            const formData = new FormData();
            formData.append('action', 'add_medicine');
            formData.append('medicine_name', medicineName);
            formData.append('dosage', dosage);
            formData.append('available', String(parseInt(available, 10)));

            if (!navigator.onLine) {
                window.bhwOfflineQueue.queueBhwAction('add_medicine', Object.fromEntries(formData.entries()), 'dashboard_bhw.php');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHTML;
                closeAddMedicineModal();
                alert('📶 Medicine addition saved locally. It will sync when the connection is restored.');
                return;
            }
            
            // Submit via AJAX to current page (dashboard_bhw.php)
            fetch(window.location.href, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            })
            .then(async response => {
                console.log('📨 Server response status:', response.status);
                const responseText = await response.text();
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (parseError) {
                    throw new Error(`Server returned HTTP ${response.status}: ${responseText.slice(0, 500)}`);
                }
                if (!response.ok) {
                    throw new Error(data.message || data.error || `Server returned HTTP ${response.status}`);
                }
                return data;
            })
            .then(data => {
                console.log('✅ Server response:', data);
                
                // Restore button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHTML;
                
                if (data.success) {
                    // Show success message
                    // Close modal
                    closeAddMedicineModal();
                    window.showCenteredSuccess({
                        title: 'Medicine Added',
                        message: `Medicine Name: ${medicineName}\nAvailable: ${available}`,
                        onClose: () => location.reload()
                    });
                } else {
                    // Show error message
                    const errorMsg = data.message || data.error || 'Unknown error occurred';
                    alert('❌ Error adding medicine:\n\n' + errorMsg);
                    console.error('Server error:', data.error || data.message, data);
                }
            })
            .catch(error => {
                console.error('🔴 Network/Fetch error:', error.message, error);
                
                // Restore button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHTML;
                
                alert('❌ Error adding medicine:\n\n' + (error.message || 'Unknown request error') + '\n\nPlease check the browser console and server log for details.');
            });
        }

        /**
         * Edit Medicine - Extract data from table row and populate modal
         * Enhanced version with better data extraction and validation
         */
        function editMedicine(button) {
            // Get the table row
            const row = button.closest('tr');
            if (!row) {
                console.error('Could not find table row');
                alert('Error: Could not identify medicine row');
                return;
            }
            
            // Extract data from table cells
            // Table columns: Medicine Name | Dosage | Available | Dispensed | Status | Actions
            const cells = row.cells;
            
            const medicineName = cells[0] ? cells[0].textContent.trim() : '';
            const dosage = cells[1] ? cells[1].textContent.trim() : '';
            const available = cells[2] ? cells[2].textContent.trim() : '0';
            const dispensed = cells[3] ? cells[3].textContent.trim() : '0';
            const medicineId = row.getAttribute('data-medicine-id');
            
            console.log('📋 Editing medicine:', {
                id: medicineId,
                name: medicineName,
                dosage: dosage,
                available: available,
                dispensed: dispensed
            });
            
            // Get modal and form
            const modal = document.getElementById('editMedicineModal');
            const form = document.getElementById('editMedicineForm');
            
            if (!modal || !form) {
                console.error('Edit Medicine modal or form not found');
                alert('Error: Modal not found. Please refresh the page.');
                return;
            }
            
            // Populate form fields with extracted data
            document.getElementById('editMedicineId').value = medicineId || '';
            document.getElementById('editMedicineName').value = medicineName || '';
            document.getElementById('editMedicineDosage').value = dosage || '';
            document.getElementById('editMedicineAvailable').value = available || '0';
            const dispensedInput = document.getElementById('editMedicineDispensed');
            dispensedInput.value = dispensed || '0';
            dispensedInput.dataset.lastDispensed = dispensed || '0';
            
            // Update status preview
            updateEditMedicineStatus();
            
            // Enable submit button
            const submitBtn = document.getElementById('editMedicineSubmitBtn');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Update Medicine';
            }
            
            // Show modal
            modal.style.display = 'flex';
            
            // Focus on first editable field
            setTimeout(() => {
                document.getElementById('editMedicineName').focus();
            }, 100);
            
            console.log('✏️ Edit modal opened');
        }

        function closeEditMedicineModal() {
            const modal = document.getElementById('editMedicineModal');
            if (modal) {
                modal.style.display = 'none';
                console.log('Edit Medicine modal closed');
            }
        }

        // Keep the two inventory quantities in sync when a staff member
        // corrects the dispensed count from the edit modal.
        function syncAvailableFromDispensed() {
            const availableInput = document.getElementById('editMedicineAvailable');
            const dispensedInput = document.getElementById('editMedicineDispensed');
            if (!availableInput || !dispensedInput) return;

            const previousDispensed = parseInt(dispensedInput.dataset.lastDispensed || '0', 10);
            const nextDispensed = Math.max(0, parseInt(dispensedInput.value || '0', 10));
            const currentAvailable = Math.max(0, parseInt(availableInput.value || '0', 10));
            const difference = nextDispensed - previousDispensed;

            if (difference > currentAvailable) {
                const allowedDispensed = previousDispensed + currentAvailable;
                dispensedInput.value = String(allowedDispensed);
                dispensedInput.dataset.lastDispensed = String(allowedDispensed);
                availableInput.value = '0';
                updateEditMedicineStatus();
                return;
            }

            availableInput.value = String(currentAvailable - difference);
            dispensedInput.value = String(nextDispensed);
            dispensedInput.dataset.lastDispensed = String(nextDispensed);
            updateEditMedicineStatus();
        }

        /**
         * Handle Edit Medicine Form Submission
         * Validates input and sends to backend via AJAX
         */
        function handleEditMedicineSubmit(event) {
            event.preventDefault();
            
            // Get form values
            const medicineId = document.getElementById('editMedicineId').value;
            const medicineName = document.getElementById('editMedicineName').value.trim();
            const dosage = document.getElementById('editMedicineDosage').value.trim();
            const available = document.getElementById('editMedicineAvailable').value.trim();
            const dispensed = document.getElementById('editMedicineDispensed').value.trim();
            
            // Client-side validation
            if (!medicineId) {
                alert('❌ Error: Medicine ID is missing');
                return;
            }
            
            if (!medicineName) {
                alert('❌ Error: Medicine name is required');
                document.getElementById('editMedicineName').focus();
                return;
            }
            
            if (available === '' || parseInt(available, 10) < 0 || dispensed === '' || parseInt(dispensed, 10) < 0) {
                alert('❌ Error: Available and dispensed quantities must be non-negative whole numbers.');
                document.getElementById('editMedicineAvailable').focus();
                return;
            }
            
            // Show loading state
            const submitBtn = document.getElementById('editMedicineSubmitBtn');
            const originalHTML = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            
            console.log('📋 Submitting Edit Medicine form:', {
                id: medicineId,
                name: medicineName,
                dosage: dosage,
                available: available,
                dispensed: dispensed
            });
            
            // Prepare form data for backend
            const formData = new FormData();
            formData.append('action', 'update_medicine');
            formData.append('medicine_id', medicineId);
            formData.append('medicine_name', medicineName);
            formData.append('dosage', dosage);
            formData.append('available', available);
            formData.append('dispensed', dispensed);

            if (!navigator.onLine) {
                window.bhwOfflineQueue.queueBhwAction('update_medicine', Object.fromEntries(formData.entries()), 'dashboard_bhw.php');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHTML;
                closeEditMedicineModal();
                alert('📶 Medicine edit saved locally. It will sync when the connection is restored.');
                return;
            }
            
            // Submit via AJAX
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('📨 Server response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('✅ Server response:', data);
                
                // Restore button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHTML;
                
                if (data.success) {
                    // Show success message
                    const status = calculateMedicineStatus(available).status;
                    // Close modal
                    closeEditMedicineModal();
                    window.showCenteredSuccess({
                        title: 'Medicine Updated',
                        message: `Medicine: ${medicineName}\nAvailable: ${available}\nDispensed: ${dispensed}\nStatus: ${status}`,
                        onClose: () => location.reload()
                    });
                } else {
                    // Show error message
                    const errorMsg = data.message || 'Unknown error occurred';
                    alert('❌ Error updating medicine:\n\n' + errorMsg);
                    console.error('Server error:', data);
                }
            })
            .catch(error => {
                console.error('🔴 Network/Fetch error:', error);
                
                // Restore button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHTML;
                
                alert('❌ Error updating medicine:\n\n' + error.message + '\n\nPlease check your connection and try again.');
            });
        }

        function handleDeleteMedicine(event) {
            const medicineId = document.getElementById('editMedicineId').value;
            const medicineName = document.getElementById('editMedicineName').value.trim();
            const deleteButton = event?.currentTarget;

            if (!medicineId) {
                alert('❌ Error: Medicine ID is missing. Cannot delete.');
                return;
            }

            window.openCenteredConfirmation({
                title: 'Delete Medicine',
                message: `Delete this medicine from inventory?\n\nMedicine: ${medicineName || 'Unknown'}\nThis action cannot be undone.`,
                confirmLabel: 'Delete Medicine',
                onConfirm: () => {
                    const originalHTML = deleteButton ? deleteButton.innerHTML : 'Deleting...';
                    if (deleteButton) {
                        deleteButton.disabled = true;
                        deleteButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
                    }

                    const formData = new FormData();
                    formData.append('action', 'delete_medicine');
                    formData.append('medicine_id', medicineId);

                    if (!navigator.onLine) {
                        window.bhwOfflineQueue.queueBhwAction('delete_medicine', Object.fromEntries(formData.entries()), 'dashboard_bhw.php');
                        closeEditMedicineModal();
                        alert('Medicine deletion saved locally. It will sync when the connection is restored.');
                        return;
                    }

                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (deleteButton) {
                            deleteButton.disabled = false;
                            deleteButton.innerHTML = originalHTML;
                        }

                        if (data.success) {
                            alert('✅ Medicine deleted successfully.');
                            closeEditMedicineModal();
                            console.log('🔄 Reloading page to remove deleted medicine...');
                            setTimeout(() => location.reload(), 500);
                        } else {
                            alert('❌ Error deleting medicine:\n\n' + (data.message || 'Unknown error occurred'));
                            console.error('Delete medicine failed:', data);
                        }
                    })
                    .catch(error => {
                        if (deleteButton) {
                            deleteButton.disabled = false;
                            deleteButton.innerHTML = originalHTML;
                        }

                        console.error('🔴 Network/Fetch error while deleting medicine:', error);
                        alert('❌ Error deleting medicine:\n\n' + error.message + '\n\nPlease try again.');
                    });
                }
            });
        }

        // ====================================================================
        // APPOINTMENT MANAGEMENT FUNCTIONS
        // ====================================================================
        function changeAppointmentStatus(button, status, appointmentId, confirmed = false) {
            // Prevent multiple submissions
            if (button.disabled) {
                return;
            }

            const confirmationDetails = {
                rendered: ['Start Consultation', 'Open this resident’s profile and record the consultation for this appointment. The appointment will be completed after the consultation is saved.'],
                'no-show': ['Mark Appointment as No Show', 'Mark this appointment as "No Show"?'],
                approved: ['Approve Appointment', 'Approve this appointment for today?']
            }[status];
            if (!confirmed && confirmationDetails) {
                window.openCenteredConfirmation({
                    title: confirmationDetails[0],
                    message: confirmationDetails[1],
                    confirmLabel: status === 'approved' ? 'Approve' : (status === 'rendered' ? 'Open Profile' : 'Confirm'),
                    confirmClass: status === 'approved' ? 'bg-emerald-600 hover:bg-emerald-700' : undefined,
                    onConfirm: () => changeAppointmentStatus(button, status, appointmentId, true)
                });
                return;
            }

            if (status === 'rendered') {
                if (!navigator.onLine) {
                    alert('Connect to the internet to start and save this appointment consultation.');
                    return;
                }

                const residentId = button.getAttribute('data-resident-id');
                if (!residentId) {
                    alert('This appointment is missing its patient ID. Refresh the page and try again.');
                    return;
                }

                window.location.href = '?module=patient-profile&patient_id=' + encodeURIComponent(residentId)
                    + '&appointment_id=' + encodeURIComponent(appointmentId);
                return;
            }

            if (!navigator.onLine) {
                const queued = window.bhwOfflineQueue && window.bhwOfflineQueue.queueBhwAction ? window.bhwOfflineQueue.queueBhwAction('update_appointment_status', {
                    action: 'update_appointment_status',
                    appointment_id: String(appointmentId),
                    resident_id: button.getAttribute('data-resident-id') || '',
                    status: status === 'rendered' ? 'completed' : status
                }) : null;
                alert(' You are offline. This appointment update has been queued and will sync when the connection is restored.');
                void queued;
                return;
            }
            
            // ================================================================
            // HANDLE NO-SHOW STATUS - Patient missed appointment
            // ================================================================
            if (status === 'no-show') {
                if (confirmed) {
                    button.disabled = true;
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    
                    // AJAX call to update appointment status to 'no_show' in database
                    fetch('', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'action=update_appointment_status&appointment_id=' + appointmentId + '&status=no_show'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.showCenteredSuccess({
                                title: 'Appointment Marked No Show',
                                message: 'The appointment will move to the Appointment History tab.',
                                onClose: () => location.reload()
                            });
                        } else {
                            alert('Error: ' + (data.message || 'Failed to update appointment'));
                            button.disabled = false;
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert('Error updating appointment');
                        button.disabled = false;
                    });
                }
            }
            // ================================================================
            // HANDLE APPROVED STATUS - BHW approves appointment for today
            // ================================================================
            else if (status === 'approved') {
                if (confirmed) {
                    button.disabled = true;
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Approving...';
                    
                    // AJAX call to update appointment status to 'approved' in database
                    fetch('', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'action=update_appointment_status&appointment_id=' + appointmentId + '&status=approved'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.showCenteredSuccess({
                                title: 'Appointment Approved',
                                message: "The appointment is now confirmed for today's queue.",
                                onClose: () => location.reload()
                            });
                        } else {
                            alert('Error: ' + (data.message || 'Failed to update appointment'));
                            button.disabled = false;
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert('Error updating appointment');
                        button.disabled = false;
                    });
                }
            }
            // ================================================================
            // If called with other status, use generic update
            // ================================================================
            else {
                console.log('Appointment status update called with status:', status);
            }
        }

        // ====================================================================
        // APPOINTMENT APPROVAL HANDLER
        // ====================================================================
        // When BHW clicks Approve:
        // 1. Updates appointment status to 'Approved'
        // 2. Creates medical_history record for resident
        // 3. Refreshes UI to show success
        // ====================================================================
        function handleAppointmentApproval(button, appointmentId, newStatus) {
            console.log('Approving appointment ID:', appointmentId);
            
            if (!appointmentId || appointmentId <= 0) {
                alert('❌ Error: Invalid appointment ID');
                return;
            }
            
            // Show loading state
            button.disabled = true;
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Approving...';
            
            // Get optional diagnosis and treatment if provided in forms
            const diagnosis = document.getElementById('appointmentDiagnosis')?.value || '';
            const treatmentPlan = document.getElementById('appointmentTreatment')?.value || '';
            
            // Prepare form data
            const formData = new FormData();
            formData.append('appointment_id', appointmentId);
            if (diagnosis) formData.append('diagnosis', diagnosis);
            if (treatmentPlan) formData.append('treatment_plan', treatmentPlan);
            
            console.log('Sending approval request:', {
                appointment_id: appointmentId,
                diagnosis: diagnosis,
                treatment_plan: treatmentPlan
            });
            
            // Make AJAX request to backend handler
            fetch('approve_appointment_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Server response:', data);
                
                if (data.success) {
                    // ============================================================
                    // SUCCESS - Appointment approved & medical history created
                    // ============================================================
                    console.log('Approval successful:', data.data);
                    
                    // Show success message
                    // Update button appearance
                    button.innerHTML = '<i class="fas fa-check-circle"></i> Approved';
                    button.style.backgroundColor = '#10b981';
                    button.style.opacity = '0.7';
                    button.disabled = true;
                    
                    // Highlight the row to show it was processed
                    const row = button.closest('tr');
                    if (row) {
                        row.style.backgroundColor = '#f0fdf4';
                    }
                    
                    // Refresh patient dropdown with newly approved patient
                    refreshPatientDropdown();
                    
                    window.showCenteredSuccess({
                        title: 'Appointment Approved',
                        message: `${data.message}\n\nMedical history record created\nResident ID: ${data.data.resident_id}\nVisit Date: ${data.data.visit_date}\nVisit Type: ${data.data.visit_type}\n\nThe patient is now available in the consultation dropdown.`
                    });
                    
                } else {
                    // ============================================================
                    // ERROR - Approval failed
                    // ============================================================
                    console.error('Approval failed:', data.message);
                    alert(`❌ ${data.message}`);
                    
                    // Restore button
                    button.disabled = false;
                    button.innerHTML = originalHTML;
                }
            })
            .catch(error => {
                // ============================================================
                // NETWORK ERROR
                // ============================================================
                console.error('Network error:', error);
                alert(`❌ Network error: ${error.message}`);
                
                // Restore button
                button.disabled = false;
                button.innerHTML = originalHTML;
            });
        }

        // ====================================================================
        // SMS COMMUNICATION FUNCTIONS
        // ====================================================================
        function updateCharCount() {
            const smsContent = document.getElementById('smsContent').value;
            document.getElementById('charCount').textContent = smsContent.length;
            document.getElementById('smsPreview').textContent = smsContent || 'Your message will appear here...';
        }

        function insertTemplate(template) {
            document.getElementById('smsContent').value = template;
            updateCharCount();
        }

        function toggleCustomNumbers() {
            const recipientType = document.querySelector('input[name="recipient_type"]:checked').value;
            const customNumbersSection = document.getElementById('customNumbersSection');
            
            if (recipientType === 'custom') {
                customNumbersSection.classList.remove('hidden');
            } else {
                customNumbersSection.classList.add('hidden');
            }
        }

        function handleSMSSubmit(event) {
            event.preventDefault();

            if (!navigator.onLine) {
                const recipientType = document.querySelector('input[name="recipient_type"]:checked')?.value || 'all';
                const messageText = document.getElementById('smsContent').value.trim();
                const customNumbers = document.getElementById('customNumbers')?.value || '';

                if (!messageText) {
                    alert('Please enter a message');
                    return;
                }

                if (window.bhwOfflineQueue && window.bhwOfflineQueue.queueBhwAction) {
                    window.bhwOfflineQueue.queueBhwAction('send_sms', {
                        action: 'send_sms',
                        recipient_type: recipientType,
                        message: messageText,
                        custom_numbers: customNumbers
                    });
                }

                document.getElementById('smsForm').reset();
                document.getElementById('charCount').textContent = '0';
                document.getElementById('smsPreview').textContent = 'Your message will appear here...';
                alert('📶 SMS queued offline. It will be sent automatically once the connection is restored.');
                return;
            }
            
            // Get form data
            const recipientType = document.querySelector('input[name="recipient_type"]:checked').value;
            const messageText = document.getElementById('smsContent').value.trim();
            const customNumbers = document.getElementById('customNumbers')?.value || '';
            
            // Validate message
            if (!messageText) {
                alert('Please enter a message');
                return;
            }
            
            if (messageText.length > 160) {
                alert('Message exceeds 160 character limit');
                return;
            }
            
            // Show loading state
            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Sending...';
            
            // Prepare form data
            const formData = new FormData();
            formData.append('action', 'send_sms');
            formData.append('recipient_type', recipientType);
            formData.append('message', messageText);
            if (customNumbers) {
                formData.append('custom_numbers', customNumbers);
            }
            
            console.log('Sending SMS:', { recipientType, messageLength: messageText.length, customNumbers: !!customNumbers });
            
            // Send to backend handler
            fetch('send_sms_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                const contentType = response.headers.get('content-type');
                console.log('Content-Type:', contentType);
                return response.text().then(text => {
                    console.log('Raw response text:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Failed to parse JSON:', e);
                        throw new Error('Server returned invalid JSON: ' + text.substring(0, 100));
                    }
                });
            })
            .then(data => {
                // Restore button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                
                console.log('Parsed data:', data);
                
                if (data.success) {
                    alert(`✅ SMS sent successfully!\n\nSent: ${data.sent}\nFailed: ${data.failed}\nTotal Recipients: ${data.total}`);
                    
                    // Clear form
                    document.getElementById('smsForm').reset();
                    document.getElementById('charCount').textContent = '0';
                    document.getElementById('smsPreview').textContent = 'Your message will appear here...';
                } else {
                    alert(`❌ SMS sending failed:\n${data.message}`);
                }
            })
            .catch(error => {
                // Restore button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                
                console.error('SMS Error:', error);
                alert('❌ An error occurred while sending SMS.\n\n' + error.message + '\n\nCheck browser console for details.');
            });
        }

        // ====================================================================
        // DATA SAVE & SYNC HANDLER
        // ====================================================================
        function triggerDataSave() {
            const syncStatus = document.getElementById('syncStatus');
            if (!navigator.onLine) {
                if (window.bhwOfflineQueue && window.bhwOfflineQueue.queueBhwAction) {
                    window.bhwOfflineQueue.queueBhwAction('save_data', {
                        action: 'save_data',
                        source: 'bhw_dashboard_manual_save',
                        saved_at: new Date().toISOString()
                    });
                }
                syncStatus.innerHTML = '<span class="w-3 h-3 rounded-full bg-amber-500"></span><span class="text-sm text-amber-600 font-semibold">Offline queue active</span>';
                return;
            }
            syncStatus.innerHTML = '<span class="w-3 h-3 rounded-full bg-blue-500 animate-spin"></span><span class="text-sm text-blue-600 font-semibold">Saving data...</span>';
            
            // Simulate save operation
            setTimeout(() => {
                syncStatus.innerHTML = '<span class="w-3 h-3 rounded-full bg-emerald-500"></span><span class="text-sm text-emerald-600 font-semibold">All data saved successfully</span>';
            }, 2000);

            // TODO: Implement actual backend data save
            console.log('Data save triggered. In production, this will:');
            console.log('1. POST consultation records to backend');
            console.log('2. POST medicine dispensing records to backend');
            console.log('3. POST appointment status updates to backend');
            console.log('4. POST SMS records to backend');
            console.log('5. Sync with cloud or save locally if offline');
        }

        // ====================================================================
        // INTERACTIVE STAT CARDS NAVIGATION
        // ====================================================================
        /**
         * Navigate to module when stat card is clicked
         * Updates URL with module parameter to show corresponding tab
         */
        function navigateToModule(moduleName) {
            // Build new URL with module parameter
            const baseUrl = window.location.href.split('?')[0];
            const newUrl = baseUrl + '?module=' + encodeURIComponent(moduleName);
            
            // Navigate to new URL (page will reload showing the selected module)
            window.location.href = newUrl;
        }

        const deletedSurveillanceMetricIds = new Set();
        const savedSurveillanceMetricsFromDb = normalizeSurveillanceMetrics(<?php echo json_encode($surveillance_metrics); ?>);
        let surveillanceData = savedSurveillanceMetricsFromDb;

        function normalizeSurveillanceMetrics(rawMetrics) {
            const values = Array.isArray(rawMetrics) ? rawMetrics : (rawMetrics && typeof rawMetrics === 'object' && ('disease_name' in rawMetrics) ? [rawMetrics] : []);
            return values.filter(record => {
                if (record.id && deletedSurveillanceMetricIds.has(String(record.id))) return false;
                const diseaseName = String(record.disease_name ?? record.diseaseName ?? '').trim();
                const caseCount = Number(record.case_count ?? record.caseCount ?? 0);
                return diseaseName !== '' && diseaseName.toLowerCase() !== 'record entry' && caseCount > 0;
            }).map(record => {
                const caseCount = Number(record.case_count ?? record.caseCount ?? 0);
                return {
                id: record.id || null,
                reportingPeriod: record.reporting_period || record.reportingPeriod || 'Current 4 Weeks',
                diseaseName: String(record.disease_name ?? record.diseaseName ?? '').trim(),
                caseCount,
                trendStatus: getSeverityLabelByCount(caseCount).label,
                metaNotes: record.meta_notes || record.metaNotes || 'No notes recorded.',
                createdAt: record.created_at || record.createdAt || ''
                };
            });
        }

        function getSavedSurveillanceMetrics() {
            return surveillanceData;
        }

        function getSeverityLabelByCount(count) {
            const value = Number(count || 0);
            if (value <= 10) return { label: 'Baseline', color: 'text-emerald-600' };
            if (value <= 20) return { label: 'Watch', color: 'text-amber-600' };
            if (value <= 30) return { label: 'Alert', color: 'text-orange-600' };
            return { label: 'Emergency', color: 'text-red-600' };
        }

        function safeSurveillanceText(value, fallback) {
            if (value === null || value === undefined || String(value).trim() === '') {
                return fallback;
            }
            return String(value).trim();
        }

        function getDiseaseKey(name) {
            const normalized = safeSurveillanceText(name, 'dengue').toLowerCase();
            if (normalized.includes('rabies')) return 'rabies';
            if (normalized.includes('respiratory')) return 'respiratory';
            if (normalized.includes('nutrition')) return 'nutrition';
            return 'dengue';
        }

        function renderSurveillancePreview(metrics) {
            const records = (Array.isArray(metrics) ? metrics : []).slice().sort((a, b) => b.caseCount - a.caseCount);
            const latest = records[0] || { diseaseName: 'None', caseCount: 0, trendStatus: 'Baseline', metaNotes: 'No notes recorded.', reportingPeriod: 'Current 4 Weeks' };
            const escapeHtml = value => String(value).replace(/[&<>'"]/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[character]));
            const reportingPeriod = document.getElementById('survReportingPeriod');
            const container = document.getElementById('surveillanceRecordsList');
            if (!reportingPeriod || !container) return;
            reportingPeriod.textContent = `Reporting Period: ${latest.reportingPeriod}`;
            container.innerHTML = '';
            container.innerHTML = records.length ? records.map((record, index) => `<article id="disease-card-${record.id}" data-id="${record.id}" data-disease-name="${escapeHtml(record.diseaseName)}" data-case-count="${record.caseCount}" class="surveillance-preview-card rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">${index === 0 ? 'Latest Entry' : 'Recorded Entry'}</p><p ${index === 0 ? 'id="previewDiseaseName"' : ''} class="mt-3 text-xl font-bold text-slate-900 dark:text-white">${escapeHtml(record.diseaseName)}</p><p ${index === 0 ? 'id="previewDiseaseMeta"' : ''} class="mt-1 text-sm text-slate-600 dark:text-slate-400">${escapeHtml(record.metaNotes)}</p><p id="${index === 0 ? 'previewQuantityCount' : 'disease-count-' + record.id}" class="mt-4 text-3xl font-bold text-slate-900 dark:text-white">${record.caseCount}</p><p ${index === 0 ? 'id="previewTrend"' : ''} class="mt-1 text-sm font-semibold text-[#3D4A1E] dark:text-lime-300">${escapeHtml(record.trendStatus)}</p><div class="mt-4 flex gap-2"><button type="button" data-edit-quantity data-metric-id="${record.id}" data-disease-name="${escapeHtml(record.diseaseName)}" data-current-qty="${record.caseCount}" class="rounded-lg border border-[#3D4A1E] px-3 py-2 text-xs font-semibold text-[#3D4A1E] transition hover:bg-[#3D4A1E] hover:text-white dark:text-lime-300">Edit Qty</button><button type="button" data-delete-disease data-id="${record.id}" data-disease-id="${record.id}" data-disease-name="${escapeHtml(record.diseaseName)}" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-600 hover:text-white dark:border-red-800 dark:text-red-300" aria-label="Delete ${escapeHtml(record.diseaseName)}"><i class="fas fa-trash-alt"></i></button></div></article>`).join('') : '<p class="col-span-full rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">No disease entries recorded yet.</p>';
            document.querySelectorAll('[id^="disease-card-"]').forEach(card => {
                const diseaseMeta = card.querySelector('#previewDiseaseMeta') || card.querySelectorAll('p')[2];
                if (diseaseMeta) diseaseMeta.remove();
            });
            document.querySelectorAll('[id^="disease-card-"]').forEach(card => {
                const countDisplay = card.querySelector('#previewQuantityCount, [id^="disease-count-"]');
                if (!countDisplay || card.querySelector('.disease-quantity-input')) return;
                countDisplay.classList.add('case-count-display');
                const input = document.createElement('input');
                input.type = 'number';
                input.min = '0';
                input.value = card.dataset.caseCount || '0';
                input.className = 'disease-quantity-input w-24 rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white';
                input.setAttribute('aria-label', `${card.dataset.diseaseName || 'Disease'} case count`);
                countDisplay.parentElement.insertBefore(input, countDisplay);
            });
            document.querySelectorAll('[data-edit-quantity]').forEach(button => button.addEventListener('click', () => editDiseaseQuantity(button.dataset.metricId, button.dataset.diseaseName, button.dataset.currentQty)));
            document.querySelectorAll('[data-delete-disease]').forEach(button => button.addEventListener('click', event => {
                const recordId = event.currentTarget.getAttribute('data-id') || event.currentTarget.dataset.id || event.currentTarget.getAttribute('data-disease-id') || event.currentTarget.dataset.diseaseId;
                if (!recordId) console.log('Missing ID for element:', event.currentTarget);
                deleteDiseaseMetric(recordId, event.currentTarget.getAttribute('data-disease-name') || event.currentTarget.dataset.diseaseName, event.currentTarget);
            }));
            updateHighestIncidenceDisplay();
        }

        function updateHighestIncidence() {
            const records = Array.from(document.querySelectorAll('[id^="disease-card-"], .summary-card')).map(card => ({
                diseaseName: card.dataset.diseaseName || '',
                caseCount: card.querySelector('.disease-quantity-input')
                    ? Number.parseInt(card.querySelector('.disease-quantity-input').value, 10) || 0
                    : Number.parseInt(card.getAttribute('data-quantity') || card.dataset.caseCount || 0, 10) || 0
            }));
            const highest = records.filter(record => record.diseaseName && record.diseaseName.toLowerCase() !== 'record entry' && record.caseCount > 0).sort((a, b) => b.caseCount - a.caseCount)[0] || { diseaseName: 'None', caseCount: 0 };
            document.getElementById('highest-incidence-name').textContent = highest.diseaseName;
            document.getElementById('highest-incidence-count').textContent = highest.caseCount;
        }

        function updateHighestIncidenceDisplay() {
            updateHighestIncidence();
        }

        window.saveSurveillanceMetric = function(id, newQuantity) {
            if (!navigator.onLine) {
                window.bhwOfflineQueue && window.bhwOfflineQueue.queueBhwAction && window.bhwOfflineQueue.queueBhwAction('save_surveillance_metrics', {
                    action: 'save_surveillance_metrics',
                    metric_id: String(id || ''),
                    disease_name: '',
                    case_count: String(Math.max(0, Number(newQuantity) || 0))
                });
                return Promise.resolve({ success: true, queued: true, message: 'Queued offline' });
            }
            const formData = new FormData();
            formData.append('action', 'save_surveillance_metrics');
            formData.append('metric_id', id);
            formData.append('case_count', String(newQuantity));
            return fetch('dashboard_bhw.php', { method: 'POST', body: formData }).then(response => response.json());
        };

        document.addEventListener('input', function(event) {
            if (!event.target.matches('.disease-quantity-input')) return;
            const input = event.target;
            const newQuantity = Math.max(0, Number.parseInt(input.value, 10) || 0);
            const parentCard = input.closest('[id^="disease-card-"]') || input.closest('.summary-card');
            if (!parentCard) return;
            parentCard.setAttribute('data-quantity', newQuantity);
            parentCard.setAttribute('data-case-count', newQuantity);
            const countDisplay = parentCard.querySelector('.case-count-display');
            if (countDisplay) countDisplay.textContent = String(newQuantity);
            const recordId = parentCard.getAttribute('data-id');
            if (!recordId || recordId === 'undefined') return;
            saveSurveillanceMetric(recordId, newQuantity).then(data => {
                if (!data.success) throw new Error(data.message || 'Unable to save quantity.');
                surveillanceData = normalizeSurveillanceMetrics(data.metrics || []);
                if (newQuantity <= 0) parentCard.remove();
                updateHighestIncidenceDisplay();
            }).catch(error => console.error('Disease quantity save failed:', error));
        });

        window.deleteDiseaseMetric = function(diseaseId, diseaseName = '', button = null, confirmed = false) {
            const recordId = diseaseId && diseaseId !== 'undefined' ? diseaseId : '';
            if (!recordId && !diseaseName) {
                console.error('Cannot delete: Missing database ID', button);
                return;
            }
            if (!confirmed) {
                window.openCenteredConfirmation({
                    title: 'Delete Disease Entry',
                    message: 'Delete this disease entry?',
                    confirmLabel: 'Delete Entry',
                    onConfirm: () => window.deleteDiseaseMetric(diseaseId, diseaseName, button, true)
                });
                return;
            }
            const formData = new FormData();
            formData.append('action', 'delete_metric');
            if (recordId) formData.append('id', recordId);
            if (diseaseName) formData.append('disease_name', diseaseName);
            fetch('dashboard_bhw.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) throw new Error(data.message || 'Unable to delete disease entry.');
                    const deletedId = String(data.deleted_id || recordId);
                    deletedSurveillanceMetricIds.add(deletedId);
                    surveillanceData = surveillanceData.filter(record => String(record.id) !== deletedId && (!diseaseName || record.diseaseName !== diseaseName));
                    localStorage.removeItem('scancareSurveillanceMetrics');
                    const card = button?.closest('[id^="disease-card-"]') || document.getElementById(`disease-card-${deletedId}`);
                    if (card) card.remove();
                    updateHighestIncidenceDisplay();
                })
                .catch(error => window.alert(error.message));
        };

        window.editDiseaseQuantity = function(id, diseaseName, currentQty) {
            const nextQuantity = window.prompt(`Update case count for ${diseaseName || 'this disease'}:`, currentQty);
            if (nextQuantity === null) return;
            const trimmedQuantity = String(nextQuantity).trim();
            const parsedQuantity = trimmedQuantity === '' ? 0 : Number.parseInt(trimmedQuantity, 10);
            if (!Number.isInteger(parsedQuantity)) {
                window.alert('Please enter a valid non-negative whole number.');
                return;
            }
            const caseCount = Math.max(0, parsedQuantity);
            const formData = new FormData();
            formData.append('action', 'save_surveillance_metrics');
            formData.append('metric_id', id);
            formData.append('disease_name', diseaseName || '');
            formData.append('case_count', String(caseCount));
            fetch('dashboard_bhw.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) throw new Error(data.message || 'Unable to update quantity.');
                    const updated = normalizeSurveillanceMetrics(data.metrics || []);
                    if (caseCount <= 0) {
                        deletedSurveillanceMetricIds.add(String(id));
                        surveillanceData = surveillanceData.filter(record => String(record.id) !== String(id));
                        localStorage.removeItem('scancareSurveillanceMetrics');
                    } else {
                        surveillanceData = surveillanceData.map(record => String(record.id) === String(id) ? { ...record, caseCount } : record);
                    }
                    surveillanceData = updated.filter(record => !deletedSurveillanceMetricIds.has(String(record.id)));
                    const card = document.getElementById(`disease-card-${id}`);
                    if (caseCount <= 0) {
                        if (card) card.remove();
                    } else if (card) {
                        const countElement = document.getElementById(`disease-count-${id}`) || card.querySelector('#previewQuantityCount');
                        const quantityInput = card.querySelector('.disease-quantity-input');
                        const editButton = card.querySelector('[data-edit-quantity]');
                        if (countElement) countElement.textContent = String(caseCount);
                        card.dataset.caseCount = String(caseCount);
                        card.dataset.quantity = String(caseCount);
                        if (quantityInput) quantityInput.value = String(caseCount);
                        if (editButton) editButton.dataset.currentQty = String(caseCount);
                    }
                    updateHighestIncidenceDisplay();
                })
                .catch(error => window.alert(error.message));
        };

        function hydrateSurveillanceForm(metrics) {
            const latest = metrics[0] || {};
            const values = {
                reportingPeriod: latest.reportingPeriod || '',
                input_disease_name: latest.diseaseName || '',
                input_quantity_count: latest.caseCount || 0,
                input_disease_meta: latest.metaNotes || ''
            };

            Object.entries(values).forEach(([key, value]) => {
                const input = document.getElementById(key) || document.querySelector(`[name="${key}"]`);
                if (input) input.value = value;
            });
        }

        function initSurveillanceModule() {
            const surveillanceForm = document.getElementById('surveillanceMetricsForm');
            const surveillancePreview = document.getElementById('surveillanceRecordsList');
            if (!surveillanceForm || !surveillancePreview) {
                return;
            }

            const metrics = getSavedSurveillanceMetrics();
            renderSurveillancePreview(metrics);
            hydrateSurveillanceForm(metrics);

            const saveMessage = document.getElementById('survSaveMessage');

            window.handleSurveillanceSubmit = function(event) {
                event.preventDefault();
                const submitButton = document.getElementById('saveSurveillanceBtn');
                if (!surveillanceForm || !submitButton || submitButton.dataset.saving === 'true') return;

                    if (!navigator.onLine) {
                        const queuedPayload = {
                            action: 'save_surveillance_metrics',
                            reporting_period: document.getElementById('input_reporting_period')?.value || 'Current 4 Weeks',
                            disease_name: document.getElementById('input_disease_name')?.value || '',
                            case_count: document.getElementById('input_quantity_count')?.value || '0',
                            meta_notes: document.getElementById('input_disease_meta')?.value || ''
                        };

                        window.bhwOfflineQueue && window.bhwOfflineQueue.queueBhwAction && window.bhwOfflineQueue.queueBhwAction('save_surveillance_metrics', queuedPayload);
                        if (saveMessage) {
                            saveMessage.classList.remove('hidden');
                            saveMessage.textContent = 'Saved locally for sync when online';
                            setTimeout(() => saveMessage.classList.add('hidden'), 1800);
                        }
                        alert('📶 Surveillance entry saved locally. It will sync automatically when you reconnect.');
                        return;
                    }

                const formData = new FormData(surveillanceForm);
                formData.set('action', 'save_surveillance_metrics');
                formData.set('case_count', document.getElementById('input_quantity_count').value || '0');
                formData.set('meta_notes', document.getElementById('input_disease_meta').value);

                const originalText = submitButton.innerHTML;
                submitButton.dataset.saving = 'true';
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';

                fetch('dashboard_bhw.php', { method: 'POST', body: formData })
                    .then(response => {
                        const rawResponsePromise = response.text();
                        return rawResponsePromise.then(rawResponse => {
                            console.debug('Surveillance save raw response:', rawResponse);
                            if (!response.ok) {
                                throw new Error(`Surveillance save failed with HTTP ${response.status}: ${rawResponse.slice(0, 300)}`);
                            }
                            try {
                                return JSON.parse(rawResponse);
                            } catch (parseError) {
                                throw new Error(`Invalid surveillance JSON response: ${rawResponse.slice(0, 300)}`);
                            }
                        });
                    })
                    .then(data => {
                        if (!data.success) throw new Error(data.message || 'Unable to save metrics.');
                        const responseId = data.id || data.created_id;
                        const submittedName = String(formData.get('disease_name') || '').trim();
                        const submittedCount = Number(formData.get('case_count') || 0);
                        const updated = (Array.isArray(data.metrics) ? normalizeSurveillanceMetrics(data.metrics) : []).map(record => {
                            if (responseId && record.diseaseName === submittedName && record.caseCount === submittedCount) {
                                return { ...record, id: Number(responseId) };
                            }
                            return record;
                        });
                        surveillanceData = updated;
                        renderSurveillancePreview(updated);
                        if (saveMessage) {
                            saveMessage.classList.remove('hidden');
                            setTimeout(() => saveMessage.classList.add('hidden'), 1600);
                        }
                        try {
                            if (typeof BroadcastChannel !== 'undefined') {
                                const channel = new BroadcastChannel('scancare-surveillance');
                                channel.postMessage({ type: 'update', metrics: updated });
                                channel.close();
                            }
                        } catch (error) { console.warn('BroadcastChannel failed:', error); }
                    })
                    .catch(error => { console.error('Error saving surveillance metrics:', error); alert(error.message); })
                    .finally(() => {
                        submitButton.dataset.saving = 'false';
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalText;
                    });
            };
        }

        // ====================================================================
        // Publish helper (global) - allows button onclick to work even if init fails
        function publishSurveillanceMetrics(evt) {
            return window.handleSurveillanceSubmit(evt);
        }

        // PAGE INITIALIZATION
        // ====================================================================
        document.addEventListener('DOMContentLoaded', function() {
            console.log('BHW Dashboard initialized');
            console.log('Worker ID:', '<?php echo htmlspecialchars($worker_id); ?>');
            console.log('Worker Name:', '<?php echo htmlspecialchars($worker_name); ?>');
            initSurveillanceModule();
        });
        // ====================================================================
        // APPOINTMENT RESCHEDULING FUNCTIONS
        // ====================================================================

        function openAppointmentScheduleModal(appointmentId, residentId, residentName, mode) {
            const modal = document.getElementById('rescheduleAppointmentModal');
            if (!modal) {
                alert('Appointment scheduling modal is unavailable.');
                return;
            }

            modal.dataset.appointmentId = appointmentId;
            modal.dataset.residentId = residentId;
            modal.dataset.mode = mode;
            document.getElementById('rescheduleResidentName').textContent = residentName || 'Resident';
            const dateInput = document.getElementById('rescheduleAppointmentDate');
            dateInput.value = '';
            const currentDate = new Date();
            dateInput.min = [currentDate.getFullYear(), String(currentDate.getMonth() + 1).padStart(2, '0'), String(currentDate.getDate()).padStart(2, '0')].join('-');
            document.getElementById('rescheduleAppointmentTime').value = '';
            document.getElementById('appointmentScheduleModalTitle').textContent = mode === 'followup'
                ? 'Schedule Follow-up Check-up'
                : 'Reschedule Appointment';
            document.getElementById('appointmentScheduleModalNotice').textContent = mode === 'followup'
                ? 'The selected date and time will update the resident dashboard. No SMS will be sent.'
                : 'The resident will receive an SMS notification after the appointment is updated.';
            const submitButton = document.getElementById('submitRescheduleAppointmentBtn');
            submitButton.className = mode === 'followup'
                ? 'px-4 py-2 rounded-lg bg-emerald-600 font-semibold text-white hover:bg-emerald-700'
                : 'px-4 py-2 rounded-lg bg-violet-600 font-semibold text-white hover:bg-violet-700';
            submitButton.innerHTML = mode === 'followup'
                ? '<i class="fas fa-stethoscope"></i> Confirm Follow-up'
                : '<i class="fas fa-calendar-day"></i> Reschedule Appointment';
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            updateRescheduleTimeAvailability();
        }

        function openRescheduleAppointmentModal(button) {
            openAppointmentScheduleModal(
                button.dataset.appointmentId,
                button.dataset.residentId,
                button.dataset.residentName,
                'reschedule'
            );
        }

        async function updateRescheduleTimeAvailability() {
            const modal = document.getElementById('rescheduleAppointmentModal');
            const dateInput = document.getElementById('rescheduleAppointmentDate');
            const timeSelect = document.getElementById('rescheduleAppointmentTime');
            const appointmentId = Number(modal?.dataset.appointmentId || 0);
            if (!appointmentId || !dateInput?.value || !timeSelect) {
                return;
            }

            const availabilityUrl = new URL(window.location.href);
            availabilityUrl.searchParams.set('action', 'get_reschedule_times');
            availabilityUrl.searchParams.set('appointment_id', String(appointmentId));
            availabilityUrl.searchParams.set('date', dateInput.value);

            try {
                const response = await fetch(availabilityUrl.toString());
                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Unable to load available appointment times.');
                }

                const occupiedTimes = new Set(Array.isArray(data.occupied_times) ? data.occupied_times : []);
                const residentConflictTimes = new Set(Array.isArray(data.resident_conflict_times) ? data.resident_conflict_times : []);
                const normalizeTime = value => {
                    const match = value.match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
                    if (!match) return '';
                    let hour = Number(match[1]);
                    if (match[3].toUpperCase() === 'PM' && hour !== 12) hour += 12;
                    if (match[3].toUpperCase() === 'AM' && hour === 12) hour = 0;
                    return String(hour).padStart(2, '0') + ':' + match[2];
                };
                const slotCounts = data.slot_counts && typeof data.slot_counts === 'object' ? data.slot_counts : {};
                const sameHourTime = data.same_hour_time || '';
                Array.from(timeSelect.options).forEach(option => {
                    if (option.value !== '') {
                        const timeKey = normalizeTime(option.value);
                        const remaining = Number(slotCounts[timeKey] ?? 5);
                        const isCurrentHour = timeKey === sameHourTime;
                        const isResidentConflict = residentConflictTimes.has(timeKey);
                        option.disabled = occupiedTimes.has(timeKey) || isCurrentHour || isResidentConflict;
                        option.text = isCurrentHour
                            ? option.value + ' - Current hour'
                            : isResidentConflict
                            ? option.value + ' - Already booked for this resident'
                            : occupiedTimes.has(timeKey)
                            ? option.value + ' - Not available'
                            : `${option.value} - ${remaining} slot${remaining === 1 ? '' : 's'} left`;
                    }
                });
            } catch (error) {
                console.error('Unable to load reschedule availability:', error);
            }
        }

        function closeRescheduleAppointmentModal() {
            const modal = document.getElementById('rescheduleAppointmentModal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }

        document.getElementById('rescheduleAppointmentDate')?.addEventListener('change', function () {
            const selectedDate = new Date(this.value + 'T00:00:00');
            if (this.value && (selectedDate.getDay() === 0 || selectedDate.getDay() === 6)) {
                this.value = '';
                const timeSelect = document.getElementById('rescheduleAppointmentTime');
                if (timeSelect) timeSelect.value = '';
                alert('Please choose a weekday. Appointments cannot be scheduled on weekends.');
                return;
            }
            const timeSelect = document.getElementById('rescheduleAppointmentTime');
            if (timeSelect) timeSelect.value = '';
            updateRescheduleTimeAvailability();
        });

        async function submitRescheduleAppointment(event) {
            event.preventDefault();
            const modal = document.getElementById('rescheduleAppointmentModal');
            const appointmentId = Number(modal?.dataset.appointmentId || 0);
            const date = document.getElementById('rescheduleAppointmentDate').value;
            const time = document.getElementById('rescheduleAppointmentTime').value;
            const button = document.getElementById('submitRescheduleAppointmentBtn');
            const isFollowup = modal?.dataset.mode === 'followup';
            const action = isFollowup ? 'schedule_followup' : 'reschedule_appointment';

            if (!appointmentId || !date || !time) {
                alert('Please select both a new date and time.');
                return;
            }

            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + (isFollowup ? 'Scheduling...' : 'Rescheduling...');
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=' + action + '&appointment_id=' + appointmentId + '&appointment_date=' + encodeURIComponent(date) + '&appointment_time=' + encodeURIComponent(time)
                });
                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || (isFollowup ? 'Unable to schedule follow-up.' : 'Unable to reschedule appointment.'));
                }

                closeRescheduleAppointmentModal();
                window.showCenteredSuccess({
                    title: isFollowup ? 'Follow-up Scheduled' : 'Appointment Rescheduled',
                    message: isFollowup
                        ? 'The selected appointment date and time are now reflected on the resident dashboard. No SMS was sent.'
                        : 'The resident has been notified by SMS.',
                    onClose: () => isFollowup
                        ? window.location.href = '?module=appointments&sub=followup'
                        : window.location.reload()
                });
            } catch (error) {
                alert((isFollowup ? 'Error scheduling follow-up: ' : 'Error rescheduling appointment: ') + error.message);
                button.disabled = false;
                button.innerHTML = isFollowup
                    ? '<i class="fas fa-stethoscope"></i> Confirm Follow-up'
                    : '<i class="fas fa-calendar-day"></i> Reschedule Appointment';
            }
        }
        
        /**
         * Flag appointment for follow-up check-up
         * Updates status to 'flagged' so it appears in the Follow-up Check-up tab
         */
        function showFollowupScheduleModal(residentId, residentName, appointmentId) {
            openAppointmentScheduleModal(appointmentId, residentId, residentName, 'followup');
        }
        
        /**
         * Update follow-up appointment date
         */
        function updateFollowupDate(appointmentId, newDate) {
            if (!newDate) {
                alert('Please select a valid date');
                return;
            }
            
            console.log(`Updating appointment ${appointmentId} follow-up date to: ${newDate}`);
            
            // AJAX call to update database
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=update_followup_date&appointment_id=' + appointmentId + '&follow_date=' + encodeURIComponent(newDate)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Follow-up date updated successfully');
                } else {
                    alert('Error updating date: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Error saving follow-up date');
            });
        }
        
        /**
         * Update follow-up appointment time
         */
        function updateFollowupTime(appointmentId, newTime) {
            if (!newTime) {
                alert('Please select a valid time');
                return;
            }
            
            console.log(`Updating appointment ${appointmentId} follow-up time to: ${newTime}`);
            
            // AJAX call to update database
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=update_followup_time&appointment_id=' + appointmentId + '&follow_time=' + encodeURIComponent(newTime)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Follow-up time updated successfully');
                } else {
                    alert('Error updating time: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Error saving follow-up time');
            });
        }

        // ====================================================================
        // CLEAR MEDICAL HISTORY FUNCTION
        // ====================================================================
        /**
         * Clear all medical history records for a patient
         * Includes confirmation dialog and visual feedback
         */
        function clearMedicalHistory(patientId, clearBtn, confirmed = false) {
            if (confirmed === false) {
                window.openCenteredConfirmation({
                    title: 'Clear Medical History',
                    message: 'WARNING: This action is permanent. Are you sure you want to clear all medical visit history for this patient? This will delete all consultation records, treatments, and notes.',
                    confirmLabel: 'Continue',
                    onConfirm: () => window.openCenteredConfirmation({
                        title: 'Permanently Clear Medical History',
                        message: 'This action cannot be undone. Permanently clear the medical history?',
                        confirmLabel: 'Clear History',
                        onConfirm: () => clearMedicalHistory(patientId, clearBtn, true)
                    })
                });
                return;
            }
            
            console.log(`🗑️ Clearing medical history for patient ID: ${patientId}`);
            
            // Find and disable the clear button
            clearBtn = clearBtn || document.getElementById(`clearHistoryBtn_${patientId}`) || null;
            if (clearBtn) {
                clearBtn.disabled = true;
                const originalHTML = clearBtn.innerHTML;
                clearBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Clearing...';
                
                // AJAX call to clear medical history
                fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=clear_medical_history&patient_id=' + patientId
                })
                .then(response => {
                    console.log('Clear history response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('✅ Clear history response:', data);
                    
                    if (data.success) {
                        console.log(`Reloading page to refresh medical history display...`);
                        // Reload page to refresh the medical history display
                        window.showCenteredSuccess({
                            title: 'Medical History Cleared',
                            message: data.message,
                            onClose: () => location.reload()
                        });
                    } else {
                        alert('❌ Error clearing medical history:\n\n' + (data.message || 'Unknown error'));
                        clearBtn.disabled = false;
                        clearBtn.innerHTML = originalHTML;
                        console.error('Server error:', data);
                    }
                })
                .catch(err => {
                    console.error('🔴 Network/Fetch error:', err);
                    alert('❌ Error clearing medical history:\n\n' + err.message + '\n\nPlease check your connection and try again.');
                    
                    // Restore button state
                    clearBtn.disabled = false;
                    clearBtn.innerHTML = originalHTML;
                });
            } else {
                console.error('Clear button not found!');
                alert('❌ Error: Could not find clear button. Please refresh the page and try again.');
            }
        }

        // ====================================================================
        // CLEAR FOLLOW-UP CHECK-UPS FUNCTION
        // ====================================================================
        /**
         * Clear all follow-up check-up records for a patient
         * Includes confirmation dialog and visual feedback
         */
        function clearFollowupCheckups(patientId, clearBtn, confirmed = false) {
            if (confirmed === false) {
                window.openCenteredConfirmation({
                    title: 'Clear Follow-up Check-ups',
                    message: 'WARNING: This action is permanent. Are you sure you want to clear all scheduled follow-up check-ups for this patient?',
                    confirmLabel: 'Continue',
                    onConfirm: () => window.openCenteredConfirmation({
                        title: 'Permanently Clear Follow-up Check-ups',
                        message: 'This action cannot be undone. Permanently clear the follow-up check-ups?',
                        confirmLabel: 'Clear Follow-ups',
                        onConfirm: () => clearFollowupCheckups(patientId, clearBtn, true)
                    })
                });
                return;
            }
            
            console.log(`🗑️ Clearing follow-up check-ups for patient ID: ${patientId}`);
            
            // Find and disable the clear button
            if (clearBtn) {
                clearBtn.disabled = true;
                const originalHTML = clearBtn.innerHTML;
                clearBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Clearing...';
                
                // AJAX call to clear follow-up check-ups
                fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=clear_followup_checkups&patient_id=' + patientId
                })
                .then(response => {
                    console.log('Clear followup response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('✅ Clear followup response:', data);
                    
                    if (data.success) {
                        console.log(`Reloading page to refresh follow-up check-ups display...`);
                        // Reload page to refresh the follow-up check-ups display
                        window.showCenteredSuccess({
                            title: 'Follow-up Check-ups Cleared',
                            message: data.message,
                            onClose: () => location.reload()
                        });
                    } else {
                        alert('❌ Error clearing follow-up check-ups:\n\n' + (data.message || 'Unknown error'));
                        clearBtn.disabled = false;
                        clearBtn.innerHTML = originalHTML;
                        console.error('Server error:', data);
                    }
                })
                .catch(err => {
                    console.error('🔴 Network/Fetch error:', err);
                    alert('❌ Error clearing follow-up check-ups:\n\n' + err.message + '\n\nPlease check your connection and try again.');
                    
                    // Restore button state
                    clearBtn.disabled = false;
                    clearBtn.innerHTML = originalHTML;
                });
            } else {
                console.error('Clear button not found!');
                alert('❌ Error: Could not find clear button. Please refresh the page and try again.');
            }
        }

        // ====================================================================
        // CLEAR APPOINTMENT DETAILS FUNCTION (UI ONLY - PERSISTENT)
        // ====================================================================
        /**
         * Clear appointment details display from the dashboard (UI only)
         * Stores state in localStorage to persist across page refreshes
         * Does NOT delete any data from the database
         */
        function clearAppointmentDetails(patientId, clearBtn, confirmed = false) {
            if (!confirmed) {
                window.openCenteredConfirmation({
                    title: 'Hide Appointment Details',
                    message: 'This will hide the appointments from the dashboard and persist after refreshing. No data will be deleted from the database.',
                    confirmLabel: 'Hide Appointments',
                    confirmClass: 'bg-emerald-600 hover:bg-emerald-700',
                    onConfirm: () => clearAppointmentDetails(patientId, clearBtn, true)
                });
                return;
            }
            
            console.log(`🧹 Clearing appointment details display for patient ID: ${patientId} (UI only, persistent)`);
            
            try {
                // Get the appointments list container
                const appointmentsList = document.getElementById(`appointmentsList_${patientId}`);
                
                if (appointmentsList) {
                    // Replace with "cleared" message with restore button
                    appointmentsList.innerHTML = `
                        <div class="text-center py-8">
                            <i class="fas fa-check-circle text-4xl text-blue-200 mb-3 block"></i>
                            <p class="text-blue-800">Appointment details cleared from display.</p>
                            <button onclick="clearAppointmentClearFlag(${patientId})" style="margin-top: 12px; padding: 6px 12px; background-color: #3b82f6; color: white; border-radius: 4px; border: none; cursor: pointer; font-size: 12px;">Restore Appointments</button>
                        </div>
                    `;
                    
                    // Hide the clear button since there's nothing to clear anymore
                    clearBtn = clearBtn || document.getElementById(`clearAppointmentDetailsBtn_${patientId}`);
                    if (clearBtn) {
                        clearBtn.style.display = 'none';
                    }
                    
                    // Store cleared state in localStorage (persists across page refreshes)
                    localStorage.setItem(`appointmentDetailsCleared_${patientId}`, 'true');
                    
                    console.log('✅ Appointment details hidden from display (UI only, database unchanged, state saved to localStorage)');
                    window.showCenteredSuccess({
                        title: 'Appointment Details Hidden',
                        message: 'Appointment details are hidden from the dashboard. The records remain in the database, and this setting persists after refreshing.'
                    });
                } else {
                    console.error('Appointments list container not found!');
                    alert('❌ Error: Could not find appointments display. Please refresh and try again.');
                }
            } catch (err) {
                console.error('🔴 Error clearing appointment display:', err);
                alert('❌ Error: ' + err.message);
            }
        }

        // ====================================================================
        // RESTORE CLEARED APPOINTMENT DETAILS STATE ON PAGE LOAD
        // ====================================================================
        document.addEventListener('DOMContentLoaded', function() {
            // Get all appointment containers on the page
            const appointmentContainers = document.querySelectorAll('[id^="appointmentsList_"]');
            
            appointmentContainers.forEach(container => {
                // Extract patient ID from container ID (format: appointmentsList_PATIENT_ID)
                const match = container.id.match(/appointmentsList_(\d+)/);
                if (match) {
                    const patientId = match[1];
                    const clearKey = `appointmentDetailsCleared_${patientId}`;
                    
                    // Check if this patient's appointments were previously cleared
                    if (localStorage.getItem(clearKey) === 'true') {
                        console.log(`🔄 Restoring cleared state for appointment details of patient ${patientId}`);
                        
                        // Hide the appointments list
                        container.innerHTML = `
                            <div class="text-center py-8">
                                <i class="fas fa-check-circle text-4xl text-blue-200 mb-3 block"></i>
                                <p class="text-blue-800">Appointment details cleared from display.</p>
                                <button onclick="clearAppointmentClearFlag(${patientId})" style="margin-top: 12px; padding: 6px 12px; background-color: #3b82f6; color: white; border-radius: 4px; border: none; cursor: pointer; font-size: 12px;">Restore Appointments</button>
                            </div>
                        `;
                        
                        // Hide the clear button
                        const clearBtn = document.getElementById(`clearAppointmentDetailsBtn_${patientId}`);
                        if (clearBtn) {
                            clearBtn.style.display = 'none';
                        }
                    }
                }
            });
        });
        
        // Function to remove clear flag and reload page to show appointments
        window.clearAppointmentClearFlag = function(patientId) {
            localStorage.removeItem(`appointmentDetailsCleared_${patientId}`);
            location.reload();
        };
    </script>

    <!-- ========================================================================
         LOGOUT CONFIRMATION MODAL
         ======================================================================== -->
    <div id="bhwLogoutConfirmModal" style="display: none;" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-sm w-full animate-in fade-in zoom-in duration-300">
            <!-- Modal Header -->
            <div class="px-6 py-6 border-b border-slate-200 flex items-center justify-center">
                <i class="fas fa-sign-out-alt text-4xl text-red-500"></i>
            </div>
            
            <!-- Modal Content -->
            <div class="px-6 py-6 text-center">
                <h2 class="text-2xl font-bold text-slate-900 mb-3">Log Out</h2>
                <p class="text-slate-600 mb-6">Are you sure you want to log out from your Health Worker account?</p>
            </div>
            
            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-slate-200 flex items-center justify-center gap-3">
                <button type="button" onclick="closeBHWLogoutConfirmModal()" class="px-6 py-2 border border-slate-300 rounded-lg font-semibold text-slate-700 hover:bg-slate-50 transition-all">
                    Cancel
                </button>
                <button type="button" onclick="confirmBHWLogout()" class="px-6 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg font-semibold transition-all">
                    Log Out
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================================
         SETTINGS MODAL
         ======================================================================== -->
    <div id="settingsModal" style="display: none;" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in duration-300">
            <!-- Modal Header -->
            <div class="sticky top-0 bg-white px-6 py-5 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                        <i class="fas fa-cog text-[#454B1B]"></i> Settings
                    </h3>
                    <p class="text-xs text-slate-500 mt-1">Manage your preferences</p>
                </div>
                <button type="button" onclick="closeSettingsModal()" class="text-slate-400 hover:text-slate-600 transition-colors text-2xl leading-none" aria-label="Close modal">&times;</button>
            </div>
            
            <!-- Modal Content -->
            <div class="p-6 space-y-6">
                <!-- Change Password Section -->
                <div class="border-t border-slate-200 pt-4">
                    <button type="button" onclick="changePassword()" class="w-full px-4 py-2.5 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-semibold transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-slate-200 flex items-center justify-end gap-3">
                <button type="button" onclick="closeSettingsModal()" class="px-4 py-2 border border-slate-300 rounded-lg font-semibold text-slate-700 hover:bg-slate-50 transition-all">
                    Close
                </button>
                <button type="button" onclick="saveSettings()" style="background-color: #454B1B;" class="px-4 py-2 text-white rounded-lg font-semibold hover:opacity-95 transition-all">
                    Save Settings
                </button>
            </div>
        </div>
    </div>

    <div id="changePasswordModal" style="display: none;" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[60] p-4" role="dialog" aria-modal="true" aria-labelledby="changePasswordModalTitle" onclick="if (event.target === this) closeChangePasswordModal()">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
            <div class="sticky top-0 bg-white dark:bg-slate-800 px-6 py-5 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <div>
                    <h3 id="changePasswordModalTitle" class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2"><i class="fas fa-key text-[#454B1B] dark:text-lime-300"></i> Change Password</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Choose a new password for your account</p>
                </div>
                <button type="button" onclick="closeChangePasswordModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-2xl leading-none" aria-label="Close modal">&times;</button>
            </div>
            <form id="changePasswordForm" onsubmit="submitPasswordChange(event)" class="p-6 space-y-4">
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200">Current password
                    <input id="currentPasswordInput" type="password" autocomplete="current-password" required class="mt-1 w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#454B1B]">
                </label>
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200">New password
                    <input id="newPasswordInput" type="password" autocomplete="new-password" minlength="6" required class="mt-1 w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#454B1B]">
                </label>
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200">Confirm new password
                    <input id="confirmPasswordInput" type="password" autocomplete="new-password" minlength="6" required class="mt-1 w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#454B1B]">
                </label>
                <p id="changePasswordMessage" class="hidden rounded-lg px-3 py-2 text-sm" role="status" aria-live="polite"></p>
                <div class="flex justify-end gap-3 border-t border-slate-200 dark:border-slate-700 pt-4">
                    <button type="button" onclick="closeChangePasswordModal()" class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700">Cancel</button>
                    <button id="submitPasswordChangeButton" type="submit" class="px-4 py-2 bg-[#454B1B] text-white rounded-lg font-semibold hover:opacity-95">Update Password</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Medicine Modal -->
    <div id="editMedicineModal" style="display: none;" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[11000] p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in duration-300">
            <!-- Modal Header -->
            <div class="sticky top-0 bg-white px-6 py-5 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                        <i class="fas fa-edit text-[#454B1B]"></i> Edit Medicine
                    </h3>
                    <p class="text-xs text-slate-500 mt-1">Update medicine details and inventory</p>
                </div>
                <button type="button" onclick="closeEditMedicineModal()" class="text-slate-400 hover:text-slate-600 transition-colors text-2xl leading-none" aria-label="Close modal">&times;</button>
            </div>
            
            <!-- Form Content -->
            <form id="editMedicineForm" onsubmit="handleEditMedicineSubmit(event)" class="p-6 space-y-5">
                <input type="hidden" id="editMedicineId" value="">
                
                <!-- Medicine Name -->
                <div>
                    <label for="editMedicineName" class="block text-sm font-semibold text-slate-700 mb-2">
                        <span class="text-red-500">*</span> Medicine Name
                    </label>
                    <input type="text" id="editMedicineName" name="medicine_name" 
                           placeholder="e.g., Paracetamol, Amoxicillin" 
                           class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent transition-all" 
                           required>
                </div>
                
                <!-- Dosage -->
                <div>
                    <label for="editMedicineDosage" class="block text-sm font-semibold text-slate-700 mb-2">Dosage/Strength</label>
                    <input type="text" id="editMedicineDosage" name="dosage" 
                           placeholder="e.g., 500mg" 
                           class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent transition-all">
                </div>
                
                <!-- Inventory Fields Grid -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="editMedicineAvailable" class="block text-sm font-semibold text-slate-700 mb-2">Available</label>
                        <input type="number" id="editMedicineAvailable" name="available" 
                               min="0" 
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent transition-all text-center" 
                               onchange="updateEditMedicineStatus()">
                    </div>
                    
                    <div>
                        <label for="editMedicineDispensed" class="block text-sm font-semibold text-slate-700 mb-2">Dispensed</label>
                        <input type="number" id="editMedicineDispensed" name="dispensed" 
                               min="0" 
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent transition-all text-center"
                               onchange="syncAvailableFromDispensed()">
                    </div>
                </div>
                
                <!-- Stock Status Preview -->
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                    <p class="text-xs font-semibold text-amber-900 mb-2">📊 Stock Status:</p>
                    <div id="editMedicineStatusPreview" class="text-sm text-amber-800">
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-amber-100 text-amber-700 rounded-full font-medium text-xs">
                            <i class="fas fa-circle text-xs"></i> Status will update
                        </span>
                    </div>
                </div>
                
                <!-- Info Box -->
                <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-xs text-blue-700">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Note:</strong> Inventory values are synchronized automatically. If available drops to 0, status will change to "Out of Stock".
                </div>
                
                <!-- Form Actions -->
                <div class="flex gap-2 justify-end pt-4 border-t border-slate-200">
                    <button type="button" onclick="handleDeleteMedicine(event)" 
                            class="px-5 py-2.5 border border-red-500 bg-red-500/10 text-red-700 font-semibold rounded-lg hover:bg-red-500/20 transition-all flex items-center gap-2">
                        <i class="fas fa-trash-alt"></i> Delete
                    </button>
                    <button type="submit" style="background-color: #454B1B;" 
                            class="px-5 py-2.5 text-white rounded-lg font-semibold hover:opacity-95 transition-all flex items-center gap-2" id="editMedicineSubmitBtn">
                        <i class="fas fa-save"></i> Update Medicine
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Medicine Modal -->
    <div id="addMedicineModal" style="display: none;" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[11000] p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in duration-300">
            <!-- Modal Header -->
            <div class="sticky top-0 bg-white px-6 py-5 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                        <i class="fas fa-plus-circle text-[#454B1B]"></i> Add New Medicine
                    </h3>
                    <p class="text-xs text-slate-500 mt-1">Enter medicine details to add to inventory</p>
                </div>
                <button type="button" onclick="closeAddMedicineModal()" class="text-slate-400 hover:text-slate-600 transition-colors text-2xl leading-none" aria-label="Close modal">&times;</button>
            </div>
            
            <!-- Form Content -->
            <form id="addMedicineForm" onsubmit="handleAddMedicineSubmit(event)" class="p-6 space-y-5">
                <!-- Medicine Name -->
                <div>
                    <label for="addMedicineName" class="block text-sm font-semibold text-slate-700 mb-2">
                        <span class="text-red-500">*</span> Medicine Name
                    </label>
                    <input type="text" id="addMedicineName" name="medicine_name" 
                           placeholder="e.g., Paracetamol, Amoxicillin" 
                           class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent transition-all" 
                           required>
                    <p class="text-xs text-slate-500 mt-1">Generic or brand name of the medicine</p>
                </div>
                
                <!-- Dosage -->
                <div>
                    <label for="addMedicineDosage" class="block text-sm font-semibold text-slate-700 mb-2">Dosage/Strength</label>
                    <input type="text" id="addMedicineDosage" name="dosage" 
                           placeholder="e.g., 500mg, 250mg, 10ml" 
                           class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent transition-all">
                    <p class="text-xs text-slate-500 mt-1">Strength and form (tablet, capsule, liquid, etc.)</p>
                </div>
                
                <div>
                        <label for="addMedicineAvailable" class="block text-sm font-semibold text-slate-700 mb-2"><span class="text-red-500">*</span> Available</label>
                        <input type="number" id="addMedicineAvailable" name="available" 
                               min="0" value="0" placeholder="0" 
                               class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B] focus:border-transparent transition-all" onchange="updateAddMedicineStatus()" required>
                        <p class="text-xs text-slate-500 mt-1">Medicine available to dispense.</p>
                </div>
                
                <!-- Stock Status Preview -->
                <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-xs font-semibold text-blue-900 mb-2">📊 Stock Status Preview:</p>
                    <div id="addMedicineStatusPreview" class="text-sm text-blue-800">
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-700 rounded-full font-medium text-xs">
                            <i class="fas fa-circle text-xs"></i> Status will be calculated
                        </span>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="flex gap-2 justify-end pt-4 border-t border-slate-200">
                    <button type="button" onclick="closeAddMedicineModal()" 
                            class="px-5 py-2.5 border border-slate-300 rounded-lg text-slate-700 font-semibold hover:bg-slate-50 transition-all">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>
                    <button type="submit" style="background-color: #454B1B;" 
                            class="px-5 py-2.5 text-white rounded-lg font-semibold hover:opacity-95 transition-all flex items-center gap-2" id="addMedicineSubmitBtn">
                        <i class="fas fa-plus-circle"></i> Add Medicine
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Patient Selection Modal - REMOVED (Using Auto-Redirect Instead) -->

    <!-- Profile Dropdown & Settings Manager -->
    <script>
        // ====================================================================
        // PROFILE DROPDOWN MENU FUNCTIONS
        // ====================================================================
        function toggleProfileDropdown() {
            const dropdown = document.getElementById('profileDropdownMenu');
            const btn = document.getElementById('profileDropdownBtn');
            
            if (dropdown && btn) {
                if (dropdown.style.display === 'none' || dropdown.style.display === '') {
                    dropdown.style.display = 'block';
                    btn.classList.add('active');
                } else {
                    dropdown.style.display = 'none';
                    btn.classList.remove('active');
                }
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const container = document.querySelector('.profile-dropdown-container');
            if (container && !container.contains(event.target)) {
                const dropdown = document.getElementById('profileDropdownMenu');
                if (dropdown) {
                    dropdown.style.display = 'none';
                    const btn = document.getElementById('profileDropdownBtn');
                    if (btn) btn.classList.remove('active');
                }
            }

            const mobileNav = document.querySelector('.sidebar');
            const toggleBtn = document.getElementById('mobileNavToggleBtn');
            if (mobileNav && toggleBtn && !mobileNav.contains(event.target) && !toggleBtn.contains(event.target)) {
                mobileNav.classList.remove('active');
                toggleBtn.classList.remove('active');
                toggleBtn.setAttribute('aria-expanded', 'false');
            }
        });

        function toggleMobileNav() {
            const mobileNav = document.querySelector('.sidebar');
            const toggleBtn = document.getElementById('mobileNavToggleBtn');
            if (!mobileNav || !toggleBtn) return;

            const isActive = mobileNav.classList.toggle('active');
            toggleBtn.classList.toggle('active', isActive);
            toggleBtn.setAttribute('aria-expanded', isActive ? 'true' : 'false');
        }

        // ====================================================================
        // LOGOUT CONFIRMATION MODAL FUNCTIONS
        // ====================================================================
        function openBHWLogoutConfirmModal() {
            closeProfileDropdown();
            const modal = document.getElementById('bhwLogoutConfirmModal');
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            } else {
                window.openCenteredConfirmation({
                    title: 'Log Out',
                    message: 'Are you sure you want to log out?',
                    confirmLabel: 'Log Out',
                    onConfirm: () => { window.location.href = 'logout.php'; }
                });
            }
        }

        function closeBHWLogoutConfirmModal() {
            const modal = document.getElementById('bhwLogoutConfirmModal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }

        function confirmBHWLogout() {
            window.location.href = 'logout.php';
        }

        // Close modal when clicking outside
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('bhwLogoutConfirmModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeBHWLogoutConfirmModal();
                    }
                });
            }
        });

        // ====================================================================
        // SETTINGS MODAL STATE MANAGEMENT
        // ====================================================================
        let settingsSaveTriggered = false;

        function openSettingsModal() {
            settingsSaveTriggered = false;
            const modal = document.getElementById('settingsModal');
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        }

        function closeSettingsModal() {
            const modal = document.getElementById('settingsModal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }

        // ====================================================================
        // DROPDOWN CLOSE HELPER
        // ====================================================================
        function closeProfileDropdown() {
            const dropdown = document.getElementById('profileDropdownMenu');
            const btn = document.getElementById('profileDropdownBtn');
            if (dropdown) dropdown.style.display = 'none';
            if (btn) btn.classList.remove('active');
        }

        // ====================================================================
        // SETTINGS SAVE FUNCTION
        // ====================================================================
        function saveSettings() {
            // Mark that Save was triggered
            settingsSaveTriggered = true;
            
            closeSettingsModal();
            window.showCenteredSuccess({ title: 'Settings Saved', message: 'Your settings have been saved.' });
        }

        // ====================================================================
        // CHANGE PASSWORD FUNCTION
        // ====================================================================
        function changePassword() {
            const modal = document.getElementById('changePasswordModal');
            const form = document.getElementById('changePasswordForm');
            const message = document.getElementById('changePasswordMessage');
            form.reset();
            message.textContent = '';
            message.className = 'hidden rounded-lg px-3 py-2 text-sm';
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            document.getElementById('currentPasswordInput').focus();
        }

        function closeChangePasswordModal() {
            document.getElementById('changePasswordModal').style.display = 'none';
            document.body.style.overflow = document.getElementById('settingsModal').style.display === 'flex' ? 'hidden' : 'auto';
        }

        async function submitPasswordChange(event) {
            event.preventDefault();
            const form = event.currentTarget;
            const currentPassword = document.getElementById('currentPasswordInput').value;
            const newPassword = document.getElementById('newPasswordInput').value;
            const confirmPassword = document.getElementById('confirmPasswordInput').value;
            const message = document.getElementById('changePasswordMessage');
            const submitButton = document.getElementById('submitPasswordChangeButton');
            const showMessage = (text, success = false) => {
                message.textContent = text;
                message.className = `rounded-lg px-3 py-2 text-sm ${success ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200' : 'bg-red-50 text-red-800 dark:bg-red-900/30 dark:text-red-200'}`;
            };

            if (newPassword.length < 6) {
                showMessage('New password must be at least 6 characters long.');
                return;
            }
            if (newPassword !== confirmPassword) {
                showMessage('New passwords do not match.');
                return;
            }

            submitButton.disabled = true;
            submitButton.textContent = 'Updating...';
            try {
                const response = await fetch('change_password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ currentPassword, newPassword, confirmPassword })
                });
                const data = await response.json();
                if (!response.ok || !data.success) {
                    showMessage(data.error || 'Unable to change password. Please check your current password and try again.');
                    return;
                }
                showMessage(data.message || 'Password updated successfully.', true);
                form.reset();
            } catch (error) {
                showMessage('Unable to connect. Please try again.');
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'Update Password';
            }
        }

        // ====================================================================
        // DARK MODE TOGGLE FUNCTION
        // ====================================================================
    </script>

    <!-- Profile Picture Upload Handler -->
    <script>
        /**
         * Handle profile picture upload
         */
        const bhwCropperState = {
            image: null,
            zoom: 1,
            panX: 0,
            panY: 0,
            dragging: false,
            lastX: 0,
            lastY: 0
        };
        window.bhwCropperState = bhwCropperState;

        async function handleProfileUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            if (!validateProfileFile(file)) {
                return;
            }

            const reader = new FileReader();
            reader.onload = function (loadEvent) {
                const image = new Image();
                image.onload = function () {
                    bhwCropperState.image = image;
                    bhwCropperState.zoom = 1;
                    bhwCropperState.panX = 0;
                    bhwCropperState.panY = 0;
                    document.getElementById('bhwZoomSlider').value = '1';
                    document.getElementById('bhwZoomValue').textContent = '100%';
                    document.getElementById('bhwCropperModal').style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                    drawBhwCropper();
                };
                image.src = loadEvent.target.result;
            };
            reader.readAsDataURL(file);
        }

        function drawBhwCropper() {
            const canvas = document.getElementById('bhwCropperCanvas');
            const preview = canvas?.parentElement;
            if (!canvas || !preview || !bhwCropperState.image) return;
            canvas.width = preview.clientWidth;
            canvas.height = preview.clientHeight;
            const ctx = canvas.getContext('2d');
            const image = bhwCropperState.image;
            const imageRatio = image.width / image.height;
            const previewRatio = canvas.width / canvas.height;
            let width = imageRatio > previewRatio ? canvas.width : canvas.height * imageRatio;
            let height = imageRatio > previewRatio ? canvas.width / imageRatio : canvas.height;
            width *= bhwCropperState.zoom;
            height *= bhwCropperState.zoom;
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(image, (canvas.width - width) / 2 + bhwCropperState.panX, (canvas.height - height) / 2 + bhwCropperState.panY, width, height);
            ctx.strokeStyle = 'rgba(69, 75, 27, .75)';
            ctx.lineWidth = 3;
            ctx.beginPath();
            ctx.arc(canvas.width / 2, canvas.height / 2, Math.min(canvas.width, canvas.height) / 2 - 10, 0, Math.PI * 2);
            ctx.stroke();
        }

        function closeBhwCropper() {
            document.getElementById('bhwCropperModal').style.display = 'none';
            document.body.style.overflow = '';
            const input = document.getElementById('profileUploadInput');
            if (input) input.value = '';
            bhwCropperState.image = null;
        }

        async function confirmBhwCropper() {
            const canvas = document.getElementById('bhwCropperCanvas');
            if (!canvas || !bhwCropperState.image) return;
            canvas.toBlob(async function (blob) {
                if (!blob) return showUploadStatus('Unable to prepare the cropped image.', 'error');
                try {
                    const formData = new FormData();
                    formData.append('profile_picture', blob, 'cropped_profile.png');
                    formData.append('profile_scope', 'health_worker');
                    const response = await fetch('upload_profile.php', { method: 'POST', body: formData, credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                    const responseText = await response.text();
                    let data;
                    try {
                        data = JSON.parse(responseText);
                    } catch (parseError) {
                        throw new Error(`Upload returned an invalid response: ${responseText.slice(0, 200)}`);
                    }
                    if (!response.ok || !data.success) throw new Error(data.message || data.error || 'Upload failed');
                    const imageUrl = data.image_url || data.path;
                    if (!imageUrl) throw new Error('Upload succeeded but no image path was returned.');
                    const workerId = document.getElementById('bhwHeader')?.dataset.workerId || 'current';
                    localStorage.setItem(`scancare_bhw_profile_picture_${workerId}`, JSON.stringify({ url: imageUrl, savedAt: Date.now() }));
                    const navbarImage = document.getElementById('profileImageNavbar');
                    const dropdownImage = document.getElementById('dropdownProfileImage');
                    if (navbarImage) { navbarImage.src = imageUrl; navbarImage.style.display = 'block'; }
                    if (dropdownImage) { dropdownImage.src = imageUrl; dropdownImage.style.display = 'block'; }
                    document.getElementById('profileInitials')?.style.setProperty('display', 'none');
                    document.getElementById('dropdownInitials')?.style.setProperty('display', 'none');
                    closeBhwCropper();
                    showUploadStatus('Profile picture updated successfully!', 'success');
                } catch (error) {
                    showUploadStatus(error.message, 'error');
                }
            }, 'image/png');
        }

        function submitProfileUploadForm(fileInput) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'upload_profile.php';
            form.enctype = 'multipart/form-data';
            form.appendChild(fileInput);
            document.body.appendChild(form);
            form.submit();
        }

        /**
         * Validate profile file
         */
        function validateProfileFile(file) {
            const allowedTypes = ['image/jpeg', 'image/png'];
            const maxFileSize = 2 * 1024 * 1024; // 2MB

            if (!allowedTypes.includes(file.type)) {
                showUploadStatus('❌ Only JPG and PNG files are allowed', 'error');
                return false;
            }

            if (file.size > maxFileSize) {
                const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                showUploadStatus(`❌ File size (${sizeMB}MB) exceeds 2MB limit`, 'error');
                return false;
            }

            return true;
        }

        /**
         * Show upload status message
         */
        function showUploadStatus(message, type) {
            // Create status element if it doesn't exist
            let statusEl = document.getElementById('profileUploadStatusMsg');
            if (!statusEl) {
                statusEl = document.createElement('div');
                statusEl.id = 'profileUploadStatusMsg';
                statusEl.style.position = 'fixed';
                statusEl.style.top = '80px';
                statusEl.style.right = '20px';
                statusEl.style.zIndex = '1000';
                statusEl.style.borderRadius = '8px';
                statusEl.style.padding = '12px 20px';
                statusEl.style.maxWidth = '300px';
                document.body.appendChild(statusEl);
            }

            statusEl.textContent = message;
            statusEl.style.display = 'block';
            statusEl.className = 'profile-upload-status ' + type;

            // Auto-hide after 4 seconds
            setTimeout(() => {
                statusEl.style.display = 'none';
            }, 4000);
        }

        /**
         * Initialize profile picture on page load
         */
        document.addEventListener('DOMContentLoaded', function() {
            initializeProfilePicture();
        });

        async function initializeProfilePicture() {
            const workerId = document.getElementById('bhwHeader')?.dataset.workerId || 'current';
            const cacheKey = `scancare_bhw_profile_picture_${workerId}`;
            const cacheLifetime = 15 * 60 * 1000;
            const applyProfilePicture = function (imageUrl) {
                const navbarImg = document.getElementById('profileImageNavbar');
                const dropdownImg = document.getElementById('dropdownProfileImage');
                if (imageUrl) {
                    if (navbarImg) { navbarImg.src = imageUrl; navbarImg.style.display = 'block'; }
                    if (dropdownImg) { dropdownImg.src = imageUrl; dropdownImg.style.display = 'block'; }
                    document.getElementById('profileInitials')?.style.setProperty('display', 'none');
                    document.getElementById('dropdownInitials')?.style.setProperty('display', 'none');
                }
            };

            let cachedProfile = null;
            try {
                cachedProfile = JSON.parse(localStorage.getItem(cacheKey) || 'null');
            } catch (error) {
                cachedProfile = null;
            }

            if (cachedProfile?.url) {
                applyProfilePicture(cachedProfile.url);
                if (Date.now() - Number(cachedProfile.savedAt || 0) < cacheLifetime) return;
            }

            try {
                const response = await fetch('get_profile_picture.php');
                const data = await response.json();
                
                if (data.success && data.has_picture && data.profile_picture) {
                    localStorage.setItem(cacheKey, JSON.stringify({ url: data.profile_picture, savedAt: Date.now() }));
                    applyProfilePicture(data.profile_picture);
                } else {
                    // No profile picture stored, show initials
                    const workerNameEl = document.querySelector('[style*="color: #454B1B"][style*="font-weight: 600"]');
                    
                    if (workerNameEl) {
                        const nameText = workerNameEl.textContent.trim();
                        const initials = getInitials(nameText);
                        
                        // Update navbar initials
                        const profileInitials = document.getElementById('profileInitials');
                        if (profileInitials) {
                            profileInitials.textContent = initials;
                            profileInitials.style.display = 'block';
                        }
                        
                        const navbarImg = document.getElementById('profileImageNavbar');
                        if (navbarImg) navbarImg.style.display = 'none';
                        
                        // Update dropdown initials
                        const dropdownInitials = document.getElementById('dropdownInitials');
                        if (dropdownInitials) {
                            dropdownInitials.textContent = initials;
                            dropdownInitials.style.display = 'flex';
                        }
                        
                        const dropdownImg = document.getElementById('dropdownProfileImage');
                        if (dropdownImg) dropdownImg.style.display = 'none';
                    }
                }
            } catch (error) {
                console.error('Error initializing profile picture:', error);
                
                // Fallback to initials on error
                const workerNameEl = document.querySelector('[style*="color: #454B1B"][style*="font-weight: 600"]');
                if (workerNameEl) {
                    const nameText = workerNameEl.textContent.trim();
                    const initials = getInitials(nameText);
                    
                    // Update navbar initials
                    const profileInitials = document.getElementById('profileInitials');
                    if (profileInitials) {
                        profileInitials.textContent = initials;
                        profileInitials.style.display = 'block';
                    }
                    
                    // Update dropdown initials
                    const dropdownInitials = document.getElementById('dropdownInitials');
                    if (dropdownInitials) {
                        dropdownInitials.textContent = initials;
                        dropdownInitials.style.display = 'flex';
                    }
                }
            }
        }

        function getInitials(fullName) {
            if (!fullName) return '--';
            const parts = fullName.trim().split(' ');
            if (parts.length >= 2) {
                return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
            }
            return fullName.substring(0, 2).toUpperCase();
        }
    </script>

    <script>
        const announcementStatusToast = document.getElementById('announcementStatusToast');
        if (announcementStatusToast) {
            window.setTimeout(function () {
                announcementStatusToast.remove();
            }, 2500);
        }

        document.querySelectorAll('[data-announcement-tab-link]').forEach(function (link) {
            link.addEventListener('click', function (event) {
                event.preventDefault();

                const mode = link.dataset.announcementMode || 'create';
                const state = link.dataset.announcementStateTab || <?php echo json_encode($announcementState); ?>;
                document.querySelectorAll('[data-announcement-panel]').forEach(function (panel) {
                    panel.hidden = panel.dataset.announcementPanel !== mode;
                });

                document.querySelectorAll('[data-announcement-state-tab]').forEach(function (stateLink) {
                    const active = stateLink.dataset.announcementStateTab === state;
                    stateLink.classList.toggle('border-[#3D4A1E]', active);
                    stateLink.classList.toggle('bg-[#3D4A1E]', active);
                    stateLink.classList.toggle('text-white', active);
                    stateLink.classList.toggle('border-slate-200', !active);
                    stateLink.classList.toggle('bg-white', !active);
                    stateLink.classList.toggle('text-slate-600', !active);
                });

                document.querySelectorAll('[data-announcement-item]').forEach(function (item) {
                    item.hidden = item.dataset.announcementItem !== state;
                });
                document.querySelectorAll('[data-announcement-empty]').forEach(function (emptyState) {
                    const hasItems = Array.from(document.querySelectorAll('[data-announcement-item]'))
                        .some(item => item.dataset.announcementItem === state);
                    emptyState.hidden = emptyState.dataset.announcementEmpty !== state || hasItems;
                });

                document.querySelectorAll('[data-announcement-primary-tab]').forEach(function (modeLink) {
                    const active = modeLink.dataset.announcementMode === mode;
                    modeLink.classList.toggle('bg-[#3D4A1E]', active);
                    modeLink.classList.toggle('text-white', active);
                    modeLink.classList.toggle('text-slate-600', !active);
                    modeLink.classList.toggle('hover:bg-slate-100', !active);
                });

                const nextUrl = new URL(link.href, window.location.href);
                nextUrl.searchParams.set('module', 'announcements');
                nextUrl.searchParams.set('announcement_tab', mode);
                nextUrl.searchParams.set('announcement_state', state);
                window.history.replaceState({}, '', nextUrl);
            });
        });

        const announcementToday = <?php echo json_encode(date('Y-m-d')); ?>;

        function updateAnnouncementStateLabel(form, state) {
            const stateLabel = form.querySelector('[data-announcement-state-label]');
            if (stateLabel) stateLabel.textContent = state;
        }

        function syncAnnouncementStateFromDate(form) {
            const dateValue = form.querySelector('[name="service_date"]')?.value;
            const stateSelect = form.querySelector('[name="state"]');
            if (!dateValue || !stateSelect) return;

            const state = dateValue < announcementToday ? 'done' : (dateValue > announcementToday ? 'upcoming' : 'now');
            stateSelect.value = state;
            updateAnnouncementStateLabel(form, state);
        }

        document.querySelectorAll('[data-announcement-panel] form').forEach(function (form) {
            const serviceDate = form.querySelector('[name="service_date"]');
            const stateSelect = form.querySelector('[name="state"]');
            if (serviceDate) {
                serviceDate.addEventListener('change', () => syncAnnouncementStateFromDate(form));
                serviceDate.addEventListener('input', () => syncAnnouncementStateFromDate(form));
                syncAnnouncementStateFromDate(form);
                form.addEventListener('submit', () => syncAnnouncementStateFromDate(form));
            }
            if (stateSelect) {
                stateSelect.addEventListener('change', () => {
                    if (!serviceDate?.value) updateAnnouncementStateLabel(form, stateSelect.value);
                });
            }
        });

        window.handleDeleteAnnouncement = async function (event, announcementId, confirmed = false) {
            const button = event.currentTarget;
            const card = button.closest('[data-announcement-item]');
            if (!card) return;
            if (!confirmed) {
                window.openCenteredConfirmation({
                    title: 'Delete Announcement',
                    message: 'Permanently delete this announcement? This cannot be undone.',
                    confirmLabel: 'Delete Announcement',
                    onConfirm: () => window.handleDeleteAnnouncement({ currentTarget: button }, announcementId, true)
                });
                return;
            }

            const payload = {
                action: 'delete_announcement',
                announcement_id: String(announcementId)
            };

            if (!navigator.onLine) {
                if (!window.bhwOfflineQueue || !window.bhwOfflineQueue.queueBhwAction) {
                    window.alert('Cannot queue this deletion while offline.');
                    return;
                }
                window.bhwOfflineQueue.queueBhwAction('delete_announcement', payload, 'dashboard_bhw.php');
                const state = card.dataset.announcementItem;
                card.remove();
                const hasItems = Array.from(document.querySelectorAll('[data-announcement-item]'))
                    .some(item => item.dataset.announcementItem === state);
                document.querySelectorAll('[data-announcement-empty]').forEach(emptyState => {
                    if (emptyState.dataset.announcementEmpty === state) emptyState.hidden = hasItems;
                });
                return;
            }

            button.disabled = true;
            try {
                const formData = new FormData();
                formData.append('action', payload.action);
                formData.append('announcement_id', payload.announcement_id);
                const response = await fetch('dashboard_bhw.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Announcement could not be deleted.');
                }
                window.location.reload();
            } catch (error) {
                button.disabled = false;
                window.alert(error.message || 'Announcement could not be deleted.');
            }
        };
    </script>

    <!-- Login Approval Notification System -->
    <script src="<?php echo htmlspecialchars(appPath('login-notification-system.js'), ENT_QUOTES, 'UTF-8'); ?>?v=13"></script>

    <script>
        document.addEventListener('click', (event) => {
            const link = event.target.closest('a[href*="download_file_request.php"]');
            if (!link || !window.matchMedia('(max-width: 768px)').matches) return;

            event.preventDefault();
            const downloadUrl = new URL(link.href, window.location.href);
            downloadUrl.searchParams.set('download', '1');
            window.location.assign(downloadUrl.href);
        });

        function compactMedicineOptions() {
            const select = document.getElementById('inventoryMedicineSelect');
            if (!select) return;

            const compact = window.matchMedia('(max-width: 768px)').matches;
            select.querySelectorAll('option[data-medicine-name]').forEach((option) => {
                if (!option.dataset.fullLabel) option.dataset.fullLabel = option.textContent;
                option.textContent = compact ? option.dataset.medicineName : option.dataset.fullLabel;
            });
        }

        compactMedicineOptions();
        window.addEventListener('resize', compactMedicineOptions);
    </script>

</body>
</html>
