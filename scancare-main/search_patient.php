<?php


header('Content-Type: application/json');
session_start();




if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access.'
    ]);
    exit;
}

$user_role = strtolower($_SESSION['user_role'] ?? $_SESSION['worker_role'] ?? '');
if (!in_array($user_role, ['bhw', 'health_worker', 'worker', 'admin'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Permission denied.'
    ]);
    exit;
}

require_once 'db_config.php';





if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['resident_id'])) {
    try {
        $resident_id = trim($_GET['resident_id']);
        
        if (!$resident_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'patient' => null]);
            exit;
        }
        
        
        if (!preg_match('/^\d{6}$/', $resident_id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'patient' => null]);
            exit;
        }
        
        $pdo = getDatabaseConnection();
        if (!$pdo) {
            throw new Exception("Database connection failed");
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                resident_id,
                full_name,
                contact_number,
                email,
                date_of_birth,
                address
            FROM resident
            WHERE patient_code = ? OR CAST(resident_id AS CHAR) = ? OR resident_id = ?
            LIMIT 1
        ");
        
        $stmt->execute([$resident_id, $resident_id, intval($resident_id)]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($patient) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'patient' => [
                    'resident_id' => $patient['resident_id'],
                    'full_name' => $patient['full_name'] ?? 'Unknown',
                    'contact_number' => $patient['contact_number'] ?? 'N/A',
                    'email' => $patient['email'] ?? 'N/A'
                ]
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'patient' => null]);
        }
        
        exit;
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'patient' => null]);
        error_log("Error in search_patient GET: " . $e->getMessage());
        exit;
    }
}




if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        
        
        
        $search_term = trim($_POST['search_term'] ?? '');
        
        if (empty($search_term)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Please enter a search term (name, phone, or ID).'
            ]);
            exit;
        }

        
        if (strlen($search_term) > 100) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Search term is too long. Please enter a shorter query.'
            ]);
            exit;
        }

        
        
        
        $pdo = getDatabaseConnection();
        
        if ($pdo === false) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Database connection error. Please try again later.'
            ]);
            exit;
        }

        
        
        
        
        $query = "
            SELECT 
                user_id,
                full_name,
                phone_number,
                email,
                role,
                DATE_FORMAT(date_of_birth, '%M %d, %Y') as formatted_dob
            FROM users
            WHERE (
                full_name LIKE ? 
                OR phone_number LIKE ?
                OR user_id = ?
            )
            AND role = 'resident'
            AND is_active = 1
            LIMIT 5
        ";

        
        
        
        $stmt = $pdo->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Statement preparation failed: " . implode(", ", $pdo->errorInfo()));
        }

        
        
        
        
        $search_param = '%' . $search_term . '%';
        $id_param = is_numeric($search_term) ? intval($search_term) : null;

        $success = $stmt->execute([
            $search_param,      
            $search_param,      
            $id_param           
        ]);

        if (!$success) {
            throw new Exception("Query execution failed: " . implode(", ", $stmt->errorInfo()));
        }

        
        
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($results)) {
            echo json_encode([
                'success' => false,
                'message' => 'Patient record not found. Please try searching with a different name, phone number, or ID.'
            ]);
            exit;
        }

        
        
        
        
        if (count($results) === 1) {
            
            $patient = $results[0];
            echo json_encode([
                'success' => true,
                'action' => 'redirect',
                'redirect_url' => 'patient_profile.php?id=' . urlencode($patient['user_id']),
                'patient_name' => htmlspecialchars($patient['full_name']),
                'message' => 'Patient found. Redirecting to profile...'
            ]);
            exit;
        } else {
            
            $patient_list = [];
            foreach ($results as $patient) {
                $patient_list[] = [
                    'id' => $patient['user_id'],
                    'name' => htmlspecialchars($patient['full_name']),
                    'phone' => htmlspecialchars($patient['phone_number'] ?? 'N/A'),
                    'dob' => $patient['formatted_dob'] ?? 'N/A'
                ];
            }

            echo json_encode([
                'success' => true,
                'action' => 'select',
                'patients' => $patient_list,
                'message' => count($results) . ' results found. Please select a patient.'
            ]);
            exit;
        }

    } catch (Exception $e) {
        error_log("PATIENT SEARCH ERROR: " . $e->getMessage() . " | Search term: " . ($_POST['search_term'] ?? 'N/A'));
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred while searching. Please try again.'
        ]);
        exit;
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Use POST request.'
    ]);
    exit;
}

?>

