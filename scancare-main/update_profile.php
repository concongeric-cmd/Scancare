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
    
    $firstName = $_POST['firstName'] ?? '';
    $lastName = $_POST['lastName'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $residentId = $_SESSION['resident_id'];

    
    if (empty($firstName) || empty($lastName)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'First name and last name are required']);
        exit;
    }

    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid email format']);
        exit;
    }

    
    $fullName = trim($firstName . ' ' . $lastName);

    
    $stmt = $pdo->prepare("
        UPDATE resident
        SET first_name = :first_name,
            last_name = :last_name,
            full_name = :full_name,
            email = :email,
            phone = :phone,
            contact_number = :phone,
            user_name = :user_name
        WHERE resident_id = :resident_id
    ");

    $result = $stmt->execute([
        ':first_name' => $firstName,
        ':last_name' => $lastName,
        ':full_name' => $fullName,
        ':email' => $email,
        ':phone' => $phone,
        ':user_name' => $fullName,
        ':resident_id' => $residentId
    ]);

    if ($result) {
        
        $_SESSION['user_name'] = $fullName;

        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully',
            'fullName' => $fullName
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update profile']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

