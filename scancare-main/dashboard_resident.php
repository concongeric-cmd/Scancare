<?php


require_once 'db_config.php';
require_once 'appointment_system.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}




if (!isset($_SESSION['resident_id']) || $_SESSION['user_type'] !== 'resident') {
    error_log("Unauthorized dashboard access attempt - Invalid session");
    header('Location: login.php');
    exit;
}

requireCurrentSessionAccount('login.php');

$resident_id = $_SESSION['resident_id'];












$is_https = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    $_SERVER['SERVER_PORT'] == 443 ||
    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
);
$protocol = $is_https ? 'https' : 'http';





$host = '';
if (!empty($_SERVER['HTTP_HOST'])) {
    $host = $_SERVER['HTTP_HOST'];
} elseif (!empty($_SERVER['SERVER_NAME'])) {
    $host = $_SERVER['SERVER_NAME'];
    
    if (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) {
        $host .= ':' . $_SERVER['SERVER_PORT'];
    }
} elseif (!empty($_SERVER['SERVER_ADDR'])) {
    $host = $_SERVER['SERVER_ADDR'];
    
    if (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) {
        $host .= ':' . $_SERVER['SERVER_PORT'];
    }
} else {
    $host = 'localhost';
}



$patient_code = null;
$pdo = getDatabaseConnection();
if ($pdo !== false) {
    $patient_code = ensureResidentPatientCode($pdo, $resident_id);
}
if (empty($patient_code)) {
    $patient_code = str_pad($resident_id, 6, '0', STR_PAD_LEFT);
}

$residentProfileRecord = [];
if ($pdo !== false) {
    try {
        $residentProfileStmt = $pdo->prepare('SELECT full_name, email, date_of_birth, address, contact_number, patient_code FROM resident WHERE resident_id = ? LIMIT 1');
        $residentProfileStmt->execute([$resident_id]);
        $residentProfileRecord = $residentProfileStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $profileError) {
        error_log('Resident medical record profile load error: ' . $profileError->getMessage());
    }
}

$residentDateOfBirth = trim((string) ($residentProfileRecord['date_of_birth'] ?? ''));
if ($residentDateOfBirth === '0000-00-00') {
    $residentDateOfBirth = '';
}
$residentBirthDate = $residentDateOfBirth !== ''
    ? DateTimeImmutable::createFromFormat('!Y-m-d', substr($residentDateOfBirth, 0, 10))
    : false;
$residentAge = $residentBirthDate instanceof DateTimeImmutable
    ? $residentBirthDate->diff(new DateTimeImmutable('today'))->y
    : '';
$residentMedicalProfile = [
    'name' => trim((string) ($residentProfileRecord['full_name'] ?? $_SESSION['user_name'] ?? '')),
    'email' => trim((string) ($residentProfileRecord['email'] ?? $_SESSION['email'] ?? '')),
    'address' => trim((string) ($residentProfileRecord['address'] ?? '')),
    'contact' => trim((string) ($residentProfileRecord['contact_number'] ?? '')),
    'birth_date' => $residentDateOfBirth,
    'patient_id' => trim((string) ($residentProfileRecord['patient_code'] ?? $patient_code)),
    'age' => $residentAge,
    'record_mode' => 'Resident Copy'
];

$record_param = 'code=' . urlencode($patient_code);
$record_url = appUrl('resident_qr.php?' . $record_param);

// Serve the QR from the same origin so it can be downloaded and cached without
// browser cross-origin canvas restrictions.
$qr_code_url = appUrl('resident_qr_image.php?' . $record_param);

$error_log_message = "QR Code Generated - Resident: {$resident_id}, PatientCode: {$patient_code}, Host: {$host}, Protocol: {$protocol}, URL: {$record_url}";
error_log($error_log_message);





$appointments = [];
$todayAppointments = [];
$bookedAppointments = [];
$fileRequests = [];
$healthAnnouncements = [];
$healthAnnouncementsByState = ['upcoming' => [], 'now' => []];
$appointmentSuccessMessage = '';
$appointmentErrorMessage = '';


$medicalHistory = [];
$surveillance_metrics = [];

function residentSurveillanceValue($metrics, $key, $fallback = '') {
    if (!is_array($metrics)) {
        return $fallback;
    }

    if (array_key_exists($key, $metrics) && $metrics[$key] !== null && $metrics[$key] !== '') {
        return $metrics[$key];
    }

    $snakeKey = str_replace('-', '_', $key);
    if (array_key_exists($snakeKey, $metrics) && $metrics[$snakeKey] !== null && $metrics[$snakeKey] !== '') {
        return $metrics[$snakeKey];
    }

    $camelKey = preg_replace_callback('/_([a-z])/', static function ($matches) {
        return strtoupper($matches[1]);
    }, $key);
    if (array_key_exists($camelKey, $metrics) && $metrics[$camelKey] !== null && $metrics[$camelKey] !== '') {
        return $metrics[$camelKey];
    }

    return $fallback;
}

try {
    $pdoMetrics = getDatabaseConnection();
    if ($pdoMetrics !== false) {
        $surveillance_metrics = getResidentSurveillanceMetrics($pdoMetrics);
        if (!$surveillance_metrics) {
            $pdoMetrics->exec("CREATE TABLE IF NOT EXISTS surveillance_metrics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            worker_id INT NOT NULL,
            reporting_period VARCHAR(255) DEFAULT NULL,
            dengue_count INT DEFAULT 0,
            dengue_trend VARCHAR(255) DEFAULT NULL,
            dengue_meta VARCHAR(255) DEFAULT NULL,
            dengue_progress INT DEFAULT 0,
            rabies_count INT DEFAULT 0,
            rabies_trend VARCHAR(255) DEFAULT NULL,
            rabies_meta VARCHAR(255) DEFAULT NULL,
            rabies_progress INT DEFAULT 0,
            respiratory_count INT DEFAULT 0,
            respiratory_trend VARCHAR(255) DEFAULT NULL,
            respiratory_meta VARCHAR(255) DEFAULT NULL,
            respiratory_progress INT DEFAULT 0,
            nutrition_count INT DEFAULT 0,
            nutrition_trend VARCHAR(255) DEFAULT NULL,
            nutrition_meta VARCHAR(255) DEFAULT NULL,
            nutrition_progress INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_worker_id (worker_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $stmtMetrics = $pdoMetrics->query('SELECT * FROM surveillance_metrics ORDER BY updated_at DESC LIMIT 1');
            $surveillance_metrics = $stmtMetrics->fetch(PDO::FETCH_ASSOC) ?: [];
        }
    }
} catch (Exception $e) {
    error_log('Resident surveillance metrics load error: ' . $e->getMessage());
    $surveillance_metrics = [];
}

$residentReportingPeriod = trim((string) residentSurveillanceValue($surveillance_metrics, 'reporting_period', 'Current 4 Weeks'));
$hasResidentPublishedMetrics = !empty($surveillance_metrics['disease_records']);
if ($residentReportingPeriod === '') {
    $residentReportingPeriod = $hasResidentPublishedMetrics ? 'Latest patient records' : 'No checked items';
}
$residentUpdatedAt = residentSurveillanceValue($surveillance_metrics, 'updated_at', '');
$residentDengueCount = (int) residentSurveillanceValue($surveillance_metrics, 'dengue_count', 0);
function residentCountBasedTrend($count) {
    $value = max(0, (int) $count);
    if ($value <= 10) return ['label' => 'Baseline', 'color' => 'text-emerald-600'];
    if ($value <= 20) return ['label' => 'Watch', 'color' => 'text-amber-600'];
    if ($value <= 30) return ['label' => 'Alert', 'color' => 'text-orange-600'];
    return ['label' => 'Emergency', 'color' => 'text-red-600'];
}

$residentDengueTrend = residentCountBasedTrend($residentDengueCount)['label'];
$residentDengueMeta = trim((string) residentSurveillanceValue($surveillance_metrics, 'dengue_meta', 'No active cases reported.')) ?: 'No active cases reported.';
$residentDengueProgress = (int) residentSurveillanceValue($surveillance_metrics, 'dengue_progress', 0);
if ($residentDengueCount > 0) {
    $residentDengueProgress = min(100, max(0, ($residentDengueCount / 50) * 100));
} else {
    $residentDengueProgress = 0;
}
$residentRabiesCount = (int) residentSurveillanceValue($surveillance_metrics, 'rabies_count', 0);
$residentRabiesTrend = residentCountBasedTrend($residentRabiesCount)['label'];
$residentRabiesMeta = trim((string) residentSurveillanceValue($surveillance_metrics, 'rabies_meta', '0% coverage | No residents vaccinated.')) ?: '0% coverage | No residents vaccinated.';
$residentRabiesProgress = (int) residentSurveillanceValue($surveillance_metrics, 'rabies_progress', 0);
if ($residentRabiesCount > 0 && ($residentRabiesProgress <= 0 || $residentRabiesProgress < min(100, max(10, $residentRabiesCount)))) {
    $residentRabiesProgress = min(100, max(10, $residentRabiesCount));
}
$residentRespiratoryCount = (int) residentSurveillanceValue($surveillance_metrics, 'respiratory_count', 0);
$residentRespiratoryTrend = residentCountBasedTrend($residentRespiratoryCount)['label'];
$residentRespiratoryMeta = trim((string) residentSurveillanceValue($surveillance_metrics, 'respiratory_meta', 'No active cases reported.')) ?: 'No active cases reported.';
$residentRespiratoryProgress = (int) residentSurveillanceValue($surveillance_metrics, 'respiratory_progress', 0);
if ($residentRespiratoryCount > 0) {
    $residentRespiratoryProgress = min(100, max(0, ($residentRespiratoryCount / 50) * 100));
} else {
    $residentRespiratoryProgress = 0;
}
$residentNutritionCount = (int) residentSurveillanceValue($surveillance_metrics, 'nutrition_count', 0);
$residentNutritionTrend = residentCountBasedTrend($residentNutritionCount)['label'];
$residentNutritionMeta = trim((string) residentSurveillanceValue($surveillance_metrics, 'nutrition_meta', 'No active cases reported.')) ?: 'No active cases reported.';
$residentNutritionProgress = (int) residentSurveillanceValue($surveillance_metrics, 'nutrition_progress', 0);
if ($residentNutritionCount > 0) {
    $residentNutritionProgress = min(100, max(0, ($residentNutritionCount / 50) * 100));
} else {
    $residentNutritionProgress = 0;
}

$residentUpdatedAtText = !empty($residentUpdatedAt)
    ? 'Latest record update: ' . date('M d, Y', strtotime($residentUpdatedAt))
    : 'Latest record update: No updates yet';

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
        $announcementStmt = $announcementPdo->prepare(
                "SELECT title, message, service_date, location,
                    CASE WHEN state IN ('upcoming', 'now') THEN state ELSE 'upcoming' END AS state,
                    created_at
             FROM health_announcements
                 WHERE is_active = 1 AND (state IS NULL OR state = '' OR state IN ('upcoming', 'now'))
             ORDER BY COALESCE(service_date, '9999-12-31') ASC, created_at DESC
             LIMIT 10"
        );
        $announcementStmt->execute();
        $healthAnnouncements = $announcementStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($healthAnnouncements as $healthAnnouncement) {
            $announcementState = $healthAnnouncement['state'] ?? 'upcoming';
            if (isset($healthAnnouncementsByState[$announcementState])) {
                $healthAnnouncementsByState[$announcementState][] = $healthAnnouncement;
            }
        }
    }
} catch (Exception $e) {
    error_log('Dashboard Announcements Fetch Error: ' . $e->getMessage());
}




if (isset($_SESSION['appointmentSuccessMessage'])) {
    $appointmentSuccessMessage = $_SESSION['appointmentSuccessMessage'];
    unset($_SESSION['appointmentSuccessMessage']); 
}




$isFormSubmission = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book_appointment');

// Minimum booking date: residents must book at least 1 day prior (tomorrow or later)
$minBookingDate = date('Y-m-d', strtotime('+1 day'));
$maxBookingDate = (new DateTimeImmutable('today'))->modify('+1 month')->format('Y-m-d');




try {

    $pdo = getDatabaseConnection();
    
    if ($pdo === false) {
        $appointmentErrorMessage = 'Database connection error. Please try again later.';
    } else {
        if (!ensureAppointmentPatientNumberSchema($pdo)) {
            throw new RuntimeException('Unable to initialize appointment queue numbers.');
        }

        $today = date('Y-m-d');
        
        
        
        $sqlAppointments = "
            SELECT 
                appointment_id,
                appointment_date,
                appointment_time,
                purpose,
                status,
                patient_number,
                resident_id
            FROM appointment
            WHERE resident_id = ? 
            AND appointment_date >= ?
            AND status IN ('scheduled', 'pending', 'approved', 'rescheduled', 'flagged')
            AND status NOT IN ('completed')
            ORDER BY appointment_date ASC, appointment_time ASC
            LIMIT 50
        ";
        
        $stmtAppointments = $pdo->prepare($sqlAppointments);
        $stmtAppointments->execute([$resident_id, $today]);
        
        
        $appointments = $stmtAppointments->fetchAll(PDO::FETCH_ASSOC);
        $todayAppointments = array_values(array_filter($appointments, static function ($appointment) use ($today) {
            return ($appointment['appointment_date'] ?? '') === $today;
        }));
        $bookedAppointments = array_values(array_filter($appointments, static function ($appointment) use ($today) {
            return ($appointment['appointment_date'] ?? '') > $today;
        }));
        
        error_log("Dashboard: Resident {$resident_id} - Appointments fetched: " . count($appointments));
    }
} catch (Exception $e) {
    error_log("Dashboard Appointments Fetch Error: " . $e->getMessage());
    $appointmentErrorMessage = 'Unable to load appointment data. Please refresh the page.';
}

