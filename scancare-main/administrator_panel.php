<?php













if (ini_get('session.gc_maxlifetime') < 7200) {
    ini_set('session.gc_maxlifetime', 7200);
}

session_start();
require_once 'db_config.php';









$is_authenticated = false;


if (isset($_SESSION['admin_auth']) && $_SESSION['admin_auth'] === true) {
    
    if (isset($_SESSION['admin_id']) && isset($_SESSION['admin_username'])) {
        $is_authenticated = true;
    }
}


if (!$is_authenticated) {
    
    session_destroy();
    header('Location: login_admin.php');
    exit;
}

requireCurrentSessionAccount('admin_login.php');


$_SESSION['last_activity'] = time();


$admin_id = $_SESSION['admin_id'] ?? null;
$admin_username = $_SESSION['admin_username'] ?? 'Administrator';
$admin_role = $_SESSION['admin_role'] ?? 'admin';
$admin_name = $_SESSION['admin_name'] ?? 'Administrator';


$admin_flash_message = $_SESSION['admin_flash_message'] ?? '';
$admin_flash_type = $_SESSION['admin_flash_type'] ?? 'success';
unset($_SESSION['admin_flash_message'], $_SESSION['admin_flash_type']);







$visible_sections = ['dashboard', 'user-management', 'activity-log'];
$active_section = $_GET['section'] ?? 'dashboard';
// The infrastructure/configuration screens below are retained for future
// implementation, but are not exposed until they operate on real data.
if (!in_array($active_section, $visible_sections, true)) {
    $active_section = 'dashboard';
}
$user_management_tab = $_GET['tab'] ?? 'accounts';









$total_residents = 0;  
$total_staff = 0;      
$total_users = 0;      
$total_appointments = 0;
$total_appointment_records = 0; 
$total_records = 0;   
$table_count = 0;     
$active_residents_count = 0;
$inactive_residents_count = 0;
$pending_staff_count = 0;
$deactivated_staff_count = 0;
$active_admin_count = 0;
$inactive_admin_count = 0;
$auth_history = [];
$db_size = 'N/A';
$last_backup = date('Y-m-d H:i:s', strtotime('-2 days'));

