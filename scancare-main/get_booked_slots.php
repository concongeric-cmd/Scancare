<?php
header('Content-Type: application/json');

require_once 'db_config.php';

$date = trim($_GET['date'] ?? '');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit;
}

$pdo = getDatabaseConnection();
if ($pdo === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT TIME_FORMAT(appointment_time, '%I:%i %p') AS appointment_time, COUNT(*) AS booked_count FROM appointment WHERE appointment_date = ? AND status != 'cancelled' GROUP BY appointment_time");
    $stmt->execute([$date]);
    $bookedTimes = [];
    $slotCounts = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $record) {
        $time = $record['appointment_time'];
        $remaining = max(0, 5 - (int) $record['booked_count']);
        $slotCounts[$time] = $remaining;
        if ($remaining === 0) {
            $bookedTimes[] = $time;
        }
    }

    echo json_encode(['success' => true, 'booked_times' => $bookedTimes, 'slot_counts' => $slotCounts]);
    exit;
} catch (Exception $e) {
    error_log('get_booked_slots error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to load booked slots']);
    exit;
}