try {
    $requestPdo = getDatabaseConnection();
    if ($requestPdo !== false && ensureFileRequestsTable($requestPdo)) {
        $requestStmt = $requestPdo->prepare("SELECT request_id, requested_file, status, response_note, processed_at, created_at FROM file_requests WHERE resident_id = ? ORDER BY created_at DESC LIMIT 20");
        $requestStmt->execute([$resident_id]);
        $fileRequests = $requestStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
} catch (Exception $e) {
    error_log('Resident file request load error: ' . $e->getMessage());
}




try {
    
    $pdo = getDatabaseConnection();
    
    if ($pdo !== false) {
        
        
        
        
        
        ensureMedicalHistoryAppointmentColumns($pdo);
        $medicalHistory = [];
        
        
        try {
            $sqlHistory1 = "
                SELECT 
                    pr.record_id,
                    pr.resident_id,
                    pr.health_worker_id,
                    pr.visit_date,
                    pr.diagnosis as visit_type,
                    'patient_record' as history_source,
                    pr.diagnosis,
                    pr.treatment,
                    pr.notes,
                    hw.full_name as bhw_name,
                    hw.role,
                    NULL as appointment_id,
                    pr.created_at as consulted_at,
                    pr.created_at
                FROM patient_record pr
                LEFT JOIN health_worker hw ON pr.health_worker_id = hw.worker_id
                WHERE pr.resident_id = ?
                ORDER BY pr.visit_date DESC
                LIMIT 50
            ";
            
            $stmtHistory1 = $pdo->prepare($sqlHistory1);
            $stmtHistory1->execute([$resident_id]);
            $records1 = $stmtHistory1->fetchAll(PDO::FETCH_ASSOC);
            $medicalHistory = array_merge($medicalHistory, $records1);
            error_log("Dashboard: Resident {$resident_id} - PATIENT_RECORD fetched: " . count($records1));
        } catch (Exception $e) {
            error_log("Dashboard: PATIENT_RECORD query failed (table may not exist): " . $e->getMessage());
        }
        
        
        try {
            $sqlHistory2 = "
                SELECT 
                    a.appointment_id as record_id,
                    a.resident_id,
                    a.health_worker_id,
                    CONCAT(a.appointment_date, ' ', COALESCE(a.appointment_time, '12:00:00')) as visit_date,
                    a.purpose as visit_type,
                    'appointment' as history_source,
                    NULL as diagnosis,
                    NULL as treatment,
                    NULL as notes,
                    hw2.full_name as bhw_name,
                    hw2.role,
                    a.appointment_id as appointment_id,
                    a.updated_at as consulted_at,
                    a.updated_at as created_at
                FROM appointment a
                LEFT JOIN health_worker hw2 ON a.health_worker_id = hw2.worker_id
                WHERE a.resident_id = ?
                    AND a.status = 'completed'
                ORDER BY CONCAT(a.appointment_date, ' ', COALESCE(a.appointment_time, '12:00:00')) DESC
                LIMIT 50
            ";
            
            $stmtHistory2 = $pdo->prepare($sqlHistory2);
            $stmtHistory2->execute([$resident_id]);
            $records2 = $stmtHistory2->fetchAll(PDO::FETCH_ASSOC);
            $medicalHistory = array_merge($medicalHistory, $records2);
            error_log("Dashboard: Resident {$resident_id} - APPOINTMENT completed records fetched: " . count($records2));
        } catch (Exception $e) {
            error_log("Dashboard: APPOINTMENT completed query failed: " . $e->getMessage());
        }
        
        
        try {
            $sqlHistory3 = "
                SELECT 
                    mh.history_id as record_id,
                    mh.user_id as resident_id,
                    NULL as health_worker_id,
                    mh.visit_date,
                    mh.visit_type,
                    'medical_history' as history_source,
                    mh.diagnosis,
                    mh.treatment,
                    mh.notes,
                    mh.bhw_name,
                    NULL as role,
                    mh.appointment_id,
                    COALESCE(mh.consulted_at, mh.created_at) as consulted_at,
                    mh.created_at
                FROM medical_history mh
                WHERE mh.user_id = ?
                ORDER BY mh.visit_date DESC
                LIMIT 50
            ";
            
            $stmtHistory3 = $pdo->prepare($sqlHistory3);
            $stmtHistory3->execute([$resident_id]);
            $records3 = $stmtHistory3->fetchAll(PDO::FETCH_ASSOC);
            $medicalHistory = array_merge($medicalHistory, $records3);
            error_log("Dashboard: Resident {$resident_id} - medical_history fallback fetched: " . count($records3));
        } catch (Exception $e) {
            error_log("Dashboard: medical_history fallback query failed (table may not exist): " . $e->getMessage());
        }
        
        
        try {
            $sqlHistory4 = "
                SELECT 
                    a.appointment_id as record_id,
                    a.resident_id,
                    a.health_worker_id,
                    CONCAT(a.appointment_date, ' ', COALESCE(a.appointment_time, '12:00:00')) as visit_date,
                    a.purpose as visit_type,
                    'no_show' as history_source,
                    NULL as diagnosis,
                    NULL as treatment,
                    NULL as notes,
                    hw2.full_name as bhw_name,
                    hw2.role,
                    a.appointment_id as appointment_id,
                    a.updated_at as consulted_at,
                    a.updated_at as created_at,
                    'no_show' as appointment_status
                FROM appointment a
                LEFT JOIN health_worker hw2 ON a.health_worker_id = hw2.worker_id
                WHERE a.resident_id = ? AND a.status = 'no_show'
                ORDER BY CONCAT(a.appointment_date, ' ', COALESCE(a.appointment_time, '12:00:00')) DESC
                LIMIT 50
            ";
            
            $stmtHistory4 = $pdo->prepare($sqlHistory4);
            $stmtHistory4->execute([$resident_id]);
            $records4 = $stmtHistory4->fetchAll(PDO::FETCH_ASSOC);
            $medicalHistory = array_merge($medicalHistory, $records4);
            error_log("Dashboard: Resident {$resident_id} - NO SHOW appointments fetched: " . count($records4));
        } catch (Exception $e) {
            error_log("Dashboard: NO SHOW appointments query failed: " . $e->getMessage());
        }
        
        
        usort($medicalHistory, function($a, $b) {
            $dateA = strtotime($a['visit_date'] ?? '1970-01-01');
            $dateB = strtotime($b['visit_date'] ?? '1970-01-01');
            if ($dateB === $dateA) {
                $priority = ['medical_history' => 0, 'patient_record' => 1, 'appointment' => 2, 'no_show' => 3];
                return ($priority[$a['history_source'] ?? ''] ?? 4) <=> ($priority[$b['history_source'] ?? ''] ?? 4);
            }
            return ($dateB > $dateA) ? 1 : -1;
        });
        
        
        $seenRecords = [];
        $medicalHistory = array_filter($medicalHistory, function($record) use (&$seenRecords) {
            $recordKey = ($record['history_source'] ?? 'record') . '_' . ($record['record_id'] ?? '') . '_' . ($record['visit_date'] ?? '');
            if (in_array($recordKey, $seenRecords)) {
                return false;
            }
            $seenRecords[] = $recordKey;
            return true;
        });
        
        
        $visitGroups = [];
        $noShowRecords = [];
        foreach ($medicalHistory as $record) {
            if (($record['history_source'] ?? '') === 'no_show') {
                $noShowRecords[] = $record;
                continue;
            }

            $visitTimestamp = strtotime($record['visit_date'] ?? '');
            $visitDescription = strtolower(trim((string) ($record['diagnosis'] ?? $record['visit_type'] ?? '')));
            $visitDescription = preg_replace('/\s+/', ' ', $visitDescription);
            if ($visitTimestamp === false || $visitDescription === '') {
                $uniqueKey = 'unique:' . ($record['history_source'] ?? 'record') . ':' . ($record['record_id'] ?? count($visitGroups));
                $visitGroups[$uniqueKey]['__unique'][] = $record;
                continue;
            }

            $visitKey = date('Y-m-d', $visitTimestamp) . '|' . $visitDescription;
            $source = $record['history_source'] ?? 'record';
            $appointmentId = (int) ($record['appointment_id'] ?? 0);
            if ($appointmentId > 0) {
                $visitGroups['appointment:' . $appointmentId][$source][] = $record;
            } else {
                $visitGroups[$visitKey][$source][] = $record;
            }
        }

        $sourcePriority = ['medical_history', 'patient_record', 'appointment'];
        $deduplicatedHistory = $noShowRecords;
        foreach ($visitGroups as $sourceGroups) {
            if (isset($sourceGroups['__unique'])) {
                $deduplicatedHistory = array_merge($deduplicatedHistory, $sourceGroups['__unique']);
                continue;
            }

            $coveredRecords = 0;
            foreach ($sourcePriority as $source) {
                $sourceRecords = $sourceGroups[$source] ?? [];
                $deduplicatedHistory = array_merge($deduplicatedHistory, array_slice($sourceRecords, $coveredRecords));
                $coveredRecords = max($coveredRecords, count($sourceRecords));
            }
        }

        usort($deduplicatedHistory, function($a, $b) {
            return (strtotime($b['visit_date'] ?? '1970-01-01') ?: 0) <=> (strtotime($a['visit_date'] ?? '1970-01-01') ?: 0);
        });
        $medicalHistory = array_values($deduplicatedHistory);
    }
} catch (Exception $e) {
    error_log("Dashboard Medical History Fetch Error: " . $e->getMessage());
    
}


$availableMedicines = [];
try {
    $pdo = getDatabaseConnection();
    
    if ($pdo !== false) {
        
        
        $sqlMedicines = "
            SELECT 
                medicine_id,
                name AS medicine_name,
                available AS stock_quantity,
                status,
                COALESCE(dosage, '') AS dosage
            FROM medicine
            WHERE COALESCE(is_deleted, 0) = 0
              AND available > 0
            ORDER BY name ASC
            LIMIT 50
        ";
        
        $stmtMedicines = $pdo->prepare($sqlMedicines);
        $stmtMedicines->execute();
        $availableMedicines = $stmtMedicines->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Dashboard: Available medicines fetched: " . count($availableMedicines));
    }
} catch (Exception $e) {
    error_log("Dashboard Available Medicines Fetch Error: " . $e->getMessage());
    $availableMedicines = [];
}




if ($isFormSubmission) {
    $appointmentDate = trim($_POST['appointment_date'] ?? '');
    $appointmentTime = trim(($_POST['appointment_time'] ?? '') ?: ($_POST['preferred_time'] ?? ''));
    $appointmentPurpose = trim($_POST['reason'] ?? '');
    $customReason = trim($_POST['reason_custom'] ?? '');

    if ($appointmentPurpose === 'Other') {
        $appointmentPurpose = $customReason;
    }

    if (!empty($appointmentTime)) {
        $timestamp = strtotime($appointmentTime);
        if ($timestamp !== false) {
            $appointmentTime = date('H:i', $timestamp);
        }
    }
    
    $appointmentErrorMessage = '';
    
    
    if (empty($appointmentDate) || empty($appointmentTime) || empty($appointmentPurpose)) {
        $appointmentErrorMessage = 'All fields are required.';
    } elseif ($appointmentDate < $minBookingDate) {
        $appointmentErrorMessage = 'Appointments must be booked at least 1 day in advance. Earliest available date is ' . date('M d, Y', strtotime($minBookingDate)) . '.';
    } elseif ($appointmentDate > $maxBookingDate) {
        $appointmentErrorMessage = 'Appointments can only be booked up to one month from today.';
    } elseif ((int) date('N', strtotime($appointmentDate)) >= 6) {
        $appointmentErrorMessage = 'Appointments are available Monday through Friday only.';
    } else if (strtotime($appointmentDate . ' ' . $appointmentTime) < time()) {
        $appointmentErrorMessage = 'Appointment date and time must be in the future.';
    } else {
        
        try {
            $pdo = getDatabaseConnection();
            
            if ($pdo === false) {
                $appointmentErrorMessage = 'Database connection error. Please try again later.';
            } else {
                    $bookingResult = bookAppointment($resident_id, $appointmentDate, $appointmentTime, $appointmentPurpose, $pdo);

                    if (!empty($bookingResult['success'])) {
                        $_SESSION['appointmentSuccessMessage'] = 'Appointment scheduled successfully! Your appointment has been booked for ' . date('M d, Y', strtotime($appointmentDate)) . ' at ' . date('g:i A', strtotime($appointmentTime)) . '.';

                        echo '<script>window.location.href = window.location.href;</script>';
                        exit;
                    }
                    $appointmentErrorMessage = $bookingResult['message'] ?? 'Failed to schedule appointment. Please try again.';
                }
            } catch (Exception $e) {
            $appointmentErrorMessage = 'An error occurred while scheduling your appointment.';
            error_log("Appointment Booking Error: " . $e->getMessage());
        }
    }
}
?>

<main class="w-full"><section class="bg-gradient-to-br from-[#454B1B]/10 to-white border-b border-slate-200 py-6 sm:py-8 lg:py-12">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-start sm:items-center justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <h1 class="text-xl sm:text-2xl lg:text-3xl xl:text-4xl font-heading font-extrabold text-slate-900 mb-1 sm:mb-2 leading-tight">
                        Welcome to Your Health Dashboard
                    </h1>
                    <p class="text-xs sm:text-sm lg:text-base text-slate-600">
                        Manage appointments, view health history, and access your patient ID
                    </p>
                </div>
            </div>
        </div>
    </section><div class="w-full max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
        <section id="community-announcements" class="mb-6 rounded-lg border border-[#454B1B]/20 bg-[#454B1B]/[0.06] p-4 sm:p-6" aria-labelledby="healthAnnouncementsHeading">
            <div class="mb-4 flex items-center gap-3">
                <i class="fa-solid fa-bullhorn text-xl text-[#454B1B]"></i>
                <div>
                    <h2 id="healthAnnouncementsHeading" class="text-lg font-heading font-bold text-[#303512]">Community Health Announcements</h2>
                    <p class="text-sm text-[#454B1B]">Updates and free services from your health workers.</p>
                </div>
            </div>
            <div class="mb-4 flex gap-2 overflow-x-auto pb-1" role="tablist" aria-label="Announcement states">
                <?php foreach (['upcoming' => 'Upcoming', 'now' => 'Now'] as $announcementState => $announcementStateLabel): ?>
                    <button type="button" data-resident-announcement-tab="<?php echo $announcementState; ?>" role="tab" aria-selected="<?php echo $announcementState === 'upcoming' ? 'true' : 'false'; ?>" class="shrink-0 rounded-full border px-4 py-2 text-sm font-semibold transition <?php echo $announcementState === 'upcoming' ? 'border-[#454B1B] bg-[#454B1B] text-white' : 'border-[#454B1B]/25 bg-white text-[#454B1B] hover:border-[#454B1B]'; ?>">
                        <?php echo $announcementStateLabel; ?> <span class="ml-1 opacity-75">(<?php echo count($healthAnnouncementsByState[$announcementState]); ?>)</span>
                    </button>
                <?php endforeach; ?>
            </div>

            <?php foreach (['upcoming', 'now'] as $announcementState): ?>
                <div data-resident-announcement-panel="<?php echo $announcementState; ?>" role="tabpanel" <?php echo $announcementState === 'upcoming' ? '' : 'hidden'; ?> >
                    <?php if (empty($healthAnnouncementsByState[$announcementState])): ?>
                        <div class="rounded-lg border border-dashed border-[#454B1B]/25 bg-white/60 p-6 text-center text-sm text-[#454B1B]">No announcements right now.</div>
                    <?php else: ?>
                        <div class="relative" data-resident-announcement-carousel="<?php echo $announcementState; ?>">
                            <div class="overflow-hidden">
                                <div class="flex transition-transform duration-300 ease-out" data-resident-announcement-track="<?php echo $announcementState; ?>" tabindex="0" aria-label="<?php echo ucfirst($announcementState); ?> announcements">
                            <?php foreach ($healthAnnouncementsByState[$announcementState] as $announcement): ?>
                            <article class="min-w-full rounded-lg border border-[#454B1B]/20 bg-white p-4 shadow-sm">
                                <div class="mb-3 flex items-start justify-between gap-3">
                                    <h3 class="font-heading font-bold text-slate-900"><?php echo htmlspecialchars($announcement['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <span class="shrink-0 rounded-full bg-[#454B1B]/10 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-[#454B1B]"><?php echo htmlspecialchars($announcementState); ?></span>
                                </div>
                                <p class="whitespace-pre-line text-sm leading-relaxed text-slate-700"><?php echo htmlspecialchars($announcement['message'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs font-semibold text-slate-500">
                                    <span><i class="fa-solid fa-clock mr-1"></i>Created <?php echo date('M d, Y', strtotime($announcement['created_at'])); ?></span>
                                    <?php if (!empty($announcement['service_date'])): ?><span><i class="fa-solid fa-calendar-day mr-1"></i><?php echo date('M d, Y', strtotime($announcement['service_date'])); ?></span><?php endif; ?>
                                    <?php if (!empty($announcement['location'])): ?><span><i class="fa-solid fa-location-dot mr-1"></i><?php echo htmlspecialchars($announcement['location'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
                                </div>
                            </article>
                            <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center justify-between gap-4">
                                <button type="button" data-resident-announcement-previous="<?php echo $announcementState; ?>" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-[#454B1B]/25 bg-white text-[#454B1B] transition hover:bg-[#454B1B] hover:text-white" aria-label="Previous announcement">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </button>
                                <div class="flex items-center justify-center gap-2" role="tablist" aria-label="<?php echo ucfirst($announcementState); ?> announcement pages">
                                    <?php foreach ($healthAnnouncementsByState[$announcementState] as $announcementIndex => $announcement): ?>
                                        <button type="button" data-resident-announcement-dot="<?php echo $announcementState; ?>" data-resident-announcement-index="<?php echo $announcementIndex; ?>" class="h-2 w-2 rounded-full bg-[#454B1B]/25 transition" aria-label="Show announcement <?php echo $announcementIndex + 1; ?>"></button>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" data-resident-announcement-next="<?php echo $announcementState; ?>" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-[#454B1B]/25 bg-white text-[#454B1B] transition hover:bg-[#454B1B] hover:text-white" aria-label="Next announcement">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8"><div class="lg:col-span-2 space-y-6"><div id="schedule-appointment" class="resident-mobile-section bg-white rounded-lg shadow-md border border-slate-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-[#454B1B]/5 to-white border-b border-slate-200 px-4 sm:px-6 py-4 sm:py-5">
                        <div class="flex items-center gap-3 mb-2">
                            <i class="fa-solid fa-calendar-plus text-xl sm:text-2xl" style="color: #454B1B;"></i>
                            <h2 class="text-lg sm:text-xl font-heading font-bold text-slate-900">Schedule Appointment</h2>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-600 ml-8">Book a consultation with our health workers</p>
                    </div>

                    <div class="p-4 sm:p-6"><?php if (!empty($appointmentSuccessMessage)): ?>
                        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg flex items-start gap-3">
                            <i class="fa-solid fa-circle-check text-green-600 mt-1 flex-shrink-0"></i>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-green-900"><?php echo htmlspecialchars($appointmentSuccessMessage); ?></p>
                            </div>
                        </div>
                        <?php endif; ?><?php if (!empty($appointmentErrorMessage)): ?>
                        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3">
                            <i class="fa-solid fa-circle-exclamation text-red-600 mt-1 flex-shrink-0"></i>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-red-900"><?php echo htmlspecialchars($appointmentErrorMessage); ?></p>
                            </div>
                        </div>
                        <?php endif; ?><form method="POST" action="" class="space-y-4">
                            <input type="hidden" name="action" value="book_appointment"><div>
                                <label id="schedule_date_label" class="mb-2 block text-sm font-semibold text-slate-900">
                                    <i class="fa-solid fa-calendar mr-2" style="color: #454B1B;"></i>Preferred Date
                                </label>
                                <div data-weekday-calendar data-target="preferred_date" data-min-date="<?php echo htmlspecialchars($minBookingDate, ENT_QUOTES, 'UTF-8'); ?>" data-max-date="<?php echo htmlspecialchars($maxBookingDate, ENT_QUOTES, 'UTF-8'); ?>" class="relative">
                                    <input type="date" id="preferred_date" name="appointment_date" tabindex="-1" aria-hidden="true" class="sr-only" min="<?php echo htmlspecialchars($minBookingDate, ENT_QUOTES, 'UTF-8'); ?>" max="<?php echo htmlspecialchars($maxBookingDate, ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="button" data-calendar-toggle aria-labelledby="schedule_date_label" aria-expanded="false" class="flex min-h-12 w-full items-center justify-between gap-3 rounded-lg border border-slate-300 bg-white px-3 py-2 text-left text-sm text-slate-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#454B1B] sm:px-4 sm:py-3 sm:text-base dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                                        <span data-calendar-display>Select a weekday</span>
                                        <i class="fa-solid fa-calendar-days shrink-0 text-[#454B1B] dark:text-lime-300" aria-hidden="true"></i>
                                    </button>
                                    <div data-calendar-popover class="absolute left-0 top-full z-30 mt-2 hidden w-full max-w-sm rounded-xl border border-slate-200 bg-white p-3 shadow-xl dark:border-slate-700 dark:bg-slate-900 sm:p-4">
                                        <div class="mb-3 flex items-center justify-between gap-2">
                                            <button type="button" data-calendar-prev aria-label="Previous month" class="flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-[#454B1B] disabled:cursor-not-allowed dark:text-slate-200 dark:hover:bg-slate-800"><i class="fas fa-chevron-left" aria-hidden="true"></i></button>
                                            <p data-calendar-month class="text-sm font-bold text-slate-900 dark:text-white"></p>
                                            <button type="button" data-calendar-next aria-label="Next month" class="flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-[#454B1B] dark:text-slate-200 dark:hover:bg-slate-800"><i class="fas fa-chevron-right" aria-hidden="true"></i></button>
                                        </div>
                                        <div class="mb-1 grid grid-cols-5 gap-1 text-center text-xs font-semibold text-slate-500 dark:text-slate-400">
                                            <span data-calendar-weekday>Mon</span><span data-calendar-weekday>Tue</span><span data-calendar-weekday>Wed</span><span data-calendar-weekday>Thu</span><span data-calendar-weekday>Fri</span>
                                        </div>
                                        <div data-calendar-grid role="grid" aria-label="Available weekdays" class="grid grid-cols-5 gap-1"></div>
                                    </div>
                                </div>
                            </div><div>
                                <label for="preferred_time" class="block text-sm font-semibold text-slate-900 mb-2">
                                    <i class="fa-solid fa-clock mr-2" style="color: #454B1B;"></i>Preferred Time
                                </label>
                                <select name="preferred_time" id="preferred_time" class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#454B1B] focus:border-transparent outline-none text-sm sm:text-base">
                                    <option value="">Select preferred time</option>
                                    <option value="08:00 AM">08:00 AM</option>
                                    <option value="09:00 AM">09:00 AM</option>
                                    <option value="10:00 AM">10:00 AM</option>
                                    <option value="11:00 AM">11:00 AM</option>
                                    <option value="01:00 PM">01:00 PM</option>
                                    <option value="02:00 PM">02:00 PM</option>
                                    <option value="03:00 PM">03:00 PM</option>
                                    <option value="04:00 PM">04:00 PM</option>
                                </select>
                                <div id="time-slot-grid" class="grid grid-cols-2 sm:grid-cols-4 gap-3"></div>
                                <input type="hidden" name="appointment_time" id="selected_time">
                                <p id="selected_time_summary" class="mt-2 text-sm text-slate-700 hidden"></p>
                                <p id="schedule_time_note" class="mt-2 text-sm text-slate-500 hidden"></p>
                            </div><div>
                                <label for="visit_reason" class="block text-sm font-semibold text-slate-900 mb-2">
                                    <i class="fa-solid fa-stethoscope mr-2" style="color: #454B1B;"></i>Reason for Visit
                                </label>
                                <select id="visit_reason" name="reason_type" required
                                    class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#454B1B] focus:border-transparent outline-none text-sm sm:text-base">
                                    <option value="">Select a reason</option>
                                    <option value="General Checkup">General Checkup</option>
                                    <option value="Vaccination">Vaccination</option>
                                    <option value="Treatment">Treatment</option>
                                    <option value="Consultation">Consultation</option>
                                    <option value="Follow-up">Follow-up</option>
                                    <option value="Other">Other</option>
                                </select>
                                <input type="hidden" name="reason" id="reason" value="">

                                <div id="custom_reason_wrapper" class="mt-3 hidden">
                                    <label for="reason_custom" class="block text-sm font-semibold text-slate-900 mb-2">
                                        <i class="fa-solid fa-pen-to-square mr-2" style="color: #454B1B;"></i>Specify Reason
                                    </label>
                                    <input type="text" id="reason_custom" name="reason_custom" maxlength="250"
                                        placeholder="Please describe the reason for your visit"
                                        class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#454B1B] focus:border-transparent outline-none text-sm sm:text-base">
                                </div>
                            </div><button id="bookAppointmentBtn" type="submit" class="w-full px-4 sm:px-6 py-3 sm:py-4 bg-[#454B1B] hover:bg-[#363812] text-white font-semibold rounded-lg transition-colors text-sm sm:text-base">
                                <i class="fa-solid fa-check mr-2"></i>Book Appointment
                            </button>
                        </form>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const reasonSelect = document.getElementById('visit_reason');
                                const customReasonWrapper = document.getElementById('custom_reason_wrapper');
                                const customReasonInput = document.getElementById('reason_custom');
                                const reasonHiddenField = document.getElementById('reason');

                                function syncCustomReason() {
                                    const otherSelected = reasonSelect && reasonSelect.value === 'Other';
                                    if (customReasonWrapper) {
                                        customReasonWrapper.classList.toggle('hidden', !otherSelected);
                                    }
                                    if (customReasonInput) {
                                        customReasonInput.required = otherSelected;
                                        if (!otherSelected) {
                                            customReasonInput.value = '';
                                        }
                                    }
                                }

                                if (reasonSelect) {
                                    reasonSelect.addEventListener('change', syncCustomReason);
                                    syncCustomReason();
                                }

                                const form = reasonSelect && reasonSelect.closest('form');
                                if (form) {
                                    form.addEventListener('submit', function(event) {
                                        if (!reasonSelect) return;

                                        if (reasonSelect.value === 'Other') {
                                            const customValue = customReasonInput ? customReasonInput.value.trim() : '';
                                            if (!customValue) {
                                                event.preventDefault();
                                                if (customReasonInput) {
                                                    customReasonInput.focus();
                                                    customReasonInput.setAttribute('placeholder', 'Please enter your custom reason');
                                                }
                                                return;
                                            }
                                            if (reasonHiddenField) {
                                                reasonHiddenField.value = customValue;
                                            }
                                        } else if (reasonHiddenField) {
                                            reasonHiddenField.value = reasonSelect.value;
                                        }
                                    });
                                }
                            });
                        </script><div class="mt-6 pt-6 border-t border-slate-200">
                            <div class="flex items-center justify-center gap-3 mb-4 sm:justify-start">
                                <i class="fa-solid fa-list-check shrink-0 text-lg sm:text-2xl" style="color: #454B1B;"></i>
                                <div class="min-w-0 text-center sm:text-left">
                                    <h3 class="text-base sm:text-xl font-heading font-bold text-slate-900">Your Appointments</h3>
                                    <p class="text-xs sm:text-sm text-slate-600">View today’s schedule or your other booked visits.</p>
                                </div>
                            </div>

                            <?php
                            $appointmentTabs = [
                                'today' => [
                                    'label' => 'Today',
                                    'appointments' => $todayAppointments,
                                    'empty_title' => 'No appointments scheduled for today.',
                                    'empty_text' => 'Your appointment for today will appear here.'
                                ],
                                'booked' => [
                                    'label' => 'Booked',
                                    'appointments' => $bookedAppointments,
                                    'empty_title' => 'No other booked appointments.',
                                    'empty_text' => 'Appointments booked for future dates will appear here.'
                                ]
                            ];
                            ?>
                            <div class="appointments-list">
                                <div class="mb-4 flex gap-2 border-b border-slate-200" role="tablist" aria-label="Appointment schedule">
                                    <?php foreach ($appointmentTabs as $tabKey => $tab): ?>
                                        <button type="button" data-resident-appointment-tab="<?php echo $tabKey; ?>" role="tab" aria-controls="resident-appointment-panel-<?php echo $tabKey; ?>" aria-selected="<?php echo $tabKey === 'today' ? 'true' : 'false'; ?>" class="border-b-2 px-4 py-2 text-sm font-semibold transition <?php echo $tabKey === 'today' ? 'border-[#454B1B] text-[#454B1B]' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'; ?>">
                                            <?php echo $tab['label']; ?> <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600"><?php echo count($tab['appointments']); ?></span>
                                        </button>
                                    <?php endforeach; ?>
                                </div>

                                <?php foreach ($appointmentTabs as $tabKey => $tab): ?>
                                    <div id="resident-appointment-panel-<?php echo $tabKey; ?>" data-resident-appointment-panel="<?php echo $tabKey; ?>" role="tabpanel" <?php echo $tabKey === 'today' ? '' : 'hidden'; ?>>
                                        <?php if (empty($tab['appointments'])): ?>
                                            <div class="rounded-lg border-2 border-dashed border-slate-200 bg-slate-50 p-6 text-center sm:p-8">
                                                <i class="fa-solid fa-calendar-xmark mb-3 block text-3xl text-slate-300 sm:text-4xl"></i>
                                                <p class="mb-2 text-sm font-semibold text-slate-600 sm:text-base"><?php echo $tab['empty_title']; ?></p>
                                                <p class="text-xs text-slate-500 sm:text-sm"><?php echo $tab['empty_text']; ?></p>
                                            </div>
                                        <?php else: ?>
                                            <div class="space-y-3 sm:space-y-4">
                                                <?php foreach ($tab['appointments'] as $appointment):
                                                    $statusColor = 'bg-yellow-100 text-yellow-800';
                                                    if ($appointment['status'] === 'approved') {
                                                        $statusColor = 'bg-blue-100 text-blue-800';
                                                    } elseif ($appointment['status'] === 'rescheduled') {
                                                        $statusColor = 'bg-violet-100 text-violet-800 border border-violet-200';
                                                    } elseif ($appointment['status'] === 'flagged') {
                                                        $statusColor = 'bg-amber-100 text-amber-800 border border-amber-200';
                                                    }
                                                ?>
                                                    <div class="rounded-lg border border-slate-200 p-3 transition-shadow hover:shadow-md sm:p-4">
                                                        <div class="flex items-start justify-between gap-3">
                                                            <div class="min-w-0 flex-1">
                                                                <div class="mb-2 flex flex-wrap items-center gap-2">
                                                                    <span class="text-xs font-semibold text-slate-600"><i class="fa-solid fa-calendar mr-1"></i><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></span>
                                                                    <span class="text-xs font-semibold text-slate-600"><i class="fa-solid fa-clock mr-1"></i><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></span>
                                                                    <span class="<?php echo $statusColor; ?> rounded px-2 py-1 text-xs font-semibold capitalize"><?php echo $appointment['status'] === 'flagged' ? 'Follow-up' : ucfirst($appointment['status']); ?></span>
                                                                </div>
                                                                <p class="text-sm font-semibold text-slate-900 sm:text-base"><?php echo htmlspecialchars($appointment['purpose']); ?></p>
                                                            </div>
                                                            <div class="shrink-0 whitespace-nowrap text-right text-xs font-semibold text-slate-600">Patient No: <?php echo (int) $appointment['patient_number']; ?></div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <script>
                                document.querySelectorAll('[data-resident-appointment-tab]').forEach(function (tab) {
                                    tab.addEventListener('click', function () {
                                        const selectedTab = tab.dataset.residentAppointmentTab;
                                        document.querySelectorAll('[data-resident-appointment-tab]').forEach(function (otherTab) {
                                            const isSelected = otherTab.dataset.residentAppointmentTab === selectedTab;
                                            otherTab.setAttribute('aria-selected', String(isSelected));
                                            otherTab.classList.toggle('border-[#454B1B]', isSelected);
                                            otherTab.classList.toggle('text-[#454B1B]', isSelected);
                                            otherTab.classList.toggle('border-transparent', !isSelected);
                                            otherTab.classList.toggle('text-slate-500', !isSelected);
                                        });
                                        document.querySelectorAll('[data-resident-appointment-panel]').forEach(function (panel) {
                                            panel.hidden = panel.dataset.residentAppointmentPanel !== selectedTab;
                                        });
                                    });
                                });
                            </script>
                        </div>
                    </div>
                </div><div id="file-requests" class="resident-mobile-section bg-white rounded-lg shadow-md border border-slate-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-[#454B1B]/5 to-white border-b border-slate-200 px-4 sm:px-6 py-4 sm:py-5">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-lg sm:text-xl font-heading font-bold text-slate-900">View Medical Record</h2>
                                <p class="text-xs sm:text-sm text-slate-600 mt-1">Request a copy of your health file and download it when approved.</p>
                            </div>
                            <button type="button" onclick="confirmResidentFileRequest()" aria-haspopup="dialog" class="shrink-0 rounded-lg bg-[#454B1B] px-3 py-2 text-xs font-semibold text-white hover:opacity-90"><i class="fas fa-file-circle-plus mr-1"></i> Request File</button>
                        </div>
                    </div>
                    <div class="p-4 sm:p-6">
                        <?php if (($_GET['request_status'] ?? '') === 'submitted'): ?><div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">Your file request was sent to the health worker.</div><?php elseif (($_GET['request_status'] ?? '') === 'deleted'): ?><div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">The file request was deleted.</div><?php endif; ?>
                        <div id="residentFileRequestList" class="space-y-3">
                        <?php if (empty($fileRequests)): ?>
                            <div id="residentFileRequestsEmpty" class="rounded-lg border-2 border-dashed border-slate-200 bg-slate-50 p-6 text-center"><i class="fas fa-folder-open mb-2 text-3xl text-slate-300"></i><p class="text-sm font-semibold text-slate-600">No file requests yet.</p><p class="mt-1 text-xs text-slate-500">Use the Request File button to submit a request from this device.</p></div>
                        <?php else: ?>
                                <?php foreach ($fileRequests as $fileRequest):
                                    $requestStatus = strtolower($fileRequest['status']);
                                    $requestBadge = $requestStatus === 'accepted' ? 'bg-green-100 text-green-800' : ($requestStatus === 'declined' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800');
                                ?>
                                <div class="flex flex-col gap-3 rounded-lg border border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div><p class="font-semibold text-slate-900"><?php echo htmlspecialchars($fileRequest['requested_file']); ?></p><p class="text-xs text-slate-500">Submitted <?php echo date('M d, Y g:i A', strtotime($fileRequest['created_at'])); ?></p><?php if (!empty($fileRequest['response_note'])): ?><p class="mt-1 text-xs text-slate-600"><?php echo htmlspecialchars($fileRequest['response_note']); ?></p><?php endif; ?></div>
                                    <div class="flex flex-wrap items-stretch gap-2"><span class="inline-flex min-h-8 items-center rounded-full px-2 py-1 text-xs font-bold capitalize <?php echo $requestBadge; ?>"><?php echo htmlspecialchars($requestStatus); ?></span><?php if ($requestStatus === 'accepted'): ?><a href="download_file_request.php?request_id=<?php echo (int) $fileRequest['request_id']; ?>" data-offline-record class="flex min-h-8 max-w-full items-center justify-center rounded-lg bg-[#454B1B] px-2 py-1.5 text-[11px] font-semibold leading-tight text-center text-white hover:opacity-90 sm:px-3 sm:py-2 sm:text-xs"><i class="fas fa-print mr-1"></i> Open printable record</a><?php endif; ?><form method="post" action="delete_file_request.php" class="flex min-h-8" onsubmit="confirmResidentFileRequestDelete(event);"><input type="hidden" name="request_id" value="<?php echo (int) $fileRequest['request_id']; ?>"><button type="submit" class="flex min-h-8 items-center justify-center rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50"><i class="fas fa-trash mr-1"></i> Delete</button></form></div>
                                </div>
                                <?php endforeach; ?>
                        <?php endif; ?>
                        </div>
                    </div>
                </div><div id="available-medicines" class="resident-mobile-section bg-white rounded-lg shadow-md border border-slate-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-[#454B1B]/5 to-white border-b border-slate-200 px-4 sm:px-6 py-4 sm:py-5">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-pills text-xl sm:text-2xl" style="color: #454B1B;"></i>
                            <div class="flex-1">
                                <h2 class="text-lg sm:text-xl font-heading font-bold text-slate-900">Available Free Medicines at RHU II</h2>
                                <p class="text-xs text-slate-500 mt-1">Check current stock availability before requesting from your BHW.</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 sm:p-6">
                        <?php if (empty($availableMedicines)): ?><div class="border-2 border-dashed border-slate-200 rounded-lg p-6 sm:p-8 text-center bg-slate-50">
                            <i class="fa-solid fa-bottle-droplet text-3xl sm:text-4xl" style="color: #cbd5e1; margin-bottom: 12px; display: block;"></i>
                            <p class="text-slate-600 font-semibold text-sm sm:text-base mb-2">No medicines currently in stock.</p>
                            <p class="text-xs sm:text-sm text-slate-500">
                                Please check back soon or contact your health worker for availability updates.
                            </p>
                        </div>
                        <?php else: ?><div class="max-h-[400px] overflow-y-auto space-y-2">
                            <?php foreach ($availableMedicines as $medicine): 
                                
                                $lowStockThreshold = 10;
                                $stockStatus = 'Available';
                                $badgeClass = 'bg-green-100 text-green-800';
                                $badgeIcon = 'fa-check-circle';
                                $dosageValue = trim((string)($medicine['dosage'] ?? ''));
                                
                                if ((int) $medicine['stock_quantity'] <= $lowStockThreshold) {
                                    $stockStatus = 'Low Stock';
                                    $badgeClass = 'bg-amber-100 text-amber-800';
                                    $badgeIcon = 'fa-exclamation-circle';
                                }
                            ?>
                            <div class="border border-slate-200 rounded-lg p-3 hover:bg-slate-50 transition-colors">
                                <div class="flex items-start justify-between gap-3 mb-2">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-semibold text-slate-900 text-sm sm:text-base truncate">
                                            <?php echo htmlspecialchars($medicine['medicine_name']); ?>
                                        </h3>
                                        <?php if ($dosageValue !== '' && $dosageValue !== '-') : ?>
                                            <p class="text-xs text-slate-500 font-medium">
                                                <i class="fa-solid fa-capsules mr-1"></i><?php echo htmlspecialchars($dosageValue); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <span class="<?php echo $badgeClass; ?> text-xs font-semibold px-2 py-1 rounded-full flex items-center gap-1 whitespace-nowrap">
                                        <i class="fa-solid <?php echo $badgeIcon; ?>" style="font-size: 12px;"></i>
                                        <?php echo $stockStatus; ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div><div class="mt-4 pt-4 border-t border-slate-200">
                            <p class="text-xs text-slate-500 text-center">
                                <i class="fa-solid fa-info-circle mr-1"></i>
                                All medicines are provided free for registered residents of Barangay Gutad.
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div><div class="space-y-6"><div id="your-patient-id" class="resident-mobile-section bg-white rounded-lg shadow-md border border-slate-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-[#454B1B]/5 to-white border-b border-slate-200 px-4 sm:px-6 py-4 sm:py-5">
                        <h2 class="text-lg sm:text-xl font-heading font-bold text-slate-900">Your Patient ID</h2>
                    </div>
                    <div class="p-4 sm:p-6 text-center">
                        <div id="qrCodeImage" class="mx-auto mb-4 flex items-center justify-center border border-slate-200 rounded-lg bg-white shadow-sm w-40 h-40 sm:w-44 sm:h-44"></div>
                        <div class="text-center">
                            <p class="text-xs sm:text-sm text-slate-600 font-medium mb-1">Your 6-Digit Patient ID:</p>
                            <p class="text-2xl sm:text-3xl font-mono font-bold text-[#454B1B]"><?php echo htmlspecialchars($patient_code); ?></p>
                        </div>
                        <button onclick="downloadQRCode('<?php echo htmlspecialchars($patient_code); ?>')" style="background-color: #454B1B; color: white; padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; font-size: 14px; margin-top: 16px; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                            <i class="fas fa-download"></i> Download QR Code
                        </button>
                    </div>
                </div><div id="community-surveillance" class="resident-mobile-section bg-white rounded-lg shadow-md border border-slate-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-[#454B1B]/5 to-white border-b border-slate-200 px-4 sm:px-6 py-4 sm:py-5">
                        <div class="flex justify-between items-start gap-4">
                            <div>
                                <h2 class="text-xl font-heading font-bold text-slate-900">Resident Surveillance Widget</h2>
                                <p class="max-w-sm text-xs text-slate-500 mt-1 leading-relaxed">
                                    Live tallies group matching items checked in residents' latest medical-history records.
                                </p>
                            </div>
                            <span id="residentReportingPeriodDisplay" class="text-[10px] tracking-wider uppercase font-semibold text-slate-400 text-right leading-tight w-28">REPORTING PERIOD: <?= htmlspecialchars(strtoupper($hasResidentPublishedMetrics ? $residentReportingPeriod : 'NO PUBLISHED UPDATES')); ?></span>
                        </div>
                    </div>
                    <div class="p-4 sm:p-6">
                        <div id="residentPublishedMetricCards" class="resident-metric-scroll mt-6 max-h-[18rem] space-y-5 overflow-y-auto overscroll-contain pr-2 sm:max-h-[24rem] lg:max-h-[28rem]">
                            <?php foreach (($surveillance_metrics['disease_records'] ?? []) as $record):
                                $recordCount = max(0, (int) $record['case_count']);
                                $recordTrend = residentCountBasedTrend($recordCount);
                                $recordProgress = min(100, max(0, ($recordCount / 50) * 100));
                                $recordName = trim((string) $record['disease_name']);
                                $recordMeta = trim((string) $record['meta_notes']) ?: 'No notes provided.';
                            ?>
                                <div class="space-y-2 pb-5" data-resident-metric-card>
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($recordName, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span class="text-[11px] font-bold <?php echo $recordTrend['color']; ?>"><?php echo htmlspecialchars($recordTrend['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                    <p class="text-xs text-slate-500"><?php echo $recordCount; ?> resident<?php echo $recordCount === 1 ? ' is' : 's are'; ?> marked with <?php echo htmlspecialchars($recordName, ENT_QUOTES, 'UTF-8'); ?></p>
                                    <div class="resident-progress-track h-3 overflow-hidden rounded-full bg-slate-100">
                                        <div class="resident-progress-fill h-full rounded-full" style="background: linear-gradient(to right, #454B1B, #ef4444); width: <?php echo $recordProgress; ?>%;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="hidden" id="residentSurveillanceMetricsContainer">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-bold text-slate-900">Dengue Cases (Last 4 Weeks)</span>
                                    <span id="residentDengueTrend" class="text-[11px] font-bold text-red-600"><?= htmlspecialchars($residentDengueTrend); ?></span>
                                </div>
                                <p id="residentDengueDetail" class="text-xs text-slate-500"><?= htmlspecialchars($residentDengueCount > 0 ? ($residentDengueCount . ' confirmed cases | ' . $residentDengueMeta) : $residentDengueMeta); ?></p>
                                <div class="resident-progress-track h-3 bg-slate-100 rounded-full overflow-hidden">
                                    <div id="residentDengueBarFill" class="resident-progress-fill h-full rounded-full transition-all duration-500" style="background: linear-gradient(to right, #454B1B, #ef4444); width: <?= (int) $residentDengueProgress; ?>%;"></div>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-bold text-slate-900">Rabies Vaccination Coverage</span>
                                    <span id="residentRabiesTrend" class="text-[11px] font-bold text-emerald-600"><?= htmlspecialchars($residentRabiesTrend); ?></span>
                                </div>
                                <p id="residentRabiesDetail" class="text-xs text-slate-500"><?= htmlspecialchars($residentRabiesCount > 0 ? ($residentRabiesCount . '% coverage | ' . $residentRabiesMeta) : $residentRabiesMeta); ?></p>
                                <div class="resident-progress-track h-3 bg-slate-100 rounded-full overflow-hidden">
                                    <div id="residentRabiesBarFill" class="resident-progress-fill h-full rounded-full transition-all duration-500" style="background: linear-gradient(to right, #454B1B, #10b981); width: <?= (int) $residentRabiesProgress; ?>%;"></div>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-bold text-slate-900">Respiratory Infections (Seasonal)</span>
                                    <span id="residentRespiratoryTrend" class="text-[11px] font-bold text-blue-600"><?= htmlspecialchars($residentRespiratoryTrend); ?></span>
                                </div>
                                <p id="residentRespiratoryDetail" class="text-xs text-slate-500"><?= htmlspecialchars($residentRespiratoryCount > 0 ? ($residentRespiratoryCount . ' active cases | ' . $residentRespiratoryMeta) : $residentRespiratoryMeta); ?></p>
                                <div class="resident-progress-track h-3 bg-slate-100 rounded-full overflow-hidden">
                                    <div id="residentRespiratoryBarFill" class="resident-progress-fill h-full rounded-full transition-all duration-500" style="background: linear-gradient(to right, #454B1B, #3b82f6); width: <?= (int) $residentRespiratoryProgress; ?>%;"></div>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-bold text-slate-900">Nutrition Deficiency (U5)</span>
                                    <span id="residentNutritionTrend" class="text-[11px] font-bold text-orange-600"><?= htmlspecialchars($residentNutritionTrend); ?></span>
                                </div>
                                <p id="residentNutritionDetail" class="text-xs text-slate-500"><?= htmlspecialchars($residentNutritionCount > 0 ? ($residentNutritionCount . ' cases | ' . $residentNutritionMeta) : $residentNutritionMeta); ?></p>
                                <div class="resident-progress-track h-3 bg-slate-100 rounded-full overflow-hidden">
                                    <div id="residentNutritionBarFill" class="resident-progress-fill h-full rounded-full transition-all duration-500" style="background: linear-gradient(to right, #454B1B, #f97316); width: <?= (int) $residentNutritionProgress; ?>%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><div id="medical-history" class="resident-mobile-section bg-white rounded-lg shadow-md border border-slate-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-[#454B1B]/5 to-white border-b border-slate-200 px-4 sm:px-6 py-4 sm:py-5">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <i class="fa-solid fa-history text-xl sm:text-2xl" style="color: #454B1B;"></i>
                                <h2 class="text-lg sm:text-xl font-heading font-bold text-slate-900">Medical Visit History</h2>
                            </div>
                            <?php if (!empty($medicalHistory)): ?>
                                <button type="button" id="clearMedicalHistoryBtn_resident" onclick="clearMedicalHistoryDisplay(event)" class="px-3 py-1 bg-red-100 text-red-700 text-xs font-semibold rounded-lg hover:bg-red-200 transition-colors duration-200 flex items-center gap-1 whitespace-nowrap">
                                    <i class="fas fa-trash-alt"></i> Clear
                                </button>
                                <button type="button" id="restoreMedicalHistoryBtn_resident" onclick="clearMedicalHistoryClearFlag()" class="px-3 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-lg hover:bg-blue-200 transition-colors duration-200 whitespace-nowrap" style="display:none">Restore all</button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="p-4 sm:p-6"><?php if (empty($medicalHistory)): ?><div class="border-2 border-dashed border-slate-200 rounded-lg p-6 sm:p-8 text-center bg-slate-50">
                            <i class="fa-solid fa-file-medical text-3xl sm:text-4xl" style="color: #cbd5e1; margin-bottom: 12px; display: block;"></i>
                            <p class="text-slate-600 font-semibold text-sm sm:text-base mb-2">No medical history recorded yet.</p>
                            <p class="text-xs sm:text-sm text-slate-500">
                                Once you visit the Rural Health Unit (RHU), your medical records will appear here.
                            </p>
                        </div>
                        <?php else: ?><div class="grid grid-cols-3 gap-2 sm:gap-3 mb-6">
                            <div class="bg-blue-50 rounded-lg p-3 text-center">
                                <div id="medicalHistoryTotalCount" class="text-xl sm:text-2xl font-bold text-blue-700">
                                    <?php echo count($medicalHistory); ?>
                                </div>
                                <div class="text-xs text-blue-600 font-medium">Total Visits</div>
                            </div>
                            <div class="bg-green-50 rounded-lg p-3 text-center">
                                <div id="medicalHistoryLatestDate" class="text-sm sm:text-base font-bold text-green-700">
                                    <?php echo date('M d, Y', strtotime($medicalHistory[0]['visit_date'])); ?>
                                </div>
                                <div class="text-xs text-green-600 font-medium">Latest Visit</div>
                            </div>
                            <div class="bg-orange-50 rounded-lg p-3 text-center">
                                <div class="text-lg sm:text-xl font-bold text-orange-700">
                                    Active
                                </div>
                                <div class="text-xs text-orange-600 font-medium">Status</div>
                            </div>
                        </div><div class="space-y-3 sm:space-y-4 max-h-[500px] overflow-y-auto pr-2" id="medicalHistoryList_resident" style="scrollbar-width: thin; scrollbar-color: #cbd5e1 #f1f5f9;">
                            <?php foreach ($medicalHistory as $index => $record):
                                $historyKey = ($record['history_source'] ?? 'record') . ':' . $record['record_id'] . ':' . hash('sha256', ($record['visit_date'] ?? '') . '|' . ($record['visit_type'] ?? $record['diagnosis'] ?? ''));
                                $historyDate = date('Y-m-d', strtotime($record['visit_date']));
                                $isNoShow = isset($record['appointment_status']) && $record['appointment_status'] === 'no_show';
                                $visitTypeBadgeColor = $isNoShow ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800';
                                $borderColor = $isNoShow ? 'border-red-300' : 'border-slate-200';
                                $bgHover = $isNoShow ? 'hover:bg-red-50' : 'hover:bg-slate-100';
                                $treatmentRaw = trim((string) ($record['treatment'] ?? ''));
                                $decodedClinicalRecord = json_decode($treatmentRaw, true);
                                $hasStructuredClinicalRecord = is_array($decodedClinicalRecord);
                                $clinicalRecord = $hasStructuredClinicalRecord ? $decodedClinicalRecord : [];
                                $historyFlagLabels = [
                                    'asthma' => 'Asthma',
                                    'epilepsy' => 'Epilepsy',
                                    'headache' => 'Sakit sa Ulo',
                                    'diabetes' => 'Diabetes',
                                    'hypertension' => 'Hypertension',
                                    'allergies' => 'Allergies',
                                    'medications' => 'On Medications',
                                    'hospitalization' => 'Hospitalization / Surgery'
                                ];
                                $customHistoryLabels = array_filter((array) ($clinicalRecord['medical_history_options'] ?? []), 'is_string');
                                $medicalHistoryFlags = array_values(array_filter(array_map(
                                    static fn($flag) => $customHistoryLabels[$flag] ?? $historyFlagLabels[$flag] ?? null,
                                    (array) ($clinicalRecord['medical_history'] ?? [])
                                )));
                                $treatmentDisplay = trim((string) ($clinicalRecord['treatment'] ?? ''));
                                $treatmentPlanDisplay = trim((string) ($clinicalRecord['treatment_plan'] ?? ''));
                                if (!$hasStructuredClinicalRecord) {
                                    $treatmentDisplay = $treatmentRaw;
                                }
                                $recommendationsDisplay = trim((string) ($clinicalRecord['recommendations'] ?? ''));
                                $appointmentType = trim((string) ($record['visit_type'] ?? ''));
                                $recordDiagnosis = trim((string) ($record['diagnosis'] ?? ''));
                                $diagnosisDisplay = $recordDiagnosis !== '' ? $recordDiagnosis : $appointmentType;
                                $visitDetails = [
                                    'date' => date('M d, Y \a\t g:i A', strtotime($record['visit_date'])),
                                    'visit_date' => date('Y-m-d', strtotime($record['visit_date'])),
                                    'visit_type' => $appointmentType,
                                    'diagnosis' => $diagnosisDisplay,
                                    'has_diagnosis' => $recordDiagnosis !== '',
                                    'treatment' => $treatmentDisplay,
                                    'treatment_plan' => $treatmentPlanDisplay,
                                    'recommendations' => $recommendationsDisplay,
                                    'medical_history' => $medicalHistoryFlags,
                                    'notes' => (string)($record['notes'] ?? ''),
                                    'provider' => (string)($record['bhw_name'] ?? 'Health Worker'),
                                    'status' => $isNoShow ? 'Missed appointment' : (($record['history_source'] ?? '') === 'appointment' ? 'Completed' : 'Recorded visit'),
                                    'is_no_show' => $isNoShow
                                ];
                            ?>
                            <div data-history-key="<?php echo htmlspecialchars($historyKey, ENT_QUOTES, 'UTF-8'); ?>" data-history-date="<?php echo htmlspecialchars($historyDate, ENT_QUOTES, 'UTF-8'); ?>" class="border <?php echo $borderColor; ?> rounded-lg overflow-hidden transition-shadow <?php echo $isNoShow ? 'bg-red-50/30' : 'hover:shadow-md'; ?>">
                                <button type="button"
                                        class="w-full cursor-pointer text-left p-3 sm:p-4 <?php echo $bgHover; ?> flex items-start justify-between gap-3 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#454B1B]"
                                        data-visit-details="<?php echo htmlspecialchars(json_encode($visitDetails, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>"
                                        aria-haspopup="dialog" aria-controls="medicalVisitModal"
                                        onclick="openMedicalVisitModal(this)">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-2 flex-wrap">
                                            <span class="text-xs font-semibold <?php echo $isNoShow ? 'text-red-600' : 'text-slate-500'; ?>">
                                                <i class="fa-solid fa-calendar mr-1"></i><span data-history-date-label><?php echo date('M d, Y', strtotime($record['visit_date'])); ?></span>
                                            </span>
                                            <span class="text-xs font-semibold <?php echo $isNoShow ? 'text-red-600' : 'text-slate-500'; ?>">
                                                <i class="fa-solid fa-clock mr-1"></i><?php echo date('g:i A', strtotime($record['visit_date'])); ?>
                                            </span>
                                            <span class="<?php echo $visitTypeBadgeColor; ?> text-xs font-semibold px-2 py-1 rounded">
                                                <?php echo $isNoShow ? '⚠️ No Show' : 'Medical Visit'; ?>
                                            </span>
                                            <?php if ($isNoShow): ?>
                                            <span class="text-xs font-bold px-2 py-1 rounded bg-red-200 text-red-900 border border-red-300">
                                                Missed Appointment
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="font-semibold <?php echo $isNoShow ? 'text-red-900' : 'text-slate-900'; ?> text-sm sm:text-base truncate">
                                            <?php echo htmlspecialchars($diagnosisDisplay !== '' ? $diagnosisDisplay : 'Medical visit'); ?>
                                        </p>
                                        <p class="text-xs sm:text-sm <?php echo $isNoShow ? 'text-red-600' : 'text-slate-600'; ?> mt-1">
                                            <i class="fa-solid fa-user-nurse mr-1" style="color: <?php echo $isNoShow ? '#991b1b' : '#454B1B'; ?>;"></i>
                                            <?php echo htmlspecialchars($record['bhw_name'] ?? 'Health Worker'); ?>
                                        </p>
                                    </div><i class="fa-solid fa-circle-info <?php echo $isNoShow ? 'text-red-300' : 'text-slate-400'; ?> mt-1 flex-shrink-0" aria-hidden="true"
                                       style="font-size: 12px;"></i>
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div id="medicalHistoryEmptyAfterClear" class="py-8 text-center text-sm text-slate-500" style="display:none">No newer history has been recorded yet.</div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>

<style>
    .medical-visit-modal {
        width: calc(100vw - 2rem);
        max-width: 56rem;
        max-height: calc(100dvh - 2rem);
        margin: auto;
        padding: 0;
        overflow: visible;
        border: 0;
        background: transparent;
    }

    .medical-visit-modal[open] {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .medical-visit-modal::backdrop {
        background: rgba(0, 0, 0, 0.6);
    }

    .medical-record-section {
        margin-top: 1.25rem;
    }

    .medical-record-section h3 {
        border-bottom: 1.5px solid #111827;
        margin-bottom: 0.5rem;
        padding-bottom: 0.25rem;
    }

    .medical-record-section p {
        min-height: 5rem;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
    }

    @media print {
        @page {
            size: A4;
            margin: 15mm;
        }

        body * {
            visibility: hidden !important;
        }

        #medicalVisitModal,
        #medicalVisitModal * {
            visibility: visible !important;
        }

        #medicalVisitModal {
            position: fixed !important;
            inset: 0 !important;
            width: 100% !important;
            height: 100% !important;
            display: block !important;
            overflow: visible !important;
            background: #ffffff !important;
            padding: 0 !important;
        }

        .medical-visit-dialog {
            width: 100% !important;
            max-width: none !important;
            max-height: none !important;
            overflow: visible !important;
            border: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
        }

        #medicalVisitToolbar {
            display: none !important;
        }

        #medicalVisitDocument {
            width: 100% !important;
            max-width: none !important;
            min-height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
        }
    }
</style>

<dialog id="medicalVisitModal" class="medical-visit-modal" aria-labelledby="medicalVisitModalTitle" aria-hidden="true" onclick="if (event.target === this) closeMedicalVisitModal()">
    <div class="medical-visit-dialog my-auto max-h-[calc(100dvh-2rem)] w-full max-w-4xl overflow-y-auto rounded-lg border border-slate-300 bg-white shadow-2xl" onclick="event.stopPropagation()">
        <div id="medicalVisitToolbar" class="flex items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-3">
            <h2 id="medicalVisitModalTitle" class="text-sm font-semibold text-slate-700">Medical visit record</h2>
            <div class="flex items-center gap-2">
                <button id="medicalVisitModalClose" type="button" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-slate-600 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#454B1B]" aria-label="Close medical record" onclick="closeMedicalVisitModal()">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <article id="medicalVisitDocument" class="medical-record-sheet mx-auto w-full max-w-[210mm] px-6 py-7 text-black sm:px-10 sm:py-10">
            <header class="mb-7 border-b border-slate-300 pb-5 text-center">
                <p class="text-xs font-bold uppercase">Republic of the Philippines</p>
                <p class="mt-1 text-xs font-bold uppercase">Province of Pampanga · Barangay Gutad Health Services</p>
                <h1 class="mt-2 text-base font-extrabold uppercase">Rural Health Unit II</h1>
                <p class="mt-1 text-xs font-bold uppercase tracking-wide">Medical Record</p>
                <p id="medicalVisitStatus" class="mt-2 text-xs font-semibold text-slate-600"></p>
            </header>

            <section class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3" aria-label="Patient details">
                <div><h2 class="text-[10px] font-bold uppercase">Email</h2><p id="medicalPatientEmail" class="mt-1 break-words text-sm"></p></div>
                <div><h2 class="text-[10px] font-bold uppercase">Address</h2><p id="medicalPatientAddress" class="mt-1 break-words text-sm"></p></div>
                <div><h2 class="text-[10px] font-bold uppercase">Contact Number</h2><p id="medicalPatientContact" class="mt-1 break-words text-sm"></p></div>
                <div><h2 class="text-[10px] font-bold uppercase">Patient Name</h2><p id="medicalPatientName" class="mt-1 break-words text-sm"></p></div>
                <div><h2 class="text-[10px] font-bold uppercase">Date of Birth</h2><p id="medicalPatientBirthDate" class="mt-1 text-sm"></p></div>
                <div><h2 class="text-[10px] font-bold uppercase">Visit Date</h2><p id="medicalVisitDate" class="mt-1 text-sm"></p></div>
                <div><h2 class="text-[10px] font-bold uppercase">Patient ID</h2><p id="medicalPatientId" class="mt-1 text-sm"></p></div>
                <div><h2 class="text-[10px] font-bold uppercase">Age</h2><p id="medicalPatientAge" class="mt-1 text-sm"></p></div>
                <div><h2 class="text-[10px] font-bold uppercase">Record Mode</h2><p id="medicalRecordMode" class="mt-1 text-sm"></p></div>
            </section>

            <section class="medical-record-section">
                <h3 class="text-xs font-bold uppercase">Diagnosis / Treatment / Others</h3>
                <p id="medicalVisitDiagnosis" class="text-sm leading-relaxed"></p>
            </section>
            <section class="medical-record-section">
                <h3 class="text-xs font-bold uppercase">Treatment Plan</h3>
                <p id="medicalVisitTreatment" class="text-sm leading-relaxed"></p>
            </section>
            <section class="medical-record-section">
                <h3 class="text-xs font-bold uppercase">Recommendations</h3>
                <p id="medicalVisitRecommendations" class="text-sm leading-relaxed"></p>
            </section>

            <footer class="mt-12 grid grid-cols-1 gap-10 sm:grid-cols-2">
                <div class="pt-6 text-center">
                    <div class="border-t border-black"></div>
                    <p class="mt-1 text-[10px] font-bold uppercase">Patient's Signature / Parent or Guardian</p>
                </div>
                <div class="pt-6 text-center">
                    <p id="medicalVisitProvider" class="mb-1 text-xs"></p>
                    <div class="border-t border-black"></div>
                    <p class="mt-1 text-[10px] font-bold uppercase">Attending BHW / Nurse / Medical Officer</p>
                </div>
            </footer>
        </article>
    </div>
</dialog>

<script>
let lastMedicalVisitTrigger = null;
const residentMedicalProfile = <?php echo json_encode($residentMedicalProfile, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

function setMedicalRecordValue(elementId, value, fallback = 'Not on file') {
    const element = document.getElementById(elementId);
    if (element) element.textContent = value || fallback;
}

window.openMedicalVisitModal = function(trigger) {
    const modal = document.getElementById('medicalVisitModal');
    let visit;
    try {
        visit = JSON.parse(trigger.dataset.visitDetails || '{}');
    } catch (error) {
        return;
    }

    lastMedicalVisitTrigger = trigger;
    setMedicalRecordValue('medicalPatientName', residentMedicalProfile.name);
    setMedicalRecordValue('medicalPatientEmail', residentMedicalProfile.email);
    setMedicalRecordValue('medicalPatientAddress', residentMedicalProfile.address);
    setMedicalRecordValue('medicalPatientContact', residentMedicalProfile.contact);
    setMedicalRecordValue('medicalPatientBirthDate', residentMedicalProfile.birth_date);
    setMedicalRecordValue('medicalPatientId', residentMedicalProfile.patient_id);
    setMedicalRecordValue('medicalPatientAge', residentMedicalProfile.age === '' ? '' : String(residentMedicalProfile.age), 'Not recorded');
    setMedicalRecordValue('medicalRecordMode', residentMedicalProfile.record_mode);
    setMedicalRecordValue('medicalVisitDate', visit.visit_date || (visit.date || '').split(' at ')[0], 'Not recorded');
    setMedicalRecordValue('medicalVisitStatus', visit.is_no_show ? 'Missed appointment' : (visit.status || 'Recorded visit'));

    const diagnosisLines = [];
    if (visit.diagnosis) {
        diagnosisLines.push((visit.has_diagnosis ? 'Diagnosis: ' : 'Appointment type: ') + visit.diagnosis);
    }
    if (visit.has_diagnosis && visit.visit_type && visit.visit_type !== visit.diagnosis) {
        diagnosisLines.push('Appointment type: ' + visit.visit_type);
    }
    if (visit.treatment) diagnosisLines.push('Treatment: ' + visit.treatment);
    const historyFlags = Array.isArray(visit.medical_history) ? visit.medical_history.filter(Boolean) : [];
    if (historyFlags.length) diagnosisLines.push('Relevant medical history: ' + historyFlags.join(', '));
    if (visit.notes) diagnosisLines.push('Other notes: ' + visit.notes);
    setMedicalRecordValue('medicalVisitDiagnosis', diagnosisLines.join('\n'), 'No diagnosis or other details recorded.');
    setMedicalRecordValue('medicalVisitTreatment', visit.treatment_plan, 'No treatment plan recorded.');
    setMedicalRecordValue('medicalVisitRecommendations', visit.recommendations, 'No recommendations recorded.');
    setMedicalRecordValue('medicalVisitProvider', visit.provider ? 'Recorded by: ' + visit.provider : '', '');

    modal.showModal();
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    document.getElementById('medicalVisitModalClose').focus();
};

window.closeMedicalVisitModal = function() {
    const modal = document.getElementById('medicalVisitModal');
    if (!modal || !modal.open) return;
    modal.close();
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    lastMedicalVisitTrigger?.focus();
};

document.addEventListener('keydown', event => {
    if (event.key === 'Escape') window.closeMedicalVisitModal();
});
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
const RESIDENT_QR_CACHE_KEY = 'scanCareResidentQrDataUrl';

function renderCachedResidentQr() {
    const wrapper = document.getElementById('qrCodeImage');
    const cached = localStorage.getItem(RESIDENT_QR_CACHE_KEY);
    if (!wrapper || !cached) {
        return false;
    }

    wrapper.innerHTML = '';
    const img = new Image();
    img.alt = 'Resident QR Code';
    img.className = 'w-full h-full object-contain rounded';
    img.src = cached;
    wrapper.appendChild(img);
    return true;
}

function renderResidentQRCode() {
    const wrapper = document.getElementById('qrCodeImage');
    if (!wrapper || typeof QRCode === 'undefined') {
        return;
    }

    if (!navigator.onLine) {
        if (renderCachedResidentQr()) {
            return;
        }
        wrapper.innerHTML = '<div class="text-xs text-slate-500">QR unavailable offline</div>';
        return;
    }

    wrapper.innerHTML = '';
    const qrUrl = '<?php echo htmlspecialchars($record_url, ENT_QUOTES, 'UTF-8'); ?>';
    new QRCode(wrapper, {
        text: qrUrl,
        width: 160,
        height: 160,
        colorDark: '#111827',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.H,
        useSVG: false
    });

    setTimeout(() => {
        const qrImage = wrapper.querySelector('img');
        if (qrImage && qrImage.src) {
            localStorage.setItem(RESIDENT_QR_CACHE_KEY, qrImage.src);
        }
    }, 100);
}

function downloadQRCode(patientID) {
    const wrapper = document.getElementById('qrCodeImage');
    if (!wrapper) {
        return;
    }

    let qrImage = wrapper.querySelector('img');
    if (!qrImage) {
        const cached = localStorage.getItem(RESIDENT_QR_CACHE_KEY);
        if (!cached) {
            return;
        }
        qrImage = new Image();
        qrImage.src = cached;
    }

    const link = document.createElement('a');
    link.href = qrImage.src;
    link.download = patientID + '_QRCode.png';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

document.addEventListener('DOMContentLoaded', function() {
    renderResidentQRCode();
    const dateInput = document.getElementById('preferred_date');
    const preferredTimeSelect = document.getElementById('preferred_time');
    const selectedTimeInput = document.getElementById('selected_time');
    const selectedTimeSummary = document.getElementById('selected_time_summary');
    const scheduleTimeNote = document.getElementById('schedule_time_note');
    const bookButton = document.getElementById('bookAppointmentBtn');

    function updateBookButtonState() {
        const ready = Boolean(dateInput?.value && preferredTimeSelect?.value);
        if (bookButton) {
            bookButton.disabled = !ready;
            bookButton.classList.toggle('opacity-50', !ready);
            bookButton.classList.toggle('cursor-not-allowed', !ready);
        }
    }

    function updateTimeOptions(bookedTimes = [], slotCounts = {}) {
        if (!preferredTimeSelect) return;
        const bookedSet = new Set(bookedTimes.map(time => String(time).trim().toUpperCase()));
        const selectedValue = preferredTimeSelect.value;

        Array.from(preferredTimeSelect.options).forEach(option => {
            if (!option.value) return;
            const optionKey = option.value.trim().toUpperCase();
            const booked = bookedSet.has(optionKey);
            const remaining = Number(slotCounts[option.value] ?? slotCounts[optionKey] ?? 5);
            option.disabled = booked;
            option.text = booked
                ? option.value + ' - Not available'
                : `${option.value} - ${remaining} slot${remaining === 1 ? '' : 's'} left`;
        });

        if (selectedValue && preferredTimeSelect.selectedOptions[0]?.disabled) {
            preferredTimeSelect.value = '';
            if (selectedTimeInput) selectedTimeInput.value = '';
        }
        updateBookButtonState();
    }

    async function loadBookedSlots(date) {
        if (!date) {
            updateTimeOptions([]);
            return;
        }
        try {
            const response = await fetch('get_booked_slots.php?date=' + encodeURIComponent(date));
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.message || 'Unable to load booked slots');
            updateTimeOptions(data.booked_times || [], data.slot_counts || {});
            if (scheduleTimeNote) {
                scheduleTimeNote.textContent = 'Unavailable times are marked above.';
                scheduleTimeNote.classList.remove('hidden');
            }
        } catch (error) {
                console.error('Unable to fetch booked slots:', error);
                updateTimeOptions([]);
        }
    }

    if (preferredTimeSelect) {
        preferredTimeSelect.addEventListener('change', function() {
            if (selectedTimeInput) selectedTimeInput.value = this.value;
            if (selectedTimeSummary) {
                selectedTimeSummary.textContent = this.value ? 'Selected: ' + this.value : '';
                selectedTimeSummary.classList.toggle('hidden', !this.value);
            }
            updateBookButtonState();
        });
    }

    if (dateInput) {
        dateInput.addEventListener('change', function() {
            const selectedDate = new Date(this.value + 'T00:00:00');
            if (this.value && (selectedDate.getDay() === 0 || selectedDate.getDay() === 6)) {
                this.value = '';
                updateTimeOptions([]);
                return;
            }
            loadBookedSlots(this.value);
        });
    }

    if (dateInput?.value) loadBookedSlots(dateInput.value);
    updateBookButtonState();
});
</script>
<style>
    body.dark-mode #schedule-appointment .bg-gradient-to-r {
        background: linear-gradient(to right, rgba(69, 75, 27, 0.15), #1e293b) !important;
        border-color: #334155 !important;
    }

    
    body.dark-mode #schedule-appointment .text-slate-900 {
        color: #f1f5f9 !important;
    }

    body.dark-mode #schedule-appointment .text-slate-600 {
        color: #cbd5e1 !important;
    }

    
    body.dark-mode #schedule-appointment input[type="date"],
    body.dark-mode #schedule-appointment input[type="text"],
    body.dark-mode #schedule-appointment select,
    body.dark-mode #schedule-appointment textarea {
        background-color: #0f172a !important;
        border-color: #334155 !important;
        color: #e2e8f0 !important;
    }

    body.dark-mode #schedule-appointment input[type="date"] {
        color-scheme: dark;
    }

    body.dark-mode #schedule-appointment input[type="date"]::-webkit-calendar-picker-indicator {
        filter: brightness(0) invert(1);
        opacity: 0.9;
    }

    body.dark-mode #schedule-appointment input[type="date"]:focus,
    body.dark-mode #schedule-appointment input[type="text"]:focus,
    body.dark-mode #schedule-appointment select:focus,
    body.dark-mode #schedule-appointment textarea:focus {
        ring-color: #454B1B !important;
        border-color: transparent !important;
        outline: none !important;
    }

    body.dark-mode #schedule-appointment input::placeholder {
        color: #64748b !important;
    }

    
    body.dark-mode #schedule-appointment button[type="submit"] {
        background-color: #334155 !important;
        color: #e2e8f0 !important;
        border: 1px solid #475569 !important;
        font-weight: 600 !important;
    }

    body.dark-mode #schedule-appointment button[type="submit"]:hover {
        background-color: #475569 !important;
        border-color: #64748b !important;
    }

    
    body.dark-mode .bg-green-50 {
        background-color: rgba(16, 185, 129, 0.1) !important;
        border-color: #10b981 !important;
    }

    body.dark-mode .text-green-900 {
        color: #a7f3d0 !important;
    }

    body.dark-mode .bg-red-50 {
        background-color: rgba(239, 68, 68, 0.1) !important;
        border-color: #ef4444 !important;
    }

    body.dark-mode .text-red-900 {
        color: #fca5a5 !important;
    }

    
    body.dark-mode #schedule-appointment {
        background-color: #1e293b !important;
        border-color: #334155 !important;
    }

    body.dark-mode #schedule-appointment .bg-gradient-to-r {
        background: linear-gradient(to right, rgba(69, 75, 27, 0.15), #1e293b) !important;
        border-color: #334155 !important;
    }

    body.dark-mode #schedule-appointment .text-slate-900 {
        color: #f1f5f9 !important;
    }

    body.dark-mode #schedule-appointment .text-slate-600 {
        color: #cbd5e1 !important;
    }

    body.dark-mode #schedule-appointment .text-slate-500 {
        color: #94a3b8 !important;
    }

    
    body.dark-mode #schedule-appointment .border-slate-200 {
        border-color: #334155 !important;
        background-color: #0f172a !important;
    }

    
    body.dark-mode .bg-yellow-100 {
        background-color: rgba(251, 191, 36, 0.2) !important;
        color: #fbbf24 !important;
    }

    body.dark-mode .bg-blue-100 {
        background-color: rgba(96, 165, 250, 0.2) !important;
        color: #93c5fd !important;
    }

    body.dark-mode .bg-green-100 {
        background-color: rgba(74, 222, 128, 0.2) !important;
        color: #86efac !important;
    }

    
    body.dark-mode .bg-slate-50 {
        background-color: #0f172a !important;
    }

    body.dark-mode .border-dashed {
        border-color: #334155 !important;
    }

    :is(html.dark, html.dark-mode, body.dark-mode) .resident-mobile-section.border-slate-200,
    :is(html.dark, html.dark-mode, body.dark-mode) .resident-mobile-section [class~="border-slate-200"] {
        border-color: rgba(51, 65, 85, 0.65) !important;
    }

    :is(html.dark, html.dark-mode, body.dark-mode) #community-surveillance .resident-progress-track {
        box-sizing: border-box;
        width: 100%;
        border: 1px solid rgba(51, 65, 85, 0.75);
        background-color: #111a36 !important;
        box-shadow: none !important;
    }

    :is(html.dark, html.dark-mode, body.dark-mode) #community-surveillance [data-resident-metric-card] {
        border-color: rgba(51, 65, 85, 0.65) !important;
    }

    :is(html.dark, html.dark-mode, body.dark-mode) #community-surveillance .resident-progress-fill {
        box-sizing: border-box;
        display: block;
        height: 100%;
        max-width: 100%;
        margin: 0;
        border-radius: 9999px 0 0 9999px !important;
        box-shadow: none !important;
    }

    body.dark-mode .text-yellow-800,
    body.dark-mode .text-blue-800,
    body.dark-mode .text-green-800 {
        color: inherit !important;
    }
