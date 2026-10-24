<?php


require_once 'db_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}








function ensureAppointmentPatientNumberSchema(PDO $pdo) {
    static $ready = false;
    if ($ready) {
        return true;
    }

    $lockName = 'scancare_appointment_queue_schema';
    try {
        $lockStmt = $pdo->prepare('SELECT GET_LOCK(?, 10)');
        $lockStmt->execute([$lockName]);
        if ((int) $lockStmt->fetchColumn() !== 1) {
            return false;
        }

        try {
            if (!ensureTableColumn($pdo, 'appointment', 'patient_number', 'INT UNSIGNED NULL')) {
                return false;
            }

            $indexStmt = $pdo->query("SHOW INDEX FROM appointment WHERE Key_name = 'idx_appointment_slot_queue'");
            if (!$indexStmt || !$indexStmt->fetch(PDO::FETCH_ASSOC)) {
                $pdo->exec('ALTER TABLE appointment ADD INDEX idx_appointment_slot_queue (appointment_date, appointment_time, patient_number)');
            }

            $pdo->beginTransaction();
            try {
                $rowsStmt = $pdo->query('SELECT appointment_id, appointment_date, appointment_time FROM appointment WHERE patient_number IS NULL ORDER BY appointment_date, appointment_time, created_at, appointment_id');
                $nextNumbers = [];
                $maxStmt = $pdo->prepare('SELECT COALESCE(MAX(patient_number), 0) FROM appointment WHERE appointment_date = ? AND appointment_time = ?');
                $updateStmt = $pdo->prepare('UPDATE appointment SET patient_number = ? WHERE appointment_id = ? AND patient_number IS NULL');

                while ($appointment = $rowsStmt->fetch(PDO::FETCH_ASSOC)) {
                    $slotKey = $appointment['appointment_date'] . ' ' . $appointment['appointment_time'];
                    if (!isset($nextNumbers[$slotKey])) {
                        $maxStmt->execute([$appointment['appointment_date'], $appointment['appointment_time']]);
                        $nextNumbers[$slotKey] = (int) $maxStmt->fetchColumn() + 1;
                    }

                    $updateStmt->execute([$nextNumbers[$slotKey], $appointment['appointment_id']]);
                    $nextNumbers[$slotKey]++;
                }

                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }

            $ready = true;
            return true;
        } finally {
            $releaseStmt = $pdo->prepare('SELECT RELEASE_LOCK(?)');
            $releaseStmt->execute([$lockName]);
        }
    } catch (Throwable $e) {
        error_log('Failed to ensure appointment patient numbers: ' . $e->getMessage());
        return false;
    }
}


