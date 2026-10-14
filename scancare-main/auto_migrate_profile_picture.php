<?php


function ensureProfilePictureColumns() {
    try {
        require_once __DIR__ . '/db_config.php';
        $pdo = getDatabaseConnection();
        
        if (!$pdo) {
            return false;
        }
        
        $migrations = [
            [
                'table' => 'health_worker',
                'column' => 'profile_picture',
                'type' => 'VARCHAR(255) NULL DEFAULT NULL COMMENT "Path to profile picture file"'
            ],
            [
                'table' => 'resident',
                'column' => 'profile_picture',
                'type' => 'VARCHAR(255) NULL DEFAULT NULL COMMENT "Path to profile picture file"'
            ]
        ];
        
        foreach ($migrations as $migration) {
            $checkQuery = "
                SELECT COLUMN_NAME 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_NAME = '{$migration['table']}' 
                AND COLUMN_NAME = '{$migration['column']}'
                AND TABLE_SCHEMA = DATABASE()
            ";
            
            try {
                $stmt = $pdo->prepare($checkQuery);
                if ($stmt && $stmt->execute()) {
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$result) {
                        
                        $addColumnQuery = "ALTER TABLE {$migration['table']} ADD COLUMN {$migration['column']} {$migration['type']}";
                        $pdo->exec($addColumnQuery);
                    }
                }
            } catch (Exception $e) {
                
                error_log("Migration error for {$migration['table']}.{$migration['column']}: " . $e->getMessage());
            }
        }
        
        
        $uploadDir = __DIR__ . '/assets/uploads/profile_pics';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Profile picture migration failed: " . $e->getMessage());
        return false;
    }
}


ensureProfilePictureColumns();
?>