</style>

<div id="centeredConfirmModal" class="fixed inset-0 z-[20000] hidden items-center justify-center bg-black/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="centeredConfirmTitle" aria-describedby="centeredConfirmMessage">
    <div class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-white/10 dark:bg-[#111813]">
        <div class="mb-4 flex items-center gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300"><i class="fas fa-exclamation-triangle"></i></div>
            <h2 id="centeredConfirmTitle" class="text-lg font-bold text-slate-900 dark:text-white">Confirm action</h2>
        </div>
        <p id="centeredConfirmMessage" class="mb-6 text-sm leading-6 text-slate-600 dark:text-slate-300"></p>
        <div class="flex justify-end gap-3">
            <button type="button" onclick="closeCenteredConfirmModal()" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Cancel</button>
            <button id="centeredConfirmAction" type="button" onclick="confirmCenteredConfirmModal()" class="rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700">Confirm</button>
        </div>
    </div>
</div>

<script>
window.centeredConfirmCallback = null;

window.openCenteredConfirmation = function({ title, message, confirmLabel = 'Confirm', confirmClass = 'bg-red-600 hover:bg-red-700', onConfirm }) {
    const modal = document.getElementById('centeredConfirmModal');
    const confirmButton = document.getElementById('centeredConfirmAction');
    if (!modal || !confirmButton) return;
    document.getElementById('centeredConfirmTitle').textContent = title;
    document.getElementById('centeredConfirmMessage').textContent = message;
    confirmButton.textContent = confirmLabel;
    confirmButton.className = `rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition ${confirmClass}`;
    window.centeredConfirmCallback = onConfirm;
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

let residentFileRequestSubmitting = false;

window.showResidentFileRequestToast = function(message, type = 'success') {
    let toast = document.getElementById('residentFileRequestToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'residentFileRequestToast';
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');
        toast.className = 'pointer-events-none fixed inset-x-4 bottom-4 z-[25000] mx-auto flex max-w-sm items-start gap-3 rounded-lg border px-4 py-3 text-sm font-semibold shadow-lg opacity-0 translate-y-2 transition-all duration-200';
        document.body.appendChild(toast);
    }

    const isSuccess = type === 'success';
    toast.className = 'pointer-events-none fixed inset-x-4 bottom-4 z-[25000] mx-auto flex max-w-sm items-start gap-3 rounded-lg border px-4 py-3 text-sm font-semibold shadow-lg transition-all duration-200 ' +
        (isSuccess ? 'border-emerald-200 bg-white text-emerald-800' : 'border-red-200 bg-white text-red-800');
    toast.textContent = message;
    requestAnimationFrame(() => toast.classList.remove('opacity-0', 'translate-y-2'));
    clearTimeout(window.residentFileRequestToastTimer);
    window.residentFileRequestToastTimer = setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-y-2');
    }, 4200);
};