function bookAppointment($resident_id, $appointment_date, $appointment_time, $purpose, $pdo = null) {
    try {
        $pdo = $pdo ?? getDatabaseConnection();
        
        if ($pdo === false) {
            return [
                'success' => false,
                'message' => 'Database connection error.',
                'appointment_id' => null
            ];
        }

        
        if (empty($resident_id) || empty($appointment_date) || empty($appointment_time) || empty($purpose)) {
            return [
                'success' => false,
                'message' => 'All fields are required.',
                'appointment_id' => null
            ];
        }

        
        $timeTimestamp = strtotime($appointment_time);
        if ($timeTimestamp === false) {
            return [
                'success' => false,
                'message' => 'Invalid appointment time.',
                'appointment_id' => null
            ];
        }
        $appointment_time = date('H:i:s', $timeTimestamp);
        $appointmentDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $appointment_date . ' ' . $appointment_time);
        if (!$appointmentDateTime || $appointmentDateTime <= new DateTime()) {
            return [
                'success' => false,
                'message' => 'Appointment date and time must be in the future.',
                'appointment_id' => null
            ];
        }
        if ((int) $appointmentDateTime->format('N') >= 6) {
            return [
                'success' => false,
                'message' => 'Appointments are available Monday through Friday only.',
                'appointment_id' => null
            ];
        }

        if (!ensureAppointmentPatientNumberSchema($pdo)) {
            return [
                'success' => false,
                'message' => 'Unable to assign a queue number. Please try again.',
                'appointment_id' => null
            ];
        }

        $lockName = 'scancare_day_' . substr(hash('sha256', $appointment_date), 0, 48);
        $lockStmt = $pdo->prepare('SELECT GET_LOCK(?, 10)');
        $lockStmt->execute([$lockName]);
        if ((int) $lockStmt->fetchColumn() !== 1) {
            return [
                'success' => false,
                'message' => 'Unable to reserve this time slot. Please try again.',
                'appointment_id' => null
            ];
        }

        try {
            $dailyStmt = $pdo->prepare("SELECT appointment_id FROM appointment WHERE resident_id = ? AND appointment_date = ? AND status != 'cancelled' LIMIT 1");
            $dailyStmt->execute([$resident_id, $appointment_date]);
            if ($dailyStmt->fetchColumn()) {
                return [
                    'success' => false,
                    'message' => 'You already have an appointment scheduled for this day. Please choose another date.',
                    'appointment_id' => null,
                    'conflict' => true
                ];
            }

            $slotStmt = $pdo->prepare("SELECT COUNT(*) FROM appointment WHERE appointment_date = ? AND appointment_time = ? AND status != 'cancelled'");
            $slotStmt->execute([$appointment_date, $appointment_time]);
            if ((int) $slotStmt->fetchColumn() >= 5) {
                return [
                    'success' => false,
                    'message' => 'Selected time slot has reached its limit of 5 appointments. Please choose another time.',
                    'appointment_id' => null,
                    'conflict' => true
                ];
            }

            $queueStmt = $pdo->prepare('SELECT COALESCE(MAX(patient_number), 0) + 1 FROM appointment WHERE appointment_date = ? AND appointment_time = ?');
            $queueStmt->execute([$appointment_date, $appointment_time]);
            $patientNumber = (int) $queueStmt->fetchColumn();

            $insert_stmt = $pdo->prepare("
            INSERT INTO appointment 
            (resident_id, appointment_date, appointment_time, purpose, status, patient_number, created_at)
            VALUES (?, ?, ?, ?, 'scheduled', ?, NOW())
            ");

            $insert_stmt->execute([
                $resident_id,
                $appointment_date,
                $appointment_time,
                htmlspecialchars(trim($purpose)),
                $patientNumber
            ]);

            $appointment_id = $pdo->lastInsertId();

            error_log("APPOINTMENT BOOKED: Resident $resident_id, Appointment ID $appointment_id, Patient No $patientNumber, Date $appointment_date $appointment_time");

            return [
                'success' => true,
                'message' => 'Appointment booked successfully!',
                'appointment_id' => $appointment_id,
                'patient_number' => $patientNumber
            ];
        } finally {
            $releaseStmt = $pdo->prepare('SELECT RELEASE_LOCK(?)');
            $releaseStmt->execute([$lockName]);
        }

    } catch (PDOException $e) {
        error_log("APPOINTMENT BOOKING ERROR: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error booking appointment. Please try again.',
            'appointment_id' => null
        ];
    }
}








