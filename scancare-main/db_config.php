<?php





require_once __DIR__ . '/app_config.php';

date_default_timezone_set(getEnvironmentValue('APP_TIMEZONE', 'Asia/Manila'));

define('DB_HOST', getEnvironmentValue('DB_HOST', 'localhost'));
define('DB_USER', getEnvironmentValue('DB_USER','root'));
define('DB_PASS', getEnvironmentValue('DB_PASS',''));
define('DB_NAME', getEnvironmentValue('DB_NAME', 'scancare_database'));
define('DB_CHARSET', getEnvironmentValue('DB_CHARSET', 'utf8mb4'));
define('DB_TIMEZONE', getEnvironmentValue('DB_TIMEZONE', '+08:00'));


function getDatabaseConnection() {
    try {
        if (DB_HOST === '' || DB_USER === '' || DB_NAME === '') {
            error_log('Database configuration is incomplete.');
            return false;
        }

        if (isProductionEnvironment() && DB_PASS === '') {
            error_log('DB_PASS is required in production.');
            return false;
        }

        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

        $pdo_options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
        $pdo->exec("SET SESSION time_zone = '" . DB_TIMEZONE . "'");
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database Connection Error: " . $e->getMessage());
        if (filter_var(ini_get('display_errors'), FILTER_VALIDATE_BOOLEAN)) {
            echo '<pre>Database Connection Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
        }
        return false;
    }
}

/**
 * Checks that the account stored in the current PHP session still exists and
 * remains active. This prevents a deleted or deactivated account from using a
 * session that was created before the account changed.
 */
function isCurrentSessionAccountActive(?PDO $pdo = null): bool {
    $userType = strtolower((string) ($_SESSION['user_type'] ?? ''));
    $accountMap = [
        'resident' => ['table' => 'resident', 'id_column' => 'resident_id', 'session_key' => 'resident_id'],
        'health_worker' => ['table' => 'health_worker', 'id_column' => 'worker_id', 'session_key' => 'worker_id'],
        'admin' => ['table' => 'system_admins', 'id_column' => 'admin_id', 'session_key' => 'admin_id'],
    ];

    if (!isset($accountMap[$userType])) {
        return false;
    }

    $account = $accountMap[$userType];
    $accountId = (int) ($_SESSION[$account['session_key']] ?? $_SESSION['user_id'] ?? 0);
    if ($accountId <= 0) {
        return false;
    }

    try {
        $pdo = $pdo ?: getDatabaseConnection();
        // Do not log a valid user out merely because the database is temporarily unavailable.
        if ($pdo === false) {
            return true;
        }

        $conditions = ["{$account['id_column']} = ?"];
        if (hasTableColumn($pdo, $account['table'], 'is_active')) {
            $conditions[] = 'is_active = 1';
        }
        if (hasTableColumn($pdo, $account['table'], 'deleted_at')) {
            $conditions[] = 'deleted_at IS NULL';
        }
        if ($userType === 'health_worker' && hasTableColumn($pdo, 'health_worker', 'approval_status')) {
            $conditions[] = "LOWER(COALESCE(approval_status, '')) = 'approved'";
        }

        $statement = $pdo->prepare(
            "SELECT 1 FROM {$account['table']} WHERE " . implode(' AND ', $conditions) . ' LIMIT 1'
        );
        $statement->execute([$accountId]);
        return (bool) $statement->fetchColumn();
    } catch (Throwable $error) {
        error_log('Session account validation failed: ' . $error->getMessage());
        return true;
    }
}

function invalidateCurrentSession(): void {
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function requireCurrentSessionAccount(string $redirect = 'login.php'): void {
    if (isCurrentSessionAccountActive()) {
        return;
    }

    invalidateCurrentSession();
    header('Location: ' . $redirect . '?account_removed=1');
    exit;
}


function hasTableColumn(PDO $pdo, $table, $column) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM {$table} LIKE '{$column}'");
        return $stmt && $stmt->rowCount() > 0;
    } catch (Exception $e) {
        error_log("Column check failed for {$table}.{$column}: " . $e->getMessage());
        return false;
    }
}


