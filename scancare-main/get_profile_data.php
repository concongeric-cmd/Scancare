<?php
session_start();
header('Content-Type: application/json');


if (!isset($_SESSION['resident_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once 'db_config.php';
$pdo = getDatabaseConnection();

try {
    $residentId = $_SESSION['resident_id'];

    
    $stmt = $pdo->prepare("
        SELECT first_name, last_name, email, COALESCE(phone, contact_number) AS phone, COALESCE(user_name, full_name) AS user_name
        FROM resident
        WHERE resident_id = :resident_id
    ");

    $stmt->execute([':resident_id' => $residentId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode([
            'success' => true,
            'firstName' => $user['first_name'] ?? '',
            'lastName' => $user['last_name'] ?? '',
            'email' => $user['email'] ?? '',
            'phone' => $user['phone'] ?? '',
            'fullName' => $user['user_name'] ?? ''
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'User not found']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unexpected server error']);
}
?>