try {
    $pdo = getDatabaseConnection();
    
    if ($pdo === false) {
        throw new Exception('Database connection failed');
    }

    
    $pdo->exec("CREATE TABLE IF NOT EXISTS account_requests (
        request_id INT AUTO_INCREMENT PRIMARY KEY,
        resident_id INT NOT NULL,
        request_type ENUM('deactivate','delete') NOT NULL,
        requested_by INT NOT NULL,
        requested_by_role VARCHAR(50) NOT NULL,
        status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        remarks TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_resident_id (resident_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    
    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        user_role_snapshot VARCHAR(50),
        action_type VARCHAR(100),
        action_description TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_timestamp (timestamp)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    
    ensureTableColumn($pdo, 'health_worker', 'is_active', 'TINYINT(1) DEFAULT 1');
    ensureTableColumn($pdo, 'health_worker', 'deleted_at', 'DATETIME NULL DEFAULT NULL');
        ensureTableColumn($pdo, 'health_worker', 'approval_status', "VARCHAR(20) NOT NULL DEFAULT 'approved'");

    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
        $postAction = trim($_POST['action']);
        $redirectSection = 'dashboard';
        $allowedSections = $visible_sections;
        $requestedSection = trim($_POST['redirect_section'] ?? $_GET['section'] ?? 'dashboard');
        if (in_array($requestedSection, $allowedSections, true)) {
            $redirectSection = $requestedSection;
        }

        if ($postAction === 'process_account_request') {
            $request_id = intval($_POST['request_id'] ?? 0);
            $decision = trim($_POST['decision'] ?? '');

            if ($request_id <= 0 || !in_array($decision, ['approve', 'reject'], true)) {
                $_SESSION['admin_flash_message'] = 'Invalid request operation.';
                $_SESSION['admin_flash_type'] = 'error';
                header("Location: administrator_panel.php?section={$redirectSection}");
                exit;
            }

            $stmt = $pdo->prepare("SELECT * FROM account_requests WHERE request_id = ? LIMIT 1");
            $stmt->execute([$request_id]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request || $request['status'] !== 'pending') {
                $_SESSION['admin_flash_message'] = 'Request not found or already processed.';
                $_SESSION['admin_flash_type'] = 'error';
                header("Location: administrator_panel.php?section={$redirectSection}");
                exit;
            }

            if ($decision === 'approve') {
                $resident_id = intval($request['resident_id']);

                ensureTableColumn($pdo, 'resident', 'is_active', 'TINYINT(1) DEFAULT 1');
                ensureTableColumn($pdo, 'resident', 'deleted_at', 'DATETIME NULL DEFAULT NULL');

                $residentCheck = $pdo->prepare("SELECT resident_id FROM resident WHERE resident_id = ? LIMIT 1");
                $residentCheck->execute([$resident_id]);
                $resident = $residentCheck->fetch(PDO::FETCH_ASSOC);

                if (!$resident) {
                    $_SESSION['admin_flash_message'] = 'Resident account not found for approval.';
                    $_SESSION['admin_flash_type'] = 'error';
                    header("Location: administrator_panel.php?section={$redirectSection}");
                    exit;
                }

                $updateResident = $pdo->prepare("UPDATE resident SET is_active = 0, deleted_at = NOW() WHERE resident_id = ?");
                $updateResident->execute([$resident_id]);

                $statusNote = ($request['request_type'] === 'delete') ? 'Approved delete request. Account set inactive (soft deleted).' : 'Approved deactivation request. Account set inactive.';
                $stmt = $pdo->prepare("UPDATE account_requests SET status = 'approved', remarks = ?, updated_at = NOW() WHERE request_id = ?");
                $stmt->execute([$statusNote, $request_id]);

                $_SESSION['admin_flash_message'] = 'Request approved successfully. Resident account has been blocked from login.';
                $_SESSION['admin_flash_type'] = 'success';
                header("Location: administrator_panel.php?section={$redirectSection}");
                exit;
            }

            if ($decision === 'reject') {
                $stmt = $pdo->prepare("UPDATE account_requests SET status = 'rejected', remarks = ?, updated_at = NOW() WHERE request_id = ?");
                $stmt->execute(['Rejected by administrator', $request_id]);

                $_SESSION['admin_flash_message'] = 'Request rejected successfully.';
                $_SESSION['admin_flash_type'] = 'success';
                header("Location: administrator_panel.php?section={$redirectSection}");
                exit;
            }
        }

        if ($postAction === 'restore_resident') {
            $resident_id = intval($_POST['resident_id'] ?? 0);
            if ($resident_id <= 0) {
                $_SESSION['admin_flash_message'] = 'Invalid resident selected for restore.';
                $_SESSION['admin_flash_type'] = 'error';
                header("Location: administrator_panel.php?section={$redirectSection}");
                exit;
            }

            ensureTableColumn($pdo, 'resident', 'is_active', 'TINYINT(1) DEFAULT 1');
            ensureTableColumn($pdo, 'resident', 'deleted_at', 'DATETIME NULL DEFAULT NULL');

            $residentCheck = $pdo->prepare("SELECT resident_id FROM resident WHERE resident_id = ? LIMIT 1");
            $residentCheck->execute([$resident_id]);
            $resident = $residentCheck->fetch(PDO::FETCH_ASSOC);

            if (!$resident) {
                $_SESSION['admin_flash_message'] = 'Resident account not found for restore.';
                $_SESSION['admin_flash_type'] = 'error';
                header("Location: administrator_panel.php?section={$redirectSection}");
                exit;
            }

            $restoreResident = $pdo->prepare("UPDATE resident SET is_active = 1, deleted_at = NULL WHERE resident_id = ?");
            $restoreResident->execute([$resident_id]);

            logActivity($pdo, $admin_id, 'admin', 'account_reactivation', "Admin restored resident account resident_id={$resident_id}");

            $_SESSION['admin_flash_message'] = 'Resident account has been restored successfully.';
            $_SESSION['admin_flash_type'] = 'success';
            header("Location: administrator_panel.php?section={$redirectSection}");
            exit;
        }

        if ($postAction === 'delete_resident') {
            $resident_id = intval($_POST['resident_id'] ?? 0);
            if ($resident_id <= 0) {
                $_SESSION['admin_flash_message'] = 'Invalid resident selected for permanent deletion.';
                $_SESSION['admin_flash_type'] = 'error';
                header("Location: administrator_panel.php?section={$redirectSection}");
                exit;
            }

            $deleteResident = $pdo->prepare("DELETE FROM resident WHERE resident_id = ?");
            $deleteResident->execute([$resident_id]);

            logActivity($pdo, $admin_id, 'admin', 'account_deletion', "Admin permanently deleted resident account resident_id={$resident_id}");

            $_SESSION['admin_flash_message'] = 'Resident account has been permanently deleted.';
            $_SESSION['admin_flash_type'] = 'success';
            header("Location: administrator_panel.php?section={$redirectSection}");
            exit;
        }

        if ($postAction === 'delete_staff') {
            $staff_id = intval($_POST['worker_id'] ?? 0);
            if ($staff_id <= 0) {
                $_SESSION['admin_flash_message'] = 'Invalid staff selected for permanent deletion.';
                $_SESSION['admin_flash_type'] = 'error';
                header("Location: administrator_panel.php?section={$redirectSection}");
                exit;
            }

            $deleteStaff = $pdo->prepare("DELETE FROM health_worker WHERE worker_id = ?");
            $deleteStaff->execute([$staff_id]);
            if ($deleteStaff->rowCount() === 0) {
                $_SESSION['admin_flash_message'] = 'BHW account not found.';
                $_SESSION['admin_flash_type'] = 'error';
            } else {
                logActivity($pdo, $admin_id, 'admin', 'account_deletion', "Admin permanently deleted BHW account worker_id={$staff_id}");
                $_SESSION['admin_flash_message'] = 'BHW account has been permanently deleted.';
                $_SESSION['admin_flash_type'] = 'success';
            }
            header("Location: administrator_panel.php?section={$redirectSection}");
            exit;
        }

        if ($postAction === 'delete_admin') {
            $targetAdminId = intval($_POST['admin_id'] ?? 0);
            if ($targetAdminId <= 0 || $targetAdminId === (int)$admin_id) {
                $_SESSION['admin_flash_message'] = 'You cannot delete the currently signed-in administrator.';
                $_SESSION['admin_flash_type'] = 'error';
                header("Location: administrator_panel.php?section={$redirectSection}");
                exit;
            }

            $deleteAdmin = $pdo->prepare("DELETE FROM system_admins WHERE admin_id = ?");
            $deleteAdmin->execute([$targetAdminId]);
            if ($deleteAdmin->rowCount() === 0) {
                $_SESSION['admin_flash_message'] = 'Administrator account not found.';
                $_SESSION['admin_flash_type'] = 'error';
            } else {
                logActivity($pdo, $admin_id, 'admin', 'account_deletion', "Admin permanently deleted administrator account admin_id={$targetAdminId}");
                $_SESSION['admin_flash_message'] = 'Administrator account has been permanently deleted.';
                $_SESSION['admin_flash_type'] = 'success';
            }
            header("Location: administrator_panel.php?section={$redirectSection}");
            exit;
        }

        if ($postAction === 'deactivate_resident') {
            $resident_id = intval($_POST['resident_id'] ?? 0);
            if ($resident_id <= 0) {
                $_SESSION['admin_flash_message'] = 'Invalid resident selected for deactivation.';
                $_SESSION['admin_flash_type'] = 'error';
                header("Location: administrator_panel.php?section={$redirectSection}");
                exit;
            }

            ensureTableColumn($pdo, 'resident', 'is_active', 'TINYINT(1) DEFAULT 1');
            ensureTableColumn($pdo, 'resident', 'deleted_at', 'DATETIME NULL DEFAULT NULL');

            $residentCheck = $pdo->prepare("SELECT resident_id FROM resident WHERE resident_id = ? LIMIT 1");
            $residentCheck->execute([$resident_id]);
            $resident = $residentCheck->fetch(PDO::FETCH_ASSOC);

            if (!$resident) {
                $_SESSION['admin_flash_message'] = 'Resident account not found for deactivation.';
                $_SESSION['admin_flash_type'] = 'error';
                header("Location: administrator_panel.php?section={$redirectSection}");
                exit;
            }

            $deactivateResident = $pdo->prepare("UPDATE resident SET is_active = 0, deleted_at = NOW() WHERE resident_id = ?");
            $deactivateResident->execute([$resident_id]);

            logActivity($pdo, $admin_id, 'admin', 'account_deactivation', "Admin deactivated resident account resident_id={$resident_id}");

            $_SESSION['admin_flash_message'] = 'Resident account has been deactivated successfully.';
            $_SESSION['admin_flash_type'] = 'success';
            header("Location: administrator_panel.php?section={$redirectSection}");
            exit;
        }

        if (in_array($postAction, ['approve_staff', 'reject_staff'], true)) {
            $staff_id = intval($_POST['worker_id'] ?? 0);
            if ($staff_id <= 0) {
                $_SESSION['admin_flash_message'] = 'Invalid staff selected for approval decision.';
                $_SESSION['admin_flash_type'] = 'error';
                header("Location: administrator_panel.php?section={$redirectSection}");
                exit;
            }

            ensureTableColumn($pdo, 'health_worker', 'approval_status', "VARCHAR(20) NOT NULL DEFAULT 'approved'");
            $isApproved = $postAction === 'approve_staff';
            $approvalStatus = $isApproved ? 'approved' : 'rejected';
            $approvalUpdate = $pdo->prepare("UPDATE health_worker SET approval_status = ?, is_active = ?, deleted_at = ? WHERE worker_id = ? AND approval_status = 'pending'");
            $approvalUpdate->execute([$approvalStatus, $isApproved ? 1 : 0, $isApproved ? null : date('Y-m-d H:i:s'), $staff_id]);

            if ($approvalUpdate->rowCount() === 0) {
                $_SESSION['admin_flash_message'] = 'BHW account was not pending or could not be found.';
                $_SESSION['admin_flash_type'] = 'error';
            } else {
                logActivity($pdo, $admin_id, 'admin', $isApproved ? 'bhw_account_approval' : 'bhw_account_rejection', "Admin processed BHW account worker_id={$staff_id}");
                $_SESSION['admin_flash_message'] = $isApproved ? 'BHW account approved successfully.' : 'BHW account rejected successfully.';
                $_SESSION['admin_flash_type'] = 'success';
            }
            header("Location: administrator_panel.php?section={$redirectSection}");
            exit;
        }

        if ($postAction === 'deactivate_staff') {
            $staff_id = intval($_POST['worker_id'] ?? 0);
            if ($staff_id <= 0) {
                $_SESSION['admin_flash_message'] = 'Invalid staff selected for deactivation.';
                $_SESSION['admin_flash_type'] = 'error';
                header("Location: administrator_panel.php?section={$redirectSection}");
                exit;
            }

            ensureTableColumn($pdo, 'health_worker', 'is_active', 'TINYINT(1) DEFAULT 1');
            ensureTableColumn($pdo, 'health_worker', 'deleted_at', 'DATETIME NULL DEFAULT NULL');

            $staffCheck = $pdo->prepare("SELECT worker_id FROM health_worker WHERE worker_id = ? LIMIT 1");
            $staffCheck->execute([$staff_id]);
            $staff = $staffCheck->fetch(PDO::FETCH_ASSOC);

            if (!$staff) {
                $_SESSION['admin_flash_message'] = 'Staff account not found for deactivation.';
                $_SESSION['admin_flash_type'] = 'error';
                header("Location: administrator_panel.php?section={$redirectSection}");
                exit;
            }

            $deactivateStaff = $pdo->prepare("UPDATE health_worker SET is_active = 0, deleted_at = NOW() WHERE worker_id = ?");
            $deactivateStaff->execute([$staff_id]);

            logActivity($pdo, $admin_id, 'admin', 'account_deactivation', "Admin deactivated staff account worker_id={$staff_id}");

            $_SESSION['admin_flash_message'] = 'Staff account has been deactivated successfully.';
            $_SESSION['admin_flash_type'] = 'success';
            header("Location: administrator_panel.php?section={$redirectSection}");
            exit;
        }

        if ($postAction === 'reactivate_staff') {
            $staff_id = intval($_POST['worker_id'] ?? 0);
            if ($staff_id <= 0) {
                $_SESSION['admin_flash_message'] = 'Invalid staff selected for reactivation.';
                $_SESSION['admin_flash_type'] = 'error';
                header("Location: administrator_panel.php?section={$redirectSection}");
                exit;
            }

            ensureTableColumn($pdo, 'health_worker', 'is_active', 'TINYINT(1) DEFAULT 1');
            ensureTableColumn($pdo, 'health_worker', 'deleted_at', 'DATETIME NULL DEFAULT NULL');

            $staffCheck = $pdo->prepare("SELECT worker_id FROM health_worker WHERE worker_id = ? LIMIT 1");
            $staffCheck->execute([$staff_id]);
            $staff = $staffCheck->fetch(PDO::FETCH_ASSOC);

            if (!$staff) {
                $_SESSION['admin_flash_message'] = 'Staff account not found for reactivation.';
                $_SESSION['admin_flash_type'] = 'error';
                header("Location: administrator_panel.php?section={$redirectSection}");
                exit;
            }

            $reactivateStaff = $pdo->prepare("UPDATE health_worker SET is_active = 1, deleted_at = NULL WHERE worker_id = ?");
            $reactivateStaff->execute([$staff_id]);

            logActivity($pdo, $admin_id, 'admin', 'account_reactivation', "Admin reactivated staff account worker_id={$staff_id}");

            $_SESSION['admin_flash_message'] = 'Staff account has been reactivated successfully.';
            $_SESSION['admin_flash_type'] = 'success';
            header("Location: administrator_panel.php?section={$redirectSection}");
            exit;
        }
    }

    ensureTableColumn($pdo, 'resident', 'is_active', 'TINYINT(1) DEFAULT 1');
    ensureTableColumn($pdo, 'resident', 'deleted_at', 'DATETIME NULL DEFAULT NULL');
    $residentStatusStmt = $pdo->query("SELECT
        COALESCE(SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END), 0) AS active_count,
        COALESCE(SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END), 0) AS inactive_count
        FROM resident");
    $residentStatusCounts = $residentStatusStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $active_residents_count = (int)($residentStatusCounts['active_count'] ?? 0);
    $inactive_residents_count = (int)($residentStatusCounts['inactive_count'] ?? 0);
    
    
    
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM resident");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_residents = intval($result['count'] ?? 0);
        error_log("Dashboard Metrics: Residents count = {$total_residents}");
    } catch (Exception $e) {
        error_log("Trying lowercase resident table... Error: " . $e->getMessage());
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM resident");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_residents = intval($result['count'] ?? 0);
            error_log("Dashboard Metrics: Residents count (lowercase) = {$total_residents}");
        } catch (Exception $e2) {
            error_log("Failed to count residents: " . $e2->getMessage());
            $total_residents = 0;
        }
    }

    $staffStatusStmt = $pdo->query("SELECT
        COALESCE(SUM(CASE WHEN LOWER(COALESCE(approval_status, 'approved')) = 'pending' THEN 1 ELSE 0 END), 0) AS pending_count,
        COALESCE(SUM(CASE WHEN COALESCE(is_active, 0) = 0 AND LOWER(COALESCE(approval_status, 'approved')) <> 'pending' THEN 1 ELSE 0 END), 0) AS deactivated_count,
        COALESCE(SUM(CASE WHEN COALESCE(is_active, 0) = 1 THEN 1 ELSE 0 END), 0) AS active_count
        FROM health_worker");
    $staffStatusCounts = $staffStatusStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $pending_staff_count = (int)($staffStatusCounts['pending_count'] ?? 0);
    $deactivated_staff_count = (int)($staffStatusCounts['deactivated_count'] ?? 0);
    $active_staff_count = (int)($staffStatusCounts['active_count'] ?? 0);
    
    
    
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM health_worker");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_staff = intval($result['count'] ?? 0);
        error_log("Dashboard Metrics: Health Workers count = {$total_staff}");
    } catch (Exception $e) {
        error_log("Trying lowercase health_worker table... Error: " . $e->getMessage());
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM health_worker");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_staff = intval($result['count'] ?? 0);
            error_log("Dashboard Metrics: Health Workers count (lowercase) = {$total_staff}");
        } catch (Exception $e2) {
            error_log("Failed to count health workers: " . $e2->getMessage());
            $total_staff = 0;
        }
    }
    
    
    
    
    
    $total_users = $total_residents + $total_staff;
    error_log("Dashboard Metrics: Combined Total Users = {$total_users} (Residents: {$total_residents} + Staff: {$total_staff})");
    
    
    
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM appointment WHERE status = 'scheduled'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_appointments = intval($result['count'] ?? 0);
        error_log("Dashboard Metrics: Scheduled appointments = {$total_appointments}");
    } catch (Exception $e) {
        error_log("Failed to count scheduled appointments: " . $e->getMessage());
        $total_appointments = 0;
    }

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM appointment");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_appointment_records = intval($result['count'] ?? 0);
        error_log("Dashboard Metrics: Total appointment records = {$total_appointment_records}");
    } catch (Exception $e) {
        error_log("Failed to count total appointment records: " . $e->getMessage());
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM appointment");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_appointment_records = intval($result['count'] ?? 0);
            error_log("Dashboard Metrics: Total appointment records (lowercase) = {$total_appointment_records}");
        } catch (Exception $e2) {
            error_log("Failed to count total appointment records (lowercase): " . $e2->getMessage());
            $total_appointment_records = 0;
        }
    }

    
    
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE()");
        $stmt->execute();
        $table_count = intval($stmt->fetchColumn() ?: 0);
    } catch (Exception $e) {
        error_log("Failed to count database tables: " . $e->getMessage());
        $table_count = 0;
    }

    try {
        $stmt = $pdo->prepare("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb FROM information_schema.tables WHERE table_schema = DATABASE()");
        $stmt->execute();
        $size_mb = $stmt->fetchColumn();
        $db_size = $size_mb !== false ? number_format((float)$size_mb, 2) . ' MB' : 'N/A';
    } catch (Exception $e) {
        error_log("Failed to read database size: " . $e->getMessage());
        $db_size = 'N/A';
    }

    
    
    
    $total_records = $total_residents + $total_staff + $total_appointment_records;

    
    
    
    $bhw_accounts = [];
    try {
        
        $stmt = $pdo->prepare("SELECT * FROM health_worker ORDER BY COALESCE(created_at, worker_id) DESC");
        $stmt->execute();
        $bhw_accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        
        error_log("Dashboard Metrics: BHW accounts fetched = " . count($bhw_accounts) . ", Active = " . $active_staff_count);
    } catch (Exception $e) {
        error_log("Failed to fetch BHW accounts with created_at: " . $e->getMessage());
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM health_worker ORDER BY COALESCE(created_at, worker_id) DESC");
            $stmt->execute();
            $bhw_accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            
            error_log("Dashboard Metrics: BHW accounts fetched (lowercase) = " . count($bhw_accounts) . ", Active = " . $active_staff_count);
        } catch (Exception $e2) {
            error_log("Failed to fetch BHW accounts (lowercase): " . $e2->getMessage());
            
            try {
                $stmt = $pdo->prepare("SELECT * FROM health_worker ORDER BY worker_id DESC");
                $stmt->execute();
                $bhw_accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("Dashboard Metrics: BHW accounts fetched (fallback) = " . count($bhw_accounts) . ", Active = " . $active_staff_count);
            } catch (Exception $e3) {
                error_log("Failed to fetch BHW accounts (all attempts): " . $e3->getMessage());
                $bhw_accounts = [];
                $active_staff_count = 0;
            }
        }
    }

    
    
    
    ensureTableColumn($pdo, 'resident', 'is_active', 'TINYINT(1) DEFAULT 1');
    ensureTableColumn($pdo, 'resident', 'deleted_at', 'DATETIME NULL DEFAULT NULL');

    
    
    
    $resident_accounts = [];
    try {
        
        $stmt = $pdo->prepare("SELECT * FROM resident ORDER BY COALESCE(created_at, resident_id) DESC");
        $stmt->execute();
        $resident_accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Dashboard Metrics: Resident accounts fetched = " . count($resident_accounts));
    } catch (Exception $e) {
        error_log("Failed to fetch resident accounts with created_at: " . $e->getMessage());
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM resident ORDER BY COALESCE(created_at, resident_id) DESC");
            $stmt->execute();
            $resident_accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Dashboard Metrics: Resident accounts fetched (lowercase) = " . count($resident_accounts));
        } catch (Exception $e2) {
            error_log("Failed to fetch resident accounts (lowercase): " . $e2->getMessage());
            
            try {
                $stmt = $pdo->prepare("SELECT * FROM resident ORDER BY resident_id DESC");
                $stmt->execute();
                $resident_accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("Dashboard Metrics: Resident accounts fetched (fallback) = " . count($resident_accounts));
            } catch (Exception $e3) {
                error_log("Failed to fetch resident accounts (all attempts): " . $e3->getMessage());
                $resident_accounts = [];
            }
        }
    }

    
    
    
    $admin_accounts = [];
    try {
        
        $stmt = $pdo->prepare("SELECT * FROM system_admins ORDER BY COALESCE(created_at, admin_id) DESC");
        $stmt->execute();
        $admin_accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($admin_accounts as $adminAccount) {
            if ((int)($adminAccount['is_active'] ?? 0) === 1) {
                $active_admin_count++;
            } else {
                $inactive_admin_count++;
            }
        }
        error_log("Dashboard Metrics: Admin accounts fetched = " . count($admin_accounts));
    } catch (Exception $e) {
        error_log("Failed to fetch admin accounts from system_admins with created_at: " . $e->getMessage());
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM system_admin ORDER BY COALESCE(created_at, admin_id) DESC");
            $stmt->execute();
            $admin_accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Dashboard Metrics: Admin accounts fetched (system_admin) = " . count($admin_accounts));
        } catch (Exception $e2) {
            error_log("Failed to fetch admin accounts from system_admin: " . $e2->getMessage());
            
            try {
                $stmt = $pdo->prepare("SELECT * FROM system_admins ORDER BY admin_id DESC");
                $stmt->execute();
                $admin_accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("Dashboard Metrics: Admin accounts fetched (fallback) = " . count($admin_accounts));
            } catch (Exception $e3) {
                error_log("Failed to fetch admin accounts (all attempts): " . $e3->getMessage());
                $admin_accounts = [];
            }
        }
    }

    try {
        $historyStmt = $pdo->query("SELECT log_id, user_id, user_role_snapshot, action_type, action_description, ip_address, timestamp
            FROM activity_logs
            WHERE action_type IN ('login', 'logout', 'signup')
            ORDER BY timestamp DESC LIMIT 100");
        $auth_history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $historyError) {
        error_log('Failed to fetch authentication history: ' . $historyError->getMessage());
    }
    
    
    
    
    $pending_account_requests = [];
    try {
        $pending_query = "SELECT ar.*, r.email AS account_email, r.full_name AS resident_name, hw.full_name AS bhw_name, hw.email AS bhw_email " .
            "FROM account_requests ar " .
            "LEFT JOIN resident r ON ar.resident_id = r.resident_id " .
            "LEFT JOIN health_worker hw ON ar.requested_by = hw.worker_id " .
            "WHERE ar.status = 'pending' " .
            "ORDER BY ar.created_at DESC " .
            "LIMIT 20";

        $stmt = $pdo->prepare($pending_query);
        $stmt->execute();
        $pending_account_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Dashboard Metrics: Pending account requests fetched = " . count($pending_account_requests));
    } catch (Exception $e) {
        error_log("Failed to fetch pending account requests: " . $e->getMessage());
        $pending_account_requests = [];
    }

    
    
    
    $inactive_residents = [];
    try {
        ensureTableColumn($pdo, 'resident', 'is_active', 'TINYINT(1) DEFAULT 1');
        ensureTableColumn($pdo, 'resident', 'deleted_at', 'DATETIME NULL DEFAULT NULL');

        $conditions = [];
        if (hasTableColumn($pdo, 'resident', 'is_active')) {
            $conditions[] = 'is_active = 0';
        }
        if (hasTableColumn($pdo, 'resident', 'deleted_at')) {
            $conditions[] = 'deleted_at IS NOT NULL';
        }

        if (!empty($conditions)) {
            $whereClause = implode(' OR ', $conditions);
            $orderBy = 'resident_id DESC';

            try {
                $stmt = $pdo->prepare("SELECT * FROM resident WHERE {$whereClause} ORDER BY {$orderBy} LIMIT 20");
                $stmt->execute();
                $inactive_residents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $inner) {
                
                $stmt = $pdo->prepare("SELECT * FROM resident WHERE {$whereClause} ORDER BY {$orderBy} LIMIT 20");
                $stmt->execute();
                $inactive_residents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    } catch (Exception $e) {
        error_log("Failed to fetch inactive resident accounts: " . $e->getMessage());
        $inactive_residents = [];
    }

    
    
    
    $all_appointments = [];
    try {
        $stmt = $pdo->prepare("
            SELECT a.*, r.full_name as resident_name, h.full_name as worker_name
            FROM appointment a
            LEFT JOIN resident r ON a.resident_id = r.resident_id
            LEFT JOIN health_worker h ON a.health_worker_id = h.worker_id
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
            LIMIT 100
        ");
        $stmt->execute();
        $all_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Dashboard Transactions: Appointments fetched = " . count($all_appointments));
    } catch (Exception $e) {
        error_log("Failed to fetch appointments: " . $e->getMessage());
        $all_appointments = [];
    }
    
    
    
    
    $medicine_transactions = [];
    try {
        $stmt = $pdo->prepare("
            SELECT md.*, r.full_name as resident_name, COALESCE(m.name, m.medicine_name) as medicine_name
            FROM medicine_dispensing md
            LEFT JOIN resident r ON md.resident_id = r.resident_id
            LEFT JOIN medicine m ON md.medicine_id = m.medicine_id
            ORDER BY md.dispensing_date DESC
            LIMIT 100
        ");
        $stmt->execute();
        $medicine_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Dashboard Transactions: Medicine dispensing fetched = " . count($medicine_transactions));
    } catch (Exception $e) {
        error_log("Failed to fetch medicine dispensing: " . $e->getMessage());
        $medicine_transactions = [];
    }
    
    
    
    
    $medical_history = [];
    try {
        $stmt = $pdo->prepare("
            SELECT mh.*
            FROM medical_history mh
            ORDER BY mh.created_at DESC
            LIMIT 100
        ");
        $stmt->execute();
        $medical_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Dashboard Transactions: Medical history fetched = " . count($medical_history));
    } catch (Exception $e) {
        error_log("Failed to fetch medical history: " . $e->getMessage());
        $medical_history = [];
    }
    
    
    
    
    $service_requests = [];
    try {
        $stmt = $pdo->prepare("
            SELECT sr.*, r.full_name as resident_name
            FROM service_request sr
            LEFT JOIN resident r ON sr.resident_id = r.resident_id
            ORDER BY sr.request_date DESC
            LIMIT 100
        ");
        $stmt->execute();
        $service_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Dashboard Transactions: Service requests fetched = " . count($service_requests));
    } catch (Exception $e) {
        error_log("Failed to fetch service requests: " . $e->getMessage());
        $service_requests = [];
    }
    
    
    
    
    $all_transactions = [];
    
    
    foreach ($all_appointments as $apt) {
        
        $activity_date = $apt['created_at'] ?? ($apt['appointment_date'] . ' ' . $apt['appointment_time']);
        
        $all_transactions[] = [
            'type' => 'appointment',
            'date' => $activity_date,
            'description' => "Appointment scheduled: " . ($apt['resident_name'] ?? 'Unknown Resident') . " - " . ($apt['purpose'] ?? 'Check-up'),
            'resident' => $apt['resident_name'] ?? 'Unknown'
        ];
    }
    
    
    foreach ($medicine_transactions as $med) {
        $all_transactions[] = [
            'type' => 'medicine',
            'date' => $med['dispensing_date'] ?? date('Y-m-d H:i:s'),
            'description' => "Medicine dispensed: " . ($med['medicine_name'] ?? 'Unknown') . " to " . ($med['resident_name'] ?? 'Unknown Resident'),
            'resident' => $med['resident_name'] ?? 'Unknown'
        ];
    }
    
    
    foreach ($medical_history as $hist) {
        $all_transactions[] = [
            'type' => 'medical',
            'date' => $hist['created_at'] ?? date('Y-m-d H:i:s'),
            'description' => "Medical record updated: " . ($hist['diagnosis'] ?? 'Health update'),
            'resident' => 'System'
        ];
    }
    
    
    foreach ($service_requests as $svc) {
        $all_transactions[] = [
            'type' => 'service',
            'date' => $svc['request_date'] ?? date('Y-m-d H:i:s'),
            'description' => "Service request: " . ($svc['service_type'] ?? 'General request') . " - " . ($svc['resident_name'] ?? 'Unknown Resident'),
            'resident' => $svc['resident_name'] ?? 'Unknown'
        ];
    }
    
    
    usort($all_transactions, function($a, $b) {
        $timestamp_a = strtotime($a['date']);
        $timestamp_b = strtotime($b['date']);
        
        
        if (!$timestamp_a) return 1;
        if (!$timestamp_b) return -1;
        
        
        return $timestamp_b - $timestamp_a;
    });
    
    error_log("Admin Dashboard Metrics Complete: Total Users={$total_users}, Staff={$total_staff}, Residents={$total_residents}, Appointments={$total_appointments}, Total Transactions=" . count($all_transactions));
    
} catch (Exception $e) {
    error_log("Admin Dashboard Database Error: " . $e->getMessage());
    
}


function isActiveSection($section) {
    global $active_section;
    return $active_section === $section;
}

?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Panel — ScanCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');
        
        .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-body { font-family: 'Inter', sans-serif; }

        body { background: #0f172a; }

        .admin-sidebar {
            width: 5rem;
            flex-shrink: 0;
            transition: width 0.2s ease;
            overflow-x: hidden;
            overflow-y: hidden;
        }

        .admin-sidebar:hover {
            width: 16rem;
            overflow-y: auto;
        }

        .admin-sidebar-user > div:first-child {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            padding: 0;
        }

        .admin-sidebar .admin-sidebar-label,
        .admin-sidebar .admin-sidebar-meta {
            max-width: 0;
            overflow: hidden;
            opacity: 0;
            white-space: nowrap;
            transition: opacity 0.15s ease, max-width 0.2s ease;
        }

        .admin-sidebar:hover .admin-sidebar-label,
        .admin-sidebar:hover .admin-sidebar-meta {
            max-width: 13rem;
            opacity: 1;
        }

        .admin-sidebar .sidebar-link {
            justify-content: center;
            padding: 10px 12px;
            gap: 10px;
            font-size: 13px;
        }

        .admin-sidebar:hover .sidebar-link {
            justify-content: flex-start;
        }

        .admin-sidebar + main {
            transition: width 0.2s ease;
        }

        .admin-sidebar .admin-sidebar-nav {
            display: grid;
            gap: 4px;
        }

        .sidebar-link {
            position: relative;
            padding: 10px 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
            color: #cbd5e1;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-link:hover {
            color: #454B1B;
            background-color: rgba(69, 75, 27, 0.15);
        }

        .sidebar-link.active {
            color: #454B1B;
            background-color: rgba(69, 75, 27, 0.25);
            font-weight: 600;
        }

        .sidebar-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background-color: #454B1B;
            border-radius: 0 4px 4px 0;
        }

        .section-content {
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .stat-card {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border: 1px solid #334155;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            border-color: #454B1B;
            box-shadow: 0 0 20px rgba(69, 75, 27, 0.1);
        }

        .action-button {
            background-color: #454B1B;
            transition: all 0.3s ease;
        }

        .action-button:hover {
            opacity: 0.95;
            box-shadow: 0 0 15px rgba(69, 75, 27, 0.3);
        }

        /* Phone layout: keep the active admin pages inside the viewport while
           preserving horizontal scrolling only for data tables. */
        @media (max-width: 767px) {
            body {
                overflow-x: hidden;
            }

            /* Remove the desktop flex-column calculation entirely on phones.
               The drawer is fixed, so the page content must occupy the full
               viewport on its own rather than sharing a flex row with it. */
            body > .flex.min-h-screen {
                display: block;
                width: 100%;
                max-width: 100%;
                overflow-x: hidden;
            }

            body .admin-sidebar {
                position: fixed !important;
                top: 4.25rem !important;
                right: auto !important;
                bottom: 0 !important;
                left: 0 !important;
                z-index: 50;
                width: min(17.5rem, 88vw);
                height: calc(100dvh - 4.25rem) !important;
                min-height: calc(100dvh - 4.25rem) !important;
                max-height: calc(100dvh - 4.25rem) !important;
                overflow-y: auto;
                border-top: 0;
                border-right: 1px solid #1e293b;
                transform: translateX(-100%);
                transition: transform 0.2s ease;
            }

            .admin-sidebar.is-open {
                transform: translateX(0);
            }

            .admin-sidebar:hover {
                width: min(17.5rem, 88vw);
                overflow-y: auto;
            }

            .admin-sidebar .admin-sidebar-nav {
                display: grid;
                height: auto;
                padding: 0 0.75rem;
                gap: 0.25rem;
            }

            .admin-sidebar .sidebar-link,
            .admin-sidebar:hover .sidebar-link {
                flex-direction: row;
                justify-content: flex-start;
                gap: 0.75rem;
                padding: 0.75rem;
                font-size: 0.875rem;
                line-height: 1.25;
                text-align: left;
            }

            .admin-sidebar .admin-sidebar-label,
            .admin-sidebar:hover .admin-sidebar-label {
                max-width: 100%;
                opacity: 1;
                white-space: nowrap;
            }

            .admin-sidebar .sidebar-link.active::before {
                top: 0;
                right: auto;
                bottom: 0;
                left: 0;
                width: 3px;
                height: auto;
                border-radius: 0 4px 4px 0;
            }

            /* The menu backdrop sits between the sidebar and main, so this
               must use the general sibling selector rather than +. */
            .admin-sidebar ~ main {
                display: block;
                flex: none !important;
                width: 100% !important;
                min-width: 0 !important;
                max-width: 100% !important;
                min-height: 100dvh;
                padding-top: 4.25rem;
                box-sizing: border-box;
                overflow-x: hidden;
            }

            .mobile-admin-menu-toggle {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 2.125rem;
                height: 2.125rem;
                flex-shrink: 0;
                border: 1px solid #334155;
                border-radius: 0.5rem;
                color: #cbd5e1;
            }

            .mobile-admin-menu-overlay {
                position: fixed;
                inset: 4.25rem 0 0;
                z-index: 45;
                background: rgba(2, 6, 23, 0.7);
            }

            body main > nav {
                position: fixed !important;
                top: 0 !important;
                right: 0 !important;
                bottom: auto !important;
                left: 0 !important;
                z-index: 40;
                height: 4.25rem !important;
            }

            main > nav > div {
                display: grid;
                grid-template-columns: 2.125rem minmax(0, 1fr) 2.125rem;
                align-items: center;
                height: 4.25rem;
                padding: 0.75rem;
                gap: 0.5rem;
                box-sizing: border-box;
            }

            main > nav > div > div:first-child {
                display: contents;
            }

            main > nav > div > div:first-child > div {
                grid-column: 2;
                min-width: 0;
                text-align: center;
            }

            main > nav > div > div:last-child {
                grid-column: 3;
                justify-self: end;
            }

            main > nav h1 {
                overflow: hidden;
                font-size: 0.875rem !important;
                line-height: 1.25;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            main > nav .text-sm.text-slate-400 {
                display: none;
            }

            main > nav > div > div:last-child > button {
                padding: 0.25rem !important;
                font-size: 0.75rem !important;
                line-height: 1 !important;
            }

            main > nav > div > div:last-child > button i {
                margin-right: 0.2rem !important;
                font-size: 0.75rem !important;
            }

            .section-content {
                padding: 1rem;
            }

            .section-content .space-y-8 {
                row-gap: 1.25rem;
            }

            .section-content .rounded-xl.p-6,
            .section-content .rounded-xl.p-8 {
                padding: 1rem;
            }

            .section-content .gap-6 {
                gap: 1rem;
            }

            .section-content .overflow-x-auto {
                max-width: 100%;
                -webkit-overflow-scrolling: touch;
            }

            .section-content table {
                min-width: 38rem;
                font-size: 0.75rem;
            }

            .section-content table th,
            .section-content table td {
                padding: 0.625rem 0.75rem;
                white-space: nowrap;
            }

            .section-content .max-h-96,
            .section-content .max-h-\[800px\] {
                padding-right: 0;
            }

            .activity-item > div,
            #activityLogContainer .flex.items-start.justify-between,
            #activityLogContainer .flex.items-center.justify-between {
                gap: 0.75rem;
            }

            #activityLogContainer .flex.items-start.justify-between {
                flex-direction: column;
            }

            #activityLogContainer .text-right {
                width: 100%;
                text-align: left;
            }

            #staffModal .grid-cols-3,
            #residentModal .grid-cols-3,
            #adminModal .grid-cols-3 {
                grid-template-columns: 1fr;
            }

            #staffModal .p-6,
            #residentModal .p-6,
            #adminModal .p-6,
            #activityModal .p-6 {
                padding: 1rem;
            }
        }

        @media (min-width: 768px) {
            .mobile-admin-menu-toggle,
            .mobile-admin-menu-overlay {
                display: none;
            }

            .admin-sidebar:not(:hover) .admin-sidebar-user {
                width: 3rem;
                height: 3rem;
                margin: 1rem auto 1.5rem;
                padding: 0.25rem;
                justify-content: center;
            }

            .admin-sidebar:not(:hover) .admin-sidebar-user .admin-sidebar-label {
                display: none;
            }
        }
    </style>
</head>
<body class="font-body text-slate-200">
    <?php if (!empty($admin_flash_message)): ?>
        <div class="mx-auto mt-4 max-w-6xl px-4">
            <div class="rounded-xl border px-4 py-3 text-sm font-medium <?php echo $admin_flash_type === 'success' ? 'bg-emerald-100 border-emerald-200 text-emerald-800' : 'bg-rose-100 border-rose-200 text-rose-800'; ?>">
                <?php echo htmlspecialchars($admin_flash_message); ?>
            </div>
        </div>
    <?php endif; ?>

                <!-- Section -->
    <div class="flex min-h-screen">

                <!-- Section -->
        <aside class="admin-sidebar flex flex-col bg-slate-900 border-r border-slate-800 sticky top-0 h-screen overflow-y-auto">
            
                <!-- Section -->
            <div class="admin-sidebar-brand p-4 border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <img src="images/SClogo.png" alt="ScanCare Logo" class="h-10 w-10 object-contain">
                    <div class="admin-sidebar-label">
                        <span style="font-family: 'Plus Jakarta Sans', sans-serif;" class="text-lg font-extrabold tracking-tight text-white block">
                            Scan<span style="color: #454B1B;">Care</span>
                        </span>
                        <p class="text-xs text-slate-500">Admin Panel</p>
                    </div>
                </div>
            </div>

                <!-- Section -->
            <div class="admin-sidebar-user flex items-center space-x-3 p-3 mx-3 mt-4 bg-slate-800/50 rounded-xl border border-slate-700/30 mb-6">
                <div class="p-2 bg-slate-700/60 rounded-lg text-slate-400 flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div class="admin-sidebar-label flex-1 min-w-0">
                    <h4 class="text-sm font-bold text-white tracking-wide truncate" title="<?php echo htmlspecialchars($admin_name); ?>"><?php echo htmlspecialchars($admin_name); ?></h4>
                    <p class="text-xs font-medium text-slate-400 mt-0.5">Admin</p>
                </div>
            </div>

                <!-- Section -->
            <nav class="admin-sidebar-nav px-3">
                
                <!-- Section -->
                <a href="administrator_panel.php?section=dashboard" class="sidebar-link <?php echo isActiveSection('dashboard') ? 'active' : ''; ?>">
                    <i class="fas fa-gauge-high"></i><span class="admin-sidebar-label">Dashboard</span>
                </a>

                <!-- Section -->
                <a href="administrator_panel.php?section=user-management" class="sidebar-link <?php echo isActiveSection('user-management') ? 'active' : ''; ?>">
                    <i class="fas fa-users-cog"></i><span class="admin-sidebar-label">User Management</span>
                </a>

                <!-- Section -->
                <a href="administrator_panel.php?section=activity-log" class="sidebar-link <?php echo isActiveSection('activity-log') ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i><span class="admin-sidebar-label">Activity Log</span>
                </a>

            </nav>

                <!-- Section -->
            <div class="admin-sidebar-status p-3 border-t border-slate-800 mt-auto">
                <div class="admin-sidebar-meta text-xs text-slate-500 space-y-1">
                    <p><strong>Session ID:</strong> <?php echo substr(session_id(), 0, 8); ?>...</p>
                    <p><strong>Last Activity:</strong> Just now</p>
                </div>
            </div>
        </aside>

        <button id="mobileAdminMenuOverlay" class="mobile-admin-menu-overlay hidden" type="button" aria-label="Close navigation menu"></button>

                <!-- Section -->
        <main class="flex-1 overflow-auto">
            
                <!-- Section -->
            <nav class="border-b border-slate-800 bg-slate-900/50 sticky top-0 z-40 backdrop-blur">
                <div class="px-8 py-4 flex items-center justify-between">
                    <div class="flex min-w-0 items-center gap-3">
                        <button id="mobileAdminMenuToggle" class="mobile-admin-menu-toggle" type="button" aria-label="Open navigation menu" aria-expanded="false">
                            <i class="fas fa-bars"></i>
                        </button>
                        <div class="min-w-0">
                        <h1 class="text-2xl font-heading font-bold text-white">
                            <?php 
                                $section_titles = [
                                    'dashboard' => 'Dashboard',
                                    'user-management' => 'User Management',
                                    'activity-log' => 'Activity Log',
                                    'system-config' => 'System Configuration',
                                    'database-hub' => 'Database Integrity Hub',
                                    'notifications' => 'Notification Settings',
                                    'cloud-storage' => 'Cloud & Storage Manager',
                                    'monitoring' => 'Monitoring Center',
                                    'deployment' => 'Deployment Zone'
                                ];
                                echo $section_titles[$active_section] ?? 'Administrator Panel';
                            ?>
                        </h1>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="text-sm text-slate-400">
                            <i class="fas fa-server text-green-500"></i> System Online
                        </div>
                        <button onclick="openLogoutConfirmModal()" class="px-4 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800 transition text-sm font-semibold border-none bg-transparent cursor-pointer">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </button>
                    </div>
                </div>
            </nav>

                <!-- Section -->
            <div class="p-8 section-content">

                <!-- SECTION 1: DASHBOARD OVERVIEW ==================================================== -->
                <?php if (isActiveSection('dashboard')): ?>
                <div class="space-y-8">
                    
                <!-- Section -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <div class="stat-card rounded-xl p-6">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center text-blue-400">
                                    <i class="fas fa-users"></i>
                                </div>
                                <span class="text-xs font-bold text-blue-400 bg-blue-500/20 px-2 py-1 rounded">+5%</span>
                            </div>
                            <p class="text-3xl font-heading font-bold text-white"><?php echo $total_users; ?></p>
                            <p class="text-sm text-slate-400 mt-1">Total Users</p>
                        </div>

                        <div class="stat-card rounded-xl p-6">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center text-emerald-400">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <span class="text-xs font-bold text-emerald-400 bg-emerald-500/20 px-2 py-1 rounded">Active</span>
                            </div>
                            <p class="text-3xl font-heading font-bold text-white"><?php echo $total_staff; ?></p>
                            <p class="text-sm text-slate-400 mt-1">Staff Accounts</p>
                        </div>

                    </div>

                <!-- Section -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        
                        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                            <h3 class="text-lg font-heading font-bold text-white mb-4">📊 All System Transactions</h3>
                            <div class="space-y-2 max-h-96 overflow-y-auto pr-4">
                                <?php if (count($all_transactions) > 0): ?>
                                    <?php foreach (array_slice($all_transactions, 0, 15) as $transaction): ?>
                                        <div class="flex items-center justify-between py-3 border-b border-slate-700/50 text-xs">
                                            <div class="flex-1">
                                                <div class="text-slate-300">
                                                    <?php 
                                                        $type = $transaction['type'];
                                                        if ($type === 'appointment') {
                                                            $color_class = 'bg-blue-500/20 text-blue-400';
                                                            $type_label = '📅 Appt';
                                                        } elseif ($type === 'medicine') {
                                                            $color_class = 'bg-green-500/20 text-green-400';
                                                            $type_label = '💊 Med';
                                                        } elseif ($type === 'medical') {
                                                            $color_class = 'bg-purple-500/20 text-purple-400';
                                                            $type_label = '🏥 Medical';
                                                        } else {
                                                            $color_class = 'bg-amber-500/20 text-amber-400';
                                                            $type_label = '📋 Service';
                                                        }
                                                    ?>
                                                    <span class="inline-block px-2 py-1 rounded text-xs font-bold mr-2 <?php echo $color_class; ?>">
                                                        <?php echo $type_label; ?>
                                                    </span>
                                                    <?php echo htmlspecialchars($transaction['description']); ?>
                                                </div>
                                                <div class="text-slate-500 text-xs mt-1">Resident: <?php echo htmlspecialchars($transaction['resident']); ?></div>
                                            </div>
                                            <span class="text-slate-500 ml-2 whitespace-nowrap"><?php 
                                                $date = strtotime($transaction['date']);
                                                $diff = time() - $date;
                                                if ($diff < 3600) echo round($diff/60) . 'm ago';
                                                elseif ($diff < 86400) echo round($diff/3600) . 'h ago';
                                                else echo round($diff/86400) . 'd ago';
                                            ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-slate-500 text-sm p-4 text-center">No transactions recorded yet</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                            <h3 class="text-lg font-heading font-bold text-white mb-4">📊 System Snapshot</h3>
                            <div class="space-y-3 text-sm">
                                <div class="flex items-center justify-between py-2 border-b border-slate-700/50">
                                    <span class="text-slate-400">Total Appointments</span>
                                    <span class="text-emerald-400 font-bold"><?php echo count($all_appointments); ?></span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-slate-700/50">
                                    <span class="text-slate-400">Medicine Dispensing</span>
                                    <span class="text-green-400 font-bold"><?php echo count($medicine_transactions); ?></span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-slate-700/50">
                                    <span class="text-slate-400">Medical Records</span>
                                    <span class="text-purple-400 font-bold"><?php echo count($medical_history); ?></span>
                                </div>
                                <div class="flex items-center justify-between py-2">
                                    <span class="text-slate-400">Service Requests</span>
                                    <span class="text-amber-400 font-bold"><?php echo count($service_requests); ?></span>
                                </div>
                                <div class="mt-4 pt-4 border-t border-slate-700">
                                    <div class="text-slate-300 font-semibold">Total Transactions: <span class="text-lime-400"><?php echo count($all_transactions); ?></span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- SECTION 2: USER ACCOUNT MANAGEMENT ================================================= -->
                <?php if (isActiveSection('user-management')): ?>
                <div class="space-y-6">
                    
                    <div class="flex items-center justify-between mb-6">
                        <p class="text-slate-400">Manage, edit, and deactivate staff and official accounts</p>
                    </div>

                    <div class="flex gap-2 border-b border-slate-700 mb-6">
                        <a href="administrator_panel.php?section=user-management&tab=accounts" class="px-4 py-2 text-sm font-semibold <?php echo $user_management_tab === 'accounts' ? 'text-white border-b-2 border-[#454B1B]' : 'text-slate-400'; ?>">Accounts</a>
                        <a href="administrator_panel.php?section=user-management&tab=auth-history" class="px-4 py-2 text-sm font-semibold <?php echo $user_management_tab === 'auth-history' ? 'text-white border-b-2 border-[#454B1B]' : 'text-slate-400'; ?>">Login, Logout &amp; Signup History</a>
                    </div>

                    <?php if ($user_management_tab === 'auth-history'): ?>
                    <div class="bg-slate-800 border border-slate-700 rounded-xl p-6 overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead><tr class="border-b border-slate-700">
                                <th class="text-left py-3 px-4 text-slate-400">Date</th>
                                <th class="text-left py-3 px-4 text-slate-400">Event</th>
                                <th class="text-left py-3 px-4 text-slate-400">Role</th>
                                <th class="text-left py-3 px-4 text-slate-400">Details</th>
                                <th class="text-left py-3 px-4 text-slate-400">IP Address</th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($auth_history as $history): ?>
                                <tr class="border-b border-slate-700/50">
                                    <td class="py-3 px-4 text-slate-300 whitespace-nowrap"><?php echo htmlspecialchars(date('M d, Y H:i:s', strtotime($history['timestamp']))); ?></td>
                                    <td class="py-3 px-4"><span class="px-2 py-1 rounded text-xs font-semibold <?php echo $history['action_type'] === 'login' ? 'bg-emerald-500/20 text-emerald-300' : ($history['action_type'] === 'logout' ? 'bg-amber-500/20 text-amber-300' : 'bg-blue-500/20 text-blue-300'); ?>"><?php echo htmlspecialchars(ucfirst($history['action_type'])); ?></span></td>
                                    <td class="py-3 px-4 text-slate-300"><?php echo htmlspecialchars($history['user_role_snapshot'] ?? 'Unknown'); ?></td>
                                    <td class="py-3 px-4 text-slate-300"><?php echo htmlspecialchars($history['action_description'] ?? ''); ?></td>
                                    <td class="py-3 px-4 text-slate-400 font-mono text-xs"><?php echo htmlspecialchars($history['ip_address'] ?? 'Unknown'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$auth_history): ?><tr><td colspan="5" class="py-10 text-center text-slate-500">No authentication history recorded yet.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div id="account-management-tab">

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        
                        <!-- Staff Accounts Card -->
                        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                            <h3 class="text-lg font-heading font-bold text-white mb-4 flex items-center gap-2">
                                <i class="fas fa-people-group text-blue-400"></i> Staff Accounts
                            </h3>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between p-3 bg-slate-700/50 rounded-lg">
                                    <span class="text-sm text-slate-300">Active Staff</span>
                                    <span class="text-2xl font-bold text-blue-400"><?php echo $active_staff_count; ?></span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-slate-700/50 rounded-lg">
                                    <span class="text-sm text-slate-300">Pending Activation</span>
                                    <span class="text-2xl font-bold text-amber-400"><?php echo $pending_staff_count; ?></span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-slate-700/50 rounded-lg">
                                    <span class="text-sm text-slate-300">Deactivated</span>
                                    <span class="text-2xl font-bold text-slate-500"><?php echo $deactivated_staff_count; ?></span>
                                </div>
                            </div>
                            <button onclick="openStaffModal()" class="w-full mt-4 action-button text-white px-4 py-2 rounded-lg font-semibold text-sm" style="background-color: #454B1B;">
                                <i class="fas fa-users"></i> Manage Staff Accounts
                            </button>
                        </div>

                        <!-- Resident Accounts Card -->
                        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                            <h3 class="text-lg font-heading font-bold text-white mb-4 flex items-center gap-2">
                                <i class="fas fa-id-card text-purple-400"></i> Resident Accounts
                            </h3>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between p-3 bg-slate-700/50 rounded-lg">
                                    <span class="text-sm text-slate-300">Total Residents</span>
                                    <span class="text-2xl font-bold text-purple-400"><?php echo $total_residents; ?></span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-slate-700/50 rounded-lg">
                                    <span class="text-sm text-slate-300">Active Status</span>
                                    <span class="text-2xl font-bold text-emerald-400"><?php echo $active_residents_count; ?></span>
                                </div>
                            </div>
                            <button onclick="openResidentModal()" class="w-full mt-4 action-button text-white px-4 py-2 rounded-lg font-semibold text-sm" style="background-color: #454B1B;">
                                <i class="fas fa-id-card"></i> Manage Resident Accounts
                            </button>
                        </div>

                        <!-- Official Accounts Card -->
                        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                            <h3 class="text-lg font-heading font-bold text-white mb-4 flex items-center gap-2">
                                <i class="fas fa-shield-alt text-emerald-400"></i> Official Accounts
                            </h3>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between p-3 bg-slate-700/50 rounded-lg">
                                    <span class="text-sm text-slate-300">Total Officials</span>
                                    <span class="text-2xl font-bold text-emerald-400"><?php echo $active_admin_count; ?></span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-slate-700/50 rounded-lg">
                                    <span class="text-sm text-slate-300">Active Admins</span>
                                    <span class="text-2xl font-bold text-emerald-400"><?php echo count($admin_accounts); ?></span>
                                </div>
                            </div>
                            <button onclick="openAdminModal()" class="w-full mt-4 action-button text-white px-4 py-2 rounded-lg font-semibold text-sm" style="background-color: #454B1B;">
                                <i class="fas fa-shield-alt"></i> Manage Officials
                            </button>
                        </div>

                        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                            <h3 class="text-lg font-heading font-bold text-white mb-4">Inactive Resident Accounts</h3>
                            <div class="overflow-x-auto">
                                <table id="inactiveResidentAccountsTable" class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-slate-700">
                                            <th class="text-left py-3 px-4 text-slate-400">Resident Name</th>
                                            <th class="text-left py-3 px-4 text-slate-400">Patient ID</th>
                                            <th class="text-left py-3 px-4 text-slate-400">Email</th>
                                            <th class="text-left py-3 px-4 text-slate-400">Phone</th>
                                            <th class="text-left py-3 px-4 text-slate-400">Status</th>
                                            <th class="text-center py-3 px-4 text-slate-400">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($inactive_residents) > 0): ?>
                                            <?php foreach ($inactive_residents as $resident): ?>
                                                <?php
                                                    $residentStatus = 'Inactive';
                                                    if (isset($resident['is_active']) && intval($resident['is_active']) === 1) {
                                                        $residentStatus = 'Blocked';
                                                    }
                                                ?>
                                                <tr class="border-b border-slate-700/50 hover:bg-slate-700/30 transition">
                                                    <td class="py-3 px-4 text-slate-300"><?php echo htmlspecialchars($resident['full_name'] ?? 'N/A'); ?></td>
                                                    <td class="py-3 px-4 text-slate-300 font-mono"><?php echo htmlspecialchars(str_pad($resident['resident_id'] ?? 'N/A', 6, '0', STR_PAD_LEFT)); ?></td>
                                                    <td class="py-3 px-4 text-slate-300 text-xs"><?php echo htmlspecialchars($resident['email'] ?? 'N/A'); ?></td>
                                                    <td class="py-3 px-4 text-slate-300"><?php echo htmlspecialchars($resident['contact_number'] ?? 'N/A'); ?></td>
                                                    <td class="py-3 px-4 text-slate-300">
                                                        <span class="inline-flex items-center rounded-full bg-slate-700 px-2 py-1 text-xs font-semibold uppercase tracking-wide text-slate-200">
                                                            <?php echo htmlspecialchars($residentStatus); ?>
                                                        </span>
                                                    </td>
                                                    <td class="py-3 px-4 text-center">
                                                        <div class="flex items-center justify-center gap-2">
                                                            <form method="post" class="inline-block">
                                                                <input type="hidden" name="action" value="restore_resident">
                                                                <input type="hidden" name="resident_id" value="<?php echo intval($resident['resident_id']); ?>">
                                                                <input type="hidden" name="redirect_section" value="<?php echo htmlspecialchars($active_section); ?>">
                                                                <button type="submit" onclick="return confirm('Reactivate this resident account and allow login again?');" class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded text-xs font-semibold transition">
                                                                    Reactivate
                                                                </button>
                                                            </form>
                                                            <form method="post" class="inline-block">
                                                                <input type="hidden" name="action" value="delete_resident">
                                                                <input type="hidden" name="resident_id" value="<?php echo intval($resident['resident_id']); ?>">
                                                                <input type="hidden" name="redirect_section" value="<?php echo htmlspecialchars($active_section); ?>">
                                                                <button type="submit" onclick="return confirm('Permanently delete this inactive resident account? This cannot be undone.');" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-xs font-semibold transition">
                                                                    Delete
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr class="border-b border-slate-700/50 hover:bg-slate-700/30 transition">
                                                <td class="py-3 px-4 text-slate-300">No inactive resident accounts found</td>
                                                <td class="py-3 px-4"></td>
                                                <td class="py-3 px-4"></td>
                                                <td class="py-3 px-4"></td>
                                                <td class="py-3 px-4"></td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    </div>
                <?php endif; ?>
                <?php endif; ?>

                <!-- SECTION 2.5: COMPREHENSIVE ACTIVITY LOG ============================================ -->
                <?php if (isActiveSection('activity-log')): ?>
                <div class="space-y-6">
                    
                    <p class="text-slate-400 mb-6">Real-time activity log showing all system events from Resident/Staff view including appointments, medical records, and service requests</p>

                <!-- Section -->
                    <div class="bg-slate-800 border border-slate-700 rounded-xl p-6 mb-6">
                        <h3 class="text-lg font-heading font-bold text-white mb-4">🔍 Activity Filters</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-300 mb-2">Activity Type</label>
                                <select id="activityTypeFilter" class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-[#454B1B]" onchange="filterActivities()">
                                    <option value="">All Activities</option>
                                    <option value="appointment">📅 Appointments</option>
                                    <option value="medicine">💊 Medicine Dispensing</option>
                                    <option value="medical">🏥 Medical Records</option>
                                    <option value="service">📋 Service Requests</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-300 mb-2">Time Period</label>
                                <select id="timePeriodFilter" class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-[#454B1B]" onchange="filterActivities()">
                                    <option value="">All Time</option>
                                    <option value="24h">Last 24 Hours</option>
                                    <option value="7d">Last 7 Days</option>
                                    <option value="30d">Last 30 Days</option>
                                    <option value="90d">Last 90 Days</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-300 mb-2">Resident/Staff</label>
                                <input type="text" id="residentFilter" placeholder="Search by name..." class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#454B1B]" onkeyup="filterActivities()">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-300 mb-2">&nbsp;</label>
                                <button onclick="resetActivityFilters()" class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-slate-300 hover:bg-slate-600 font-semibold transition">
                                    <i class="fas fa-redo mr-2"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>

                <!-- Section -->
                    <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-heading font-bold text-white">📋 System Activity Feed</h3>
                            <span class="text-sm text-slate-400">Total: <span id="activityCount"><?php echo count($all_transactions); ?></span> events</span>
                        </div>
                        
                        <div id="activityLogContainer" class="space-y-3 max-h-[800px] overflow-y-auto pr-4">
                            <?php if (count($all_transactions) > 0): ?>
                                <?php foreach ($all_transactions as $index => $transaction): 
                                    $type = $transaction['type'];
                                    
                                    
                                    if ($type === 'appointment') {
                                        $icon_class = 'fa-calendar-check text-blue-400';
                                        $bg_class = 'bg-blue-900/20 border-blue-700/30';
                                        $badge_class = 'bg-blue-500/20 text-blue-400';
                                        $type_label = '📅 Appointment';
                                    } elseif ($type === 'medicine') {
                                        $icon_class = 'fa-pills text-green-400';
                                        $bg_class = 'bg-green-900/20 border-green-700/30';
                                        $badge_class = 'bg-green-500/20 text-green-400';
                                        $type_label = '💊 Medicine';
                                    } elseif ($type === 'medical') {
                                        $icon_class = 'fa-heart-pulse text-purple-400';
                                        $bg_class = 'bg-purple-900/20 border-purple-700/30';
                                        $badge_class = 'bg-purple-500/20 text-purple-400';
                                        $type_label = '🏥 Medical Record';
                                    } else {
                                        $icon_class = 'fa-clipboard-list text-amber-400';
                                        $bg_class = 'bg-amber-900/20 border-amber-700/30';
                                        $badge_class = 'bg-amber-500/20 text-amber-400';
                                        $type_label = '📋 Service Request';
                                    }
                                    
                                    
                                    $timestamp = $transaction['date'];
                                    $dt = new DateTime($timestamp);
                                    $formatted_date = $dt->format('M d, Y');
                                    $formatted_time = $dt->format('h:i:s A');
                                    $relative_time = date_create_from_format('Y-m-d H:i:s', $timestamp);
                                    if ($relative_time) {
                                        $diff = time() - $relative_time->getTimestamp();
                                        if ($diff < 60) $rel = 'Just now';
                                        elseif ($diff < 3600) $rel = round($diff/60) . ' minutes ago';
                                        elseif ($diff < 86400) $rel = round($diff/3600) . ' hours ago';
                                        elseif ($diff < 604800) $rel = round($diff/86400) . ' days ago';
                                        else $rel = $formatted_date;
                                    } else {
                                        $rel = 'Unknown time';
                                    }
                                ?>
                                <div class="activity-item border border-slate-700 rounded-lg p-4 hover:border-slate-600 transition <?php echo $bg_class; ?>" data-type="<?php echo $type; ?>" data-resident="<?php echo htmlspecialchars($transaction['resident']); ?>" data-timestamp="<?php echo strtotime($timestamp); ?>">
                                    <div class="flex gap-4">
                                        <!-- Activity Icon -->
                                        <div class="flex-shrink-0 pt-1">
                                            <div class="w-10 h-10 rounded-lg bg-slate-700/50 flex items-center justify-center">
                                                <i class="fas <?php echo $icon_class; ?> text-lg"></i>
                                            </div>
                                        </div>
                                        
                                        <!-- Activity Details -->
                                        <div class="flex-1">
                                            <div class="flex items-start justify-between mb-2">
                                                <div>
                                                    <span class="inline-block px-2 py-1 rounded-md text-xs font-semibold <?php echo $badge_class; ?> mr-2">
                                                        <?php echo $type_label; ?>
                                                    </span>
                                                    <span class="text-xs text-slate-500"><?php echo htmlspecialchars($transaction['resident']); ?></span>
                                                </div>
                                                <div class="text-right">
                                                    <div class="text-xs font-mono text-slate-400"><?php echo $formatted_time; ?></div>
                                                    <div class="text-xs text-slate-500"><?php echo $rel; ?></div>
                                                </div>
                                            </div>
                                            
                                            <p class="text-slate-300 text-sm mb-3">
                                                <?php echo htmlspecialchars($transaction['description']); ?>
                                            </p>
                                            
                                            <div class="flex items-center justify-between pt-2 border-t border-slate-700/50">
                                                <div class="text-xs text-slate-500">
                                                    <strong>Date:</strong> <?php echo $formatted_date; ?>
                                                </div>
                                                <button onclick="showActivityDetails(<?php echo htmlspecialchars(json_encode($transaction)); ?>)" class="text-xs px-3 py-1 bg-slate-700 hover:bg-slate-600 text-slate-300 rounded transition">
                                                    <i class="fas fa-expand-alt mr-1"></i> Details
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-12">
                                    <i class="fas fa-inbox text-6xl text-slate-600 mb-4"></i>
                                    <p class="text-slate-400 text-lg">No activities recorded yet</p>
                                    <p class="text-slate-500 text-sm mt-2">Activities will appear here as appointments are scheduled, medicines are dispensed, and medical records are updated.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <!-- Section -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-sm font-semibold text-slate-300">Total Appointments</h4>
                                <i class="fas fa-calendar-check text-blue-400 text-xl"></i>
                            </div>
                            <p class="text-3xl font-bold text-blue-400"><?php echo count($all_appointments); ?></p>
                            <p class="text-xs text-slate-500 mt-2">Scheduled events</p>
                        </div>
                        
                        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-sm font-semibold text-slate-300">Medicine Dispensing</h4>
                                <i class="fas fa-pills text-green-400 text-xl"></i>
                            </div>
                            <p class="text-3xl font-bold text-green-400"><?php echo count($medicine_transactions); ?></p>
                            <p class="text-xs text-slate-500 mt-2">Dispensing records</p>
                        </div>
                        
                        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-sm font-semibold text-slate-300">Medical Records</h4>
                                <i class="fas fa-heart-pulse text-purple-400 text-xl"></i>
                            </div>
                            <p class="text-3xl font-bold text-purple-400"><?php echo count($medical_history); ?></p>
                            <p class="text-xs text-slate-500 mt-2">Medical updates</p>
                        </div>
                        
                        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-sm font-semibold text-slate-300">Service Requests</h4>
                                <i class="fas fa-clipboard-list text-amber-400 text-xl"></i>
                            </div>
                            <p class="text-3xl font-bold text-amber-400"><?php echo count($service_requests); ?></p>
                            <p class="text-xs text-slate-500 mt-2">Service events</p>
                        </div>
                    </div>

                </div>
                <?php endif; ?>

                <!-- SECTION 3: SYSTEM CONFIGURATION ================================================== -->
                <?php if (isActiveSection('system-config')): ?>
                <div class="space-y-6">
                    
                    <p class="text-slate-400 mb-6">Configure general site-wide system settings and preferences</p>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        
                        <!-- General Settings Card -->
                        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                            <h3 class="text-lg font-heading font-bold text-white mb-4">General Settings</h3>
                            <form class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-300 mb-2">Application Name</label>
                                    <input type="text" value="ScanCare" class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-[#454B1B]">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-300 mb-2">Primary Email</label>
                                    <input type="email" value="admin@scancare.local" class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-[#454B1B]">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-300 mb-2">Organization</label>
                                    <input type="text" value="Barangay Gutad Health Center" class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-[#454B1B]">
                                </div>
                                <button type="button" class="action-button w-full text-white px-4 py-2 rounded-lg font-semibold text-sm mt-4">
                                    <i class="fas fa-save mr-2"></i> Save Settings
                                </button>
                            </form>
                        </div>

                        <!-- Security Settings Card -->
                        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                            <h3 class="text-lg font-heading font-bold text-white mb-4">Security Settings</h3>
                            <form class="space-y-4">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" checked class="w-4 h-4 rounded bg-slate-700 border-slate-600 checked:bg-[#454B1B]">
                                    <span class="text-sm text-slate-300">Require strong passwords (8+ chars)</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" checked class="w-4 h-4 rounded bg-slate-700 border-slate-600 checked:bg-[#454B1B]">
                                    <span class="text-sm text-slate-300">Enable two-factor authentication</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" checked class="w-4 h-4 rounded bg-slate-700 border-slate-600 checked:bg-[#454B1B]">
                                    <span class="text-sm text-slate-300">Log all admin actions</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" class="w-4 h-4 rounded bg-slate-700 border-slate-600 checked:bg-[#454B1B]">
                                    <span class="text-sm text-slate-300">Enable maintenance mode</span>
                                </label>
                                <button type="button" class="action-button w-full text-white px-4 py-2 rounded-lg font-semibold text-sm mt-4">
                                    <i class="fas fa-shield-alt mr-2"></i> Update Security
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- SECTION 4: DATABASE INTEGRITY HUB -->
                <?php if (isActiveSection('database-hub')): ?>
                <div class="space-y-6">
                    
                    <p class="text-slate-400 mb-6">Manage database backups, integrity checks, and data maintenance</p>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        
                        <!-- Database Backups Card -->
                        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                            <h3 class="text-lg font-heading font-bold text-white mb-4 flex items-center gap-2">
                                <i class="fas fa-backup text-blue-400"></i> Database Backups
                            </h3>
                            <div class="space-y-4">
                                <div class="p-3 bg-slate-700/50 rounded-lg border border-slate-600">
                                    <p class="text-sm text-slate-300">Last Backup: <span class="text-emerald-400 font-semibold"><?php echo $last_backup; ?></span></p>
                                </div>
                                <button class="action-button w-full text-white px-4 py-3 rounded-lg font-semibold flex items-center justify-center gap-2">
                                    <i class="fas fa-play"></i> Create Backup Now
                                </button>
                                <button class="w-full px-4 py-3 rounded-lg font-semibold border border-slate-600 text-slate-300 hover:bg-slate-700 transition flex items-center justify-center gap-2">
                                    <i class="fas fa-history"></i> View Backup History
                                </button>
                            </div>
                        </div>

                        <!-- Integrity Check Card -->
                        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                            <h3 class="text-lg font-heading font-bold text-white mb-4 flex items-center gap-2">
                                <i class="fas fa-stethoscope text-emerald-400"></i> Integrity Check
                            </h3>
                            <div class="space-y-4">
                                <div class="p-3 bg-slate-700/50 rounded-lg border border-emerald-600/30">
                                    <p class="text-sm text-slate-300">Status: <span class="text-emerald-400 font-semibold">✓ All tables healthy</span></p>
                                </div>
                                <button class="action-button w-full text-white px-4 py-3 rounded-lg font-semibold flex items-center justify-center gap-2">
                                    <i class="fas fa-check-circle"></i> Run Integrity Check
                                </button>
                                <button class="w-full px-4 py-3 rounded-lg font-semibold border border-slate-600 text-slate-300 hover:bg-slate-700 transition flex items-center justify-center gap-2">
                                    <i class="fas fa-tools"></i> Optimize Tables
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Database Statistics Card -->
                    <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                        <h3 class="text-lg font-heading font-bold text-white mb-4">Database Statistics</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="p-4 bg-slate-700/50 rounded-lg">
                                <p class="text-sm text-slate-400">Total Size</p>
                                <p class="text-2xl font-bold text-blue-400"><?php echo $db_size; ?></p>
                            </div>
                            <div class="p-4 bg-slate-700/50 rounded-lg">
                                <p class="text-sm text-slate-400">Tables</p>
                                <p class="text-2xl font-bold text-emerald-400"><?php echo number_format($table_count); ?></p>
                            </div>
                            <div class="p-4 bg-slate-700/50 rounded-lg">
                                <p class="text-sm text-slate-400">Combined Records</p>
                                <p class="text-2xl font-bold text-purple-400"><?php echo number_format($total_records); ?></p>
                            </div>
                            <div class="p-4 bg-slate-700/50 rounded-lg">
                                <p class="text-sm text-slate-400">Last Optimized</p>
                                <p class="text-sm font-bold text-orange-400">1 week ago</p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- SECTION 5: NOTIFICATION SETTINGS ================================================= -->
                <?php if (isActiveSection('notifications')): ?>
                <div class="space-y-6">
                    
                    <p class="text-slate-400 mb-6">Configure SMS notification triggers and alerting rules</p>

                    <div class="bg-slate-800 border border-slate-700 rounded-xl p-6 mb-6">
                        <h3 class="text-lg font-heading font-bold text-white mb-6 flex items-center gap-2">
                            <i class="fas fa-bell"></i> SMS Notification Rules
                        </h3>
                        
                        <div class="space-y-4">
                            
                <!-- Section -->
                            <div class="p-4 bg-slate-700/50 rounded-lg border border-slate-600 flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="font-semibold text-white">Appointment Reminders</p>
                                    <p class="text-sm text-slate-400">Send SMS 24 hours before appointment</p>
                                </div>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" checked class="w-4 h-4 rounded bg-slate-600 border-slate-500 checked:bg-emerald-500">
                                    <span class="text-sm text-slate-300">Enabled</span>
                                </label>
                            </div>

                <!-- Section -->
                            <div class="p-4 bg-slate-700/50 rounded-lg border border-slate-600 flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="font-semibold text-white">System Alerts</p>
                                    <p class="text-sm text-slate-400">Critical system events and errors</p>
                                </div>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" checked class="w-4 h-4 rounded bg-slate-600 border-slate-500 checked:bg-emerald-500">
                                    <span class="text-sm text-slate-300">Enabled</span>
                                </label>
                            </div>

                <!-- Section -->
                            <div class="p-4 bg-slate-700/50 rounded-lg border border-slate-600 flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="font-semibold text-white">Health Announcements</p>
                                    <p class="text-sm text-slate-400">Clinic announcements and health tips</p>
                                </div>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" class="w-4 h-4 rounded bg-slate-600 border-slate-500 checked:bg-emerald-500">
                                    <span class="text-sm text-slate-300">Enabled</span>
                                </label>
                            </div>

                <!-- Section -->
                            <div class="p-4 bg-slate-700/50 rounded-lg border border-slate-600 flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="font-semibold text-white">Admin Actions</p>
                                    <p class="text-sm text-slate-400">Log admin actions and configuration changes</p>
                                </div>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" checked class="w-4 h-4 rounded bg-slate-600 border-slate-500 checked:bg-emerald-500">
                                    <span class="text-sm text-slate-300">Enabled</span>
                                </label>
                            </div>
                        </div>

                        <button class="action-button w-full text-white px-6 py-3 rounded-lg font-semibold mt-6 flex items-center justify-center gap-2">
                            <i class="fas fa-save mr-2"></i> Save Notification Settings
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- SECTION 6: CLOUD & STORAGE MANAGER =============================================== -->
                <?php if (isActiveSection('cloud-storage')): ?>
                <div class="space-y-6">
                    
                    <p class="text-slate-400 mb-6">Manage cloud synchronization and local storage settings</p>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        
                <!-- Section -->
                        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                            <h3 class="text-lg font-heading font-bold text-white mb-4 flex items-center gap-2">
                                <i class="fas fa-cloud text-blue-400"></i> Cloud Synchronization
                            </h3>
                            <form class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-300 mb-2">Cloud Provider</label>
                                    <select class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-[#454B1B]">
                                        <option>AWS S3</option>
                                        <option>Google Cloud Storage</option>
                                        <option>Azure Blob Storage</option>
                                        <option>Local Server Only</option>
                                    </select>
                                </div>
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" checked class="w-4 h-4 rounded bg-slate-700 border-slate-600 checked:bg-[#454B1B]">
                                    <span class="text-sm text-slate-300">Enable automatic sync</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" checked class="w-4 h-4 rounded bg-slate-700 border-slate-600 checked:bg-[#454B1B]">
                                    <span class="text-sm text-slate-300">Encrypt cloud data</span>
                                </label>
                                <button type="button" class="action-button w-full text-white px-4 py-2 rounded-lg font-semibold text-sm">
                                    <i class="fas fa-sync mr-2"></i> Test Connection
                                </button>
                            </form>
                        </div>

                <!-- Section -->
                        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                            <h3 class="text-lg font-heading font-bold text-white mb-4 flex items-center gap-2">
                                <i class="fas fa-hdd text-orange-400"></i> Local Storage
                            </h3>
                            <form class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-300 mb-2">Storage Path</label>
                                    <input type="text" value="/var/www/scancare/backups" class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-[#454B1B]" readonly>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between text-sm text-slate-400">
                                        <span>Disk Usage</span>
                                        <span>185 GB / 500 GB</span>
                                    </div>
                                    <div class="w-full bg-slate-700 rounded-full h-2">
                                        <div class="bg-emerald-500 h-2 rounded-full" style="width: 37%"></div>
                                    </div>
                                </div>
                                <button type="button" class="action-button w-full text-white px-4 py-2 rounded-lg font-semibold text-sm">
                                    <i class="fas fa-broom mr-2"></i> Clean Old Backups
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- SECTION 7: MONITORING CENTER ==================================================== -->
                <?php if (isActiveSection('monitoring')): ?>
                <div class="space-y-6">
                    
                    <p class="text-slate-400 mb-6">Real-time system logs and performance monitoring</p>

                <!-- Section -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        
                        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                            <h4 class="text-sm font-bold text-slate-400 mb-3">CPU Usage</h4>
                            <p class="text-3xl font-bold text-blue-400">34%</p>
                            <div class="w-full bg-slate-700 rounded-full h-2 mt-3">
                                <div class="bg-blue-500 h-2 rounded-full" style="width: 34%"></div>
                            </div>
                        </div>

                        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                            <h4 class="text-sm font-bold text-slate-400 mb-3">Memory Usage</h4>
                            <p class="text-3xl font-bold text-emerald-400">52%</p>
                            <div class="w-full bg-slate-700 rounded-full h-2 mt-3">
                                <div class="bg-emerald-500 h-2 rounded-full" style="width: 52%"></div>
                            </div>
                        </div>

                        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                            <h4 class="text-sm font-bold text-slate-400 mb-3">Response Time</h4>
                            <p class="text-3xl font-bold text-purple-400">142ms</p>
                            <p class="text-xs text-slate-500 mt-2">Average page load</p>
                        </div>
                    </div>

                <!-- Section -->
                    <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
                        <h3 class="text-lg font-heading font-bold text-white mb-4">System Logs</h3>
                        <div class="space-y-2 max-h-96 overflow-y-auto pr-4">
                            <div class="text-sm text-slate-400 font-mono p-2 bg-slate-900 rounded border border-slate-700">
                                <span class="text-emerald-400">[2026-06-09 15:23:45]</span> INFO: Database backup completed (245 MB)
                            </div>
                            <div class="text-sm text-slate-400 font-mono p-2 bg-slate-900 rounded border border-slate-700">
                                <span class="text-blue-400">[2026-06-09 14:55:12]</span> INFO: Admin user login: admin
                            </div>
                            <div class="text-sm text-slate-400 font-mono p-2 bg-slate-900 rounded border border-slate-700">
                                <span class="text-amber-400">[2026-06-09 14:22:33]</span> WARNING: High memory usage detected (76%)
                            </div>
                            <div class="text-sm text-slate-400 font-mono p-2 bg-slate-900 rounded border border-slate-700">
                                <span class="text-emerald-400">[2026-06-09 13:00:00]</span> INFO: Automated system check completed
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- SECTION 8: DEPLOYMENT ZONE ====================================================== -->
                <?php if (isActiveSection('deployment')): ?>
                <div class="space-y-6">
                    
                    <p class="text-slate-400 mb-6">Deploy system updates and manage application versions</p>

                    <div class="bg-slate-800 border border-slate-700 rounded-xl p-8">
                        <h3 class="text-2xl font-heading font-bold text-white mb-6 flex items-center gap-3">
                            <i class="fas fa-rocket text-emerald-400 text-3xl"></i> System Deployment
                        </h3>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                            
                <!-- Section -->
                            <div class="bg-slate-700/50 rounded-lg p-6 border border-slate-600">
                                <h4 class="font-semibold text-white mb-4">Current Version</h4>
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-400">Version:</span>
                                        <span class="font-semibold text-white">v2.1.0</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-400">Release Date:</span>
                                        <span class="font-semibold text-white">2026-05-15</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-400">Status:</span>
                                        <span class="text-emerald-400 font-semibold">✓ Stable</span>
                                    </div>
                                </div>
                            </div>

                <!-- Section -->
                            <div class="bg-slate-700/50 rounded-lg p-6 border border-slate-600">
                                <h4 class="font-semibold text-white mb-4">Available Updates</h4>
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-400">Latest Version:</span>
                                        <span class="font-semibold text-amber-400">v2.2.0 (Beta)</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-400">Release Date:</span>
                                        <span class="font-semibold text-white">2026-06-01</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-400">Changes:</span>
                                        <span class="font-semibold text-white">12 fixes, 5 features</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                <!-- Section -->
                        <div class="bg-gradient-to-r from-emerald-900/30 to-blue-900/30 border border-emerald-600/30 rounded-lg p-8 mb-8">
                            <h4 class="text-lg font-heading font-bold text-white mb-3 flex items-center gap-2">
                                <i class="fas fa-exclamation-triangle text-amber-400"></i> Important Notice
                            </h4>
                            <p class="text-slate-300 mb-4">
                                System updates will restart your application. Scheduled deployments should occur during maintenance windows to minimize user impact.
                            </p>
                            <p class="text-sm text-slate-400">
                                Current users: <span class="text-emerald-400 font-semibold">12 active</span> | 
                                Recommended downtime: <span class="text-amber-400 font-semibold">5-10 minutes</span>
                            </p>
                        </div>

                <!-- Section -->
                        <button class="action-button w-full text-white px-8 py-4 rounded-lg font-bold text-lg flex items-center justify-center gap-3">
                            <i class="fas fa-rocket"></i> Deploy System Update (v2.2.0)
                        </button>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </main>
    </div>

                <!-- Section -->
    <div id="staffModal" style="display: none;" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div class="bg-slate-800 rounded-xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-y-auto border border-slate-700">
                <!-- Section -->
            <div class="sticky top-0 bg-slate-900 px-6 py-4 border-b border-slate-700 flex items-center justify-between">
                <h2 class="text-xl font-heading font-bold text-white flex items-center gap-2">
                    <i class="fas fa-users text-blue-400"></i> BHW Staff Accounts
                </h2>
                <button onclick="closeStaffModal()" class="text-slate-400 hover:text-white transition">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>

                <!-- Section -->
            <div class="p-6">
                <?php if (count($bhw_accounts) > 0): ?>
                <!-- Section -->
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="bg-slate-700/50 rounded-lg p-4 text-center border border-slate-600">
                            <p class="text-slate-400 text-sm">Total BHW Accounts</p>
                            <p class="text-2xl font-bold text-blue-400"><?php echo count($bhw_accounts); ?></p>
                        </div>
                        <div class="bg-slate-700/50 rounded-lg p-4 text-center border border-slate-600">
                            <p class="text-slate-400 text-sm">Active Staff</p>
                            <p class="text-2xl font-bold text-emerald-400"><?php echo $active_staff_count; ?></p>
                        </div>
                        <div class="bg-slate-700/50 rounded-lg p-4 text-center border border-slate-600">
                            <p class="text-slate-400 text-sm">Inactive</p>
                            <p class="text-2xl font-bold text-slate-400"><?php echo $deactivated_staff_count; ?></p>
                        </div>
                    </div>

                <!-- Section -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-700 bg-slate-700/30">
                                    <th class="px-4 py-3 text-left text-slate-300 font-semibold">Name</th>
                                    <th class="px-4 py-3 text-left text-slate-300 font-semibold">Email</th>
                                    <th class="px-4 py-3 text-left text-slate-300 font-semibold">Contact</th>
                                    <th class="px-4 py-3 text-left text-slate-300 font-semibold">Position</th>
                                    <th class="px-4 py-3 text-left text-slate-300 font-semibold">Status</th>
                                    <th class="px-4 py-3 text-left text-slate-300 font-semibold">Assignment</th>
                                    <th class="px-4 py-3 text-left text-slate-300 font-semibold">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bhw_accounts as $account): 
                                    $health_worker_id = $account['worker_id'] ?? null;
                                    $approvalStatus = strtolower((string)($account['approval_status'] ?? 'approved'));
                                    $staffStatus = $approvalStatus === 'pending' ? 'pending approval' : ($approvalStatus === 'rejected' ? 'rejected' : 'active');
                                    $statusColor = 'text-emerald-400 bg-emerald-400/10';
                                    $statusIcon = '<i class="fas fa-check-circle"></i>';
                                    $actionLabel = 'Deactivate';
                                    $actionClass = 'bg-amber-500 hover:bg-amber-600';
                                    $actionValue = 'deactivate';

                                    if ($approvalStatus === 'pending') {
                                        $statusColor = 'text-yellow-400 bg-yellow-400/10';
                                        $statusIcon = '<i class="fas fa-clock"></i>';
                                        $actionLabel = 'Approve';
                                        $actionClass = 'bg-emerald-500 hover:bg-emerald-600';
                                        $actionValue = 'approve';
                                    } elseif ($approvalStatus === 'rejected') {
                                        $statusColor = 'text-red-400 bg-red-400/10';
                                        $statusIcon = '<i class="fas fa-ban"></i>';
                                        $actionLabel = 'No action';
                                        $actionClass = 'bg-slate-600';
                                        $actionValue = 'rejected';
                                    } elseif (isset($account['is_active'])) {
                                        if (intval($account['is_active']) === 0) {
                                            $staffStatus = 'inactive';
                                            $statusColor = 'text-slate-400 bg-slate-700/30';
                                            $statusIcon = '<i class="fas fa-times-circle"></i>';
                                            $actionLabel = 'Reactivate';
                                            $actionClass = 'bg-blue-500 hover:bg-blue-600';
                                            $actionValue = 'reactivate';
                                        }
                                    }
                                ?>
                                <tr class="border-b border-slate-700 hover:bg-slate-700/30 transition">
                                    <td class="px-4 py-3 text-white font-medium"><?php echo htmlspecialchars($account['full_name'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-3 text-slate-300 text-xs"><?php echo htmlspecialchars($account['email'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-3 text-slate-300"><?php echo htmlspecialchars($account['contact_number'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-3 text-slate-300"><?php echo htmlspecialchars($account['role'] ?? 'Health Worker'); ?></td>
                                    <td class="px-4 py-3">
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $statusColor; ?>">
                                            <?php echo $statusIcon; ?> <?php echo ucfirst($staffStatus); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-300 text-xs">Assigned</td>
                                    <td class="px-4 py-3 grid gap-2">
                                        <button onclick="openActivityModal(<?php echo $health_worker_id; ?>, '<?php echo htmlspecialchars($account['full_name'] ?? 'Staff Member'); ?>', 'staff')" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-semibold transition">
                                            <i class="fas fa-history"></i> View Activity
                                        </button>
                                        <?php if ($actionValue === 'approve'): ?>
                                            <form method="post" class="inline-block">
                                                <input type="hidden" name="action" value="approve_staff">
                                                <input type="hidden" name="worker_id" value="<?php echo intval($health_worker_id); ?>">
                                                <button type="submit" class="px-3 py-1 <?php echo $actionClass; ?> text-white rounded text-xs font-semibold transition">Approve</button>
                                            </form>
                                            <form method="post" class="inline-block">
                                                <input type="hidden" name="action" value="reject_staff">
                                                <input type="hidden" name="worker_id" value="<?php echo intval($health_worker_id); ?>">
                                                <button type="submit" onclick="return confirm('Reject this BHW registration?');" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-xs font-semibold transition">Reject</button>
                                            </form>
                                        <?php elseif ($actionValue !== 'rejected'): ?>
                                            <form method="post" class="inline-block">
                                                <input type="hidden" name="action" value="<?php echo $actionValue === 'deactivate' ? 'deactivate_staff' : 'reactivate_staff'; ?>">
                                                <input type="hidden" name="worker_id" value="<?php echo intval($health_worker_id); ?>">
                                                <button type="submit" onclick="return confirm('<?php echo $actionValue === 'deactivate' ? 'Deactivate this staff account?' : 'Reactivate this staff account?'; ?>');" class="px-3 py-1 <?php echo $actionClass; ?> text-white rounded text-xs font-semibold transition">
                                                    <?php echo $actionLabel; ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="post" class="inline-block">
                                            <input type="hidden" name="action" value="delete_staff">
                                            <input type="hidden" name="worker_id" value="<?php echo intval($health_worker_id); ?>">
                                            <input type="hidden" name="redirect_section" value="user-management">
                                            <button type="submit" onclick="return confirm('Permanently delete this BHW account? This cannot be undone.');" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-xs font-semibold transition">
                                                Delete
                                            </button>
                                        </form>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <i class="fas fa-inbox text-6xl text-slate-600 mb-4"></i>
                        <p class="text-slate-400 text-lg">No BHW accounts found in the system.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

                <!-- Section -->
    <div id="residentModal" style="display: none;" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div class="bg-slate-800 rounded-xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-y-auto border border-slate-700">
                <!-- Section -->
            <div class="sticky top-0 bg-slate-900 px-6 py-4 border-b border-slate-700 flex items-center justify-between">
                <h2 class="text-xl font-heading font-bold text-white flex items-center gap-2">
                    <i class="fas fa-id-card text-purple-400"></i> Resident Accounts
                </h2>
                <button onclick="closeResidentModal()" class="text-slate-400 hover:text-white transition">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>

                <!-- Section -->
            <div class="p-6">
                <?php if (count($resident_accounts) > 0): ?>
                <!-- Section -->
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="bg-slate-700/50 rounded-lg p-4 text-center border border-slate-600">
                            <p class="text-slate-400 text-sm">Total Residents</p>
                            <p class="text-2xl font-bold text-purple-400"><?php echo count($resident_accounts); ?></p>
                        </div>
                        <div class="bg-slate-700/50 rounded-lg p-4 text-center border border-slate-600">
                            <p class="text-slate-400 text-sm">Active Members</p>
                            <p class="text-2xl font-bold text-emerald-400"><?php echo $active_residents_count; ?></p>
                        </div>
                        <div class="bg-slate-700/50 rounded-lg p-4 text-center border border-slate-600">
                            <p class="text-slate-400 text-sm">Inactive</p>
                            <p class="text-2xl font-bold text-slate-400"><?php echo $inactive_residents_count; ?></p>
                        </div>
                    </div>

                <!-- Section -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-700 bg-slate-700/30">
                                    <th class="px-4 py-3 text-left text-slate-300 font-semibold">Name</th>
                                    <th class="px-4 py-3 text-left text-slate-300 font-semibold">Patient ID</th>
                                    <th class="px-4 py-3 text-left text-slate-300 font-semibold">Email</th>
                                    <th class="px-4 py-3 text-left text-slate-300 font-semibold">Phone</th>
                                    <th class="px-4 py-3 text-left text-slate-300 font-semibold">DOB</th>
                                    <th class="px-4 py-3 text-left text-slate-300 font-semibold">Barangay</th>
                                    <th class="px-4 py-3 text-left text-slate-300 font-semibold">Senior</th>
                                    <th class="px-4 py-3 text-left text-slate-300 font-semibold">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resident_accounts as $resident): 
                                    $is_senior = isset($resident['is_senior_citizen']) && $resident['is_senior_citizen'] == 1;
                                    $residentStatus = 'active';
                                    $residentColor = 'text-emerald-400 bg-emerald-400/10';
                                    $residentIcon = '<i class="fas fa-check-circle"></i>';
                                    $residentAction = 'deactivate_resident';
                                    $residentActionLabel = 'Deactivate';
                                    $residentActionClass = 'bg-amber-500 hover:bg-amber-600';

                                    if (isset($resident['is_active']) && intval($resident['is_active']) === 0) {
                                        $residentStatus = 'inactive';
                                        $residentColor = 'text-slate-400 bg-slate-700/30';
                                        $residentIcon = '<i class="fas fa-times-circle"></i>';
                                        $residentAction = 'restore_resident';
                                        $residentActionLabel = 'Reactivate';
                                        $residentActionClass = 'bg-blue-500 hover:bg-blue-600';
                                    }
                                ?>
                                <tr class="border-b border-slate-700 hover:bg-slate-700/30 transition">
                                    <td class="px-4 py-3 text-white font-medium"><?php echo htmlspecialchars($resident['full_name'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-3 text-slate-300 font-mono"><?php echo htmlspecialchars(str_pad($resident['resident_id'] ?? 'N/A', 6, '0', STR_PAD_LEFT)); ?></td>
                                    <td class="px-4 py-3 text-slate-300 text-xs"><?php echo htmlspecialchars($resident['email'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-3 text-slate-300"><?php echo htmlspecialchars($resident['contact_number'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-3 text-slate-300"><?php echo htmlspecialchars($resident['date_of_birth'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-3 text-slate-300 text-xs"><?php echo htmlspecialchars($resident['address'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-3">
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $is_senior ? 'text-amber-400 bg-amber-400/10' : 'text-slate-400 bg-slate-700/30'; ?>">
                                            <?php echo $is_senior ? '<i class="fas fa-user-clock"></i> Yes' : 'No'; ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 grid gap-2">
                                        <div>
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $residentColor; ?>">
                                                <?php echo $residentIcon; ?> <?php echo ucfirst($residentStatus); ?>
                                            </span>
                                        </div>
                                        <button onclick="openActivityModal(<?php echo $resident['resident_id']; ?>, '<?php echo htmlspecialchars($resident['full_name'] ?? 'Resident'); ?>', 'resident')" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-semibold transition">
                                            <i class="fas fa-history"></i> View Activity
                                        </button>
                                        <form method="post" class="inline-block">
                                            <input type="hidden" name="action" value="<?php echo $residentAction; ?>">
                                            <input type="hidden" name="resident_id" value="<?php echo intval($resident['resident_id']); ?>">
                                            <input type="hidden" name="redirect_section" value="<?php echo htmlspecialchars($active_section); ?>">
                                            <button type="submit" onclick="return confirm('<?php echo $residentActionLabel === 'Deactivate' ? 'Deactivate this resident account?' : 'Reactivate this resident account?'; ?>');" class="px-3 py-1 <?php echo $residentActionClass; ?> text-white rounded text-xs font-semibold transition">
                                                <?php echo $residentActionLabel; ?>
                                            </button>
                                        </form>
                                        <form method="post" class="inline-block">
                                            <input type="hidden" name="action" value="delete_resident">
                                            <input type="hidden" name="resident_id" value="<?php echo intval($resident['resident_id']); ?>">
                                            <input type="hidden" name="redirect_section" value="user-management">
                                            <button type="submit" onclick="return confirm('Permanently delete this resident account? This cannot be undone.');" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-xs font-semibold transition">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <i class="fas fa-inbox text-6xl text-slate-600 mb-4"></i>
                        <p class="text-slate-400 text-lg">No resident accounts found in the system.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

                <!-- Section -->
    <div id="adminModal" style="display: none;" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div class="bg-slate-800 rounded-xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-y-auto border border-slate-700">
                <!-- Section -->
            <div class="sticky top-0 bg-slate-900 px-6 py-4 border-b border-slate-700 flex items-center justify-between">
                <h2 class="text-xl font-heading font-bold text-white flex items-center gap-2">
                    <i class="fas fa-shield-alt text-emerald-400"></i> Official Accounts
                </h2>
                <button onclick="closeAdminModal()" class="text-slate-400 hover:text-white transition">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>

                <!-- Section -->
            <div class="p-6">
                <?php if (count($admin_accounts) > 0): ?>
                <!-- Section -->
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="bg-slate-700/50 rounded-lg p-4 text-center border border-slate-600">
                            <p class="text-slate-400 text-sm">Total Officials</p>
                            <p class="text-2xl font-bold text-emerald-400"><?php echo count($admin_accounts); ?></p>
                        </div>
                        <div class="bg-slate-700/50 rounded-lg p-4 text-center border border-slate-600">
                            <p class="text-slate-400 text-sm">Active Admins</p>
                            <p class="text-2xl font-bold text-emerald-400"><?php echo count($admin_accounts); ?></p>
                        </div>
                        <div class="bg-slate-700/50 rounded-lg p-4 text-center border border-slate-600">
                            <p class="text-slate-400 text-sm">Inactive</p>
                            <p class="text-2xl font-bold text-slate-400"><?php echo $inactive_admin_count; ?></p>
                        </div>
                    </div>

                <!-- Section -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-700 bg-slate-700/30">
                                    <th class="px-4 py-3 text-left text-slate-300 font-semibold">Name</th>
                                    <th class="px-4 py-3 text-left text-slate-300 font-semibold">Email</th>
                                    <th class="px-4 py-3 text-left text-slate-300 font-semibold">Role</th>
                                    <th class="px-4 py-3 text-left text-slate-300 font-semibold">Username</th>
                                    <th class="px-4 py-3 text-left text-slate-300 font-semibold">Status</th>
                                    <th class="px-4 py-3 text-left text-slate-300 font-semibold">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($admin_accounts as $admin): 
                                    $is_active = isset($admin['is_active']) ? $admin['is_active'] : 0;
                                    $status = ($is_active == 1) ? 'active' : 'inactive';
                                    $statusColor = ($status === 'active') ? 'text-emerald-400 bg-emerald-400/10' : 'text-slate-400 bg-slate-700/30';
                                    $statusIcon = ($status === 'active') ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>';
                                    $role = isset($admin['role']) ? $admin['role'] : 'Administrator';
                                ?>
                                <tr class="border-b border-slate-700 hover:bg-slate-700/30 transition">
                                    <td class="px-4 py-3 text-white font-medium"><?php echo htmlspecialchars($admin['full_name'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-3 text-slate-300"><?php echo htmlspecialchars($admin['email'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-3 text-slate-300"><?php echo htmlspecialchars($role); ?></td>
                                    <td class="px-4 py-3 text-slate-300 font-mono"><?php echo htmlspecialchars($admin['username'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-3">
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $statusColor; ?>">
                                            <?php echo $statusIcon; ?> <?php echo ucfirst($status); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php if ((int)($admin['admin_id'] ?? 0) !== (int)$admin_id): ?>
                                            <form method="post">
                                                <input type="hidden" name="action" value="delete_admin">
                                                <input type="hidden" name="admin_id" value="<?php echo intval($admin['admin_id'] ?? 0); ?>">
                                                <input type="hidden" name="redirect_section" value="user-management">
                                                <button type="submit" onclick="return confirm('Permanently delete this administrator account? This cannot be undone.');" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-xs font-semibold transition">Delete</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-500">Current account</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <i class="fas fa-inbox text-6xl text-slate-600 mb-4"></i>
                        <p class="text-slate-400 text-lg">No official accounts found in the system.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

                <!-- Section -->
    <div id="logoutConfirmModal" style="display: none;" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div class="bg-slate-800 rounded-xl shadow-2xl max-w-sm w-full border border-slate-700">
                <!-- Section -->
            <div class="bg-slate-900 px-6 py-4 border-b border-slate-700 flex items-center justify-between">
                <h2 class="text-lg font-heading font-bold text-white flex items-center gap-2">
                    <i class="fas fa-exclamation-circle text-amber-400"></i> Confirm Logout
                </h2>
                <button onclick="closeLogoutConfirmModal()" class="text-slate-400 hover:text-white transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

                <!-- Section -->
            <div class="p-6">
                <p class="text-slate-300 mb-6 text-center">
                    Are you sure you want to log out? You will need to log in again to access the admin panel.
                </p>
                
                <!-- Section -->
                <div class="flex gap-3">
                    <button onclick="closeLogoutConfirmModal()" class="flex-1 px-4 py-3 rounded-lg bg-slate-700 hover:bg-slate-600 text-white font-semibold transition">
                        <i class="fas fa-times mr-2"></i> Cancel
                    </button>
                    <button onclick="confirmLogout()" class="flex-1 px-4 py-3 rounded-lg bg-red-600 hover:bg-red-700 text-white font-semibold transition">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </button>
                </div>
            </div>
        </div>
    </div>

                <!-- Section -->
    <div id="activityModal" style="display: none;" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div class="bg-slate-800 rounded-xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto border border-slate-700">
                <!-- Section -->
            <div class="sticky top-0 bg-slate-900 px-6 py-4 border-b border-slate-700 flex items-center justify-between">
                <h2 class="text-xl font-heading font-bold text-white flex items-center gap-2">
                    <i class="fas fa-history text-blue-400"></i> Activity Log - <span id="activityStaffName" class="text-blue-400">Staff Member</span>
                </h2>
                <button onclick="closeActivityModal()" class="text-slate-400 hover:text-white transition">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>

                <!-- Section -->
            <div class="p-6">
                <div id="activityList" class="space-y-4">
                <!-- Section -->
                </div>
            </div>

        </div>
    </div>

    <script>
        const mobileAdminMenu = document.querySelector('.admin-sidebar');
        const mobileAdminMenuToggle = document.getElementById('mobileAdminMenuToggle');
        const mobileAdminMenuOverlay = document.getElementById('mobileAdminMenuOverlay');

        function setMobileAdminMenu(open) {
            mobileAdminMenu?.classList.toggle('is-open', open);
            mobileAdminMenuOverlay?.classList.toggle('hidden', !open);
            mobileAdminMenuToggle?.setAttribute('aria-expanded', String(open));
            document.body.classList.toggle('overflow-hidden', open);
        }

        mobileAdminMenuToggle?.addEventListener('click', () => {
            setMobileAdminMenu(!mobileAdminMenu?.classList.contains('is-open'));
        });
        mobileAdminMenuOverlay?.addEventListener('click', () => setMobileAdminMenu(false));
        document.querySelectorAll('.admin-sidebar .sidebar-link').forEach(link => {
            link.addEventListener('click', () => setMobileAdminMenu(false));
        });
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) setMobileAdminMenu(false);
        });
        
        
        
        function openLogoutConfirmModal() {
            document.getElementById('logoutConfirmModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeLogoutConfirmModal() {
            document.getElementById('logoutConfirmModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function confirmLogout() {
            window.location.href = 'logout_admin.php';
        }

        
        document.getElementById('logoutConfirmModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeLogoutConfirmModal();
            }
        });

        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('logoutConfirmModal').style.display !== 'none') {
                closeLogoutConfirmModal();
            }
        });

        function openStaffModal() {
            document.getElementById('staffModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeStaffModal() {
            document.getElementById('staffModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        
        document.getElementById('staffModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeStaffModal();
            }
        });

        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('staffModal').style.display !== 'none') {
                closeStaffModal();
            }
        });

        
        
        
        function openResidentModal() {
            document.getElementById('residentModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeResidentModal() {
            document.getElementById('residentModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        
        document.getElementById('residentModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeResidentModal();
            }
        });

        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('residentModal').style.display !== 'none') {
                closeResidentModal();
            }
        });

        
        
        
        function openAdminModal() {
            document.getElementById('adminModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeAdminModal() {
            document.getElementById('adminModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        
        document.getElementById('adminModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAdminModal();
            }
        });

        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('adminModal').style.display !== 'none') {
                closeAdminModal();
            }
        });

        
        
        
        function openActivityModal(userId, userName, userType = 'staff') {
            document.getElementById('activityModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            loadActivityLog(userId, userName, userType);
        }

        function closeActivityModal() {
            document.getElementById('activityModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function loadActivityLog(userId, userName, userType = 'staff') {
            
            document.getElementById('activityStaffName').textContent = userName;
            document.getElementById('activityList').innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner animate-spin text-2xl text-blue-400"></i><p class="text-slate-400 mt-2">Loading activities...</p></div>';

            console.log('[Activity Modal] Loading activities for', userType, 'ID:', userId, 'Name:', userName);

            
            let endpoint = userType === 'resident' ? 'get_resident_activities.php?resident_id=' : 'get_staff_activities.php?staff_id=';
            let paramName = userType === 'resident' ? 'resident_id' : 'staff_id';
            const fullUrl = endpoint + userId;
            
            console.log('[Activity Modal] API URL:', fullUrl);

            
            fetch(fullUrl)
                .then(response => {
                    console.log('[Activity Modal] Response status:', response.status);
                    console.log('[Activity Modal] Response headers:', response.headers);
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status + ': ' + response.statusText);
                    }
                    return response.text();  
                })
                .then(text => {
                    console.log('[Activity Modal] Raw response text:', text);
                    try {
                        const data = JSON.parse(text);
                        console.log('[Activity Modal] Parsed JSON:', data);
                        
                        
                        if (data && data.error) {
                            throw new Error(data.message || data.error);
                        }
                        
                        
                        if (Array.isArray(data)) {
                            console.log('[Activity Modal] Data is array with', data.length, 'items');
                            if (data.length > 0) {
                                console.log('[Activity Modal] First item:', data[0]);
                            }
                        }
                        
                        displayActivityLog(data);
                    } catch (parseError) {
                        console.error('[Activity Modal] JSON parse error:', parseError);
                        throw new Error('Invalid JSON response: ' + parseError.message);
                    }
                })
                .catch(error => {
                    console.error('[Activity Modal] Error:', error.message);
                    console.error('[Activity Modal] Full error:', error);
                    document.getElementById('activityList').innerHTML = '<div class="text-center py-8"><i class="fas fa-exclamation-triangle text-2xl text-red-400"></i><p class="text-red-400 mt-2">Error: ' + htmlEscape(error.message) + '</p><p class="text-slate-400 text-xs mt-2">Check browser console for details</p></div>';
                });
        }

        function displayActivityLog(activities) {
            const activityList = document.getElementById('activityList');
            
            if (!activities || activities.length === 0) {
                activityList.innerHTML = '<div class="text-center py-12"><i class="fas fa-inbox text-6xl text-slate-600 mb-4"></i><p class="text-slate-400 text-lg">No activities found for this staff member.</p></div>';
                return;
            }

            let html = '<div class="space-y-4">';
            
            activities.forEach(activity => {
                
                let icon = 'fa-circle';
                let bgColor = 'bg-slate-700/30';
                let textColor = 'text-slate-300';
                
                let headerText = '';
                let subtextContent = '';
                
                
                
                
                if (activity.action_type.toLowerCase() === 'scheduled_appointment') {
                    icon = 'fa-calendar-check';
                    bgColor = 'bg-blue-900/30';
                    textColor = 'text-blue-400';
                    
                    
                    
                    const actionTimestamp = new Date(activity.action_timestamp.replace(' ', 'T'));
                    const actionDateStr = actionTimestamp.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                    const actionTimeStr = actionTimestamp.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                    
                    
                    headerText = `Appointment Scheduled - ${actionDateStr} at ${actionTimeStr}`;
                    
                    
                    
                    const targetDate = new Date(activity.appointment_date);
                    const appointmentTime = activity.appointment_time ? activity.appointment_time.substring(0, 5) : '00:00'; 
                    
                    const targetDateStr = targetDate.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                    
                    
                    const [hours, minutes] = appointmentTime.split(':');
                    const date12Hour = new Date();
                    date12Hour.setHours(parseInt(hours), parseInt(minutes));
                    const timeStr12Hour = date12Hour.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                    
                    subtextContent = `Appointment Time: ${targetDateStr} at ${timeStr12Hour}`;
                } else {
                    
                    
                    
                    switch(activity.action_type.toLowerCase()) {
                        case 'appointment':
                        case 'appointment_rendered':
                            icon = 'fa-calendar-check';
                            bgColor = 'bg-blue-900/30';
                            textColor = 'text-blue-400';
                            break;
                        case 'rescheduled_appointment':
                            icon = 'fa-calendar-alt';
                            bgColor = 'bg-cyan-900/30';
                            textColor = 'text-cyan-400';
                            break;
                        case 'medicine':
                        case 'added_medicine':
                        case 'edited_medicine':
                            icon = 'fa-pills';
                            bgColor = 'bg-emerald-900/30';
                            textColor = 'text-emerald-400';
                            break;
                        case 'consultation':
                            icon = 'fa-stethoscope';
                            bgColor = 'bg-purple-900/30';
                            textColor = 'text-purple-400';
                            break;
                        case 'medical_history':
                            icon = 'fa-file-medical';
                            bgColor = 'bg-orange-900/30';
                            textColor = 'text-orange-400';
                            break;
                        case 'login':
                            icon = 'fa-sign-in-alt';
                            bgColor = 'bg-green-900/30';
                            textColor = 'text-green-400';
                            break;
                        default:
                            icon = 'fa-check-circle';
                            bgColor = 'bg-slate-700/30';
                            textColor = 'text-slate-300';
                    }
                    
                    
                    const timestamp = new Date(activity.timestamp || activity.action_timestamp);
                    const dateStr = timestamp.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                    const timeStr = timestamp.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                    
                    headerText = activity.action_type.replace(/_/g, ' ').toUpperCase();
                    subtextContent = `<i class="fas fa-clock"></i> ${dateStr} at ${timeStr}`;
                }
                
                html += `
                    <div class="flex gap-4 p-4 rounded-lg ${bgColor} border border-slate-700 hover:border-slate-600 transition">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-slate-800 border border-slate-600">
                                <i class="fas ${icon} ${textColor}"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <p class="text-white font-medium">${htmlEscape(headerText)}</p>
                            <p class="text-slate-300 text-sm mt-1">${htmlEscape(activity.action_description || '')}</p>
                            <p class="text-slate-500 text-xs mt-2">${subtextContent}</p>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            activityList.innerHTML = html;
        }

        function htmlEscape(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        
        document.getElementById('activityModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeActivityModal();
            }
        });

        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('activityModal').style.display !== 'none') {
                closeActivityModal();
            }
        });

        
        
        
        function filterActivities() {
            const typeFilter = document.getElementById('activityTypeFilter').value.toLowerCase();
            const timePeriodFilter = document.getElementById('timePeriodFilter').value;
            const residentFilter = document.getElementById('residentFilter').value.toLowerCase();
            
            const activityItems = document.querySelectorAll('.activity-item');
            let visibleCount = 0;
            const now = Math.floor(Date.now() / 1000);
            
            
            let timeThreshold = 0;
            if (timePeriodFilter === '24h') timeThreshold = now - (24 * 3600);
            else if (timePeriodFilter === '7d') timeThreshold = now - (7 * 24 * 3600);
            else if (timePeriodFilter === '30d') timeThreshold = now - (30 * 24 * 3600);
            else if (timePeriodFilter === '90d') timeThreshold = now - (90 * 24 * 3600);
            
            activityItems.forEach(item => {
                
                const itemType = item.getAttribute('data-type');
                const typeMatch = !typeFilter || itemType === typeFilter;
                
                
                const itemTimestamp = parseInt(item.getAttribute('data-timestamp'));
                const timeMatch = !timeThreshold || itemTimestamp >= timeThreshold;
                
                
                const itemResident = item.getAttribute('data-resident').toLowerCase();
                const residentMatch = !residentFilter || itemResident.includes(residentFilter);
                
                const shouldShow = typeMatch && timeMatch && residentMatch;
                item.style.display = shouldShow ? 'block' : 'none';
                
                if (shouldShow) visibleCount++;
            });
            
            
            document.getElementById('activityCount').textContent = visibleCount;
            
            
            if (visibleCount === 0) {
                const container = document.getElementById('activityLogContainer');
                const existingEmpty = container.querySelector('.empty-message');
                if (!existingEmpty) {
                    const emptyDiv = document.createElement('div');
                    emptyDiv.className = 'empty-message text-center py-12';
                    emptyDiv.innerHTML = '<i class="fas fa-search text-6xl text-slate-600 mb-4"></i><p class="text-slate-400 text-lg">No activities match your filters</p>';
                    container.appendChild(emptyDiv);
                }
            } else {
                const existingEmpty = document.querySelector('.empty-message');
                if (existingEmpty) existingEmpty.remove();
            }
        }

        function resetActivityFilters() {
            document.getElementById('activityTypeFilter').value = '';
            document.getElementById('timePeriodFilter').value = '';
            document.getElementById('residentFilter').value = '';
            
            
            const activityItems = document.querySelectorAll('.activity-item');
            activityItems.forEach(item => {
                item.style.display = 'block';
            });
            
            
            const existingEmpty = document.querySelector('.empty-message');
            if (existingEmpty) existingEmpty.remove();
            
            
            document.getElementById('activityCount').textContent = activityItems.length;
        }

        
        
        
        function showActivityDetails(transaction) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4';
            modal.id = 'activityDetailsModal';
            
            const typeLabelMap = {
                'appointment': { label: '📅 Appointment', icon: 'fa-calendar-check', color: 'blue' },
                'medicine': { label: '💊 Medicine Dispensing', icon: 'fa-pills', color: 'green' },
                'medical': { label: '🏥 Medical Record', icon: 'fa-heart-pulse', color: 'purple' },
                'service': { label: '📋 Service Request', icon: 'fa-clipboard-list', color: 'amber' }
            };
            
            const typeInfo = typeLabelMap[transaction.type] || { label: 'Activity', icon: 'fa-info-circle', color: 'slate' };
            
            
            const date = new Date(transaction.date);
            const formattedDate = date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            const formattedTime = date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            
            modal.innerHTML = `
                <div class="bg-slate-800 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto border border-slate-700">
                <!-- Section -->
                    <div class="sticky top-0 bg-slate-900 px-6 py-4 border-b border-slate-700 flex items-center justify-between">
                        <h2 class="text-xl font-heading font-bold text-white flex items-center gap-2">
                            <i class="fas ${typeInfo.icon} text-${typeInfo.color}-400"></i> ${typeInfo.label}
                        </h2>
                        <button onclick="document.getElementById('activityDetailsModal').remove()" class="text-slate-400 hover:text-white transition">
                            <i class="fas fa-times text-2xl"></i>
                        </button>
                    </div>

                <!-- Section -->
                    <div class="p-6 space-y-6">
                <!-- Section -->
                        <div class="bg-slate-700/30 border border-slate-600 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-white mb-4">📋 Activity Details</h3>
                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-slate-400 text-sm font-semibold mb-1">Activity Type</p>
                                        <p class="text-white font-medium">${typeInfo.label}</p>
                                    </div>
                                    <div>
                                        <p class="text-slate-400 text-sm font-semibold mb-1">Resident/Person</p>
                                        <p class="text-white font-medium">${transaction.resident}</p>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-slate-400 text-sm font-semibold mb-1">Description</p>
                                    <p class="text-white">${transaction.description}</p>
                                </div>
                                <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-600">
                                    <div>
                                        <p class="text-slate-400 text-sm font-semibold mb-1">Date</p>
                                        <p class="text-white font-mono">${formattedDate}</p>
                                    </div>
                                    <div>
                                        <p class="text-slate-400 text-sm font-semibold mb-1">Time</p>
                                        <p class="text-white font-mono">${formattedTime}</p>
                                    </div>
                                </div>
                            </div>
                        </div>


                    </div>

                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.remove();
                }
            });
            
            
            const closeHandler = function(e) {
                if (e.key === 'Escape') {
                    modal.remove();
                    document.removeEventListener('keydown', closeHandler);
                }
            };
            document.addEventListener('keydown', closeHandler);
        }
    </script>

    <script>
    async function handleApproveRequest(event) {
        event.preventDefault();
        const button = event.currentTarget;
        const originalText = button.textContent;
        const requestId = button.dataset.requestId;
        const residentId = button.dataset.residentId;
        const residentEmail = button.dataset.residentEmail;
        const residentName = button.dataset.residentName;
        const contactNumber = button.dataset.contactNumber || '';

        if (!confirm('Approve this request and block the resident account?')) {
            return;
        }

        
        button.disabled = true;
        button.textContent = 'Approving...';
        const siblingRejectBtn = button.parentElement.querySelector('.reject-request-btn');
        if (siblingRejectBtn) siblingRejectBtn.disabled = true;

        try {
            
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000); 

            const formData = new FormData();
            formData.append('action', 'process_account_request');
            formData.append('request_id', requestId);
            formData.append('decision', 'approve');

            const response = await fetch('approve_request_handler.php', {
                method: 'POST',
                body: formData,
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            
            let result = {};
            try {
                result = await response.json();
            } catch (e) {
                throw new Error('Invalid response from server');
            }

            
            if (!result.success) {
                throw new Error(result.message || 'Server returned error');
            }

            
            const requestRow = document.querySelector(`tr[data-request-id="${requestId}"]`);
            if (requestRow) {
                requestRow.remove();
            }

            
            
            const inactiveBody = document.querySelector('#inactiveResidentAccountsTable tbody');
            if (inactiveBody) {
                    
                    const emptyRows = inactiveBody.querySelectorAll('tr');
                    emptyRows.forEach(row => {
                        if (row.textContent.includes('No inactive resident accounts found')) {
                            row.remove();
                        }
                    });

                    
                    const newRow = document.createElement('tr');
                    newRow.className = 'border-b border-slate-700/50 hover:bg-slate-700/30 transition';
                    newRow.innerHTML = `
                        <td class="py-3 px-4 text-slate-300">${escapeHtml(residentName)}</td>
                        <td class="py-3 px-4 text-slate-300 font-mono">${escapeHtml(String(residentId).padStart(6, '0'))}</td>
                        <td class="py-3 px-4 text-slate-300 text-xs">${escapeHtml(residentEmail)}</td>
                        <td class="py-3 px-4 text-slate-300">${escapeHtml(contactNumber)}</td>
                        <td class="py-3 px-4 text-slate-300">
                            <span class="inline-flex items-center rounded-full bg-slate-700 px-2 py-1 text-xs font-semibold uppercase tracking-wide text-slate-200">
                                Inactive
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <form method="post" class="inline-block">
                                    <input type="hidden" name="action" value="restore_resident">
                                    <input type="hidden" name="resident_id" value="${escapeHtml(residentId)}">
                                    <input type="hidden" name="redirect_section" value="<?php echo htmlspecialchars($active_section); ?>">
                                    <button type="submit" class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded text-xs font-semibold transition">
                                        Reactivate
                                    </button>
                                </form>
                                <form method="post" class="inline-block">
                                    <input type="hidden" name="action" value="delete_resident">
                                    <input type="hidden" name="resident_id" value="${escapeHtml(residentId)}">
                                    <input type="hidden" name="redirect_section" value="<?php echo htmlspecialchars($active_section); ?>">
                                    <button type="submit" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-xs font-semibold transition">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    `;
                    inactiveBody.insertBefore(newRow, inactiveBody.firstChild);
            }

            showFlashMessage('Request approved successfully. Resident account blocked.', 'success');

        } catch (error) {
            console.error('Approval error:', error);
            
            
            if (error.name === 'AbortError') {
                alert('Request timed out. Please try again. If this persists, reload the page.');
            } else {
                alert('Error: ' + (error.message || 'Unknown error occurred'));
            }

        } finally {
            
            button.disabled = false;
            button.textContent = originalText;
            if (siblingRejectBtn) siblingRejectBtn.disabled = false;
        }
    }

    async function handleRejectRequest(event) {
        event.preventDefault();
        const button = event.currentTarget;
        const originalText = button.textContent;
        const requestId = button.dataset.requestId;

        if (!confirm('Reject this request?')) {
            return;
        }

        
        button.disabled = true;
        button.textContent = 'Rejecting...';
        const siblingApproveBtn = button.parentElement.querySelector('.approve-request-btn');
        if (siblingApproveBtn) siblingApproveBtn.disabled = true;

        try {
            
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000); 

            const formData = new FormData();
            formData.append('action', 'process_account_request');
            formData.append('request_id', requestId);
            formData.append('decision', 'reject');

            const response = await fetch('approve_request_handler.php', {
                method: 'POST',
                body: formData,
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            
            let result = {};
            try {
                result = await response.json();
            } catch (e) {
                throw new Error('Invalid response from server');
            }

            
            if (!result.success) {
                throw new Error(result.message || 'Server returned error');
            }

            
            const requestRow = document.querySelector(`tr[data-request-id="${requestId}"]`);
            if (requestRow) {
                requestRow.remove();
            }

            showFlashMessage('Request rejected successfully.', 'success');

        } catch (error) {
            console.error('Rejection error:', error);
            
            if (error.name === 'AbortError') {
                alert('Request timed out. Please try again. If this persists, reload the page.');
            } else {
                alert('Error: ' + (error.message || 'Unknown error occurred'));
            }

        } finally {
            
            button.disabled = false;
            button.textContent = originalText;
            if (siblingApproveBtn) siblingApproveBtn.disabled = false;
        }
    }

    function escapeHtml(value) {
        if (value === null || value === undefined) {
            return '';
        }
        const div = document.createElement('div');
        div.textContent = String(value);
        return div.innerHTML;
    }

    function showFlashMessage(message, type) {
        const container = document.querySelector('.max-w-6xl.px-4');
        if (!container) return;

        const messageDiv = document.createElement('div');
        messageDiv.className = `rounded-xl border px-4 py-3 text-sm font-medium ${
            type === 'success' 
                ? 'bg-emerald-100 border-emerald-200 text-emerald-800' 
                : 'bg-rose-100 border-rose-200 text-rose-800'
        }`;
        messageDiv.textContent = message;

        container.prepend(messageDiv);

        setTimeout(() => {
            messageDiv.style.opacity = '0';
            messageDiv.style.transition = 'opacity 0.3s ease';
            setTimeout(() => messageDiv.remove(), 300);
        }, 3000);
    }
    </script>

    <!-- Login Approval Notification System -->
                    <script src="<?php echo htmlspecialchars(appPath('login-notification-system.js'), ENT_QUOTES, 'UTF-8'); ?>?v=13"></script>
    <script src="account-session-monitor.js?v=1"></script>

</body>
</html>