function appendResidentFileRequest(request) {
    const list = document.getElementById('residentFileRequestList');
    if (!list || !request) return;
    document.getElementById('residentFileRequestsEmpty')?.remove();

    const card = document.createElement('div');
    card.className = 'flex flex-col gap-3 rounded-lg border border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between';
    const details = document.createElement('div');
    const fileName = document.createElement('p');
    fileName.className = 'font-semibold text-slate-900';
    fileName.textContent = request.requested_file || 'Medical Record';
    const submitted = document.createElement('p');
    submitted.className = 'text-xs text-slate-500';
    const createdAt = String(request.created_at || '').replace(' ', 'T');
    submitted.textContent = 'Submitted ' + (createdAt ? new Date(createdAt).toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' }) : 'just now');
    details.append(fileName, submitted);

    const actions = document.createElement('div');
    actions.className = 'flex flex-wrap items-stretch gap-2';
    const badge = document.createElement('span');
    badge.className = 'inline-flex min-h-8 items-center rounded-full bg-amber-100 px-2 py-1 text-xs font-bold capitalize text-amber-800';
    badge.textContent = request.status || 'pending';
    actions.appendChild(badge);

    const deleteForm = document.createElement('form');
    deleteForm.method = 'post';
    deleteForm.action = 'delete_file_request.php';
    deleteForm.className = 'flex min-h-8';
    deleteForm.addEventListener('submit', window.confirmResidentFileRequestDelete);
    const requestId = document.createElement('input');
    requestId.type = 'hidden';
    requestId.name = 'request_id';
    requestId.value = String(request.request_id);
    const deleteButton = document.createElement('button');
    deleteButton.type = 'submit';
    deleteButton.className = 'flex min-h-8 items-center justify-center rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50';
    deleteButton.innerHTML = '<i class="fas fa-trash mr-1" aria-hidden="true"></i> Delete';
    deleteForm.append(requestId, deleteButton);
    actions.appendChild(deleteForm);
    card.append(details, actions);
    list.prepend(card);
}

window.confirmResidentFileRequest = function() {
    window.openCenteredConfirmation({
        title: 'Request a copy of your medical record?',
        message: 'Your health worker will review your request and prepare the file after approval.',
        confirmLabel: 'Send File Request',
        confirmClass: 'bg-[#454B1B] hover:bg-[#363812]',
        onConfirm: async () => {
            if (residentFileRequestSubmitting) return;
            residentFileRequestSubmitting = true;
            try {
                const formData = new FormData();
                formData.append('requested_file', 'Medical Record');
                const response = await fetch('request_file_action.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                    body: formData
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Unable to submit your file request.');
                }

                if (result.already_pending) {
                    window.showResidentFileRequestToast('You already have a pending medical record request.', 'success');
                } else {
                    appendResidentFileRequest(result.request);
                    window.showResidentFileRequestToast('File request sent successfully.');
                }
            } catch (error) {
                window.showResidentFileRequestToast(error.message || 'Unable to submit your file request.', 'error');
            } finally {
                residentFileRequestSubmitting = false;
            }
        }
    });
};

window.confirmResidentFileRequestDelete = function(event) {
    event.preventDefault();
    const form = event.currentTarget;
    window.openCenteredConfirmation({
        title: 'Delete File Request',
        message: 'Delete this file request from your history? This action cannot be undone.',
        confirmLabel: 'Delete Request',
        onConfirm: () => form.requestSubmit()
    });
};

window.legacyClearMedicalHistoryDisplay = function(evt) {
        
        const confirmMsg = `🧹 Clear Medical History Display\n\nThis will hide your medical history from the dashboard.\n\nℹ️ No data will be deleted from the database.\n\nThis will persist even after refreshing.`;
        
        window.openCenteredConfirmation({
            title: 'Clear Medical History Display',
            message: confirmMsg,
            confirmLabel: 'Clear History',
            onConfirm: () => {
                console.log('🧹 Clearing medical history display (UI only, persistent)');
                try {
            
            const medicalList = document.getElementById('medicalHistoryList_resident');
            
            if (medicalList) {
                
                medicalList.innerHTML = `
                    <div class="text-center py-8">
                        <i class="fa-solid fa-check-circle text-4xl" style="color: #cbd5e1; margin-bottom: 12px;"></i>
                        <p class="text-slate-600">Medical history cleared from display.</p>
                        <button onclick="clearMedicalHistoryClearFlag()" style="margin-top: 12px; padding: 6px 12px; background-color: #3b82f6; color: white; border-radius: 4px; border: none; cursor: pointer; font-size: 12px;">Restore History</button>
                    </div>
                `;
                
                
                const clearBtn = evt?.target || document.getElementById('clearMedicalHistoryBtn_resident');
                if (clearBtn) {
                    clearBtn.style.display = 'none';
                }
                
                
                localStorage.setItem('medicalHistoryCleared_resident', 'true');
                
                console.log('✅ Medical history hidden from display (UI only, database unchanged, state saved to localStorage)');
                alert(`✓ Medical history cleared from display!\n\nℹ️ Data remains in the database.\n💾 This will persist even after refreshing.`);
            } else {
                console.error('Medical history list container not found!');
                alert('❌ Error: Could not find medical history display. Please refresh and try again.');
            }
                } catch (err) {
                    console.error('🔴 Error clearing medical history:', err);
                    alert('❌ Error: ' + err.message);
                }
            }
        });
    };

    
    
    
    
    
    const restoreMedicalHistoryState = function() {
        const clearKey = 'medicalHistoryCleared_resident';
        
        
        if (localStorage.getItem(clearKey) === 'true') {
            console.log('🔄 Restoring cleared state for medical history');
            
            const medicalList = document.getElementById('medicalHistoryList_resident');
            if (medicalList) {
                
                medicalList.innerHTML = `
                    <div class="text-center py-8">
                        <i class="fa-solid fa-check-circle text-4xl" style="color: #cbd5e1; margin-bottom: 12px;"></i>
                        <p class="text-slate-600">Medical history cleared from display.</p>
                        <button onclick="clearMedicalHistoryClearFlag()" style="margin-top: 12px; padding: 6px 12px; background-color: #3b82f6; color: white; border-radius: 4px; border: none; cursor: pointer; font-size: 12px;">Restore History</button>
                    </div>
                `;
                
                
                const clearBtn = document.getElementById('clearMedicalHistoryBtn_resident');
                if (clearBtn) {
                    clearBtn.style.display = 'none';
                }
                
                console.log('✅ Restored cleared state: medical history is now hidden');
            }
        }
    };
    
    
    window.clearMedicalHistoryClearFlag = function() {
        localStorage.removeItem('medicalHistoryCleared_resident');
        location.reload();
    };
    
    

    
    
</script>

<script>
const hiddenMedicalHistoryKey = 'medicalHistoryHidden_resident';
const legacyMedicalHistoryKey = 'medicalHistoryCleared_resident';

function readHiddenMedicalHistory() {
    try {
        const keys = JSON.parse(localStorage.getItem(hiddenMedicalHistoryKey) || '[]');
        return new Set(Array.isArray(keys) ? keys : []);
    } catch (error) {
        return new Set();
    }
}

function applyMedicalHistoryVisibility() {
    const rows = Array.from(document.querySelectorAll('#medicalHistoryList_resident [data-history-key]'));
    if (!rows.length) return;

    const hiddenKeys = readHiddenMedicalHistory();
    const restoreButton = document.getElementById('restoreMedicalHistoryBtn_resident');
    const clearButton = document.getElementById('clearMedicalHistoryBtn_resident');
    const emptyMessage = document.getElementById('medicalHistoryEmptyAfterClear');
    let visibleCount = 0;

    rows.forEach(row => {
        const isHidden = hiddenKeys.has(row.dataset.historyKey);
        row.style.display = isHidden ? 'none' : '';
        if (!isHidden) visibleCount++;
    });

    if (restoreButton) restoreButton.style.display = hiddenKeys.size ? 'inline-flex' : 'none';
    if (clearButton) clearButton.style.display = visibleCount ? 'inline-flex' : 'none';
    if (emptyMessage) emptyMessage.style.display = visibleCount ? 'none' : 'block';

    const countElement = document.getElementById('medicalHistoryTotalCount');
    if (countElement) countElement.textContent = String(visibleCount);

    const latestDateElement = document.getElementById('medicalHistoryLatestDate');
    const latestVisibleDate = rows.find(row => row.style.display !== 'none')?.querySelector('[data-history-date-label]');
    if (latestDateElement) latestDateElement.textContent = latestVisibleDate?.textContent || '—';
}

window.clearMedicalHistoryDisplay = function() {
    window.openCenteredConfirmation({
        title: 'Hide Medical History',
        message: 'Hide the history records currently shown? New records will remain visible. You can restore all records at any time.',
        confirmLabel: 'Hide Records',
        confirmClass: 'bg-emerald-600 hover:bg-emerald-700',
        onConfirm: () => {
            const hiddenKeys = readHiddenMedicalHistory();
            document.querySelectorAll('#medicalHistoryList_resident [data-history-key]').forEach(row => {
                if (row.style.display !== 'none') hiddenKeys.add(row.dataset.historyKey);
            });
            localStorage.setItem(hiddenMedicalHistoryKey, JSON.stringify(Array.from(hiddenKeys)));
            applyMedicalHistoryVisibility();
        }
    });
};

window.clearMedicalHistoryClearFlag = function() {
    localStorage.removeItem(hiddenMedicalHistoryKey);
    localStorage.removeItem(legacyMedicalHistoryKey);
    applyMedicalHistoryVisibility();
};

if (localStorage.getItem(legacyMedicalHistoryKey) === 'true') {
    const existingKeys = Array.from(document.querySelectorAll('#medicalHistoryList_resident [data-history-key]'))
        .map(row => row.dataset.historyKey);
    localStorage.setItem(hiddenMedicalHistoryKey, JSON.stringify(existingKeys));
    localStorage.removeItem(legacyMedicalHistoryKey);
}

applyMedicalHistoryVisibility();

(function watchForResidentHistoryChanges() {
    if (!document.getElementById('medical-history')) return;

    let currentVersion = null;
    let checking = false;

    async function checkForChanges() {
        if (checking || document.visibilityState !== 'visible') return;
        checking = true;

        try {
            const response = await fetch('resident_history_version.php', { cache: 'no-store' });
            if (!response.ok) return;
            const data = await response.json();
            if (!data.success || !data.version) return;

            if (currentVersion === null) {
                currentVersion = data.version;
            } else if (data.version !== currentVersion) {
                window.location.reload();
            }
        } catch (error) {
            console.error('Unable to refresh resident history status:', error);
        } finally {
            checking = false;
        }
    }

    checkForChanges();
    window.setInterval(checkForChanges, 15000);
    document.addEventListener('visibilitychange', checkForChanges);
})();
</script>

<script>
    function getCountBasedTrendLabel(count) {
        const value = Number(count || 0);
        if (value <= 10) return { label: 'Baseline', color: 'text-emerald-600' };
        if (value <= 20) return { label: 'Watch', color: 'text-amber-600' };
        if (value <= 30) return { label: 'Alert', color: 'text-orange-600' };
        return { label: 'Emergency', color: 'text-red-600' };
    }

    function normalizeResidentSurveillanceMetrics(rawMetrics) {
        const values = rawMetrics || {};
        const hasPublishedRecords = Array.isArray(values.disease_records) && values.disease_records.length > 0;
        const reportingPeriod = hasPublishedRecords
            ? (values.reporting_period || values.reportingPeriod || 'Latest patient records')
            : 'No checked items';
        const updatedAt = values.updated_at || values.updatedAt || '';

        return {
            reportingPeriod,
            updatedAt: hasPublishedRecords && updatedAt ? `Latest record update: ${new Date(updatedAt).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}` : 'Latest record update: No updates yet',
            dengueCount: Number(values.dengue_count ?? values.dengueCount ?? 0) || 0,
            dengueTrend: getCountBasedTrendLabel(Number(values.dengue_count ?? values.dengueCount ?? 0) || 0).label,
            dengueMeta: values.dengue_meta || values.dengueMeta || 'No active cases reported.',
            dengueProgress: Number(values.dengue_progress ?? values.dengueProgress ?? 0) || 0,
            rabiesCount: Number(values.rabies_count ?? values.rabiesCount ?? 0) || 0,
            rabiesTrend: getCountBasedTrendLabel(Number(values.rabies_count ?? values.rabiesCount ?? 0) || 0).label,
            rabiesMeta: values.rabies_meta || values.rabiesMeta || '0% coverage | No residents vaccinated.',
            rabiesProgress: Number(values.rabies_progress ?? values.rabiesProgress ?? 0) || 0,
            respiratoryCount: Number(values.respiratory_count ?? values.respiratoryCount ?? 0) || 0,
            respiratoryTrend: getCountBasedTrendLabel(Number(values.respiratory_count ?? values.respiratoryCount ?? 0) || 0).label,
            respiratoryMeta: values.respiratory_meta || values.respiratoryMeta || 'No active cases reported.',
            respiratoryProgress: Number(values.respiratory_progress ?? values.respiratoryProgress ?? 0) || 0,
            nutritionCount: Number(values.nutrition_count ?? values.nutritionCount ?? 0) || 0,
            nutritionTrend: getCountBasedTrendLabel(Number(values.nutrition_count ?? values.nutritionCount ?? 0) || 0).label,
            nutritionMeta: values.nutrition_meta || values.nutritionMeta || 'No active cases reported.',
            nutritionProgress: Number(values.nutrition_progress ?? values.nutritionProgress ?? 0) || 0
        };
    }

    function deriveResidentSurveillanceProgress(count, metricType) {
        const numericCount = Number(count || 0);
        if (numericCount <= 0) {
            return 0;
        }
        switch (metricType) {
            case 'rabies':
                return Math.min(100, Math.max(10, numericCount));
            case 'dengue':
            case 'respiratory':
            case 'nutrition':
                return Math.min(100, Math.max(0, (numericCount / 50) * 100));
            default:
                return Math.min(100, Math.max(10, numericCount * 10));
        }
    }

    function resolveResidentProgress(progress, count, metricType) {
        if (count <= 0) {
            return 0;
        }

        const fallbackProgress = deriveResidentSurveillanceProgress(count, metricType);
        if (metricType === 'dengue') {
            return fallbackProgress;
        }

        const numericProgress = Number(progress || 0);
        if (!Number.isFinite(numericProgress) || numericProgress <= 0) {
            return fallbackProgress;
        }
        return Math.max(numericProgress, fallbackProgress);
    }

    function updateResidentSurveillanceBar(fillId, percent) {
        const fill = document.getElementById(fillId);
        if (!fill) return;
        const value = Number(percent);
        fill.style.width = `${Math.max(0, Math.min(100, Number.isFinite(value) ? value : 0))}%`;
    }

    function renderResidentSurveillanceWidget(rawMetrics) {
        const metrics = normalizeResidentSurveillanceMetrics(rawMetrics);
        const publishedRecords = Array.isArray(rawMetrics?.disease_records) ? rawMetrics.disease_records : [];
        const surveillanceSection = document.getElementById('community-surveillance');
        if (surveillanceSection) {
            surveillanceSection.classList.remove('mobile-hidden-section');
            surveillanceSection.style.display = '';
        }

        const reportingPeriodEl = document.getElementById('residentReportingPeriodDisplay');
        if (reportingPeriodEl) {
            reportingPeriodEl.textContent = `REPORTING PERIOD: ${(metrics.reportingPeriod || 'CURRENT 4 WEEKS').toUpperCase()}`;
        }

        const updatedAtEl = document.getElementById('residentSurveillanceUpdatedAt');
        if (updatedAtEl) {
            updatedAtEl.textContent = metrics.updatedAt;
        }

        const metricCardsContainer = document.getElementById('residentPublishedMetricCards');
        const summaryContainer = document.getElementById('residentSurveillanceMetricsContainer');
        if (summaryContainer) {
            summaryContainer.classList.add('hidden');
        }
        if (metricCardsContainer) {
            metricCardsContainer.innerHTML = publishedRecords.length
                ? publishedRecords.map(record => {
                    const count = Math.max(0, Number(record.case_count || 0));
                    const trend = getCountBasedTrendLabel(count);
                    const progress = Math.min(100, Math.max(0, (count / 50) * 100));
                    const name = escapeResidentText(record.disease_name || 'Community health metric');
                    const note = escapeResidentText(record.meta_notes || 'No notes provided.');
                    return `<div class="space-y-2 pb-5" data-resident-metric-card><div class="flex items-center justify-between gap-2"><span class="text-sm font-bold text-slate-900">${name}</span><span class="text-[11px] font-bold ${trend.color}">${trend.label}</span></div><p class="text-xs text-slate-500">${count} resident${count === 1 ? ' is' : 's are'} marked with ${name}</p><div class="resident-progress-track h-3 overflow-hidden rounded-full bg-slate-100"><div class="resident-progress-fill h-full rounded-full" style="background: linear-gradient(to right, #454B1B, #ef4444); width: ${progress}%;"></div></div></div>`;
                }).join('')
                : '<div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">No checked medical-history items have been recorded yet.</div>';
        }

        const items = [
            {
                detailId: 'residentDengueDetail',
                trendId: 'residentDengueTrend',
                fillId: 'residentDengueBarFill',
                count: metrics.dengueCount,
                meta: metrics.dengueMeta,
                suffix: 'confirmed cases',
                trend: metrics.dengueTrend,
                detailFallback: 'No active cases reported.',
                progress: metrics.dengueProgress,
                metricType: 'dengue'
            },
            {
                detailId: 'residentRabiesDetail',
                trendId: 'residentRabiesTrend',
                fillId: 'residentRabiesBarFill',
                count: metrics.rabiesCount,
                meta: metrics.rabiesMeta,
                suffix: '% coverage',
                trend: metrics.rabiesTrend,
                detailFallback: '0% coverage | No residents vaccinated.',
                progress: metrics.rabiesProgress,
                metricType: 'rabies'
            },
            {
                detailId: 'residentRespiratoryDetail',
                trendId: 'residentRespiratoryTrend',
                fillId: 'residentRespiratoryBarFill',
                count: metrics.respiratoryCount,
                meta: metrics.respiratoryMeta,
                suffix: 'active cases',
                trend: metrics.respiratoryTrend,
                detailFallback: 'No active cases reported.',
                progress: metrics.respiratoryProgress,
                metricType: 'respiratory'
            },
            {
                detailId: 'residentNutritionDetail',
                trendId: 'residentNutritionTrend',
                fillId: 'residentNutritionBarFill',
                count: metrics.nutritionCount,
                meta: metrics.nutritionMeta,
                suffix: 'cases',
                trend: metrics.nutritionTrend,
                detailFallback: 'No active cases reported.',
                progress: metrics.nutritionProgress,
                metricType: 'nutrition'
            }
        ];

        items.forEach(item => {
            const detailEl = document.getElementById(item.detailId);
            const trendEl = document.getElementById(item.trendId);
            const progress = resolveResidentProgress(item.progress, item.count, item.metricType);

            if (detailEl) {
                detailEl.textContent = item.count > 0
                    ? `${item.count} ${item.suffix} | ${item.meta || item.detailFallback}`
                    : item.detailFallback;
            }

            if (trendEl) {
                const computedTrend = getCountBasedTrendLabel(item.count);
                trendEl.textContent = computedTrend.label;
                trendEl.className = `text-[11px] font-bold ${computedTrend.color}`;
            }

            updateResidentSurveillanceBar(item.fillId, progress);
        });
    }

    function escapeResidentText(value) {
        return String(value).replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[character]));
    }

    function applyResidentSurveillanceUpdate(metrics) {
        if (!metrics || typeof metrics !== 'object') {
            return;
        }

        try {
            renderResidentSurveillanceWidget(metrics);
        } catch (error) {
            console.error('Resident surveillance widget update error:', error);
        }
    }

    try {
        const __initialResidentMetrics = <?php echo json_encode($surveillance_metrics); ?> || {};
        renderResidentSurveillanceWidget(__initialResidentMetrics);
    } catch (error) {
        console.error('Resident surveillance widget render error:', error);
    }

    try {
        window.addEventListener('scancare-surveillance-update', (event) => {
            if (event && event.detail && event.detail.metrics) {
                applyResidentSurveillanceUpdate(event.detail.metrics);
            }
        });

        window.addEventListener('storage', (event) => {
            if (event && event.key === 'scancareSurveillanceMetrics' && event.newValue) {
                try {
                    const savedMetrics = JSON.parse(event.newValue);
                    applyResidentSurveillanceUpdate(savedMetrics);
                } catch (error) {
                    console.warn('Stored surveillance metrics parse failed:', error);
                }
            }
        });

        if (typeof BroadcastChannel !== 'undefined') {
            const bc = new BroadcastChannel('scancare-surveillance');
            bc.onmessage = (ev) => {
                if (ev && ev.data && ev.data.type === 'update' && ev.data.metrics) {
                    applyResidentSurveillanceUpdate(ev.data.metrics);
                }
            };
        }
    } catch (error) {
        console.warn('BroadcastChannel listener setup failed:', error);
    }
    
    </script>

    <script>
        const residentAnnouncementIndexes = { upcoming: 0, now: 0 };
        const residentAnnouncementAutoAdvanceDelay = 5000;
        const residentAnnouncementReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        let activeResidentAnnouncementState = 'upcoming';
        let residentAnnouncementHovered = false;
        let residentAnnouncementFocused = false;
        let residentAnnouncementTimer = null;

        function showResidentAnnouncement(state, index) {
            const track = document.querySelector('[data-resident-announcement-track="' + state + '"]');
            const dots = document.querySelectorAll('[data-resident-announcement-dot="' + state + '"]');
            if (!track || dots.length === 0) return;

            const nextIndex = ((index % dots.length) + dots.length) % dots.length;
            residentAnnouncementIndexes[state] = nextIndex;
            track.style.transform = 'translateX(-' + (nextIndex * 100) + '%)';

            dots.forEach(function (dot, dotIndex) {
                const active = dotIndex === nextIndex;
                dot.classList.toggle('bg-[#454B1B]', active);
                dot.classList.toggle('bg-[#454B1B]/25', !active);
                dot.setAttribute('aria-selected', active ? 'true' : 'false');
            });
        }

        function restartResidentAnnouncementAutoAdvance() {
            window.clearInterval(residentAnnouncementTimer);
            if (residentAnnouncementReducedMotion) return;

            residentAnnouncementTimer = window.setInterval(function () {
                const panel = document.querySelector('[data-resident-announcement-panel="' + activeResidentAnnouncementState + '"]');
                const dots = document.querySelectorAll('[data-resident-announcement-dot="' + activeResidentAnnouncementState + '"]');
                if (document.hidden || residentAnnouncementHovered || residentAnnouncementFocused || !panel || panel.hidden || dots.length < 2) return;

                showResidentAnnouncement(activeResidentAnnouncementState, residentAnnouncementIndexes[activeResidentAnnouncementState] + 1);
            }, residentAnnouncementAutoAdvanceDelay);
        }

        document.querySelectorAll('[data-resident-announcement-carousel]').forEach(function (carousel) {
            carousel.addEventListener('mouseenter', function () {
                residentAnnouncementHovered = true;
            });
            carousel.addEventListener('mouseleave', function () {
                residentAnnouncementHovered = false;
            });
            carousel.addEventListener('focusin', function () {
                residentAnnouncementFocused = true;
            });
            carousel.addEventListener('focusout', function (event) {
                if (!carousel.contains(event.relatedTarget)) {
                    residentAnnouncementFocused = false;
                }
            });
        });

        document.querySelectorAll('[data-resident-announcement-previous]').forEach(function (button) {
            button.addEventListener('click', function () {
                const state = button.dataset.residentAnnouncementPrevious;
                showResidentAnnouncement(state, residentAnnouncementIndexes[state] - 1);
            });
        });

        document.querySelectorAll('[data-resident-announcement-next]').forEach(function (button) {
            button.addEventListener('click', function () {
                const state = button.dataset.residentAnnouncementNext;
                showResidentAnnouncement(state, residentAnnouncementIndexes[state] + 1);
            });
        });

        document.querySelectorAll('[data-resident-announcement-dot]').forEach(function (dot) {
            dot.addEventListener('click', function () {
                showResidentAnnouncement(dot.dataset.residentAnnouncementDot, Number(dot.dataset.residentAnnouncementIndex));
            });
        });

        ['upcoming', 'now'].forEach(function (state) {
            showResidentAnnouncement(state, 0);
        });

        document.querySelectorAll('[data-resident-announcement-tab]').forEach(function (tab) {
            tab.addEventListener('click', function () {
                const state = tab.dataset.residentAnnouncementTab;
                activeResidentAnnouncementState = state;

                document.querySelectorAll('[data-resident-announcement-tab]').forEach(function (otherTab) {
                    const active = otherTab.dataset.residentAnnouncementTab === state;
                    otherTab.setAttribute('aria-selected', active ? 'true' : 'false');
                    otherTab.classList.toggle('border-[#454B1B]', active);
                    otherTab.classList.toggle('bg-[#454B1B]', active);
                    otherTab.classList.toggle('text-white', active);
                    otherTab.classList.toggle('border-[#454B1B]/25', !active);
                    otherTab.classList.toggle('bg-white', !active);
                    otherTab.classList.toggle('text-[#454B1B]', !active);
                });

                document.querySelectorAll('[data-resident-announcement-panel]').forEach(function (panel) {
                    panel.hidden = panel.dataset.residentAnnouncementPanel !== state;
                });
                restartResidentAnnouncementAutoAdvance();
            });
        });

        restartResidentAnnouncementAutoAdvance();
    </script>
    <script src="login-notification-system.js?v=13"></script>
    <script src="pwa-offline.js?v=4"></script>
    <script src="offline-pdf-download.js?v=4"></script>
    <script src="weekday-calendar.js?v=8"></script>
    <script src="account-session-monitor.js?v=1"></script>
    </body>
    </html>
