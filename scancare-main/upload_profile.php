<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', '0');
ob_start();

$expectsJson = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
    || str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');

function sendUploadResponse(array $payload, bool $expectsJson): void {
    if (ob_get_level() > 0) {
        ob_clean();
        ob_end_clean();
    }
    while (ob_get_level() > 0) ob_end_clean();

    if ($expectsJson) {
        header('Content-Type: application/json');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    } else {
        $returnUrl = $_SERVER['HTTP_REFERER'] ?? 'index.php';
        $separator = str_contains($returnUrl, '?') ? '&' : '?';
        if (!empty($payload['success'])) {
            $path = !empty($payload['path']) ? '&path=' . urlencode((string)$payload['path']) : '';
            header('Location: ' . $returnUrl . $separator . 'upload=success' . $path);
        } else {
            $message = urlencode((string)($payload['message'] ?? 'Profile picture upload failed.'));
            header('Location: ' . $returnUrl . $separator . 'upload=failed&msg=' . $message);
        }
    }
    exit;
}

try {
    error_log('PROFILE_UPLOAD_SESSION: ' . json_encode($_SESSION, JSON_UNESCAPED_SLASHES));

    $sessionUser = is_array($_SESSION['user'] ?? null) ? $_SESSION['user'] : [];
    $isLoggedIn = !empty($_SESSION['user'])
        || !empty($_SESSION['user_id'])
        || !empty($_SESSION['worker_id'])
        || !empty($_SESSION['bhw_id'])
        || !empty($_SESSION['resident_id'])
        || !empty($_SESSION['role'])
        || !empty($_SESSION['user_type'])
        || !empty($_SESSION['full_name'])
        || !empty($_SESSION['email']);

    if (!$isLoggedIn) {
        sendUploadResponse([
            'success' => false,
            'message' => 'User session not active. Please re-login.'
        ], $expectsJson);
    }

    $userId = $_SESSION['user_id']
        ?? $_SESSION['worker_id']
        ?? $_SESSION['bhw_id']
        ?? $_SESSION['resident_id']
        ?? $_SESSION['id']
        ?? $sessionUser['id']
        ?? $sessionUser['user_id']
        ?? $_SESSION['account_id']
        ?? 'bhw_user';
    $tableName = null;
    $userType = $_SESSION['user_type']
        ?? $_SESSION['role']
        ?? $sessionUser['user_type']
        ?? $sessionUser['role']
        ?? null;

    if (($_POST['profile_scope'] ?? '') === 'health_worker') {
        $userId = $_SESSION['worker_id'] ?? $_SESSION['bhw_id'] ?? $userId;
        $tableName = 'health_worker';
        $userType = 'health_worker';
    }
    elseif ($userType === 'health_worker' || $userType === 'bhw') {
        $userId = $_SESSION['worker_id'] ?? $_SESSION['bhw_id'] ?? $userId;
        $tableName = 'health_worker';
        $userType = 'health_worker';
    }

    elseif ($userType === 'resident') {
        $userId = $_SESSION['resident_id'] ?? $userId;
        $tableName = 'resident';
        $userType = 'resident';
    }
    
    elseif (($_POST['profile_scope'] ?? '') === 'users') {
        $userId = intval($userId);
        $tableName = 'users';
        $userType = 'users';
    }
    else {
        $tableName = 'users';
        $userType = 'users';
    }

    if (!$userId || !$tableName) {
        throw new Exception('User session not active. Please re-login.');
    }

    
    if (!isset($_FILES['profile_picture'])) {
        throw new Exception('No file selected from device');
    }

    $file = $_FILES['profile_picture'];

    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds max allowed size',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds max allowed size',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file selected from device',
            UPLOAD_ERR_NO_TMP_DIR => 'Server folder permission issue',
            UPLOAD_ERR_CANT_WRITE => 'Server folder permission issue',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        throw new Exception($errorMessages[$file['error']] ?? 'Unknown upload error');
    }

    
    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $extensionMatches = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp'
    ];
    if (!$mimeType || !isset($allowedMimes[$mimeType]) || !isset($extensionMatches[$fileExt]) || $extensionMatches[$fileExt] !== $mimeType) {
        throw new Exception('Only valid JPG, PNG, or WebP images are allowed');
    }

    
    $maxFileSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxFileSize) {
        throw new Exception('File size exceeds the 5MB limit');
    }

    
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        throw new Exception('Invalid image file');
    }

    
    $dir = __DIR__ . '/assets/uploads/profile_pics/';
    if (!file_exists($dir) && !mkdir($dir, 0777, true)) {
        throw new Exception('Failed to create upload directory. Check server permissions.');
    }
    if (!is_writable($dir)) {
        throw new Exception('Upload directory is not writable. Check directory permissions.');
    }

    $timestamp = time();
    $extension = $allowedMimes[$mimeType];
    $new_filename = 'avatar_' . $userId . '_' . $timestamp . '.' . $extension;
    $uploadPath = $dir . $new_filename;

    
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Failed to save file to server directory. Check directory write permissions.');
    }

    $relativePath = 'assets/uploads/profile_pics/' . basename($new_filename);
    $_SESSION['profile_pic'] = $relativePath;
    $_SESSION['avatar'] = $relativePath;
    if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
        $_SESSION['user']['profile_pic'] = $relativePath;
    }

    
    require_once 'db_config.php';
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        throw new Exception('Database connection failed while saving the profile picture.');
    }

    
    $idColumn = ($userType === 'health_worker') ? 'worker_id' : ($tableName === 'resident' ? 'resident_id' : 'user_id');

    $columnCheck = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'profile_picture'");
    $columnCheck->execute([$tableName]);
    if (!$columnCheck->fetchColumn()) {
        $pdo->exec("ALTER TABLE {$tableName} ADD COLUMN profile_picture VARCHAR(255) NULL DEFAULT NULL");
    }

    
    $oldFileQuery = "SELECT profile_picture FROM {$tableName} WHERE {$idColumn} = ? LIMIT 1";
    $stmt = $pdo->prepare($oldFileQuery);
    if ($stmt && $stmt->execute([$userId])) {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result && !empty($result['profile_picture'])) {
            $oldFilePath = __DIR__ . '/' . $result['profile_picture'];
            if (file_exists($oldFilePath)) {
                @unlink($oldFilePath);
            }
        }
    }

    
    $updateQuery = "UPDATE {$tableName} SET profile_picture = ? WHERE {$idColumn} = ?";
    $stmt = $pdo->prepare($updateQuery);
    if (!$stmt) {
        throw new Exception('Failed to prepare database statement: ' . implode(', ', $pdo->errorInfo()));
    }

    if (!$stmt->execute([$relativePath, $userId])) {
        throw new Exception('Failed to update profile picture in database: ' . implode(', ', $stmt->errorInfo()));
    }

    $_SESSION['profile_picture'] = $relativePath;
    $_SESSION['profile_pic'] = $relativePath;
    $_SESSION['avatar'] = $relativePath;
    if (is_array($_SESSION['user'] ?? null)) {
        $_SESSION['user']['profile_pic'] = $relativePath;
    }

    
    sendUploadResponse([
        'success' => true,
        'message' => 'Profile picture uploaded successfully',
        'path' => $relativePath,
        'image_url' => $relativePath . '?t=' . $timestamp,
        'user_type' => $userType
    ], $expectsJson);

} catch (Throwable $e) {
    error_log('PROFILE_UPLOAD_ERROR: ' . $e->getMessage());
    sendUploadResponse([
        'success' => false,
        'message' => $e->getMessage()
    ], $expectsJson);
}
?>

