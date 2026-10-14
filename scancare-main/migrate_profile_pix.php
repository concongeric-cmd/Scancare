<?php


header('Content-Type: text/html; charset=utf-8');

$results = [];
$success = true;

try {
    require_once 'db_config.php';
    $pdo = getDatabaseConnection();
    
    
    
    
    $checkHealthWorkerQuery = "
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'health_worker' 
        AND COLUMN_NAME = 'profile_picture'
        AND TABLE_SCHEMA = DATABASE()
    ";
    
    $stmt = $pdo->prepare($checkHealthWorkerQuery);
    if (!$stmt || !$stmt->execute()) {
        $results[] = [
            'table' => 'health_worker',
            'status' => 'error',
            'message' => 'Failed to check for profile_picture column'
        ];
        $success = false;
    } else {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            
            $addColumnQuery = "ALTER TABLE health_worker ADD COLUMN profile_picture VARCHAR(255) NULL DEFAULT NULL COMMENT 'Path to profile picture file'";
            
            try {
                $pdo->exec($addColumnQuery);
                $results[] = [
                    'table' => 'health_worker',
                    'status' => 'success',
                    'message' => 'Profile picture column added successfully'
                ];
            } catch (PDOException $e) {
                $results[] = [
                    'table' => 'health_worker',
                    'status' => 'error',
                    'message' => 'Failed to add profile_picture column: ' . $e->getMessage()
                ];
                $success = false;
            }
        } else {
            $results[] = [
                'table' => 'health_worker',
                'status' => 'info',
                'message' => 'Profile picture column already exists'
            ];
        }
    }
    
    
    
    
    $checkResidentQuery = "
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'resident' 
        AND COLUMN_NAME = 'profile_picture'
        AND TABLE_SCHEMA = DATABASE()
    ";
    
    $stmt = $pdo->prepare($checkResidentQuery);
    if (!$stmt || !$stmt->execute()) {
        $results[] = [
            'table' => 'resident',
            'status' => 'error',
            'message' => 'Failed to check for profile_picture column'
        ];
        $success = false;
    } else {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            
            $addColumnQuery = "ALTER TABLE resident ADD COLUMN profile_picture VARCHAR(255) NULL DEFAULT NULL COMMENT 'Path to profile picture file'";
            
            try {
                $pdo->exec($addColumnQuery);
                $results[] = [
                    'table' => 'resident',
                    'status' => 'success',
                    'message' => 'Profile picture column added successfully'
                ];
            } catch (PDOException $e) {
                $results[] = [
                    'table' => 'resident',
                    'status' => 'error',
                    'message' => 'Failed to add profile_picture column: ' . $e->getMessage()
                ];
                $success = false;
            }
        } else {
            $results[] = [
                'table' => 'resident',
                'status' => 'info',
                'message' => 'Profile picture column already exists'
            ];
        }
    }
    
    
    
    
    $uploadDir = __DIR__ . '/assets/uploads/profile_pics';
    if (!is_dir($uploadDir)) {
        if (mkdir($uploadDir, 0755, true)) {
            $results[] = [
                'table' => 'filesystem',
                'status' => 'success',
                'message' => 'Upload directory created: ' . $uploadDir
            ];
        } else {
            $results[] = [
                'table' => 'filesystem',
                'status' => 'error',
                'message' => 'Failed to create upload directory: ' . $uploadDir
            ];
            $success = false;
        }
    } else {
        $results[] = [
            'table' => 'filesystem',
            'status' => 'info',
            'message' => 'Upload directory already exists: ' . $uploadDir
        ];
    }
    
    
    if (!is_writable($uploadDir)) {
        @chmod($uploadDir, 0755);
        if (is_writable($uploadDir)) {
            $results[] = [
                'table' => 'filesystem',
                'status' => 'success',
                'message' => 'Upload directory permissions fixed'
            ];
        } else {
            $results[] = [
                'table' => 'filesystem',
                'status' => 'warning',
                'message' => 'Upload directory may not be writable: ' . $uploadDir
            ];
        }
    }
    
} catch (Exception $e) {
    $results[] = [
        'table' => 'general',
        'status' => 'error',
        'message' => 'Migration error: ' . $e->getMessage()
    ];
    $success = false;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Picture Migration</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #333;
            margin: 0 0 30px 0;
            text-align: center;
            font-size: 28px;
        }
        .result-item {
            padding: 12px 16px;
            margin-bottom: 12px;
            border-radius: 8px;
            border-left: 4px solid #ccc;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .result-item.success {
            background-color: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        .result-item.error {
            background-color: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .result-item.info {
            background-color: #d1ecf1;
            border-left-color: #17a2b8;
            color: #0c5460;
        }
        .result-item.warning {
            background-color: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
        }
        .result-icon {
            font-size: 20px;
            font-weight: bold;
        }
        .result-message {
            flex: 1;
        }
        .result-table {
            font-size: 12px;
            color: #666;
            font-weight: 500;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
        }
        .footer a {
            display: inline-block;
            margin: 10px;
            padding: 10px 20px;
            background-color: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background-color 0.3s;
        }
        .footer a:hover {
            background-color: #764ba2;
        }
        .status-summary {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
        }
        .status-summary.success {
            background-color: #d4edda;
            color: #155724;
        }
        .status-summary.error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Profile Picture Migration</h1>
        
        <div class="status-summary <?php echo $success ? 'success' : 'error'; ?>">
            <?php echo $success ? '✅ Migration completed successfully!' : '❌ Migration completed with errors'; ?>
        </div>
        
        <div class="results">
            <?php foreach ($results as $result): ?>
            <div class="result-item <?php echo $result['status']; ?>">
                <span class="result-icon">
                    <?php
                    switch ($result['status']) {
                        case 'success':
                            echo '✓';
                            break;
                        case 'error':
                            echo '✕';
                            break;
                        case 'info':
                            echo 'ℹ';
                            break;
                        case 'warning':
                            echo '⚠';
                            break;
                    }
                    ?>
                </span>
                <div>
                    <div class="result-message"><?php echo htmlspecialchars($result['message']); ?></div>
                    <div class="result-table"><?php echo htmlspecialchars($result['table']); ?> table</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="footer">
            <a href="dashboard.php">Return to Dashboard</a>
            <a href="dashboard_bhw.php">BHW Dashboard</a>
        </div>
    </div>
</body>
</html>