function getAppointmentMetrics() {
    try {
        $pdo = getDatabaseConnection();
        
        if ($pdo === false) {
            return [
                'total_appointments' => 0,
                'pending_consultations' => 0,
                'completed_consultations' => 0,
                'cancelled_appointments' => 0
            ];
        }

        
        $total_stmt = $pdo->query("
            SELECT COUNT(*) as count 
            FROM appointment 
            WHERE status IN ('scheduled', 'pending', 'completed')
        ");
        $total_result = $total_stmt->fetch(PDO::FETCH_ASSOC);
        $total_appointments = $total_result['count'] ?? 0;

        
        $pending_stmt = $pdo->query("
            SELECT COUNT(*) as count 
            FROM appointment 
            WHERE status IN ('scheduled', 'pending')
            AND appointment_date >= CURDATE()
        ");
        $pending_result = $pending_stmt->fetch(PDO::FETCH_ASSOC);
        $pending_consultations = $pending_result['count'] ?? 0;

        
        $completed_stmt = $pdo->query("
            SELECT COUNT(*) as count 
            FROM appointment 
            WHERE status = 'completed'
        ");
        $completed_result = $completed_stmt->fetch(PDO::FETCH_ASSOC);
        $completed_consultations = $completed_result['count'] ?? 0;

        
        $cancelled_stmt = $pdo->query("
            SELECT COUNT(*) as count 
            FROM appointment 
            WHERE status = 'cancelled'
        ");
        $cancelled_result = $cancelled_stmt->fetch(PDO::FETCH_ASSOC);
        $cancelled_appointments = $cancelled_result['count'] ?? 0;

        return [
            'total_appointments' => $total_appointments,
            'pending_consultations' => $pending_consultations,
            'completed_consultations' => $completed_consultations,
            'cancelled_appointments' => $cancelled_appointments
        ];

    } catch (Exception $e) {
        error_log("Appointment metrics error: " . $e->getMessage());
        return [
            'total_appointments' => 0,
            'pending_consultations' => 0,
            'completed_consultations' => 0,
            'cancelled_appointments' => 0
        ];
    }
}








function getUpcomingAppointments() {
    try {
        $pdo = getDatabaseConnection();
        
        if ($pdo === false) {
            return [];
        }

        $query = "
            SELECT 
                a.appointment_id,
                a.resident_id,
                a.appointment_date,
                a.appointment_time,
                a.purpose,
                a.status,
                r.full_name as resident_name,
                r.contact_number,
                r.email
            FROM appointment a
            JOIN resident r ON a.resident_id = r.resident_id
            WHERE a.status IN ('scheduled', 'pending')
            AND a.appointment_date >= CURDATE()
            ORDER BY a.appointment_date ASC, a.appointment_time ASC
            LIMIT 20
        ";

        $stmt = $pdo->query($query);
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $appointments ?? [];

    } catch (Exception $e) {
        error_log("Fetch appointments error: " . $e->getMessage());
        return [];
    }
}





function confirmCheckup($appointment_id, $health_worker_id, $diagnosis = '', $treatment = '', $notes = '') {
    try {
        $pdo = getDatabaseConnection();
        
        if ($pdo === false) {
            return [
                'success' => false,
                'message' => 'Database connection error.'
            ];
        }

        
        $check_stmt = $pdo->prepare("
            SELECT appointment_id, resident_id, status 
            FROM appointment 
            WHERE appointment_id = ?
        ");
        $check_stmt->execute([$appointment_id]);
        $appointment = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$appointment) {
            return [
                'success' => false,
                'message' => 'Appointment not found.'
            ];
        }

        
        $pdo->beginTransaction();

        try {
            
            $update_stmt = $pdo->prepare("
                UPDATE appointment 
                SET status = 'confirmed', 
                    health_worker_id = ?,
                    updated_at = NOW()
                WHERE appointment_id = ?
            ");
            $update_stmt->execute([$health_worker_id, $appointment_id]);

            
            if (!empty($diagnosis) || !empty($treatment)) {
                $record_stmt = $pdo->prepare("
                    INSERT INTO patient_record 
                    (resident_id, health_worker_id, visit_date, diagnosis, treatment, notes)
                    VALUES (?, ?, NOW(), ?, ?, ?)
                ");
                $record_stmt->execute([
                    $appointment['resident_id'],
                    $health_worker_id,
                    htmlspecialchars(trim($diagnosis)),
                    htmlspecialchars(trim($treatment)),
                    htmlspecialchars(trim($notes))
                ]);
            }

            $pdo->commit();

            error_log("CHECKUP CONFIRMED: Appointment $appointment_id by Health Worker $health_worker_id");

            return [
                'success' => true,
                'message' => 'Checkup confirmed successfully!'
            ];

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

    } catch (Exception $e) {
        error_log("Confirm checkup error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error confirming checkup. Please try again.'
        ];
    }
}







function cancelAppointment($appointment_id, $cancellation_reason = '') {
    try {
        $pdo = getDatabaseConnection();
        
        if ($pdo === false) {
            return [
                'success' => false,
                'message' => 'Database connection error.'
            ];
        }

        $update_stmt = $pdo->prepare("
            UPDATE appointment 
            SET status = 'cancelled', 
                notes = ?,
                updated_at = NOW()
            WHERE appointment_id = ?
        ");

        $update_stmt->execute([
            htmlspecialchars(trim($cancellation_reason)),
            $appointment_id
        ]);

        error_log("APPOINTMENT CANCELLED: Appointment $appointment_id");

        return [
            'success' => true,
            'message' => 'Appointment cancelled successfully.'
        ];

    } catch (Exception $e) {
        error_log("Cancel appointment error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error cancelling appointment.'
        ];
    }
}

?>