function ensureTableColumn(PDO $pdo, $table, $column, $definition) {
    try {
        if (!hasTableColumn($pdo, $table, $column)) {
            $sql = "ALTER TABLE {$table} ADD COLUMN {$column} {$definition}";
            $pdo->exec($sql);
            error_log("Added missing column {$table}.{$column}");
        }
        return true;
    } catch (Exception $e) {
        error_log("Failed to ensure column {$table}.{$column}: " . $e->getMessage());
        return false;
    }
}

function ensureMedicalHistoryAppointmentColumns(PDO $pdo) {
    if (!ensureTableColumn($pdo, 'medical_history', 'appointment_id', 'BIGINT UNSIGNED NULL')
        || !ensureTableColumn($pdo, 'medical_history', 'consulted_at', 'DATETIME NULL')) {
        return false;
    }

    try {
        $index = $pdo->query("SHOW INDEX FROM medical_history WHERE Key_name = 'idx_medical_history_appointment'");
        if (!$index || !$index->fetch(PDO::FETCH_ASSOC)) {
            $pdo->exec('ALTER TABLE medical_history ADD INDEX idx_medical_history_appointment (appointment_id)');
        }
        return true;
    } catch (Throwable $e) {
        error_log('Failed to ensure medical history appointment index: ' . $e->getMessage());
        return false;
    }
}

function ensureResidentPatientCodeColumn(PDO $pdo) {
    try {
        // Add the patient_code column if it does not exist
        if (!hasTableColumn($pdo, 'resident', 'patient_code')) {
            $pdo->exec("ALTER TABLE resident ADD COLUMN patient_code VARCHAR(10) DEFAULT NULL");
            error_log('Added missing column resident.patient_code');
        }

        // Add a unique index to ensure patient_code values stay unique
        $indexStmt = $pdo->query("SHOW INDEX FROM resident WHERE Key_name = 'uk_patient_code'");
        if ($indexStmt && $indexStmt->rowCount() === 0) {
            $pdo->exec("ALTER TABLE resident ADD UNIQUE KEY uk_patient_code (patient_code)");
            error_log('Added unique index resident.uk_patient_code');
        }

        return true;
    } catch (Exception $e) {
        error_log('Failed to ensure resident.patient_code column/index: ' . $e->getMessage());
        return false;
    }
}

function ensureResidentSexColumn(PDO $pdo) {
    try {
        return ensureTableColumn($pdo, 'resident', 'sex', 'VARCHAR(10) DEFAULT NULL');
    } catch (Exception $e) {
        error_log('Failed to ensure resident.sex column: ' . $e->getMessage());
        return false;
    }
}

