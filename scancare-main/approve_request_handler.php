<?php


require_once 'db_config.php';

header('Content-Type: application/json; charset=utf-8');
http_response_code(200); 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

session_start();


if (!isset($_SESSION['admin_auth']) || $_SESSION['admin_auth'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$action = trim($_POST['action'] ?? '');
$request_id = intval($_POST['request_id'] ?? 0);
$decision = trim($_POST['decision'] ?? '');

if ($request_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
    exit;
}

if (!in_array($decision, ['approve', 'reject'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid decision']);
    exit;
}

try {
    $pdo = getDatabaseConnection();
    if ($pdo === false) {
        throw new Exception('Database connection failed');
    }

    
    $selectStmt = $pdo->prepare("
        SELECT ar.request_id, ar.resident_id, ar.request_type, ar.status,
               r.email AS account_email, r.full_name AS resident_name, r.contact_number
        FROM account_requests ar
        LEFT JOIN resident r ON ar.resident_id = r.resident_id
        WHERE ar.request_id = ?
    ");
    $selectStmt->execute([$request_id]);
    $request = $selectStmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        exit;
    }

    if ($request['status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Request already processed']);
        exit;
    }

    $resident_id = intval($request['resident_id']);

    
    $pdo->beginTransaction();

    try {
        if ($decision === 'approve') {
            
            $updateResident = $pdo->prepare("
                UPDATE resident
                SET is_active = 0, deleted_at = NOW() 
                WHERE resident_id = ?
            ");
            if (!$updateResident->execute([$resident_id])) {
                throw new Exception('Failed to update resident');
            }

            
            $updateRequest = $pdo->prepare("
                UPDATE account_requests 
                SET status = 'approved', updated_at = NOW() 
                WHERE request_id = ?
            ");
            if (!$updateRequest->execute([$request_id])) {
                throw new Exception('Failed to update request');
            }

            
            $pdo->commit();

            error_log("SUCCESS: Approved request_id={$request_id}, resident_id={$resident_id}");

            echo json_encode([
                'success' => true,
                'message' => 'Request approved successfully',
                'resident' => [
                    'resident_id' => $resident_id,
                    'full_name' => $request['resident_name'],
                    'email' => $request['account_email'],
                    'contact_number' => $request['contact_number']
                ]
            ]);
            exit;
        }

        if ($decision === 'reject') {
            
            $updateRequest = $pdo->prepare("
                UPDATE account_requests 
                SET status = 'rejected', updated_at = NOW() 
                WHERE request_id = ?
            ");
            if (!$updateRequest->execute([$request_id])) {
                throw new Exception('Failed to reject request');
            }

            
            $pdo->commit();

            error_log("SUCCESS: Rejected request_id={$request_id}");

            echo json_encode([
                'success' => true,
                'message' => 'Request rejected successfully'
            ]);
            exit;
        }

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

} catch (Throwable $e) {
    error_log('APPROVAL ERROR: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Unable to process the request.'
    ]);
    exit;
}


echo json_encode(['success' => false, 'message' => 'Unknown error']);
exit;
?>