function ensureFileRequestsTable(PDO $pdo) {
    try {
        $residentKey = $pdo->query("SHOW COLUMNS FROM resident LIKE 'resident_id'")->fetch(PDO::FETCH_ASSOC);
        $workerKey = $pdo->query("SHOW COLUMNS FROM health_worker LIKE 'worker_id'")->fetch(PDO::FETCH_ASSOC);
        $residentType = (is_array($residentKey) && stripos($residentKey['Type'] ?? '', 'unsigned') !== false) ? 'BIGINT UNSIGNED' : 'BIGINT';
        $workerType = (is_array($workerKey) && stripos($workerKey['Type'] ?? '', 'unsigned') !== false) ? 'BIGINT UNSIGNED' : 'BIGINT';
        $pdo->exec("CREATE TABLE IF NOT EXISTS file_requests (
            request_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            resident_id {$residentType} NOT NULL,
            health_worker_id {$workerType} NULL,
            requested_file VARCHAR(100) NOT NULL DEFAULT 'Medical Record',
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            response_note TEXT NULL,
            processed_at DATETIME NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (request_id),
            KEY idx_file_request_resident_status (resident_id, status),
            KEY idx_file_request_worker_status (health_worker_id, status),
            CONSTRAINT fk_file_request_resident FOREIGN KEY (resident_id) REFERENCES resident(resident_id) ON DELETE CASCADE,
            CONSTRAINT fk_file_request_worker FOREIGN KEY (health_worker_id) REFERENCES health_worker(worker_id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        return true;
    } catch (Exception $e) {
        error_log('Failed to ensure file_requests table: ' . $e->getMessage());
        return false;
    }
}

function generateUniquePatientCode(PDO $pdo) {
    $maxAttempts = 100;
    while ($maxAttempts-- > 0) {
        $candidate = random_int(100000, 999999);
        $stmt = $pdo->prepare('SELECT 1 FROM resident WHERE patient_code = ? LIMIT 1');
        $stmt->execute([$candidate]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            return (string)$candidate;
        }
    }
    throw new Exception('Unable to generate unique patient code after multiple attempts.');
}

function ensureResidentPatientCode(PDO $pdo, int $residentId) {
    try {
        $stmt = $pdo->prepare('SELECT patient_code FROM resident WHERE resident_id = ? LIMIT 1');
        $stmt->execute([$residentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        if (!empty($row['patient_code'])) {
            return $row['patient_code'];
        }

        $patientCode = generateUniquePatientCode($pdo);
        $update = $pdo->prepare('UPDATE resident SET patient_code = ? WHERE resident_id = ?');
        $update->execute([$patientCode, $residentId]);
        return $patientCode;
    } catch (Exception $e) {
        error_log('Failed to ensure patient code for resident_id ' . $residentId . ': ' . $e->getMessage());
        return null;
    }
}

function ensureUserLoginSessionsTable(PDO $pdo) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_login_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(255) NOT NULL,
            user_type VARCHAR(50) NOT NULL,
            session_id VARCHAR(255) NOT NULL,
            device_id VARCHAR(255) NULL,
            device_token VARCHAR(255) NULL,
            location VARCHAR(255) NULL,
            ip_address VARCHAR(45) NULL,
            temp_token VARCHAR(255) DEFAULT NULL,
            status VARCHAR(25) NOT NULL,
            trusted TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_session (user_id, user_type, session_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ensureTableColumn($pdo, 'user_login_sessions', 'device_token', 'VARCHAR(255) NULL');
        ensureTableColumn($pdo, 'user_login_sessions', 'trusted', 'TINYINT(1) NOT NULL DEFAULT 0');
        return true;
    } catch (Exception $e) {
        error_log('Failed to ensure user_login_sessions table: ' . $e->getMessage());
        return false;
    }
}


function initializeDatabaseTables() {
    try {
        $pdo = getDatabaseConnection();
        if (!$pdo) {
            return false;
        }
        
        
        $create_users_table = "
            CREATE TABLE IF NOT EXISTS users (
                user_id INT AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                role ENUM('resident', 'bhw', 'admin') NOT NULL DEFAULT 'resident',
                profile_pix VARCHAR(255) NULL DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_email (email),
                INDEX idx_role (role)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET . "
        ";
        
        $pdo->exec($create_users_table);
        
        
        $create_appointments_table = "
            CREATE TABLE IF NOT EXISTS appointments (
                appointment_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                appointment_date DATE NOT NULL,
                appointment_time TIME NOT NULL,
                reason VARCHAR(255) NOT NULL,
                status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_date (appointment_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET . "
        ";
        
        $pdo->exec($create_appointments_table);
        
        
        $create_admins_table = "
            CREATE TABLE IF NOT EXISTS system_admins (
                admin_id BIGINT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(255) NOT NULL UNIQUE,
                email VARCHAR(255) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                full_name VARCHAR(255) NOT NULL,
                role ENUM('super_admin','admin','moderator') DEFAULT 'admin',
                is_active TINYINT(1) DEFAULT 1,
                last_login DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_email (email),
                UNIQUE KEY uk_username (username),
                KEY idx_is_active (is_active),
                KEY idx_role (role)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET . "
        ";
        
        $pdo->exec($create_admins_table);
        
        return true;
        
    } catch (PDOException $e) {
        error_log("Database Table Creation Error: " . $e->getMessage());
        return false;
    }
}


function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}


function verifyPassword($password, $hash) {
    return is_string($password) && is_string($hash) && $hash !== '' && password_verify($password, $hash);
}


function sanitizeInput($input) {
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}


function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}


function ensurePhoneSignupSchema(PDO $pdo) {
    $tables = [
        ['table' => 'resident', 'email_column' => 'email', 'phone_column' => 'contact_number'],
        ['table' => 'health_worker', 'email_column' => 'email', 'phone_column' => 'contact_number'],
    ];

    foreach ($tables as $tableConfig) {
        $table = $tableConfig['table'];
        $emailColumn = $tableConfig['email_column'];
        $phoneColumn = $tableConfig['phone_column'];

        try {
            if (hasTableColumn($pdo, $table, $emailColumn)) {
            $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE '{$emailColumn}'");
            $column = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
                if ($column && strtoupper($column['Null'] ?? 'NO') !== 'YES') {
                    $pdo->exec("ALTER TABLE `{$table}` MODIFY `{$emailColumn}` VARCHAR(255) NULL");
                }

                $indexStmt = $pdo->query("SHOW INDEX FROM `{$table}`");
                $indexes = $indexStmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($indexes as $index) {
                    if ($index['Column_name'] === $emailColumn && $index['Non_unique'] == 0 && $index['Key_name'] !== 'PRIMARY') {
                        $pdo->exec("ALTER TABLE `{$table}` DROP INDEX `{$index['Key_name']}`");
                        break;
                    }
                }
            }

            if (hasTableColumn($pdo, $table, $phoneColumn)) {
                $phoneIndexExists = false;
                $indexStmt = $pdo->query("SHOW INDEX FROM `{$table}`");
                $indexes = $indexStmt ? $indexStmt->fetchAll(PDO::FETCH_ASSOC) : [];
                foreach ($indexes as $index) {
                    if ($index['Column_name'] === $phoneColumn && $index['Non_unique'] == 0) {
                        $phoneIndexExists = true;
                        break;
                    }
                }

                if (!$phoneIndexExists) {
                    $pdo->exec("ALTER TABLE `{$table}` ADD UNIQUE INDEX `ux_{$table}_{$phoneColumn}` (`{$phoneColumn}`)");
                }
            }
        } catch (Exception $e) {
            error_log("Phone signup schema compatibility check failed for {$table}: " . $e->getMessage());
        }
    }

    return true;
}


function generateUserId($role) {
    $role_prefix = strtoupper(substr($role, 0, 3));
    $timestamp = time();
    $random = strtoupper(bin2hex(random_bytes(3)));
    return "SC-{$role_prefix}-{$timestamp}-{$random}";
}


function logActivity($pdo, $user_id, $user_role, $action_type, $action_description) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
            log_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            user_role_snapshot VARCHAR(50) NULL,
            action_type VARCHAR(100) NULL,
            action_description TEXT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_activity_user (user_id),
            INDEX idx_activity_action (action_type),
            INDEX idx_activity_timestamp (timestamp)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        
        try {
            $log_query = "
                INSERT INTO activity_logs 
                (user_id, user_role_snapshot, action_type, action_description, ip_address, user_agent, timestamp) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ";
            
            $log_stmt = $pdo->prepare($log_query);
            $result = $log_stmt->execute([
                $user_id,
                $user_role,
                $action_type,
                $action_description,
                $ip_address,
                $user_agent
            ]);
            
            if ($result) {
                error_log("✓ Activity logged: action_type={$action_type}, user_id={$user_id}, role={$user_role}");
                return true;
            }
        } catch (Exception $fk_error) {
            
            error_log("FK constraint issue, attempting to disable constraint: " . $fk_error->getMessage());
            
            try {
                
                $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
                
                $log_query = "
                    INSERT INTO activity_logs 
                    (user_id, user_role_snapshot, action_type, action_description, ip_address, user_agent, timestamp) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ";
                
                $log_stmt = $pdo->prepare($log_query);
                $result = $log_stmt->execute([
                    $user_id,
                    $user_role,
                    $action_type,
                    $action_description,
                    $ip_address,
                    $user_agent
                ]);
                
                
                $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
                
                if ($result) {
                    error_log("✓ Activity logged (FK disabled): action_type={$action_type}, user_id={$user_id}, role={$user_role}");
                    return true;
                }
            } catch (Exception $fallback_error) {
                error_log("Activity logging failed completely: " . $fallback_error->getMessage());
                return false;
            }
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Activity Log Error: " . $e->getMessage());
        return false;
    }
}


function ensureProfilePictureColumns() {
    static $migrated = false;
    
    if ($migrated) {
        return true;
    }
    
    try {
        $pdo = getDatabaseConnection();
        if (!$pdo) {
            return false;
        }
        
        $migrations = [
            [
                'table' => 'health_worker',
                'column' => 'profile_picture',
                'type' => 'VARCHAR(255) NULL DEFAULT NULL'
            ],
            [
                'table' => 'resident',
                'column' => 'profile_picture',
                'type' => 'VARCHAR(255) NULL DEFAULT NULL'
            ]
        ];
        
        foreach ($migrations as $migration) {
            $checkQuery = "
                SELECT COLUMN_NAME 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_NAME = ? 
                AND COLUMN_NAME = ?
                AND TABLE_SCHEMA = DATABASE()
            ";
            
            try {
                $stmt = $pdo->prepare($checkQuery);
                if ($stmt && $stmt->execute([$migration['table'], $migration['column']])) {
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$result) {
                        
                        $addColumnQuery = "ALTER TABLE {$migration['table']} ADD COLUMN {$migration['column']} {$migration['type']}";
                        try {
                            $pdo->exec($addColumnQuery);
                            error_log("✓ Added profile_picture column to {$migration['table']} table");
                        } catch (Exception $e) {
                            error_log("Note: {$migration['table']}.{$migration['column']} may already exist: " . $e->getMessage());
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Migration check for {$migration['table']}: " . $e->getMessage());
            }
        }
        
        
        $uploadDir = __DIR__ . '/assets/uploads/profile_pics';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        
        $migrated = true;
        return true;
        
    } catch (Exception $e) {
        error_log("Profile picture migration error: " . $e->getMessage());
        return false;
    }
}


function ensureSurveillanceMetricsSchema(PDO $pdo): bool {
    $pdo->exec("CREATE TABLE IF NOT EXISTS surveillance_metrics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        worker_id INT NOT NULL,
        reporting_period VARCHAR(255) DEFAULT NULL,
        disease_name VARCHAR(255) DEFAULT NULL,
        case_count INT DEFAULT 0,
        trend_status VARCHAR(255) DEFAULT NULL,
        meta_notes TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $columns = [
        'disease_name' => 'VARCHAR(255) DEFAULT NULL',
        'case_count' => 'INT DEFAULT 0',
        'trend_status' => 'VARCHAR(255) DEFAULT NULL',
        'meta_notes' => 'TEXT DEFAULT NULL'
    ];
    foreach ($columns as $column => $definition) {
        if (!hasTableColumn($pdo, 'surveillance_metrics', $column)) {
            $pdo->exec("ALTER TABLE surveillance_metrics ADD COLUMN {$column} {$definition}");
        }
    }

    $uniqueIndexes = $pdo->query("SELECT DISTINCT INDEX_NAME
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'surveillance_metrics'
        AND COLUMN_NAME = 'worker_id'
        AND NON_UNIQUE = 0
        AND INDEX_NAME <> 'PRIMARY'")->fetchAll(PDO::FETCH_COLUMN);
    $workerIndex = $pdo->query("SHOW INDEX FROM surveillance_metrics WHERE Key_name = 'idx_surveillance_worker'")->fetch(PDO::FETCH_ASSOC);
    if (!$workerIndex) {
        $pdo->exec('ALTER TABLE surveillance_metrics ADD INDEX idx_surveillance_worker (worker_id)');
    }
    foreach ($uniqueIndexes as $indexName) {
        $pdo->exec('ALTER TABLE surveillance_metrics DROP INDEX `' . str_replace('`', '``', $indexName) . '`');
    }

    return true;
}

function getResidentSurveillanceMetrics(PDO $pdo): array {
    $metrics = [
        'reporting_period' => 'Latest patient records',
        'updated_at' => null,
        'disease_records' => [],
        'dengue_count' => 0,
        'rabies_count' => 0,
        'respiratory_count' => 0,
        'nutrition_count' => 0,
        'dengue_meta' => '',
        'rabies_meta' => '',
        'respiratory_meta' => '',
        'nutrition_meta' => ''
    ];

    // Community tallies come from each resident's latest saved medical-history checklist.
    // One checked item contributes at most one count per resident.
    try {
        $historyStmt = $pdo->query("SELECT mh.user_id, mh.treatment, mh.visit_date, mh.created_at
            FROM medical_history mh
            INNER JOIN (
                SELECT user_id, MAX(history_id) AS latest_history_id
                FROM medical_history
                GROUP BY user_id
            ) latest ON latest.latest_history_id = mh.history_id
            ORDER BY mh.visit_date DESC, mh.created_at DESC");
        $historyRows = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $error) {
        error_log('Resident surveillance aggregation error: ' . $error->getMessage());
        $historyRows = [];
    }

    $defaultLabels = [
        'asthma' => 'Asthma',
        'epilepsy' => 'Epilepsy',
        'headache' => 'Sakit sa Ulo',
        'diabetes' => 'Diabetes',
        'hypertension' => 'Hypertension',
        'allergies' => 'Allergies',
        'medications' => 'On Medications',
        'hospitalization' => 'Hospitalization / Surgery'
    ];
    $tallies = [];
    $latestUpdatedAt = null;

    foreach ($historyRows as $row) {
        $clinicalData = json_decode((string) ($row['treatment'] ?? ''), true);
        if (!is_array($clinicalData)) {
            continue;
        }
        $customLabels = array_filter((array) ($clinicalData['medical_history_options'] ?? []), 'is_string');
        $seenForResident = [];
        foreach ((array) ($clinicalData['medical_history'] ?? []) as $optionId) {
            $optionId = trim((string) $optionId);
            $label = trim((string) ($customLabels[$optionId] ?? $defaultLabels[$optionId] ?? $optionId));
            if ($label === '') {
                continue;
            }
            $key = strtolower(preg_replace('/\s+/', ' ', $label));
            if (isset($seenForResident[$key])) {
                continue;
            }
            $seenForResident[$key] = true;
            if (!isset($tallies[$key])) {
                $tallies[$key] = ['label' => $label, 'count' => 0];
            }
            $tallies[$key]['count']++;
        }
        $rowUpdatedAt = $row['created_at'] ?? $row['visit_date'] ?? null;
        if ($rowUpdatedAt && ($latestUpdatedAt === null || strtotime($rowUpdatedAt) > strtotime($latestUpdatedAt))) {
            $latestUpdatedAt = $rowUpdatedAt;
        }
    }

    foreach ($tallies as $key => $tally) {
        $metrics['disease_records'][] = [
            'id' => 'history-' . substr(sha1($key), 0, 12),
            'disease_name' => $tally['label'],
            'case_count' => $tally['count'],
            'trend_status' => 'Baseline',
            'meta_notes' => $tally['count'] . ' resident' . ($tally['count'] === 1 ? ' is' : 's are') . ' marked with ' . $tally['label'] . '.',
            'reporting_period' => 'Latest patient records',
            'updated_at' => $latestUpdatedAt
        ];
    }
    usort($metrics['disease_records'], static fn($a, $b) => $b['case_count'] <=> $a['case_count'] ?: strcasecmp($a['disease_name'], $b['disease_name']));
    $metrics['updated_at'] = $latestUpdatedAt;

    foreach ($metrics['disease_records'] as $record) {
        $name = strtolower(trim((string) ($record['disease_name'] ?? '')));
        $category = null;
        if (str_contains($name, 'dengue')) {
            $category = 'dengue';
        } elseif (str_contains($name, 'rabies') || str_contains($name, 'bite')) {
            $category = 'rabies';
        } elseif (str_contains($name, 'respiratory') || str_contains($name, 'flu') || str_contains($name, 'pneumonia') || str_contains($name, 'covid') || str_contains($name, 'tuberculosis')) {
            $category = 'respiratory';
        } elseif (str_contains($name, 'nutrition') || str_contains($name, 'malnutrition') || str_contains($name, 'underweight')) {
            $category = 'nutrition';
        }

        if ($category === null) {
            continue;
        }

        $count = max(0, (int) ($record['case_count'] ?? 0));
        $metrics[$category . '_count'] += $count;
        $note = trim((string) ($record['meta_notes'] ?? ''));
        $metrics[$category . '_meta'] = $note !== '' ? $note : ucfirst($name) . ' cases reported.';
    }

    foreach (['dengue', 'rabies', 'respiratory', 'nutrition'] as $category) {
        if ($metrics[$category . '_count'] === 0) {
            $metrics[$category . '_meta'] = $category === 'rabies'
                ? '0% coverage | No residents vaccinated.'
                : 'No active cases reported.';
        }
    }

    return $metrics;
}

?>

